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
use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\QuickMode\QuickModeSubmittedValueHydrator;

if (!defined('JPATH_SITE')) {
    define('JPATH_SITE', __DIR__ . '/../../../../');
}

if (!defined('JPATH_ADMINISTRATOR')) {
    define('JPATH_ADMINISTRATOR', __DIR__ . '/../../../../administrator');
}

if (!class_exists(HTML_facileFormsProcessor::class)) {
    require_once __DIR__ . '/../../../../components/com_breezingformsng/src/Support/processor_facade.php';
}

final class QuickModeSubmittedValueHydratorProcessorDouble extends HTML_facileFormsProcessor
{
    public function __construct()
    {
    }

    public function replaceCode($code, $name, $type, $id, $pane)
    {
        return 'processed:' . $name . ':' . $code;
    }
}

final class QuickModeSubmittedValueHydratorTest extends TestCase
{
    public function testHydratesScalarValuesAndTranslations(): void
    {
        $processor = $this->processor([
            (object) [
                'name' => 'title',
                'data1' => 'submitted title',
                'data2' => '',
                'flag1' => 0,
            ],
        ]);

        $result = QuickModeSubmittedValueHydrator::hydrate(
            $processor,
            [
                'bfName' => 'title',
                'bfType' => 'bfTextfield',
                'dbId' => 12,
                'value' => 'default',
                'value_translation-fr-FR' => 'translated {title}',
            ],
            '-fr-FR'
        );

        self::assertSame('processed:data1 of title:submitted title', $result['value']);
        self::assertSame('processed:data1 of title:translated {title}', $result['value_translation-fr-FR']);
    }

    public function testHydratesSelectAndChoiceGroupFromDataTwo(): void
    {
        $processor = $this->processor([
            (object) [
                'name' => 'country',
                'data1' => 'ignored',
                'data2' => '1;France;FR',
                'flag1' => 0,
            ],
        ]);

        $select = QuickModeSubmittedValueHydrator::hydrate(
            $processor,
            ['bfName' => 'country', 'bfType' => 'bfSelect', 'dbId' => 13, 'list' => 'default'],
            ''
        );
        $group = QuickModeSubmittedValueHydrator::hydrate(
            $processor,
            ['bfName' => 'country', 'bfType' => 'bfRadioGroup', 'dbId' => 14, 'group' => 'default'],
            ''
        );

        self::assertSame('processed:data2 of country:1;France;FR', $select['list']);
        self::assertSame('processed:data2 of country:1;France;FR', $group['group']);
    }

    public function testSynchronizesCheckboxStateAndLeavesUnknownRowsUntouched(): void
    {
        $processor = $this->processor([
            (object) [
                'name' => 'terms',
                'data1' => 'yes',
                'data2' => '',
                'flag1' => 1,
            ],
        ]);

        $checked = QuickModeSubmittedValueHydrator::hydrate(
            $processor,
            ['bfName' => 'terms', 'bfType' => 'bfCheckbox', 'dbId' => 15, 'value' => 'no', 'checked' => false],
            ''
        );
        $unknown = QuickModeSubmittedValueHydrator::hydrate(
            $processor,
            ['bfName' => 'missing', 'bfType' => 'bfTextfield', 'dbId' => 16, 'value' => 'default'],
            ''
        );

        self::assertTrue($checked['checked']);
        self::assertSame('processed:data1 of terms:yes', $checked['value']);
        self::assertSame('default', $unknown['value']);
    }

    public function testAllActiveRenderersDelegateRecordHydrationToTheSharedService(): void
    {
        foreach (['ClassicRenderer', 'BootstrapRenderer', 'OnePageRenderer'] as $renderer) {
            $source = file_get_contents(
                __DIR__ . '/../../../../components/com_breezingformsng/src/Service/Rendering/QuickMode/' . $renderer . '.php'
            );

            self::assertIsString($source);
            self::assertStringContainsString(
                '$mdata = QuickModeSubmittedValueHydrator::hydrate(',
                $source
            );
            self::assertStringNotContainsString(
                'for ($i = 0; $i < $this->p->rowcount; $i++)',
                $source
            );
        }
    }

    /**
     * @param list<object> $rows
     */
    private function processor(array $rows): QuickModeSubmittedValueHydratorProcessorDouble
    {
        $processor = new QuickModeSubmittedValueHydratorProcessorDouble();
        $processor->rows = $rows;
        $processor->rowcount = count($rows);

        return $processor;
    }
}
