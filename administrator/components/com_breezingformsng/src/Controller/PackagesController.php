<?php
/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\MVC\Factory\MVCFactoryServiceInterface;
use Joomla\CMS\Router\Route;
use Vcmb\Component\BreezingformsNG\Administrator\Model\PackageTransferModel;

final class PackagesController extends BaseController
{
    public function display($cachable = false, $urlparams = []): static
    {
        $this->assertAuthorised();
        $this->app->getInput()->set('view', 'packages');

        return parent::display($cachable, $urlparams);
    }

    public function export(): void
    {
        $this->assertAuthorised();
        $this->checkToken();
        $package = $this->app->getInput()->getString('package', '');

        try {
            $json = $this->model()->export($package);
            $fileName = 'breezingformsng-' . preg_replace('/[^A-Za-z0-9._-]/', '-', $package) . '.json';
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            $this->app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
            $this->app->setHeader('Content-Disposition', 'attachment; filename="' . $fileName . '"', true);
            $this->app->sendHeaders();
            echo $json;
            $this->app->close();
        } catch (\Throwable $exception) {
            $this->app->enqueueMessage($exception->getMessage(), 'error');
            $this->app->redirect(Route::_('index.php?option=com_breezingformsng&view=packages', false));
        }
    }

    public function import(): void
    {
        $this->assertAuthorised();
        $this->checkToken();
        $file = $this->app->getInput()->files->get('package_file', [], 'array');
        $path = is_array($file) ? (string) ($file['tmp_name'] ?? '') : '';

        try {
            if ($path === '' || !is_uploaded_file($path)) {
                throw new \RuntimeException(Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_FILE_REQUIRED'));
            }
            $this->model()->import($path);
            $this->app->enqueueMessage(Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_IMPORTED'), 'message');
        } catch (\Throwable $exception) {
            $this->app->enqueueMessage($exception->getMessage(), 'error');
        }
        $this->app->redirect(Route::_('index.php?option=com_breezingformsng&view=packages', false));
    }

    private function assertAuthorised(): void
    {
        if (!$this->app->getIdentity()->authorise('core.manage', 'com_breezingformsng')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }
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
