<?php
/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\MVC\Factory\MVCFactoryServiceInterface;
use Joomla\CMS\Router\Route;
use Vcmb\Component\BreezingformsNG\Administrator\Model\PackageTransferModel;
use Vcmb\Component\BreezingformsNG\Administrator\Service\AreaAccess;
use Vcmb\Component\BreezingformsNG\Administrator\Service\ActionLogger;

final class PackagesController extends BaseController
{
    /**
     * @param array<string, string> $urlparams
     */
    public function display($cachable = false, $urlparams = []): static
    {
        $this->assertAuthorised();
        Factory::getApplication()->getInput()->set('view', 'packages');

        return parent::display($cachable, $urlparams);
    }

    /**
     * Stores the package details and returns to the Create Package screen.
     */
    public function save(): void
    {
        /** @var CMSApplication $app */
        $app = Factory::getApplication();
        $this->assertAuthorised();
        $this->checkToken();
        $package = $this->submittedPackage();

        try {
            $this->model()->savePackage($package, $app->getInput()->get('metadata', [], 'array'));
            $app->enqueueMessage(Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_SAVED'), 'message');
        } catch (\Throwable $exception) {
            $app->enqueueMessage($exception->getMessage(), 'error');
        }
        $app->redirect(Route::_('index.php?option=com_breezingformsng&view=packages&layout=export&package=' . rawurlencode($package), false));
    }

    /**
     * Saves the package details, then downloads the package: the selected
     * items when there are any, else the items assigned to the package.
     */
    public function export(): void
    {
        /** @var CMSApplication $app */
        $app = Factory::getApplication();
        $this->assertAuthorised();
        $this->checkToken();
        $input = $app->getInput();
        $package = $this->submittedPackage();

        try {
            $formIds = $input->get("forms", [], "array");
            $scriptIds = $input->get("scripts", [], "array");
            $pieceIds = $input->get("pieces", [], "array");
            $menuIds = $input->get("menus", [], "array");
            $metadata = $input->get('metadata', [], 'array');
            $json = $formIds === [] && $scriptIds === [] && $pieceIds === [] && $menuIds === []
                ? $this->model()->export($package, $metadata)
                : $this->model()->exportSelection($package, $formIds, $scriptIds, $pieceIds, $menuIds, $metadata);
            $this->model()->savePackage($package, $metadata);
            $fileName = 'breezingformsng-' . preg_replace('/[^A-Za-z0-9._-]/', '-', $package) . '.json';
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            $app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
            $app->setHeader('Content-Disposition', 'attachment; filename="' . $fileName . '"', true);
            $app->sendHeaders();
            echo $json;
            $app->close();
        } catch (\Throwable $exception) {
            $app->enqueueMessage($exception->getMessage(), 'error');
            $app->redirect(Route::_('index.php?option=com_breezingformsng&view=packages&layout=export&package=' . rawurlencode($package), false));
        }
    }

    public function import(): void
    {
        /** @var CMSApplication $app */
        $app = Factory::getApplication();
        $this->assertAuthorised();
        $this->checkToken();
        $file = $app->getInput()->files->get('package_file', [], 'array');
        $path = is_array($file) ? (string) ($file['tmp_name'] ?? '') : '';

        try {
            if ($path === '' || !is_uploaded_file($path)) {
                throw new \RuntimeException(Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_FILE_REQUIRED'));
            }
            $retiredForms = $this->model()->import($path);
            (new ActionLogger($app))->log(ActionLogger::IMPORTED, ActionLogger::PACKAGE);
            $app->enqueueMessage(Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_IMPORTED'), 'message');

            foreach ($retiredForms as $form) {
                // The name comes from the uploaded file.
                $name = htmlspecialchars($form['name'], ENT_QUOTES, 'UTF-8');
                $app->enqueueMessage(
                    $form['previousPublished'] === null
                        ? Text::sprintf('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_BACKEND_ONLY_NEW', $name)
                        : Text::sprintf(
                            'COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_BACKEND_ONLY_REPLACED',
                            $name,
                            Text::_($form['previousPublished'] === 1 ? 'JPUBLISHED' : 'JUNPUBLISHED')
                        ),
                    'warning'
                );
            }
        } catch (\Throwable $exception) {
            $app->enqueueMessage($exception->getMessage(), 'error');
        }
        $app->redirect(Route::_('index.php?option=com_breezingformsng&view=packages&layout=import', false));
    }

    public function delete(): void
    {
        /** @var CMSApplication $app */
        $app = Factory::getApplication();
        $this->assertAuthorised();
        $this->checkToken();
        $package = $this->submittedPackage();

        try {
            $this->model()->deletePackage($package);
            (new ActionLogger($app))->log(ActionLogger::DELETED, ActionLogger::PACKAGE, [], $package);
            $app->enqueueMessage(Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_DELETED'), 'message');
            $app->redirect(Route::_('index.php?option=com_breezingformsng&view=packages', false));
        } catch (\Throwable $exception) {
            $app->enqueueMessage($exception->getMessage(), 'error');
            $app->redirect(Route::_('index.php?option=com_breezingformsng&view=packages&layout=delete', false));
        }
    }

    private function submittedPackage(): string
    {
        return trim(Factory::getApplication()->getInput()->getString('package', ''));
    }

    private function assertAuthorised(): void
    {
        $user = $this->app->getIdentity() ?? throw new \RuntimeException('The current user is not loaded.');
        AreaAccess::assert($user, AreaAccess::CONFIGURATION);
    }

    private function model(): PackageTransferModel
    {
        $component = $this->app->bootComponent('com_breezingformsng');
        if (!$component instanceof MVCFactoryServiceInterface) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }
        $model = $component->getMVCFactory()->createModel('PackageTransfer', 'Administrator', ['ignore_request' => true]);
        if (!$model instanceof PackageTransferModel) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }

        return $model;
    }
}
