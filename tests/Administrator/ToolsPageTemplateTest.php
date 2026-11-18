<?php

/**
 * BreezingForms NG - A Joomla Forms Application
 *
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 **/

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator;

use PHPUnit\Framework\TestCase;

/**
 * The Tools page (packages view, default layout) shows its actions in Atum-style
 * cards, not in the toolbar, and keeps every action it offered before.
 */
final class ToolsPageTemplateTest extends TestCase
{
    private function template(): string
    {
        return (string) file_get_contents(
            __DIR__ . '/../../administrator/components/com_breezingformsng/tmpl/packages/default.php'
        );
    }

    public function testFourCardsAndSevenQuickAccessTiles(): void
    {
        $template = $this->template();

        self::assertSame(4, substr_count($template, '<section class="card"'));
        foreach (['bf-tools-maintenance', 'bf-tools-packages', 'bf-mcp-status', 'bf-tools-sample'] as $id) {
            self::assertStringContainsString('aria-labelledby="' . $id . '-title"', $template);
        }
        foreach (
            [
            'bf-tools-audit', 'bf-tools-statistics', 'bf-tools-trash', 'bf-tools-log',
            'bf-tools-install', 'bf-tools-create', 'bf-tools-dissolve',
            ] as $tile
        ) {
            self::assertStringContainsString("'id' => '" . $tile . "'", $template);
        }
        self::assertStringContainsString('bf-tile--danger', $template);
    }

    public function testPackageActionsAndOptionsLinkAreKept(): void
    {
        $template = $this->template();

        foreach (['import', 'export', 'delete'] as $layout) {
            self::assertStringContainsString("'layout' => '$layout'", $template);
        }
        self::assertStringContainsString('option=com_config&view=component&component=com_breezingformsng', $template);
        self::assertStringContainsString('$this->canConfigure', $template);
    }

    public function testTrashTileShowsHowManyRecordsItHolds(): void
    {
        $template = $this->template();

        self::assertStringContainsString("'badge' => \$this->trashedTotal", $template);
        self::assertStringContainsString('data-trash-count', $template);
        self::assertStringContainsString("Text::plural('COM_BREEZINGFORMSNG_TOOLS_TRASH_TOTAL'", $template);
        self::assertStringContainsString("aboutUrl('trash')", $template);
    }

    public function testToolbarHoldsOnlyTheOptionsButton(): void
    {
        $view = (string) file_get_contents(
            __DIR__ . '/../../administrator/components/com_breezingformsng/src/View/Packages/HtmlView.php'
        );
        $default = substr($view, (int) strpos($view, "            default:"));

        self::assertStringNotContainsString("linkButton('installer'", $default);
        self::assertStringContainsString("preferences('com_breezingformsng')", $default);
    }

    public function testAuditIsStartedByATokenProtectedPost(): void
    {
        $template = $this->template();

        self::assertStringContainsString('method="post"', $template);
        self::assertStringContainsString('task=about.runAudit', $template);
        self::assertStringContainsString("HTMLHelper::_('form.token')", $template);
    }

    public function testTrashOpensItsOwnScreenAndLeavesTheAudit(): void
    {
        $base = __DIR__ . '/../../administrator/components/com_breezingformsng';
        $audit = (string) file_get_contents($base . '/tmpl/about/audit.php');
        $trash = (string) file_get_contents($base . '/tmpl/about/trash.php');

        self::assertStringContainsString("aboutUrl('trash')", $this->template());
        self::assertStringNotContainsString('about.emptyRecordsTrash', $audit);
        self::assertStringContainsString('about.emptyRecordsTrash', $trash);
        self::assertStringContainsString("HTMLHelper::_('form.token')", $trash);
    }

    public function testAuditBadgeIsCountedInTheBackgroundWithATokenProtectedPost(): void
    {
        $base = __DIR__ . '/../../administrator/components/com_breezingformsng';
        $js = (string) file_get_contents($base . '/../../../media/com_breezingformsng/js/admin/tools-audit.js');
        $controller = (string) file_get_contents($base . '/src/Controller/AboutController.php');

        self::assertStringContainsString('data-bf-audit-badge', $this->template());
        self::assertStringContainsString('spinner-border', $this->template());
        self::assertStringContainsString('task=about.auditSummary', $this->template());
        self::assertStringContainsString("method: 'POST'", $js);
        self::assertStringContainsString('new FormData(form)', $js);
        self::assertMatchesRegularExpression(
            "/function auditSummary\\(\\): void\\s*\\{\\s*if \\(!\\\$this->checkToken\\('post', false\\)\\)/",
            $controller
        );
        $start = strpos($controller, 'public function auditSummary(): void');
        $end = strpos($controller, 'public function repairDuplicateIndexes(): void', $start ?: 0);
        self::assertNotFalse($start);
        self::assertNotFalse($end);
        $action = substr($controller, $start, $end - $start);
        self::assertStringContainsString('$this->getAuthorizedApplication();', $action);
        self::assertStringContainsString('JINVALID_TOKEN', $action);
        self::assertStringContainsString("'issues' => (int)", $action);
        self::assertStringContainsString("'errors' => (int)", $action);
        self::assertStringNotContainsString("'report' =>", $action);
    }

    public function testConfigurationExportAndImportAreGone(): void
    {
        $base = __DIR__ . '/../../administrator/components/com_breezingformsng';
        $about = (string) file_get_contents($base . '/src/View/About/HtmlView.php');
        $controller = (string) file_get_contents($base . '/src/Controller/AboutController.php');

        // Packages cover the transfers: the raw configuration export and the import that never worked are removed.
        self::assertStringNotContainsString('Configuration', $about . $this->template());
        self::assertStringNotContainsString('function exportConfiguration', $controller);
        self::assertStringNotContainsString('function importConfiguration', $controller);
        self::assertStringNotContainsString('about-actions-group', $about);
    }

    public function testEveryToolsLayoutIsCoveredByTheControllerManagePermission(): void
    {
        $base = __DIR__ . '/../../administrator/components/com_breezingformsng';
        $controller = (string) file_get_contents($base . '/src/Controller/AboutController.php');
        $view = (string) file_get_contents($base . '/src/View/About/HtmlView.php');

        self::assertMatchesRegularExpression(
            "/public function display\\([^)]*\\)\\s*\\{(?:(?!public function).)*authorise\\('core.manage', 'com_breezingformsng'\\)/s",
            $controller
        );
        foreach (['audit', 'statistics', 'trash', 'log'] as $layout) {
            self::assertStringContainsString("'" . $layout . "'", $view);
        }
        self::assertMatchesRegularExpression(
            "/public function auditSummary\\(\\): void\\s*\\{(?:(?!public function).)*getAuthorizedApplication\\(\\)/s",
            $controller
        );
    }

    public function testInstallLogMovedFromAboutToItsOwnToolsScreen(): void
    {
        $base = __DIR__ . '/../../administrator/components/com_breezingformsng';
        $about = (string) file_get_contents($base . '/tmpl/about/default.php');
        $view = (string) file_get_contents($base . '/src/View/About/HtmlView.php');
        $controller = (string) file_get_contents($base . '/src/Controller/AboutController.php');

        self::assertStringContainsString("aboutUrl('log')", $this->template());
        self::assertStringContainsString('bf-about-log', (string) file_get_contents($base . '/tmpl/about/log.php'));
        self::assertStringNotContainsString('bf-about-log', $about);
        self::assertStringNotContainsString('about.showLog', $view);
        self::assertStringNotContainsString('function showLog', $controller);
        self::assertStringContainsString('breezingforms_install2.log', (string) file_get_contents($base . '/src/Service/InstallLogReader.php'));
    }

    public function testSampleFormInstallIsATokenProtectedPostThatNeverOverwrites(): void
    {
        $base = __DIR__ . '/../../administrator/components/com_breezingformsng';
        $controller = (string) file_get_contents($base . '/src/Controller/PackagesController.php');
        $install = substr($controller, (int) strpos($controller, 'public function installSample'));
        $install = substr($install, 0, (int) strpos($install, 'public function import'));

        self::assertStringContainsString('task=packages.installSample', $this->template());
        self::assertStringContainsString("HTMLHelper::_('form.token')", $this->template());
        // The confirmation names the forms ticked: it lives in the script of the card, not in the template.
        $script = (string) file_get_contents(__DIR__ . '/../../media/com_breezingformsng/js/admin/tools-samples.js');
        self::assertStringContainsString('window.confirm', $script);
        self::assertStringContainsString('data-confirm', $this->template());
        self::assertStringContainsString('$this->checkToken();', $install);
        self::assertStringContainsString('$this->assertAuthorised();', $install);
        self::assertStringContainsString('mayTransferTemplates()', $install);
        // An installed sample is never replaced: the form may have been edited.
        self::assertLessThan(
            (int) strpos($install, '->import('),
            (int) strpos($install, 'getInstalledFormId() > 0')
        );
    }

    public function testSampleInstallButtonIsDisabledWhenTheSampleNameIsTaken(): void
    {
        $base = __DIR__ . '/../../administrator/components/com_breezingformsng';
        $controller = (string) file_get_contents($base . '/src/Controller/PackagesController.php');
        $install = substr($controller, (int) strpos($controller, 'public function installSample'));
        $install = substr($install, 0, (int) strpos($install, 'public function import'));

        self::assertStringContainsString('!$sample[\'nameTaken\']', $this->template());
        // A name already in use is a notice, never a "could not be installed" error.
        self::assertStringNotContainsString(
            "throw new \\RuntimeException(Text::_('COM_BREEZINGFORMSNG_TOOLS_SAMPLE_ALREADY'))",
            $install
        );
    }

    public function testToolsTranslationKeysExistInEveryLanguage(): void
    {
        preg_match_all("/'(COM_BREEZINGFORMSNG_TOOLS_[A-Z_]+)'/", $this->template(), $matches);
        self::assertNotEmpty($matches[1]);
        // The sample keys are built from the catalog: a prefix in the template, the whole key checked here.
        $keys = array_filter($matches[1], static fn (string $key): bool => !str_ends_with($key, '_'));
        foreach (['contact', 'event', 'quote', 'survey'] as $slug) {
            $keys[] = 'COM_BREEZINGFORMSNG_TOOLS_SAMPLE_' . strtoupper($slug) . '_ITEM';
            $keys[] = 'COM_BREEZINGFORMSNG_TOOLS_SAMPLE_' . strtoupper($slug) . '_DESC';
        }
        foreach (['business', 'technical'] as $category) {
            $keys[] = 'COM_BREEZINGFORMSNG_TOOLS_SAMPLE_GROUP_' . strtoupper($category);
        }

        foreach (['en-GB', 'fr-FR', 'de-DE', 'it-IT', 'es-ES', 'hu-HU', 'nl-NL', 'tr-TR'] as $lang) {
            $ini = (string) file_get_contents(
                __DIR__ . "/../../administrator/components/com_breezingformsng/language/$lang/com_breezingformsng.ini"
            );
            foreach (array_unique($keys) as $key) {
                self::assertMatchesRegularExpression('/^' . $key . '="[^"]+"/m', $ini, "$key missing in $lang");
            }
        }
    }

    public function testSamplesAreTickedThenInstalledTogetherWithOneButton(): void
    {
        $template = $this->template();
        $base = __DIR__ . '/../../administrator/components/com_breezingformsng';
        $controller = (string) file_get_contents($base . '/src/Controller/PackagesController.php');

        // One form, one token and one button for the whole list; an installed sample is ticked and cannot be changed.
        self::assertSame(1, substr_count($template, 'id="bf-tools-sample-form"'));
        $card = substr($template, (int) strpos($template, 'id="bf-tools-sample"'));
        self::assertSame(1, substr_count($card, "HTMLHelper::_('form.token')"));
        self::assertStringContainsString('name="samples[]"', $template);
        self::assertStringContainsString("\$installed ? 'checked'", $template);
        self::assertStringContainsString("\$selectable ? '' : 'disabled'", $template);
        // The controller installs each ticked sample on its own: one failure does not stop the others.
        self::assertStringContainsString("get('samples', [], 'array')", $controller);
        self::assertStringContainsString('private function installOneSample', $controller);
        self::assertStringContainsString('foreach ($slugs as $slug)', $controller);
    }
}
