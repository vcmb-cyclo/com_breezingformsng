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
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryServiceInterface;
use Joomla\CMS\Router\Route;
use Joomla\Database\DatabaseInterface;
use Vcmb\Component\BreezingformsNG\Administrator\Controller\PackagesController;
use Vcmb\Component\BreezingformsNG\Administrator\Mcp\McpAvailability;
use Vcmb\Component\BreezingformsNG\Administrator\Model\PackageTransferModel;
use Vcmb\Component\BreezingformsNG\Administrator\Service\AreaAccess;
use Vcmb\Component\BreezingformsNG\Administrator\Service\RecordInventoryService;
use Vcmb\Component\BreezingformsNG\Administrator\Service\SampleFormService;
use Vcmb\Component\BreezingformsNG\Administrator\Service\TrashInventoryService;
use Vcmb\Component\BreezingformsNG\Administrator\View\BreezingformsNG\HtmlView as BaseHtmlView;

/**
 * Tools screen: the default layout only holds the toolbar, each
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
    /** Package preselected in the list, after a save. */
    public string $selectedPackage = '';

    /** The management screen is creating a package: nothing is selected, the identifier is asked. */
    public bool $isNewPackage = false;

    /**
     * What the user typed and selected when a save was refused, to show it again.
     *
     * @var array{package?: string, metadata?: array<string, string>, select?: array<string, list<int>>}
     */
    public array $draft = [];
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

    /** MCP: enabled in the options, and its route plugin enabled. */
    public bool $mcpEnabled = false;
    public bool $mcpRouted = false;
    public bool $canConfigure = false;
    /** Submitted records in the trash, shown on the Trash card. */
    public int $trashedTotal = 0;
    /**
     * The sample forms, by slug: the id of the installed one (0 when it is not installed), whether another
     * form already uses its name (it cannot be installed, and is left as it is), and the form the Manage
     * button opens: the installed sample, else the form already using its name.
     *
     * @var array<string, array{name: string, installedId: int, nameTaken: bool, manageId: int}>
     */
    public array $samples = [];
    /**
     * The sample slugs by category, the business templates before the technical examples; a category
     * without any sample is left out.
     *
     * @var array<string, list<string>>
     */
    public array $sampleGroups = [];
    /** Installing the sample writes a form layout: it takes the Configure permission. */
    public bool $canInstallSample = false;

    public function display($tpl = null)
    {
        AreaAccess::assert($this->getCurrentUser(), AreaAccess::CONFIGURATION);
        // Export and import tell beforehand what the user may not transfer.
        $this->mayTransferTemplates = $this->getCurrentUser()->authorise('core.admin', 'com_breezingformsng');
        $this->mayTransferIntegrator = AreaAccess::isAllowed($this->getCurrentUser(), AreaAccess::CODE);

        if (in_array($this->getLayout(), ['export', 'update'], true)) {
            HTMLHelper::_('bootstrap.collapse');
            $this->getDocument()->getWebAssetManager()->useStyle('com_breezingformsng.admin-style');
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
            if ($this->getLayout() === 'update' && $this->selectedPackage === PackageTransferModel::NEW_PACKAGE) {
                $this->isNewPackage = true;
                $this->selectedPackage = '';
            }
            $this->profiles = $model->getPackageProfiles();
            $this->profiles = $model->resolveProfileAuditNames($this->profiles);
            $this->timezone = $this->getCurrentUser()->getTimezone();

            if ($this->getLayout() === 'update') {
                if ($this->selectedPackage !== '') {
                    if (!in_array($this->selectedPackage, $this->packages, true)) {
                        throw new \RuntimeException(Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_NOT_FOUND'), 404);
                    }
                }
            }
            $this->choices = $model->getExportChoices();

            if ($this->getLayout() === 'update') {
                /** @var CMSApplication $app */
                $app = Factory::getApplication();
                $draft = $app->getUserState(PackagesController::UPDATE_DRAFT_STATE, []);
                $this->draft = is_array($draft) ? $draft : [];
                $app->setUserState(PackagesController::UPDATE_DRAFT_STATE, null);
                $this->getDocument()->getWebAssetManager()
                    ->useStyle('com_breezingformsng.package-update-style')
                    ->useScript('com_breezingformsng.package-update');
                foreach (['NO_CHANGES', 'PENDING_CHANGES'] as $key) {
                    Text::script('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_UPDATE_' . $key);
                }
            }
        }

        if ($this->getLayout() === 'import') {
            /** @var CMSApplication $app */
            $app = Factory::getApplication();
            $report = $app->getUserState(PackagesController::REPORT_STATE, []);
            $this->importReport = is_array($report) ? $report : [];
            $app->setUserState(PackagesController::REPORT_STATE, null);
        }

        if ($this->getLayout() === 'default') {
            $this->mcpEnabled = McpAvailability::isEnabled();
            $this->mcpRouted = McpAvailability::isRouted();
            $database = Factory::getContainer()->get(DatabaseInterface::class);
            $this->trashedTotal = array_sum(array_column((new RecordInventoryService($database))->getInventory(), 'trashed'))
                + array_sum(array_column((new TrashInventoryService($database))->getTrashedItems(), 'count'));
            foreach (SampleFormService::slugs() as $slug) {
                $sampleService = new SampleFormService($database, $slug);
                $installedId = $sampleService->getInstalledFormId();
                $nameTaken = $installedId === 0 && $sampleService->getFormNameCount() > 0;
                $this->samples[$slug] = [
                    'name' => $sampleService->formName(),
                    'installedId' => $installedId,
                    'nameTaken' => $nameTaken,
                    'manageId' => $installedId > 0
                        ? $installedId
                        : ($nameTaken ? $sampleService->getFormIdByName() : 0),
                ];
            }
            foreach (SampleFormService::CATEGORIES as $category) {
                $slugs = array_values(array_filter(
                    SampleFormService::slugs(),
                    static fn (string $slug): bool => SampleFormService::category($slug) === $category
                ));
                if ($slugs !== []) {
                    $this->sampleGroups[$category] = $slugs;
                }
            }
            $this->canInstallSample = $this->getCurrentUser()->authorise('core.admin', 'com_breezingformsng');
            $this->canConfigure = $this->getCurrentUser()->authorise('core.admin', 'com_breezingformsng');
            $this->getDocument()->getWebAssetManager()
                ->useStyle('com_breezingformsng.mcp-status')
                ->useStyle('com_breezingformsng.tools-samples-style')
                ->useScript('com_breezingformsng.tools-audit')
                ->useScript('com_breezingformsng.tools-samples');
            foreach (
                [
                    'TOOLS_AUDIT_ISSUES', 'TOOLS_AUDIT_ISSUES_1', 'TOOLS_AUDIT_CLEAN', 'TOOLS_AUDIT_UNAVAILABLE',
                    'TOOLS_SAMPLE_HELP_NONE', 'TOOLS_SAMPLE_SELECTED_ONE', 'TOOLS_SAMPLE_SELECTED_MANY',
                ] as $key
            ) {
                Text::script('COM_BREEZINGFORMSNG_' . $key);
            }
        }

        $this->addToolbar();

        parent::display($tpl);
    }

    protected function getDetailLabel(): ?string
    {
        return match ($this->getLayout()) {
            'export' => Text::_('COM_BREEZINGFORMSNG_TOOLS_TILE_EXPORT'),
            'update' => Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_MANAGE_TITLE'),
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
            Text::script('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_CONFIGURATION_FILE');
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
                $toolbar->standardButton('download', 'COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_EXPORT_PACKAGE', 'packages.export')
                    ->form('bf-package-export')
                    ->formValidation(true);
                $toolbar->linkButton('close', 'JTOOLBAR_CLOSE')->url($home)->icon('icon-times');
                break;

            case 'update':
                if ($this->selectedPackage !== '' || $this->isNewPackage) {
                    $toolbar->standardButton('apply', 'JTOOLBAR_APPLY', 'packages.update')
                        ->form('bf-package-update')
                        ->icon('icon-check');
                }
                if ($this->selectedPackage !== '') {
                    $toolbar->confirmButton('delete', 'COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_REMOVE', 'packages.remove')
                        ->form('bf-package-remove')
                        ->message('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_REMOVE_CONFIRM')
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
                // Last: Joomla right-aligns Options and every button after it.
                if ($user->authorise('core.admin', 'com_breezingformsng') || $user->authorise('core.options', 'com_breezingformsng')) {
                    $toolbar->preferences('com_breezingformsng');
                }
        }
    }
}
