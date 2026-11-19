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
            self::removeDirectory($directory);
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
            $rules = (string) file_get_contents($file);
            self::assertStringContainsString("<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>", $rules);
            self::assertStringContainsString("<IfModule !mod_authz_core.c>\n    deny from all\n</IfModule>", $rules);
            self::assertFileExists($directory . '/index.html');
        } finally {
            self::removeDirectory($directory);
        }
    }

    public function testIndexIsAddedNextToExistingHtaccessWithoutTouchingIt(): void
    {
        $directory = sys_get_temp_dir() . '/bf-protection-' . bin2hex(random_bytes(8));
        mkdir($directory);
        file_put_contents($directory . '/.htaccess', "custom\n");
        file_put_contents($directory . '/index.html', 'mine');

        try {
            UploadDirectoryProtection::ensure($directory, true);
            self::assertSame("custom\n", file_get_contents($directory . '/.htaccess'));
            self::assertSame('mine', file_get_contents($directory . '/index.html'));
        } finally {
            self::removeDirectory($directory);
        }
    }

    public function testOnlyDirectoriesUnderTheWebRootAreProtected(): void
    {
        $base = sys_get_temp_dir() . '/bf-protection-' . bin2hex(random_bytes(8));
        $root = $base . '/site';
        $inside = $root . '/media/uploads';
        $outside = $base . '/private';
        $sibling = $base . '/site-other';
        mkdir($inside, 0777, true);
        mkdir($outside);
        mkdir($sibling);

        try {
            UploadDirectoryProtection::ensureWithinWebRoot($inside, $root);
            UploadDirectoryProtection::ensureWithinWebRoot($outside, $root);
            UploadDirectoryProtection::ensureWithinWebRoot($sibling, $root);
            UploadDirectoryProtection::ensureWithinWebRoot($base . '/missing', $root);

            self::assertFileExists($inside . '/.htaccess');
            self::assertFileExists($inside . '/index.html');
            self::assertFileDoesNotExist($outside . '/.htaccess');
            self::assertFileDoesNotExist($sibling . '/.htaccess');
            self::assertFalse(UploadDirectoryProtection::isWithin($inside . '/../../../private', $root));
        } finally {
            self::removeDirectory($base);
        }
    }

    private static function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        foreach (new \FilesystemIterator($directory) as $entry) {
            $entry->isDir() && !$entry->isLink() ? self::removeDirectory($entry->getPathname()) : unlink($entry->getPathname());
        }

        rmdir($directory);
    }
}
