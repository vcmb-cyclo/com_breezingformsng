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

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Rendering;

use HTML_facileFormsProcessor;
use Joomla\CMS\Application\CMSApplication;
use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\ClassicFormRenderer;

if (!defined('JPATH_SITE')) {
    define('JPATH_SITE', __DIR__ . '/../../../../');
}

if (!defined('JPATH_ADMINISTRATOR')) {
    define('JPATH_ADMINISTRATOR', __DIR__ . '/../../../../administrator');
}

if (!class_exists(HTML_facileFormsProcessor::class)) {
    require_once __DIR__ . '/../../../../components/com_breezingformsng/src/Support/processor_facade.php';
}

require_once __DIR__ . '/QuickMode/joomla-uri-stub.php';
require_once __DIR__ . '/QuickMode/joomla-text-stub.php';
require_once __DIR__ . '/QuickMode/joomla-cmsapplication-stub.php';

if (!function_exists('Vcmb\Component\BreezingformsNG\Site\Service\Rendering\nl')) {
    eval('namespace Vcmb\\Component\\BreezingformsNG\\Site\\Service\\Rendering; function nl(): string { return "\\n"; }');
}

if (!function_exists('Vcmb\Component\BreezingformsNG\Site\Service\Rendering\nlc')) {
    eval('namespace Vcmb\\Component\\BreezingformsNG\\Site\\Service\\Rendering; function nlc(): string { return "\\n"; }');
}

if (!function_exists('Vcmb\Component\BreezingformsNG\Site\Service\Rendering\indentc')) {
    eval('namespace Vcmb\\Component\\BreezingformsNG\\Site\\Service\\Rendering; function indentc(int $level): string { return str_repeat("\\t", $level); }');
}

final class ClassicFormRendererProcessorDouble extends HTML_facileFormsProcessor
{
    public bool $buryRequested = false;

    public function __construct()
    {
    }

    public function replaceCode($code, $name, $type, $id, $pane)
    {
        return (string) $code;
    }

    public function script2clause(&$row)
    {
        return '';
    }

    public function getClassName($classdef)
    {
        return 'resolved-' . $classdef;
    }

    public function bury()
    {
        return $this->buryRequested;
    }
}

final class ClassicFormRendererTest extends TestCase
{
    public function testRendersFieldFamiliesThroughOneClassicOrchestrator(): void
    {
        $processor = $this->processor();
        $processor->rows = [
            $this->row('Static Text/HTML', 1, ['data1' => '<p>Intro</p>']),
            $this->row('Hidden Input', 2, ['name' => 'token', 'data1' => 'abc123']),
            $this->row('Text', 3, ['name' => 'title', 'data1' => 'Hello']),
            $this->row('Checkbox', 4, ['name' => 'terms', 'data1' => 'yes', 'data2' => 'Terms']),
            $this->row('Select List', 5, ['name' => 'country', 'data1' => '1', 'data2' => "1;France;FR\n0;Germany;DE"]),
            $this->row('Captcha', 6),
        ];
        $processor->rowcount = count($processor->rows);

        $html = (new ClassicFormRenderer($processor))->render('https://example.test');

        self::assertIsString($html);
        self::assertStringContainsString('id="ff_div1"', $html);
        self::assertStringContainsString('>Intro</p></div>', $html);
        self::assertStringContainsString('name="ff_nm_token[]" value="abc123"', $html);
        self::assertStringContainsString('name="ff_nm_title[]" value="Hello"', $html);
        self::assertStringContainsString('type="checkbox"', $html);
        self::assertStringContainsString('<option value="FR" selected="selected">France</option>', $html);
        self::assertStringContainsString('id="ff_capimgValue" class="ff_capimg"', $html);
    }

    public function testRendersQueryListRowsAndKeepsPreparedProcessorState(): void
    {
        $processor = $this->processor();
        $processor->rows = [
            $this->row('Query List', 20, [
                'name' => 'results',
                'data1' => implode("\n", ['', '', '', '', '', '', '', '', '1']),
                'flag1' => 1,
                'height' => 2,
            ]),
        ];
        $processor->rowcount = 1;
        $processor->queryCols = [
            'ff_20' => [
                (object) [
                    'thspan' => 1,
                    'thalign' => 0,
                    'thvalign' => 0,
                    'thwrap' => 0,
                    'class1' => '',
                    'title' => 'Name',
                    'name' => 'name',
                    'width' => 0,
                    'widthmd' => false,
                    'align' => 0,
                    'valign' => 0,
                    'wrap' => 0,
                    'class2' => '',
                    'class3' => '',
                ],
            ],
        ];
        $processor->queryRows = ['ff_20' => [['Alice']]];

        $html = (new ClassicFormRenderer($processor))->render('https://example.test');

        self::assertIsString($html);
        self::assertStringContainsString('<table', $html);
        self::assertStringContainsString('<th>Name</th>', $html);
        self::assertStringContainsString('>Alice</td>', $html);
        self::assertStringContainsString('id="ff_elem20"', $html);
    }

    public function testReturnsNullAtTheHistoricalBuryPoint(): void
    {
        $processor = $this->processor();
        $processor->rows = [$this->row('Text', 30)];
        $processor->rowcount = 1;
        $processor->buryRequested = true;

        self::assertNull((new ClassicFormRenderer($processor))->render('https://example.test'));
    }

    private function processor(): ClassicFormRendererProcessorDouble
    {
        $processor = new ClassicFormRendererProcessorDouble();
        $processor->page = 1;
        $processor->browser = 'Mozilla';
        $processor->form = 7;
        $processor->dying = false;
        $processor->app = new CMSApplication();

        return $processor;
    }

    /** @param array<string, mixed> $overrides */
    private function row(string $type, int $id, array $overrides = []): object
    {
        return (object) array_replace([
            'id' => $id,
            'type' => $type,
            'name' => 'field' . $id,
            'data1' => 'value',
            'data2' => 'label',
            'data3' => '',
            'title' => 'Title',
            'width' => 100,
            'widthmode' => 0,
            'height' => 20,
            'heightmode' => 0,
            'posx' => 2,
            'posxmode' => 0,
            'posy' => 3,
            'posymode' => 0,
            'page' => 1,
            'class1' => 'wrapper',
            'class2' => 'control',
            'flag1' => 0,
            'flag2' => 0,
        ], $overrides);
    }
}
