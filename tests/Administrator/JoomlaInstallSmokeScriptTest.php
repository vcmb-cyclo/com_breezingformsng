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

final class JoomlaInstallSmokeScriptTest extends TestCase
{
    public function testContentBuilderRuntimeContextIsLoadedBeforeTheBFNGLoaders(): void
    {
        $source = file_get_contents(__DIR__ . '/../../scripts/joomla-install-smoke.sh');

        self::assertIsString($source);

        $runtimeContextPosition = strpos($source, 'src/Helper/RuntimeContextHelper.php');
        $associationLoaderPosition = strpos($source, 'ContentBuilderFormMetadataLoader.php');

        self::assertIsInt($runtimeContextPosition);
        self::assertIsInt($associationLoaderPosition);
        self::assertLessThan($associationLoaderPosition, $runtimeContextPosition);
    }

    public function testContentBuilderLoaderCommandStartsAWebApplicationContext(): void
    {
        $source = file_get_contents(__DIR__ . '/../../scripts/joomla-install-smoke.sh');

        self::assertIsString($source);
        self::assertStringContainsString('docker exec -e HTTP_HOST=localhost', $source);
        self::assertStringContainsString('Factory::$application = $app;', $source);
    }

    public function testContentBuilderSmokeExercisesExistingFilesAndSignatures(): void
    {
        $source = file_get_contents(__DIR__ . '/../../scripts/joomla-install-smoke.sh');

        self::assertIsString($source);
        self::assertStringContainsString('ContentBuilderHydrationScriptBuilder.php', $source);
        self::assertStringContainsString('ff_elem701', $source);
        self::assertStringContainsString('data:image', $source);
        self::assertStringContainsString('unlink($signaturePath);', $source);
    }

    public function testUpdateMigratesALegacyInstallAndAuditsTheDatabase(): void
    {
        $source = file_get_contents(__DIR__ . '/../../scripts/joomla-install-smoke.sh');

        self::assertIsString($source);

        $legacySetupPosition = strpos($source, 'ADD \`archived\` TINYINT(1)');
        $updatePosition = strpos($source, 'must succeed without errors, migrate the legacy state');
        $auditPosition = strpos($source, 'maintenance:database');

        self::assertIsInt($legacySetupPosition);
        self::assertIsInt($updatePosition);
        self::assertIsInt($auditPosition);
        self::assertLessThan($updatePosition, $legacySetupPosition);
        self::assertLessThan($auditPosition, $updatePosition);
        self::assertStringContainsString('SET s.version_id = \'${first_update_version}\'', $source);
        self::assertStringContainsString('"${schema_version}" != "${last_update_version}"', $source);
        self::assertStringContainsString('All database table structures are up to date.', $source);
    }

    public function testSmokeUsesTheInstalledComposerAutoloaderForCaptchaAndPdf(): void
    {
        $source = file_get_contents(__DIR__ . '/../../scripts/joomla-install-smoke.sh');

        self::assertIsString($source);
        self::assertStringContainsString('src/Helper/VendorHelper.php', $source);
        self::assertStringContainsString('VendorHelper::load();', $source);
        self::assertStringContainsString('class_exists("TCPDF")', $source);
        self::assertStringContainsString('src/Service/PdfDocument.php', $source);
    }
}
