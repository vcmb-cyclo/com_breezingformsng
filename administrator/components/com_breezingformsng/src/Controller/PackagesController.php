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
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Router\Route;
use Vcmb\Component\BreezingformsNG\Administrator\Exception\InvalidPackageException;
use Vcmb\Component\BreezingformsNG\Administrator\Model\PackageTransferModel;
use Vcmb\Component\BreezingformsNG\Administrator\Service\AreaAccess;
use Vcmb\Component\BreezingformsNG\Administrator\Service\ActionLogger;

final class PackagesController extends BaseController
{
    /** User state holding the report of the last import, shown once by the import page. */
    public const REPORT_STATE = 'com_breezingformsng.packages.import_report';

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
            // An unchecked switch is not posted: Input returns the default as it is.
            $withTemplates = $input->getBool('include_templates', false);
            $withIntegrator = $input->getBool('include_integrator', false);
            // Template files are PHP code run by the site.
            if ($withTemplates && !$this->mayTransferTemplates()) {
                throw new \RuntimeException(Text::sprintf('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_TEMPLATES_NOT_ALLOWED', Text::_('JACTION_ADMIN')));
            }
            if ($withIntegrator) {
                $this->assertCodeAuthorised();
            }
            $json = $formIds === [] && $scriptIds === [] && $pieceIds === [] && $menuIds === []
                ? $this->model()->export($package, $metadata, $withTemplates, $withIntegrator)
                : $this->model()->exportSelection($package, $formIds, $scriptIds, $pieceIds, $menuIds, $metadata, $withTemplates, $withIntegrator);
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
            $uploadError = is_array($file) ? (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) : UPLOAD_ERR_NO_FILE;
            if ($uploadError === UPLOAD_ERR_NO_FILE) {
                throw new \RuntimeException(Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_FILE_REQUIRED'));
            }
            if ($uploadError === UPLOAD_ERR_INI_SIZE || $uploadError === UPLOAD_ERR_FORM_SIZE) {
                throw new \RuntimeException(Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_UPLOAD_TOO_LARGE'));
            }
            if ($uploadError !== UPLOAD_ERR_OK || $path === '' || !is_uploaded_file($path)) {
                throw new \RuntimeException(Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_UPLOAD_FAILED'));
            }
            $model = $this->model();
            // The name the file had on the computer it was sent from, for the version notes.
            $sourceName = (string) preg_replace('/[\x00-\x1F\x7F]+/', ' ', basename(str_replace('\\', '/', (string) ($file['name'] ?? ''))));
            $user = $app->getIdentity() ?? throw new \RuntimeException('The current user is not loaded.');
            $retiredForms = $model->import(
                $path,
                $this->mayTransferTemplates(),
                $sourceName,
                AreaAccess::isAllowed($user, AreaAccess::CODE),
                $this->submittedSelection()
            );
            (new ActionLogger($app))->log(ActionLogger::IMPORTED, ActionLogger::PACKAGE);
            $summary = $model->getImportSummary();
            // Shown once on the import page, as the tables of what was written.
            $app->setUserState(self::REPORT_STATE, ['package' => $summary['package'], 'items' => $summary['items']]);
            $app->enqueueMessage(Text::sprintf(
                'COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_IMPORTED_NAMED',
                htmlspecialchars($summary['package'], ENT_QUOTES, 'UTF-8')
            ), 'message');

            if ($summary['templates'] > 0) {
                $app->enqueueMessage(Text::plural('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_IMPORTED_TEMPLATES', $summary['templates']), 'message');
            }
            if ($summary['rules'] > 0) {
                $app->enqueueMessage(Text::plural('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_IMPORTED_INTEGRATOR', $summary['rules']), 'message');
            }
            if ($summary['unlinked'] > 0) {
                $app->enqueueMessage(Text::plural('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_INTEGRATOR_UNLINKED', $summary['unlinked']), 'warning');
            }
            if (in_array('templates', $summary['skipped'], true)) {
                $app->enqueueMessage(Text::sprintf('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_SKIPPED_TEMPLATES', Text::_('JACTION_ADMIN')), 'warning');
            }
            if (in_array('integrator', $summary['skipped'], true)) {
                $app->enqueueMessage(Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_SKIPPED_INTEGRATOR'), 'warning');
            }
            if ($summary['shared'] !== []) {
                // The names come from the uploaded file.
                $app->enqueueMessage(Text::sprintf(
                    'COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_TEMPLATES_SHARED',
                    htmlspecialchars(implode(', ', $summary['shared']), ENT_QUOTES, 'UTF-8')
                ), 'warning');
            }
            if ($summary['conflicts'] !== []) {
                // The paths come from names in the uploaded file.
                $app->enqueueMessage(Text::sprintf(
                    'COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_TEMPLATE_CONFLICT',
                    htmlspecialchars(implode(', ', $summary['conflicts']), ENT_QUOTES, 'UTF-8')
                ), 'warning');
            }
            if ($summary['failed'] !== []) {
                $app->enqueueMessage(Text::sprintf(
                    'COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_TEMPLATE_WRITE_FAILED',
                    htmlspecialchars(implode(', ', $summary['failed']), ENT_QUOTES, 'UTF-8')
                ), 'warning');
            }

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
        } catch (InvalidPackageException $exception) {
            $name = basename(str_replace('\\', '/', (string) ($file['name'] ?? '')));
            $key = match ($exception->getReason()) {
                InvalidPackageException::READ => 'COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_FILE_NAMED_READ_ERROR',
                InvalidPackageException::JSON => 'COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_INVALID_JSON',
                InvalidPackageException::XML => 'COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_INVALID_XML',
                InvalidPackageException::VERSION => 'COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_UNSUPPORTED_VERSION',
                default => 'COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_INVALID_NAMED_FILE',
            };
            $app->enqueueMessage(Text::sprintf(
                $key,
                htmlspecialchars($name, ENT_QUOTES, 'UTF-8')
            ), 'error');
        } catch (\Throwable $exception) {
            $app->enqueueMessage($exception->getMessage(), 'error');
        }
        $app->redirect(Route::_('index.php?option=com_breezingformsng&view=packages&layout=import', false));
    }

    /**
     * What importing each item of the chosen file would do, for the tables of
     * the import page: create it, replace it, or nothing, as the site has it
     * as it is. Nothing is written.
     */
    public function preview(): void
    {
        /** @var CMSApplication $app */
        $app = $this->app;
        $this->assertAuthorised();

        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        $app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
        $app->sendHeaders();

        if (!$this->checkToken('post', false)) {
            echo new JsonResponse(null, Text::_('JINVALID_TOKEN'), true);
            $app->close();
        }

        $file = $app->getInput()->files->get('package_file', [], 'array');
        $path = is_array($file) ? (string) ($file['tmp_name'] ?? '') : '';

        try {
            if (!is_array($file) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || $path === '' || !is_uploaded_file($path)) {
                throw new \RuntimeException(Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_UPLOAD_FAILED'));
            }
            $user = $app->getIdentity() ?? throw new \RuntimeException('The current user is not loaded.');
            echo new JsonResponse($this->model()->compareFile(
                $path,
                $this->mayTransferTemplates(),
                AreaAccess::isAllowed($user, AreaAccess::CODE)
            ));
        } catch (\Throwable $exception) {
            // The import itself tells what is wrong with the file.
            echo new JsonResponse(null, $exception instanceof InvalidPackageException ? '' : $exception->getMessage(), true);
        }
        $app->close();
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
            $app->enqueueMessage(Text::sprintf(
                'COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_DELETED_NAMED',
                htmlspecialchars($package, ENT_QUOTES, 'UTF-8')
            ), 'message');
            $app->redirect(Route::_('index.php?option=com_breezingformsng&view=packages', false));
        } catch (\Throwable $exception) {
            $app->enqueueMessage($exception->getMessage(), 'error');
            $app->redirect(Route::_('index.php?option=com_breezingformsng&view=packages&layout=delete', false));
        }
    }

    /**
     * The items chosen in the tables of the import page, by part, or null
     * when the page sent no choice (without JavaScript): the whole file.
     *
     * @return array<string, list<int>>|null
     */
    private function submittedSelection(): ?array
    {
        $input = Factory::getApplication()->getInput();
        if ($input->getInt('import_selected', 0) !== 1) {
            return null;
        }

        $submitted = $input->get('import_select', [], 'array');
        $selection = [];
        foreach (PackageTransferModel::SELECTABLE_PARTS as $part) {
            $positions = is_array($submitted[$part] ?? null) ? $submitted[$part] : [];
            $selection[$part] = array_values(array_unique(array_filter(
                array_map('intval', $positions),
                static fn (int $position): bool => $position >= 0
            )));
        }

        return $selection;
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

    private function assertCodeAuthorised(): void
    {
        $user = $this->app->getIdentity() ?? throw new \RuntimeException('The current user is not loaded.');
        AreaAccess::assert($user, AreaAccess::CODE);
    }

    /** Template files hold PHP code: moving them takes the Configure permission of the component. */
    private function mayTransferTemplates(): bool
    {
        $user = $this->app->getIdentity() ?? throw new \RuntimeException('The current user is not loaded.');

        return $user->authorise('core.admin', 'com_breezingformsng');
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
