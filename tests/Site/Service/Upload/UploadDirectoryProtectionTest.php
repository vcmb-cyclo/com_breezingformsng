<?php

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Upload;

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Site\Service\Upload\UploadDirectoryProtection;

#[RunTestsInSeparateProcesses]
final class UploadDirectoryProtectionTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists(\Joomla\Filesystem\File::class)) {
            eval('namespace Joomla\\Filesystem; class File {'
                . 'public static function write($path, $content) {'
                . 'return file_put_contents($path, $content) !== false; }'
                . '}');
        }
    }

    public function testExistingRulesArePreservedWhetherProtectionIsEnabledOrNot(): void
    {
        $directory = sys_get_temp_dir() . '/bf-protection-' . bin2hex(random_bytes(8));
        mkdir($directory);
        $file = $directory . '/.htaccess';
        file_put_contents($file, "custom rules\n");

        try {
            UploadDirectoryProtection::ensure($directory, false);
            self::assertSame("custom rules\n", file_get_contents($file));
            UploadDirectoryProtection::ensure($directory, true);
            self::assertSame("custom rules\n", file_get_contents($file));
        } finally {
            if (is_file($file)) {
                unlink($file);
            }
            rmdir($directory);
        }
    }

    public function testProtectionIsCreatedOnlyWhenEnabled(): void
    {
        $directory = sys_get_temp_dir() . '/bf-protection-' . bin2hex(random_bytes(8));
        mkdir($directory);
        $file = $directory . '/.htaccess';

        try {
            UploadDirectoryProtection::ensure($directory, false);
            self::assertFileDoesNotExist($file);
            UploadDirectoryProtection::ensure($directory, true);
            self::assertSame('deny from all', file_get_contents($file));
        } finally {
            if (is_file($file)) {
                unlink($file);
            }
            rmdir($directory);
        }
    }
}
