<?php
/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Administrator\View\Packages;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryServiceInterface;
use Joomla\CMS\Router\Route;
use Vcmb\Component\BreezingformsNG\Administrator\Controller\PackagesController;
use Vcmb\Component\BreezingformsNG\Administrator\Model\PackageTransferModel;
use Vcmb\Component\BreezingformsNG\Administrator\Service\AreaAccess;
use Vcmb\Component\BreezingformsNG\Administrator\View\BreezingformsNG\HtmlView as BaseHtmlView;

/**
 * Configuration screen: the default layout only holds the toolbar, each
 * action (create a package, install one) opens its own layout.
 */
final class HtmlView extends BaseHtmlView
{
    /** @var list<string> */
    public array $packages = [];
    /** @var array<string, list<array<string, mixed>>> */
    public array $choices = [];
    /** @var array<array<string, mixed>> */
    public array $profiles = [];
    /** @var array<string, list<array<string, mixed>>> What is attached to the selected package. */
    public array $contents = [];
    /** Package preselected in the list, after a save. */
    public string $selectedPackage = '';
    /** Time zone the audit dates are shown in. */
    public \DateTimeZone $timezone;
    /** Template files hold PHP code: exporting them takes the Configure permission, as the controller checks. */
    public bool $mayTransferTemplates = false;
    /** Integrator rules hold PHP code: exporting them takes the code area, as the controller checks. */
    public bool $mayTransferIntegrator = false;

    /**
     * What the last import wrote, shown once after it.
     *
     * @var array{package?: string, items?: array<string, list<array<string, mixed>>>}
     */
    public array $importReport = [];

    public function display($tpl = null)
    {
        AreaAccess::assert($this->getCurrentUser(), AreaAccess::CONFIGURATION);
        // Export and import tell beforehand what the user may not transfer.
        $this->mayTransferTemplates = $this->getCurrentUser()->authorise('core.admin', 'com_breezingformsng');
        $this->mayTransferIntegrator = AreaAccess::isAllowed($this->getCurrentUser(), AreaAccess::CODE);

        if (in_array($this->getLayout(), ['export', 'delete'], true)) {
            $app = Factory::getApplication();
            $component = $app->bootComponent('com_breezingformsng');
            if (!$component instanceof MVCFactoryServiceInterface) {
                throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
            }
            $model = $component->getMVCFactory()->createModel('PackageTransfer', 'Administrator', ['ignore_request' => true]);
            if (!$model instanceof PackageTransferModel) {
                throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
            }
            $this->packages = $model->getPackages();
            $this->selectedPackage = $app->getInput()->getString('package', '');

            if ($this->getLayout() === 'delete') {
                if ($this->selectedPackage !== '') {
                    if (!in_array($this->selectedPackage, $this->packages, true)) {
                        throw new \RuntimeException(Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_NOT_FOUND'), 404);
                    }
                    $this->contents = $model->getPackageContents($this->selectedPackage);
                }
            } else {
                $this->choices = $model->getExportChoices();
                $this->profiles = $model->getPackageProfiles();
                $this->timezone = $this->getCurrentUser()->getTimezone();
            }
        }

        if ($this->getLayout() === 'import') {
            /** @var CMSApplication $app */
            $app = Factory::getApplication();
            $report = $app->getUserState(PackagesController::REPORT_STATE, []);
            $this->importReport = is_array($report) ? $report : [];
            $app->setUserState(PackagesController::REPORT_STATE, null);
        }

        $this->addToolbar();

        parent::display($tpl);
    }

    protected function getDetailLabel(): ?string
    {
        return match ($this->getLayout()) {
            'export' => Text::_('COM_BREEZINGFORMSNG_INSTALLER_CREATEPKG'),
            'delete' => Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_DELETE_CONFIRM_TITLE'),
            'import' => Text::_('COM_BREEZINGFORMSNG_INSTALLER'),
            default => null,
        };
    }

    private function addToolbar(): void
    {
        /** @var HtmlDocument $document */
        $document = $this->getDocument();
        $toolbar = $document->getToolbar() ?? throw new \RuntimeException('The toolbar is not available.');
        $user = $this->getCurrentUser();
        $home = Route::_('index.php?option=com_breezingformsng&view=packages');

        // The validating toolbar buttons call document.formvalidator.
        if (in_array($this->getLayout(), ['export', 'import'], true)) {
            $document->getWebAssetManager()->useScript('form.validate');
        }

        // Unsaved changes guard of the Create Package layout: joomla.dialog confirm.
        if ($this->getLayout() === 'export') {
            $document->getWebAssetManager()->useScript('joomla.dialog');
            Text::script('JYES');
            Text::script('JNO');
            Text::script('COM_BREEZINGFORMSNG_CONFIRM_DISCARD_CHANGES');
        }

        // The import asks for confirmation, telling what the file will write.
        if ($this->getLayout() === 'import') {
            $document->getWebAssetManager()->useScript('joomla.dialog');
            Text::script('JYES');
            Text::script('JNO');
            foreach (['FILE', 'CONTENT', 'REPLACE', 'TEMPLATES', 'INTEGRATOR', 'TEMPLATES_SKIPPED', 'INTEGRATOR_SKIPPED'] as $part) {
                Text::script('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_IMPORT_CONFIRM_' . $part);
            }
            // The tables of the items of the file, to choose what to import.
            $selectionKeys = [
                'COM_BREEZINGFORMSNG_MANAGEFORMS', 'COM_BREEZINGFORMSNG_MANAGESCRIPTS',
                'COM_BREEZINGFORMSNG_MANAGEPIECES', 'COM_BREEZINGFORMSNG_MANAGEMENUS',
                'JGLOBAL_TITLE', 'COM_BREEZINGFORMSNG_FORMS_NAME', 'COM_BREEZINGFORMSNG_ELEMENTS',
                'COM_BREEZINGFORMSNG_INSTALLER_SELECTALL', 'COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_IMPORT_NOTHING_SELECTED',
                'COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_IMPORT_STATUS', 'COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_IMPORT_STATUS_NEW',
                'COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_IMPORT_STATUS_REPLACE', 'COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_IMPORT_STATUS_IDENTICAL',
                'COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_IMPORT_STATUS_IDENTICAL_HINT', 'COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_IMPORT_ALL_IDENTICAL',
                'COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_IMPORT_CONFIRM_STATUS',
                'COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_IMPORT_OPEN_EXISTING', 'COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_IMPORT_OPEN_EXISTING_LABEL',
            ];
            // Counts, in the singular (_1) or the plural.
            foreach (['FORMS', 'SCRIPTS', 'PIECES', 'MENUS', 'CREATED', 'REPLACED', 'TEMPLATES', 'INTEGRATOR', 'TEMPLATES_SKIPPED', 'INTEGRATOR_SKIPPED'] as $count) {
                $selectionKeys[] = 'COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_IMPORT_CONFIRM_' . $count;
                $selectionKeys[] = 'COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_IMPORT_CONFIRM_' . $count . '_1';
            }
            foreach ($selectionKeys as $key) {
                Text::script($key);
            }
        }

        switch ($this->getLayout()) {
            case 'export':
                $toolbar->apply('packages.save')
                    ->form('bf-package-export')
                    ->formValidation(true);
                $toolbar->standardButton('download', 'COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_EXPORT_PACKAGE', 'packages.export')
                    ->form('bf-package-export')
                    ->formValidation(true);
                $toolbar->linkButton('close', 'JTOOLBAR_CLOSE')->url($home)->icon('icon-times');
                break;

            case 'delete':
                if ($this->selectedPackage !== '') {
                    $toolbar->standardButton('delete', 'COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_DELETE', 'packages.delete')
                        ->form('bf-package-delete')
                        ->icon('icon-trash');
                }
                $toolbar->linkButton('close', 'JTOOLBAR_CANCEL')->url($home)->icon('icon-times');
                break;

            case 'import':
                $toolbar->standardButton('upload', 'COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_IMPORT', 'packages.import')
                    ->form('bf-package-import')
                    ->formValidation(true);
                $toolbar->linkButton('close', 'JTOOLBAR_CLOSE')->url($home)->icon('icon-times');
                break;

            default:
                $toolbar->linkButton('installer', 'COM_BREEZINGFORMSNG_INSTALLER')
                    ->url(Route::_('index.php?option=com_breezingformsng&view=packages&layout=import'))
                    ->icon('icon-upload');
                $toolbar->linkButton('createpkg', 'COM_BREEZINGFORMSNG_INSTALLER_CREATEPKG')
                    ->url(Route::_('index.php?option=com_breezingformsng&view=packages&layout=export'))
                    ->icon('icon-plus');
                $toolbar->linkButton('delete-package', 'COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_DELETE')
                    ->url(Route::_('index.php?option=com_breezingformsng&view=packages&layout=delete'))
                    ->icon('icon-trash');

                // Last: Joomla right-aligns Options and every button after it.
                if ($user->authorise('core.admin', 'com_breezingformsng') || $user->authorise('core.options', 'com_breezingformsng')) {
                    $toolbar->preferences('com_breezingformsng');
                }
        }
    }
}
