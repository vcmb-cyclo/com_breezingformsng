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

namespace Joomla\CMS\Date {
    if (!class_exists(Date::class)) {
        class Date extends \DateTime
        {
            public function format(string $format, bool $local = false): string
            {
                return parent::format($format);
            }

            public function getOffsetFromGMT(): int
            {
                return (int) parent::format('Z');
            }
        }
    }
}

namespace Joomla\CMS\Filter {
    if (!class_exists(InputFilter::class)) {
        final class InputFilter
        {
            public static function getInstance(array $tags = [], array $attr = [], int $tagMethod = 0, int $attrMethod = 0): self
            {
                return new self();
            }

            public function clean(string $value, string $type = 'string'): string
            {
                return strip_tags($value);
            }
        }
    }
}

namespace Joomla\Filesystem {
    if (!class_exists(File::class)) {
        final class File
        {
            public static function makeSafe(string $file): string
            {
                return (string) preg_replace('/[^A-Za-z0-9._-]/', '_', $file);
            }

            public static function getExt(string $file): string
            {
                return pathinfo($file, PATHINFO_EXTENSION);
            }
        }
    }

    if (!class_exists(Path::class)) {
        final class Path
        {
            public static function clean(string $path): string
            {
                return str_replace('\\', '/', $path);
            }
        }
    }

    if (!class_exists(Folder::class)) {
        final class Folder
        {
            public static function create(string $path): bool
            {
                return is_dir($path) || mkdir($path, 0777, true);
            }
        }
    }
}

namespace Joomla\Input {
    if (!class_exists(Input::class)) {
        class Input
        {
            public object $post;

            public function __construct()
            {
                $this->post = new class {
                    /** @var array<string, mixed> */
                    public array $values = [];

                    public function get(string $name, mixed $default = null, string $filter = 'cmd'): mixed
                    {
                        return $this->values[$name] ?? $default;
                    }
                };
            }
        }
    }
}

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Upload {

use Joomla\Input\Input;
use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Site\Service\Upload\TokenizedDirectoryResolver;
use Vcmb\Component\BreezingformsNG\Site\Service\Upload\UploadPathResolver;

final class UploadPathResolverTest extends TestCase
{
    public function testResolvesDestinationTokensWithoutFilemask(): void
    {
        $resolver = new UploadPathResolver(new Input());

        self::assertSame(
            [
                'directory' => '/uploads/contact',
                'filename' => 'photo.pdf',
                'path' => '/uploads/contact/photo.pdf',
            ],
            $resolver->resolve(
                '/uploads/{form}',
                'photo.pdf',
                ['{form}'],
                ['contact'],
                [],
                '2024-01-02 03:04:05',
                'UTC',
                []
            )
        );
    }

    public function testResolvesFilemaskFromSubmittedFieldAndFilename(): void
    {
        $input = new Input();
        $input->post->values['ff_nm_Category'] = ['A <b>B</b>'];
        $resolver = new UploadPathResolver($input);

        $result = $resolver->resolve(
            '/uploads/{filemask:category}_{filemask:_filename}',
            'photo.pdf',
            [],
            [],
            [(object) ['name' => 'Category']],
            '2024-01-02 03:04:05',
            'UTC',
            []
        );

        self::assertSame('/uploads', $result['directory']);
        self::assertSame('A_B_photo.pdf', $result['filename']);
        self::assertSame('/uploads/A_B_photo.pdf', $result['path']);
    }

    public function testResolvesTokenizedDirectoryAndCreatesIt(): void
    {
        $baseDirectory = sys_get_temp_dir() . '/bfng-tokenized-' . bin2hex(random_bytes(4));
        $input = new Input();
        $input->post->values['ff_nm_Category'] = ['A <b>B</b>'];

        try {
            $result = (new TokenizedDirectoryResolver($input))->resolve(
                $baseDirectory . '/{field:category}/{category:value}|download',
                [(object) ['name' => 'Category']],
                'Attachment',
                [],
                [],
                ['id' => 12, 'username' => 'xavier', 'name' => 'Xavier'],
                '2024-01-02 03:04:05',
                'UTC'
            );

            self::assertSame($baseDirectory . '/category/A_B/download', $result);
            self::assertDirectoryExists($baseDirectory . '/category/A_B');
        } finally {
            rmdir($baseDirectory . '/category/A_B');
            rmdir($baseDirectory . '/category');
            rmdir($baseDirectory);
        }
    }

    public function testResolvesIdentityAndDateTokensInTokenizedDirectory(): void
    {
        $baseDirectory = sys_get_temp_dir() . '/bfng-tokenized-' . bin2hex(random_bytes(4));

        try {
            $result = (new TokenizedDirectoryResolver(new Input()))->resolve(
                $baseDirectory . '/{userid}/{username}/{name}/{field}/{date}_{time}_{datetime}|download',
                [],
                'Attachment',
                [],
                [],
                ['id' => 12, 'username' => 'xavier', 'name' => 'Xavier'],
                '2024-01-02 03:04:05',
                'UTC'
            );

            $relative = '12/xavier_12/Xavier_12/attachment/'
                . '2024_01_02_03_04_05_2024_01_02_03_04_05';
            self::assertSame($baseDirectory . '/' . $relative . '/download', $result);
            self::assertDirectoryExists($baseDirectory . '/' . $relative);
        } finally {
            if (is_dir($baseDirectory)) {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($baseDirectory, \FilesystemIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::CHILD_FIRST
                );
                foreach ($iterator as $item) {
                    if ($item->isDir()) {
                        rmdir($item->getPathname());
                    } else {
                        unlink($item->getPathname());
                    }
                }
                rmdir($baseDirectory);
            }
        }
    }
}
}
