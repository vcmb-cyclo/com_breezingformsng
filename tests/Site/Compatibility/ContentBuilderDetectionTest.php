<?php

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Compatibility;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Site\Service\Integration\ContentBuilderInstallation;

/**
 * ContentBuilder NG renamed its manifest (com_contentbuilderng.xml ->
 * contentbuilderng.xml) and removes the old file on update: both names must
 * be recognised.
 */
final class ContentBuilderDetectionTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/bfng-cbng-' . bin2hex(random_bytes(8));
        mkdir($this->directory);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->directory . '/*') ?: []);
        rmdir($this->directory);
    }

    /** @return iterable<string, array{list<string>, bool}> */
    public static function manifests(): iterable
    {
        yield 'not installed' => [[], false];
        yield 'before 6.1.19: old name only' => [['com_contentbuilderng.xml'], true];
        yield '6.1.19 to 6.1.22.3: both names' => [['com_contentbuilderng.xml', 'contentbuilderng.xml'], true];
        yield '6.1.22.4 and later: new name only' => [['contentbuilderng.xml'], true];
        yield 'unrelated file' => [['access.xml'], false];
    }

    /** @param list<string> $files */
    #[DataProvider('manifests')]
    public function testInstallationIsRecognisedUnderBothManifestNames(array $files, bool $expected): void
    {
        foreach ($files as $file) {
            touch($this->directory . '/' . $file);
        }

        self::assertSame($expected, ContentBuilderInstallation::isInstalled($this->directory));
    }

    /** @return iterable<string, array{string}> */
    public static function integrationFiles(): iterable
    {
        yield 'processor facade' => ['components/com_breezingformsng/src/Support/processor_facade.php'];
        yield 'export engine' => ['components/com_breezingformsng/src/Service/Export/ExportEngine.php'];
        yield 'rendering engine' => ['components/com_breezingformsng/src/Service/Rendering/RenderingEngine.php'];
        yield 'record model' => ['administrator/components/com_breezingformsng/src/Model/RecordModel.php'];
        yield 'quickmode controller' => [
            'administrator/components/com_breezingformsng/src/Controller/QuickmodeController.php',
        ];
    }

    #[DataProvider('integrationFiles')]
    public function testIntegrationPointsShareTheDetection(string $file): void
    {
        $source = file_get_contents(\dirname(__DIR__, 3) . '/' . $file);

        self::assertIsString($source);
        self::assertStringNotContainsString('contentbuilderng.xml', $source);
        self::assertStringContainsString('ContentBuilderInstallation::isInstalled(', $source);
    }
}
