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
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Uri\Uri;
use Vcmb\Component\BreezingformsNG\Administrator\Model\AboutModel;
use Vcmb\Component\BreezingformsNG\Administrator\View\BreezingformsNG\HtmlView as BaseHtmlView;

class HtmlView extends BaseHtmlView
{
    public array $plugins = [];
    public array $logReport = [];
    public array $auditReport = [];

    public function display($tpl = null)
    {
        if ($this->getLayout() === 'help') {
            parent::display($tpl);
            return;
        }

        /** @var CMSApplication $app */
        $app = Factory::getApplication();
        /** @var HtmlDocument $document */
        $document = $app->getDocument();
        $wa = $document->getWebAssetManager();
        $wa->getRegistry()->addExtensionRegistryFile('com_breezingformsng');
        $wa->useStyle('com_breezingformsng.about-style');
        $wa->useScript('com_breezingformsng.about');
        $layout = $this->getLayout();

        /** @var Toolbar $toolbar */
        $toolbar = $document->getToolbar('toolbar');

        if ($layout !== 'extensions') {
            $actionsDropdown = $toolbar->dropdownButton('about-actions-group');
            $actionsDropdown->text(Text::_('COM_BREEZINGFORMSNG_TOOLBAR_ACTIONS'));
            $actionsDropdown->toggleSplit(false);
            $actionsDropdown->icon('fa-solid fa-wrench');
            $actionsDropdown->buttonClass('btn btn-action');
            $actionsDropdown->listCheck(false);

            $actionsChildToolbar = $actionsDropdown->getChildToolbar();
            $actionsChildToolbar->standardButton('about_audit')
                ->task('about.runAudit')
                ->text('COM_BREEZINGFORMSNG_ABOUT_RUN_AUDIT')
                ->icon('fa-solid fa-stethoscope')
                ->listCheck(false);

            $actionsChildToolbar->standardButton('about_export_configuration')
                ->task('about.exportConfiguration')
                ->text('COM_BREEZINGFORMSNG_ABOUT_EXPORT_CONFIGURATION')
                ->icon('fa-solid fa-download')
                ->listCheck(false);

            $actionsChildToolbar->standardButton('about_import_configuration')
                ->task('about.importConfiguration')
                ->text('COM_BREEZINGFORMSNG_ABOUT_IMPORT_CONFIGURATION')
                ->icon('fa-solid fa-upload')
                ->listCheck(false);

            $toolbar->standardButton('about_show_log')
                ->task('about.showLog')
                ->text('COM_BREEZINGFORMSNG_ABOUT_SHOW_LOG')
                ->icon('fa-solid fa-file-lines')
                ->listCheck(false);

            $toolbar->linkButton('about_extensions')
                ->url(Route::_('index.php?option=com_breezingformsng&task=about.display&view=about&layout=extensions', false))
                ->text('COM_BREEZINGFORMSNG_ABOUT_EXTENSIONS')
                ->icon('fa-solid fa-plug');
        }

        ToolbarHelper::preferences('com_breezingformsng');
        ToolbarHelper::help(
            'COM_BREEZINGFORMSNG_HELP_ABOUT_TITLE',
            false,
            Uri::base() . 'index.php?option=com_breezingformsng&task=about.display&view=about&layout=help&tmpl=component'
        );

        if ($layout === 'extensions') {
            $this->plugins = $this->getInstalledPlugins();
        } else {
            HTMLHelper::_('bootstrap.tooltip', '.hasTooltip');
            $auditReport = $app->getUserState('com_breezingformsng.about.audit', []);
            $this->auditReport = is_array($auditReport) ? $auditReport : [];
            $app->setUserState('com_breezingformsng.about.audit', []);
            $logReport = $app->getUserState('com_breezingformsng.about.log', []);
            $this->logReport = is_array($logReport) ? $logReport : [];
            $app->setUserState('com_breezingformsng.about.log', []);
        }

        parent::display($tpl);
    }

    private function getInstalledPlugins(): array
    {
        try {
            $model = $this->getModel();

            if (!$model instanceof AboutModel) {
                throw new \RuntimeException('Unable to create BreezingForms NG About model.');
            }

            $rows = $model->getInstalledPluginRows();
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

            $group = (string) ($row['folder'] ?? '');
            $element = (string) ($row['element'] ?? '');
            $languageExtension = 'plg_' . $group . '_' . $element;
            $language->load($languageExtension, JPATH_ADMINISTRATOR) || $language->load($languageExtension, JPATH_SITE);

            $name = trim((string) ($manifest['name'] ?? $row['name'] ?? ''));
            $description = trim((string) ($manifest['description'] ?? ''));
            $description = trim(strip_tags(Text::_($description)));
            $description = preg_replace('/\s+/', ' ', $description) ?? $description;

            $plugins[] = [
                'id' => (int) ($row['extension_id'] ?? 0),
                'name' => $name !== '' ? Text::_($name) : Text::_('COM_BREEZINGFORMSNG_NOT_AVAILABLE'),
                'group' => $group,
                'element' => $element,
                'version' => (string) ($manifest['version'] ?? ''),
                'enabled' => (int) ($row['enabled'] ?? 0) === 1,
                'description' => $description !== '' ? $description : Text::_('COM_BREEZINGFORMSNG_NOT_AVAILABLE'),
                'category' => $this->getPluginCategory($group, $element),
                'purpose' => $this->getPluginPurpose($group, $element),
                'usage' => $this->getPluginUsage($group, $element),
            ];
        }

        return $plugins;
    }

    private function getPluginCategory(string $group, string $element): string
    {
        return match ($group) {
            'system' => Text::_('COM_BREEZINGFORMSNG_EXTENSION_CATEGORY_SYSTEM'),
            default => Text::_('COM_BREEZINGFORMSNG_EXTENSION_CATEGORY_OTHER'),
        };
    }

    private function getPluginPurpose(string $group, string $element): string
    {
        return match ($group . '.' . $element) {
            'system.bfcompat' => Text::_('COM_BREEZINGFORMSNG_EXTENSION_PURPOSE_COMPAT'),
            default => Text::_('COM_BREEZINGFORMSNG_EXTENSION_PURPOSE_OTHER'),
        };
    }

    private function getPluginUsage(string $group, string $element): string
    {
        return match ($group) {
            'system' => Text::_('COM_BREEZINGFORMSNG_EXTENSION_USAGE_SYSTEM'),
            default => Text::_('COM_BREEZINGFORMSNG_EXTENSION_USAGE_OTHER'),
        };
    }
}
