<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Administrator\View\About;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;
use Vcmb\Component\BreezingformsNG\Administrator\Model\AboutModel;
use Vcmb\Component\BreezingformsNG\Administrator\Service\AreaAccess;
use Vcmb\Component\BreezingformsNG\Administrator\Service\DatabaseAuditService;
use Vcmb\Component\BreezingformsNG\Administrator\Service\NgExtensions;
use Vcmb\Component\BreezingformsNG\Administrator\Service\InstallLogReader;
use Vcmb\Component\BreezingformsNG\Administrator\Service\RecordInventoryService;
use Vcmb\Component\BreezingformsNG\Administrator\Service\TrashInventoryService;
use Vcmb\Component\BreezingformsNG\Administrator\View\BreezingformsNG\HtmlView as BaseHtmlView;

class HtmlView extends BaseHtmlView
{
    /** @var list<array<string, mixed>> */
    public array $plugins = [];
    public bool $canEditPluginState = false;
    /** The module of the package: its state is a right of the Joomla Installer. */
    public bool $canEditModuleState = false;
    /** Uninstalling is a right of the Joomla Installer. */

    /** The changelog popup is served by the Installer component: it needs the right to manage it. */
    public bool $canViewChangelog = false;
    /** @var array<mixed> */
    public array $logReport = [];
    /** @var array<mixed> */
    public array $auditReport = [];

    /** Error of the last repair, shown in the audit section: HTML, escaped by the controller. */
    public string $auditError = '';
    /** @var list<array{form: int, name: string, published: int, archived: int, trashed: int, total: int}> */
    public array $recordInventory = [];
    /** @var list<array{view: string, label: string, count: int, items: list<array{id: int, title: string, name: string}>}> */
    public array $trashedItems = [];
    /** @var array<string, bool> Who may empty the trash of which type. */
    public array $canEmpty = [];
    public bool $canRepair = false;

    public bool $canDeleteOrphans = false;

    public function display($tpl = null)
    {
        if ($this->getLayout() === 'help') {
            parent::display($tpl);
            return;
        }

        /** @var CMSApplication $app */
        $app = Factory::getApplication();
        $user = $this->getCurrentUser();
        $this->canRepair = AreaAccess::canRepair($user);
        $this->canDeleteOrphans = AreaAccess::canDeleteOrphans($user);
        $this->canEditPluginState = $user->authorise('core.edit.state', 'com_plugins');
        $this->canViewChangelog = $user->authorise('core.manage', 'com_installer');
        $this->canEditModuleState = $this->canViewChangelog
            && $user->authorise('core.edit.state', 'com_installer');
        /** @var HtmlDocument $document */
        $document = $app->getDocument();
        $wa = $document->getWebAssetManager();
        $wa->getRegistry()->addExtensionRegistryFile('com_breezingformsng');
        $wa->useStyle('com_breezingformsng.about-style');
        $wa->useScript('com_breezingformsng.about');

        if ($this->getLayout() === 'extensions' && $this->canViewChangelog) {
            // The changelog of an extension opens in a dialog, like in the Installer's Manage screen.
            $wa->useScript('joomla.dialog-autocreate');
            Factory::getApplication()->getLanguage()->load('com_installer', JPATH_ADMINISTRATOR);
        }
        $layout = $this->getLayout();

        /** @var Toolbar $toolbar */
        $toolbar = $document->getToolbar('toolbar');

        if (in_array($layout, ['statistics', 'trash', 'log'], true)) {
            $toolbar->linkButton('about_back')
                ->url(Route::_('index.php?option=com_breezingformsng&view=packages', false))
                ->text('JTOOLBAR_BACK')
                ->icon('fa-solid fa-arrow-left');
        }

        if ($layout === 'extensions') {
            $toolbar->linkButton('about_extensions_back')
                ->url(Route::_('index.php?option=com_breezingformsng&task=about.display&view=about', false))
                ->text('JTOOLBAR_BACK')
                ->icon('fa-solid fa-arrow-left');
            // The Extensions Manager of Joomla, on the BreezingForms extensions.
            if ($user->authorise('core.manage', 'com_installer')) {
                $toolbar->linkButton('about_manage_plugins')
                    ->url(Route::_('index.php?option=com_installer&view=manage&filter[search]=' . rawurlencode('breezingforms'), false))
                    ->text('COM_BREEZINGFORMSNG_ABOUT_EXTENSIONS_MANAGE_PLUGINS')
                    // The icon of the Extensions screens of Joomla.
                    ->icon('fa-solid fa-puzzle-piece');
            }
        }

        if ($layout === 'audit') {
            $toolbar->linkButton('about_audit_back')
                ->url(Route::_('index.php?option=com_breezingformsng&view=packages', false))
                ->text('JTOOLBAR_BACK')
                ->icon('fa-solid fa-arrow-left');
            $toolbar->standardButton('about_audit')
                ->task('about.runAudit')
                ->text('COM_BREEZINGFORMSNG_TOOLS_AUDIT_RUN')
                ->icon('fa-solid fa-wave-square')
                ->listCheck(false);
        }

        if (!in_array($layout, ['extensions', 'audit', 'statistics', 'trash', 'log'], true)) {
            // The audit, the statistics, the trash and the install log are on the Tools page.
            $toolbar->linkButton('about_extensions')
                ->url(Route::_('index.php?option=com_breezingformsng&task=about.display&view=about&layout=extensions', false))
                ->text('COM_BREEZINGFORMSNG_ABOUT_EXTENSIONS')
                ->icon('fa-solid fa-puzzle-piece');
        }

        if ($user->authorise('core.admin', 'com_breezingformsng') || $user->authorise('core.options', 'com_breezingformsng')) {
            ToolbarHelper::preferences('com_breezingformsng');
        }
        ToolbarHelper::help(
            'COM_BREEZINGFORMSNG_HELP_ABOUT_TITLE',
            false,
            Uri::base() . 'index.php?option=com_breezingformsng&task=about.display&view=about&layout=help&tmpl=component'
        );

        if ($layout === 'extensions') {
            $this->plugins = $this->getInstalledPlugins();

            if ($this->canEditPluginState || $this->canEditModuleState) {
                $wa->registerAndUseScript(
                    'com_breezingformsng.admin-toggle-published',
                    'media/com_breezingformsng/js/admin/admin-toggle-published.js',
                    ['version' => 'auto'],
                    ['defer' => true],
                    ['core']
                );
                $document->addScriptOptions(
                    'com_breezingformsng.admin-toggle-published',
                    ['csrfToken' => Session::getFormToken()]
                );
                Text::script('JPUBLISHED');
                Text::script('JUNPUBLISHED');
                Text::script('COM_BREEZINGFORMSNG_AJAX_STATE_ERROR');
            }
        } elseif ($layout === 'statistics') {
            // Read-only: the table inventory of the audit, without its repair actions.
            $this->auditReport = (new DatabaseAuditService(
                Factory::getContainer()->get(DatabaseInterface::class),
                (string) $app->get('tmp_path', JPATH_ROOT . '/tmp')
            ))->run();
            $this->recordInventory = (new RecordInventoryService(
                Factory::getContainer()->get(DatabaseInterface::class)
            ))->getInventory();
        } elseif ($layout === 'trash') {
            $database = Factory::getContainer()->get(DatabaseInterface::class);
            $this->recordInventory = (new RecordInventoryService($database))->getInventory();
            $this->trashedItems = (new TrashInventoryService($database))->getTrashedByType();
            $canDelete = $user->authorise('core.delete', 'com_breezingformsng');
            $canCode = AreaAccess::isAllowed($user, AreaAccess::CODE);
            $this->canEmpty = [
                'forms' => $canDelete,
                'scripts' => $canDelete && $canCode,
                'pieces' => $canDelete && $canCode,
                'integrator' => $canDelete && $canCode,
            ];
        } elseif ($layout === 'audit') {
            $auditReport = $app->getUserState('com_breezingformsng.about.audit', []);
            $this->auditReport = is_array($auditReport) ? $auditReport : [];
            $app->setUserState('com_breezingformsng.about.audit', []);
            $this->auditError = (string) $app->getUserState('com_breezingformsng.about.audit_error', '');
            $app->setUserState('com_breezingformsng.about.audit_error', '');
        } elseif ($layout === 'log') {
            try {
                $this->logReport = (new InstallLogReader((string) $app->get('offset', 'UTC')))->read();
            } catch (\Throwable $exception) {
                $app->enqueueMessage(Text::sprintf('COM_BREEZINGFORMSNG_ABOUT_LOG_LOAD_FAILED', $exception->getMessage()), 'error');
            }
        }

        parent::display($tpl);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function getInstalledPlugins(): array
    {
        try {
            $model = $this->getModel();

            if (!$model instanceof AboutModel) {
                throw new \RuntimeException('Unable to create BreezingForms NG About model.');
            }

            $rows = $model->getBreezingFormsExtensionRows();
        } catch (\Throwable) {
            return [];
        }

        $plugins = [];
        $language = Factory::getApplication()->getLanguage();

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $manifest = [];
            $manifestCache = (string) ($row['manifest_cache'] ?? '');

            if ($manifestCache !== '') {
                $decodedManifest = json_decode($manifestCache, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($decodedManifest)) {
                    $manifest = $decodedManifest;
                }
            }

            $type = (string) ($row['type'] ?? 'plugin');
            $group = (string) ($row['folder'] ?? '');
            $element = (string) ($row['element'] ?? '');
            $languageExtension = match ($type) {
                'plugin' => 'plg_' . $group . '_' . $element,
                'library' => 'lib_' . $element,
                'package' => 'pkg_' . $element,
                'file' => 'file_' . $element,
                default => $element,
            };
            // The name and the description of an extension live in its .sys.ini file; the other strings in its .ini.
            foreach ([$languageExtension . '.sys', $languageExtension] as $languageFile) {
                $language->load($languageFile, JPATH_ADMINISTRATOR) || $language->load($languageFile, JPATH_SITE);
            }

            $name = trim((string) ($manifest['name'] ?? $row['name'] ?? ''));
            $description = trim((string) ($manifest['description'] ?? ''));
            $description = trim(strip_tags(Text::_($description)));
            $description = preg_replace('/\s+/', ' ', $description) ?? $description;

            $plugins[] = [
                'id' => (int) ($row['extension_id'] ?? 0),
                'type' => $type,
                // The changelog XML the extension declares in its manifest, when it has one.
                'changelogUrl' => $this->changelogUrl((string) ($manifest['changelogurl'] ?? '')),
                'name' => $name !== '' ? Text::_($name) : Text::_('COM_BREEZINGFORMSNG_NOT_AVAILABLE'),
                'group' => $group,
                'element' => $element,
                'version' => (string) ($manifest['version'] ?? ''),
                'enabled' => (int) ($row['enabled'] ?? 0) === 1,
                'canToggle' => match ($type) {
                    'plugin' => $this->canEditPluginState,
                    // Only the module of the package, not one of a former BreezingForms.
                    'module' => $this->canEditModuleState
                        && NgExtensions::owns($type, $element, '', (int) ($row['client_id'] ?? 0)),
                    default => false,
                },
                'description' => $description !== '' ? $description : Text::_('COM_BREEZINGFORMSNG_NOT_AVAILABLE'),
                'category' => $this->getPluginCategory($group, $element),
            ];
        }

        return $plugins;
    }

    /** Only a web address is linked: the manifest of an extension is not trusted to hold anything else. */
    private function changelogUrl(string $url): string
    {
        $url = trim($url);

        return preg_match('#^https?://#i', $url) === 1 && filter_var($url, FILTER_VALIDATE_URL) !== false ? $url : '';
    }

    private function getPluginCategory(string $group, string $element): string
    {
        return match ($group) {
            'system' => Text::_('COM_BREEZINGFORMSNG_EXTENSION_CATEGORY_SYSTEM'),
            default => Text::_('COM_BREEZINGFORMSNG_EXTENSION_CATEGORY_OTHER'),
        };
    }

    #[\Override]
    protected function getDetailLabel(): ?string
    {
        return match ($this->getLayout()) {
            'extensions' => Text::_('COM_BREEZINGFORMSNG_ABOUT_EXTENSIONS'),
            'audit' => Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT'),
            'statistics' => Text::_('COM_BREEZINGFORMSNG_TOOLS_STATISTICS_TITLE'),
            'trash' => Text::_('COM_BREEZINGFORMSNG_TOOLS_TRASH_TITLE'),
            'log' => Text::_('COM_BREEZINGFORMSNG_TOOLS_LOG_TITLE'),
            default => null,
        };
    }
}
