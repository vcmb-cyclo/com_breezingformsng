<?php

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator;

use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Administrator\Service\QuickmodeThemePath;

final class QuickmodeThemePathTest extends TestCase
{
    public function testOnlyAnExistingFileInARealThemeDirectoryIsAccepted(): void
    {
        $root = sys_get_temp_dir() . '/bf-themes-' . bin2hex(random_bytes(8));
        mkdir($root);
        mkdir($root . '/themes');
        mkdir($root . '/themes/Example Theme');
        mkdir($root . '/outside');
        file_put_contents($root . '/themes/Example Theme/vars.txt', 'valid');
        file_put_contents($root . '/outside/vars.txt', 'sentinel');
        symlink($root . '/outside', $root . '/themes/link');
        mkdir($root . '/themes/filelink');
        symlink($root . '/outside/vars.txt', $root . '/themes/filelink/vars.txt');

        try {
            $themes = $root . '/themes';
            $validPath = QuickmodeThemePath::resolve($themes, 'Example Theme');
            self::assertSame(realpath($themes . '/Example Theme/vars.txt'), $validPath);
            file_put_contents($validPath, 'updated');
            self::assertSame('updated', file_get_contents($themes . '/Example Theme/vars.txt'));
            $invalidNames = ['../outside', '..\\outside', "Example\0Theme", '.', '..', 'link', 'filelink', 'missing'];
            foreach ($invalidNames as $name) {
                self::assertNull(QuickmodeThemePath::resolve($themes, $name), $name);
            }
            self::assertSame('sentinel', file_get_contents($root . '/outside/vars.txt'));
        } finally {
            unlink($root . '/themes/filelink/vars.txt');
            rmdir($root . '/themes/filelink');
            unlink($root . '/themes/link');
            unlink($root . '/themes/Example Theme/vars.txt');
            rmdir($root . '/themes/Example Theme');
            unlink($root . '/outside/vars.txt');
            rmdir($root . '/outside');
            rmdir($root . '/themes');
            rmdir($root);
        }
    }
}
