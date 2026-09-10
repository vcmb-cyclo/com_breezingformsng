<?php

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator;

use PHPUnit\Framework\TestCase;

final class AboutVersionSummaryTest extends TestCase
{
    public function testVersionSummaryDisplaysBuildAndPlatformMetadata(): void
    {
        $template = file_get_contents(__DIR__ . '/../../administrator/components/com_breezingformsng/tmpl/about/default.php');
        $manifest = file_get_contents(__DIR__ . '/../../com_breezingformsng.xml');
        $buildScript = file_get_contents(__DIR__ . '/../../scripts/build-package.sh');

        self::assertIsString($template);
        self::assertIsString($manifest);
        self::assertIsString($buildScript);
        self::assertStringContainsString("'buildType' => ''", $template);
        self::assertStringContainsString('COM_BREEZINGFORMSNG_ABOUT_PLATFORM_JOOMLA_6', $template);
        self::assertStringContainsString('COM_BREEZINGFORMSNG_ABOUT_PLATFORM_PHP_83', $template);
        self::assertStringContainsString('<buildType>development</buildType>', $manifest);
        self::assertStringContainsString('<buildType>production</buildType>', $buildScript);
    }
}
