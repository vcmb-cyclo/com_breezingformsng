<?php

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
            $this->sectionBefore($source, 'public function preflight(string $type, $parent): bool')
        );
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
}
