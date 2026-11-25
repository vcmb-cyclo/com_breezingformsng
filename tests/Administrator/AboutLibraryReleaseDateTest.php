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
 * Runs the real library helpers of the About template (extracted from the file, not matched as text).
 */
final class AboutLibraryReleaseDateTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        $template = file_get_contents(
            __DIR__ . '/../../administrator/components/com_breezingformsng/tmpl/about/default.php'
        );
        self::assertIsString($template);

        $functions = [
            'bf_about_read_json_file',
            'bf_about_add_php_library',
            'bf_about_collect_php_libraries_from_installed_json',
            'bf_about_extract_release_date_from_file',
            'bf_about_format_library_date',
        ];

        foreach ($functions as $function) {
            if (function_exists($function)) {
                continue;
            }

            $start = strpos($template, "if (!function_exists('" . $function . "')) {");
            self::assertNotFalse($start, $function . ' is missing from the About template');

            $depth = 0;
            $end = $start;

            for ($length = strlen($template); $end < $length; $end++) {
                if ($template[$end] === '{') {
                    $depth++;
                } elseif ($template[$end] === '}' && --$depth === 0) {
                    break;
                }
            }

            eval(substr($template, $start, $end - $start + 1));
        }
    }

    public function testPhpLibraryReleaseDateComesFromComposerMetadata(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'bf-installed-');
        file_put_contents($path, json_encode(['packages' => [
            ['name' => 'a/with-date', 'pretty_version' => '1.0.0', 'time' => '2026-06-07T11:47:49+00:00'],
            ['name' => 'b/without-date', 'pretty_version' => '2.0.0'],
        ]]));

        $libraries = [];
        bf_about_collect_php_libraries_from_installed_json($libraries, $path);
        unlink($path);

        self::assertSame('2026-06-07T11:47:49+00:00', $libraries['a/with-date']['released']);
        self::assertSame('', $libraries['b/without-date']['released']);
    }

    public function testJavascriptReleaseDateIsReadFromTheBundledHeaderOnly(): void
    {
        $base = __DIR__ . '/../../administrator/components/com_breezingformsng/libraries/jquery/jstree3/jstree.min.js';
        self::assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}$/',
            bf_about_extract_release_date_from_file($base)
        );

        $path = tempnam(sys_get_temp_dir(), 'bf-js-');
        file_put_contents($path, '/*! jQuery v3.7.1 | (c) OpenJS Foundation */ !function(){}();');
        self::assertSame('', bf_about_extract_release_date_from_file($path));
        unlink($path);

        self::assertSame('', bf_about_extract_release_date_from_file('/nonexistent/file.js'));
    }

    public function testEmptyOrMissingDateIsNotFormatted(): void
    {
        self::assertSame('', bf_about_format_library_date(''));
    }

    public function testLibraryTablesAreSortableOnEveryColumn(): void
    {
        $template = file_get_contents(
            __DIR__ . '/../../administrator/components/com_breezingformsng/tmpl/about/default.php'
        );
        self::assertIsString($template);

        self::assertSame(2, substr_count($template, 'data-bf-sortable-table'));
        self::assertSame(9, substr_count($template, '$sortHeader(\'COM_BREEZINGFORMSNG_'));
        self::assertSame(2, substr_count($template, 'data-bf-sort-value="<?php echo htmlspecialchars(substr('));
    }
}
