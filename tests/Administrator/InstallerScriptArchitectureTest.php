<?php

/**
 * BreezingForms NG - A Joomla Forms Application
 *
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 **/

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator;

use PHPUnit\Framework\TestCase;

final class InstallerScriptArchitectureTest extends TestCase
{
    public function testPreflightDocumentsItsBooleanContract(): void
    {
        $source = file_get_contents(__DIR__ . '/../../script.php');

        self::assertIsString($source);
        self::assertStringContainsString(
            '* @return bool',
            $this->sectionBefore($source, 'public function preflight(string $type, InstallerAdapter $adapter): bool')
        );
    }

    public function testScriptImplementsTheInstallerScriptInterface(): void
    {
        $source = file_get_contents(__DIR__ . '/../../script.php');

        self::assertIsString($source);
        self::assertStringContainsString(
            'class com_breezingformsngInstallerScript implements InstallerScriptInterface',
            $source
        );

        foreach (
            [
                'public function install(InstallerAdapter $adapter): bool',
                'public function update(InstallerAdapter $adapter): bool',
                'public function uninstall(InstallerAdapter $adapter): bool',
                'public function preflight(string $type, InstallerAdapter $adapter): bool',
                'public function postflight(string $type, InstallerAdapter $adapter): bool',
            ] as $signature
        ) {
            self::assertStringContainsString($signature, $source);
        }
    }

    public function testPluginUninstallUsesTheJoomlaSixArgumentList(): void
    {
        $source = file_get_contents(__DIR__ . '/../../script.php');

        self::assertIsString($source);
        self::assertStringNotContainsString("->uninstall('plugin', \$extensionId, 1)", $source);
        self::assertStringNotContainsString("->uninstall('plugin', \$id, 1)", $source);
        self::assertSame(2, substr_count($source, "->uninstall('plugin', \$"));
    }

    public function testUninstallAlsoRemovesTheSiteModule(): void
    {
        $source = file_get_contents(__DIR__ . '/../../script.php');

        self::assertIsString($source);
        self::assertSame(1, substr_count($source, "->uninstall('module', \$moduleId)"));
    }

    public function testPostflightRemovesTheObsoleteGlobalPdfTemplate(): void
    {
        $source = file_get_contents(__DIR__ . '/../../script.php');

        self::assertIsString($source);
        foreach ([
            "JPATH_SITE . '/language/fr-FR/fr-FR.com_breezingforms.ini'",
            "JPATH_SITE . '/language/en-GB/en-GB.com_breezingforms.ini'",
            "JPATH_SITE . '/media/breezingforms/pdftpl/pdf_attachment.php'",
            "JPATH_SITE . '/media/breezingforms/pdftpl/export_pdf.php'",
            "JPATH_SITE . '/media/breezingforms/downloadtpl/download.php'",
            "JPATH_SITE . '/media/breezingforms/downloadtpl/sofort_download.php'",
            "JPATH_SITE . '/media/breezingforms/downloadtpl/sofort_success.php'",
            "JPATH_SITE . '/media/breezingforms/downloadtpl/stripe_download.php'",
            "JPATH_SITE . '/media/breezingforms/downloadtpl/error.php'",
            "JPATH_ADMINISTRATOR . '/language/fr-FR/fr-FR.com_breezingforms.ini'",
            "JPATH_ADMINISTRATOR . '/language/fr-FR/fr-FR.com_breezingforms.sys.ini'",
            "JPATH_ADMINISTRATOR . '/language/en-GB/en-GB.com_breezingforms.sys.ini'",
            "JPATH_ADMINISTRATOR . '/language/en-GB/en-GB.com_breezingforms.ini'",
        ] as $obsoletePath) {
            self::assertStringContainsString($obsoletePath, $source);
        }

        self::assertStringContainsString('This generic Joomla 3 PDF template is superseded', $source);
        self::assertStringContainsString(
            '$this->removeObsoleteComponentFiles();',
            $source
        );
    }

    public function testPostflightReportsLegacyPdfCustomisations(): void
    {
        $source = file_get_contents(__DIR__ . '/../../script.php');

        self::assertIsString($source);
        self::assertStringContainsString('$this->reportLegacyPdfCustomisations();', $source);
        self::assertSame(1, preg_match("/\\\$legacyCall = '(.+?)'\\s*\\.\\s*'(.+?)';/", $source, $match));

        $legacyCall = $match[1] . $match[2];
        $legacyCalls = [
            '$this->SetY(-25);',
            '$this->getAliasNbPages()',
            '$pdf->writeHTML($html);',
            '$pdf -> Output($name, "F");',
        ];
        foreach ($legacyCalls as $call) {
            self::assertSame(1, preg_match($legacyCall, $call), $call);
        }
        $nativeCalls = ['$this->getHTMLCell($html);', '$pdf->addDocumentPage();', 'echo (int) $pdfPageNumber;'];
        foreach ($nativeCalls as $call) {
            self::assertSame(0, preg_match($legacyCall, $call), $call);
        }

        $keys = [
            'COM_BREEZINGFORMSNG_INSTALLER_WARNING_PDF_LEGACY_TEMPLATES',
            'COM_BREEZINGFORMSNG_INSTALLER_WARNING_PDF_IGNORED_TEMPLATES',
            'COM_BREEZINGFORMSNG_INSTALLER_WARNING_PDF_LEGACY_FONTS',
            'COM_BREEZINGFORMSNG_INSTALLER_WARNING_PDF_BFPDF_CODE',
        ];
        $languages = glob(
            __DIR__ . '/../../administrator/components/com_breezingformsng/language/*/com_breezingformsng.ini'
        );
        self::assertIsArray($languages);
        self::assertCount(8, $languages);

        foreach ($keys as $key) {
            self::assertStringContainsString("'{$key}'", $source);
            foreach ($languages as $language) {
                $strings = parse_ini_file($language);
                self::assertIsArray($strings);
                $label = basename(dirname($language)) . ' ' . $key;
                self::assertStringEndsWith(': %s', $strings[$key] ?? '', $label);
            }
        }
    }

    public function testAboutPageUsesOnlyTheCurrentManifestPath(): void
    {
        $source = file_get_contents(__DIR__ . '/../../administrator/components/com_breezingformsng/tmpl/about/default.php');

        self::assertIsString($source);
        self::assertSame(
            1,
            substr_count(
                $source,
                "JPATH_ADMINISTRATOR . '/components/com_breezingformsng/breezingformsng.xml'"
            )
        );

        foreach ([
            "JPATH_ADMINISTRATOR . '/components/com_breezingformsng/com_breezingformsng.xml'",
            "JPATH_ADMINISTRATOR . '/components/com_breezingformsng/breezingforms.xml'",
            "JPATH_ADMINISTRATOR . '/components/com_breezingformsng/com_breezingformsng_ng.xml'",
            "JPATH_ADMINISTRATOR . '/components/com_breezingformsng/com_breezingformsng-ng.xml'",
        ] as $obsoleteManifestPath) {
            self::assertStringNotContainsString($obsoleteManifestPath, $source);
        }
    }

    private function sectionBefore(string $source, string $needle): string
    {
        $position = strpos($source, $needle);

        self::assertIsInt($position);

        return substr($source, max(0, $position - 250), 250);
    }

    /**
     * The cleanup must never name a file or a folder the package still ships:
     * it would delete it right after the update installed it.
     */
    public function testObsoleteCleanupOnlyNamesPathsThePackageNoLongerShips(): void
    {
        $root = __DIR__ . '/../..';
        $source = file_get_contents($root . '/script.php');

        self::assertIsString($source);

        $start = strpos($source, 'private function removeObsoleteComponentFiles(): void');
        self::assertNotFalse($start);
        $body = substr($source, $start, strpos($source, "\n    }\n", $start) - $start);

        self::assertSame(
            1,
            preg_match_all("/JPATH_(SITE|ADMINISTRATOR) \\. '(\\/[^']+)'/", $body, $matches, PREG_SET_ORDER) > 100 ? 1 : 0
        );

        foreach ($matches as [, $base, $path]) {
            if ($path === '/components/com_breezingformsng/vendor/tecnickcom/tcpdf') {
                // The ignored local Composer vendor can still contain the old
                // package; the release validator proves it is absent from ZIPs.
                $validator = file_get_contents($root . '/scripts/validate-package.sh');
                self::assertIsString($validator);
                self::assertStringContainsString('vendor/tecnickcom/tcpdf/', $validator);
                continue;
            }

            $shipped = $root . ($base === 'ADMINISTRATOR' ? '/administrator' : '') . $path;

            self::assertFileDoesNotExist($shipped, "The cleanup would delete a shipped path: {$path}");
        }

        self::assertStringContainsString("/components/com_breezingformsng/libraries/jquery/ladda',", $body);
        self::assertStringContainsString("/components/com_breezingformsng/src/Service/ScriptManager.php',", $body);
        self::assertStringContainsString("/components/com_breezingformsng/vendor/tecnickcom/tcpdf',", $body);
        // The classic skins may hold site customisations: they are not removed.
        self::assertStringNotContainsString("/themes/quickmode/aqua'", $body);
    }
}
