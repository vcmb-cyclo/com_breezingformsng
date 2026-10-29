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

namespace Vcmb\Component\BreezingformsNG\Site\Service\Rendering;

defined('_JEXEC') or die('Direct Access to this location is not allowed.');

use HTML_facileFormsProcessor;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

/**
 * Orchestrates the complete fixed-position Classic form rendering branch.
 *
 * The individual markup builders remain separate because they own the
 * historical output for a field type. This service owns the shared Classic
 * concerns around them: value replacement, wrapper geometry, field dispatch,
 * and Query List composition.
 */
final class ClassicFormRenderer
{
    private ?ClassicStaticTextBuilder $staticTextBuilder = null;
    private ?ClassicHiddenInputBuilder $hiddenInputBuilder = null;
    private ?ClassicTextInputBuilder $textInputBuilder = null;
    private ?ClassicTextareaBuilder $textareaBuilder = null;
    private ?ClassicChoiceBuilder $choiceBuilder = null;
    private ?ClassicSelectBuilder $selectBuilder = null;
    private ?ClassicRegularButtonBuilder $regularButtonBuilder = null;
    private ?ClassicGraphicButtonBuilder $graphicButtonBuilder = null;
    private ?ClassicFileUploadBuilder $fileUploadBuilder = null;
    private ?ClassicCaptchaBuilder $captchaBuilder = null;
    private ?ClassicQueryListSettingsBuilder $queryListSettingsBuilder = null;
    private ?ClassicQueryListHeaderBuilder $queryListHeaderBuilder = null;
    private ?ClassicQueryListCellBuilder $queryListCellBuilder = null;
    private ?ClassicQueryListRowBuilder $queryListRowBuilder = null;
    private ?ClassicQueryListFooterBuilder $queryListFooterBuilder = null;
    private ?ClassicQueryListMarkupBuilder $queryListMarkupBuilder = null;
    private ?CaptchaSupportBuilder $captchaSupport = null;

    public function __construct(private readonly HTML_facileFormsProcessor $processor)
    {
    }

    /**
     * Render all fixed-position Classic rows.
     *
     * A null result means that the processor requested the same early abort
     * that the historical loop handled through RenderingEngine::view().
     */
    public function render(string $siteRoot): ?string
    {
        $html = '';

        for ($i = 0; $i < $this->processor->rowcount; $i++) {
            $row = &$this->processor->rows[$i];
            $markup = $this->renderRow($row, $i, $siteRoot);
            unset($row);

            if ($markup === null) {
                return null;
            }

            $html .= $markup;
        }

        return $html;
    }

    /**
     * @param \stdClass $row
     */
    private function renderRow(object $row, int $index, string $siteRoot): ?string
    {
        $data1 = '';
        $data2 = '';
        $data3 = '';

        if (!is_numeric($row->width)) {
            $row->width = 0;
        }
        if (!is_numeric($row->height)) {
            $row->height = 0;
        }

        if ($row->type != 'Query List') {
            $data1 = $this->processor->replaceCode($row->data1, "data1 of $row->name", 'e', $row->id, 0);
            if ($this->processor->bury()) {
                return null;
            }
            $data2 = $this->processor->replaceCode($row->data2, "data2 of $row->name", 'e', $row->id, 0);
            if ($this->processor->bury()) {
                return null;
            }
            $data3 = $this->processor->replaceCode($row->data3, "data3 of $row->name", 'e', $row->id, 0);
            if ($this->processor->bury()) {
                return null;
            }
        }

        $attributes = 'position:absolute;z-index:' . $index . ';';
        if ($row->posx >= 0) {
            $attributes .= 'left:' . $row->posx;
        } else {
            $attributes .= 'right:' . (-$row->posx);
        }
        $attributes .= $row->posxmode ? '%;' : 'px;';
        if ($row->posy >= 0) {
            $attributes .= 'top:' . $row->posy;
        } else {
            $attributes .= 'bottom:' . (-$row->posy);
        }
        $attributes .= $row->posymode ? '%;' : 'px;';

        [$class1, $class2] = $this->classAttributes($row);

        switch ($row->type) {
            case 'Static Text/HTML':
            case 'Rectangle':
            case 'Image':
                if ($row->height > 0) {
                    $attributes .= 'height:' . $row->height . ($row->heightmode ? '%;' : 'px;');
                }
                // Fall through: Classic visual elements also receive width.
            case 'Query List':
                if ($row->width > 0) {
                    $attributes .= 'width:' . $row->width . ($row->widthmode ? '%;' : 'px;');
                }
                break;
            default:
                break;
        }

        if ($row->page != $this->processor->page) {
            $attributes .= 'visibility:hidden;';
        }

        return match ($row->type) {
            'Static Text/HTML' => $this->staticTextBuilder()->build(
                (int) $row->id,
                $attributes,
                $class1,
                $data1,
                indentc(1),
                nl()
            ),
            'Rectangle' => $this->staticTextBuilder()->buildRectangle(
                (int) $row->id,
                $attributes,
                $class1,
                $data1,
                $data2,
                indentc(1),
                nl()
            ),
            'Image' => $this->staticTextBuilder()->buildImage(
                (int) $row->id,
                $attributes,
                $class1,
                $class2,
                $data1,
                $data2,
                (int) $row->width,
                (int) $row->height,
                indentc(1),
                nlc() ?? ''
            ),
            'Tooltip' => $this->staticTextBuilder()->buildTooltip(
                (int) $row->id,
                $attributes,
                $class1,
                $class2,
                (string) $row->title,
                $data2,
                $data1,
                (int) $row->flag1,
                $siteRoot,
                indentc(1),
                nlc() ?? ''
            ),
            'Hidden Input' => $this->hiddenInputBuilder()->build(
                (int) $row->id,
                (string) $row->name,
                $data1,
                indentc(1),
                nl()
            ),
            'Checkbox' => $this->choiceBuilder()->build(
                'checkbox',
                (int) $row->id,
                (string) $row->name,
                $data1,
                $data2,
                $attributes,
                $class1,
                $class2,
                (bool) $row->flag1,
                (bool) $row->flag2,
                $this->processor->script2clause($row),
                indentc(1),
                nlc() ?? ''
            ),
            'Radio Button' => $this->choiceBuilder()->build(
                'radio',
                (int) $row->id,
                (string) $row->name,
                $data1,
                $data2,
                $attributes,
                $class1,
                $class2,
                (bool) $row->flag1,
                (bool) $row->flag2,
                $this->processor->script2clause($row),
                indentc(1),
                nlc() ?? ''
            ),
            'Regular Button' => $this->regularButtonBuilder()->build(
                (int) $row->id,
                (string) $row->name,
                $data2,
                $attributes,
                $class1,
                $class2,
                (bool) $row->flag2,
                $this->processor->script2clause($row),
                indentc(1),
                nlc() ?? ''
            ),
            'Graphic Button' => $this->graphicButtonBuilder()->build(
                (int) $row->id,
                (string) $row->name,
                $data1,
                $data2,
                $attributes,
                $class1,
                $class2,
                (int) $row->width,
                (int) $row->height,
                (bool) $row->flag2,
                $this->processor->script2clause($row),
                (int) $row->flag1,
                indentc(1),
                nlc() ?? ''
            ),
            'Icon' => $this->staticTextBuilder()->buildIcon(
                (int) $row->id,
                $attributes,
                $class1,
                $class2,
                $data1,
                $data2,
                $data3,
                $this->processor->script2clause($row),
                (int) $row->flag1,
                (bool) $row->flag2,
                (int) $row->width,
                (int) $row->height,
                indentc(1),
                nlc() ?? ''
            ),
            'Select List' => $this->selectBuilder()->build(
                (int) $row->id,
                (string) $row->name,
                $attributes,
                $class1,
                $class2,
                (string) $data1,
                $data2,
                (int) $row->width,
                (int) $row->height,
                (bool) $row->flag1,
                (bool) $row->flag2,
                $this->processor->script2clause($row),
                indentc(1),
                nlc() ?? ''
            ),
            'Text' => $this->textInputBuilder()->build(
                (int) $row->id,
                (string) $row->name,
                $data1,
                $attributes,
                $class1,
                $class2,
                (int) $row->width,
                (int) $row->widthmode,
                (int) $row->height,
                (bool) $row->flag1,
                (int) $row->flag2,
                $this->processor->script2clause($row),
                indentc(1),
                nlc() ?? ''
            ),
            'Textarea' => $this->textareaBuilder()->build(
                (int) $row->id,
                (string) $row->name,
                $data1,
                $attributes,
                $class1,
                $class2,
                (int) $row->width,
                (int) $row->widthmode,
                (int) $row->height,
                (int) $row->heightmode,
                stristr($this->processor->browser ?? '', 'mozilla') !== false,
                (int) $row->flag2,
                $this->processor->script2clause($row),
                indentc(1),
                nlc() ?? ''
            ),
            'File Upload' => $this->fileUploadBuilder()->build(
                (int) $row->id,
                (string) $row->name,
                $attributes,
                $class1,
                $class2,
                (int) $row->width,
                (int) $row->height,
                (bool) $row->flag2,
                $row->data2 !== '' ? $data2 : '',
                $this->processor->script2clause($row),
                indentc(1),
                nlc() ?? ''
            ),
            'Captcha' => $this->captchaBuilder()->build(
                (int) $row->id,
                $attributes,
                $class1,
                $this->captchaSupport()->endpoints(
                    Uri::root(true),
                    $this->processor->app->isClient('administrator'),
                    (int) $this->processor->form
                )['captcha'],
                Uri::root(),
                (int) $row->width,
                (int) $row->height,
                indentc(1),
                nlc() ?? '',
                nl()
            ),
            'Query List' => $this->renderQueryList($row, $attributes, $class1, $class2),
            default => '',
        };
    }

    /**
     * @param \stdClass $row
     * @return array{0: string, 1: string}
     */
    private function classAttributes(object $row): array
    {
        $class1 = $row->class1 != ''
            ? ' class="' . $this->processor->getClassName($row->class1) . '"'
            : '';

        if ($row->type == 'Select List') {
            $class2 = $row->class2 != ''
                ? ' class="' . $this->processor->getClassName($row->class2) . ' chzn-done"'
                : ' class="chzn-done"';
        } else {
            $class2 = $row->class2 != ''
                ? ' class="' . $this->processor->getClassName($row->class2) . '"'
                : '';
        }

        return [$class1, $class2];
    }

    /**
     * @param \stdClass $row
     */
    private function renderQueryList(object $row, string $wrapperStyle, string $class1, string $class2): ?string
    {
        $settings = $this->queryListSettingsBuilder()->build(
            (string) $row->data1,
            (int) $row->width,
            fn (string $class): string => $this->processor->getClassName($class)
        );
        $html = $this->queryListMarkupBuilder()->open(
            (int) $row->id,
            $wrapperStyle,
            $class1,
            $settings['tableAttributes'],
            $class2,
            indentc(1),
            indentc(2),
            nlc() ?? '',
            nl()
        );

        $columns = &$this->processor->queryCols['ff_' . $row->id];
        $columnCount = count($columns);
        if ($row->flag1) {
            $html .= $this->queryListHeaderBuilder()->build(
                $columns,
                (int) $row->id,
                (int) $row->flag2,
                $settings['headerClass'],
                fn (string $class): string => $this->processor->getClassName($class),
                fn (object $column): string => $this->processor->replaceCode(
                    $column->title ?? '',
                    Text::_('COM_BREEZINGFORMSNG_PROCESS_QTITLEOF') . " $row->name::$column->name",
                    'e',
                    $row->id,
                    2
                ),
                indentc(3),
                indentc(4),
                nlc() ?? ''
            );
        }

        $rows = &$this->processor->queryRows['ff_' . $row->id];
        $rowCount = count($rows);
        $visibleRowCount = $row->height > 0 && $rowCount > $row->height ? $row->height : $rowCount;
        $rowParity = 1;
        for ($q = 0; $q < $visibleRowCount; $q++) {
            $queryRow = &$rows[$q];
            $html .= $this->queryListRowBuilder()->build(
                $columns,
                $queryRow,
                (int) $row->id,
                $q,
                (string) $row->name,
                $rowParity === 1 ? $settings['oddClass'] : $settings['evenClass'],
                (int) $row->flag2,
                $rowParity === 1,
                fn (string $class): string => $this->processor->getClassName($class),
                fn (): bool => $this->processor->dying ?? false,
                indentc(3),
                indentc(4),
                nlc() ?? '',
                nl()
            );
            $rowParity = 3 - $rowParity;
            unset($queryRow);
            if ($this->processor->dying) {
                break;
            }
        }

        if ($this->processor->bury()) {
            unset($rows, $columns);
            return null;
        }

        if ($row->height > 0 && $settings['pageNavigation'] > 0) {
            $span = 0;
            for ($column = 0; $column < $columnCount; $column++) {
                if ($columns[$column]->thspan > 0) {
                    $span++;
                }
            }
            $pages = (int) (($rowCount + $row->height - 1) / $row->height);
            $html .= $this->queryListFooterBuilder()->build(
                (int) $row->id,
                $span,
                $pages,
                (int) $settings['pageNavigation'],
                $settings['footerClass'],
                $settings['footerCellClass'],
                Text::_('COM_BREEZINGFORMSNG_PROCESS_PAGESTART'),
                Text::_('COM_BREEZINGFORMSNG_PROCESS_PAGEPREV'),
                Text::_('COM_BREEZINGFORMSNG_PROCESS_PAGENEXT'),
                Text::_('COM_BREEZINGFORMSNG_PROCESS_PAGEEND'),
                indentc(3),
                indentc(4),
                indentc(5),
                nlc() ?? '',
                nl()
            );
        }

        $html .= $this->queryListMarkupBuilder()->close(
            indentc(2),
            indentc(1),
            nlc() ?? '',
            nl()
        );
        unset($rows, $columns);

        return $html;
    }

    private function staticTextBuilder(): ClassicStaticTextBuilder
    {
        return $this->staticTextBuilder ??= new ClassicStaticTextBuilder();
    }

    private function hiddenInputBuilder(): ClassicHiddenInputBuilder
    {
        return $this->hiddenInputBuilder ??= new ClassicHiddenInputBuilder();
    }

    private function textInputBuilder(): ClassicTextInputBuilder
    {
        return $this->textInputBuilder ??= new ClassicTextInputBuilder();
    }

    private function textareaBuilder(): ClassicTextareaBuilder
    {
        return $this->textareaBuilder ??= new ClassicTextareaBuilder();
    }

    private function choiceBuilder(): ClassicChoiceBuilder
    {
        return $this->choiceBuilder ??= new ClassicChoiceBuilder();
    }

    private function selectBuilder(): ClassicSelectBuilder
    {
        return $this->selectBuilder ??= new ClassicSelectBuilder();
    }

    private function regularButtonBuilder(): ClassicRegularButtonBuilder
    {
        return $this->regularButtonBuilder ??= new ClassicRegularButtonBuilder();
    }

    private function graphicButtonBuilder(): ClassicGraphicButtonBuilder
    {
        return $this->graphicButtonBuilder ??= new ClassicGraphicButtonBuilder();
    }

    private function fileUploadBuilder(): ClassicFileUploadBuilder
    {
        return $this->fileUploadBuilder ??= new ClassicFileUploadBuilder();
    }

    private function captchaBuilder(): ClassicCaptchaBuilder
    {
        return $this->captchaBuilder ??= new ClassicCaptchaBuilder();
    }

    private function queryListSettingsBuilder(): ClassicQueryListSettingsBuilder
    {
        return $this->queryListSettingsBuilder ??= new ClassicQueryListSettingsBuilder();
    }

    private function queryListHeaderBuilder(): ClassicQueryListHeaderBuilder
    {
        return $this->queryListHeaderBuilder ??= new ClassicQueryListHeaderBuilder();
    }

    private function queryListCellBuilder(): ClassicQueryListCellBuilder
    {
        return $this->queryListCellBuilder ??= new ClassicQueryListCellBuilder();
    }

    private function queryListRowBuilder(): ClassicQueryListRowBuilder
    {
        return $this->queryListRowBuilder ??= new ClassicQueryListRowBuilder($this->queryListCellBuilder());
    }

    private function queryListFooterBuilder(): ClassicQueryListFooterBuilder
    {
        return $this->queryListFooterBuilder ??= new ClassicQueryListFooterBuilder();
    }

    private function queryListMarkupBuilder(): ClassicQueryListMarkupBuilder
    {
        return $this->queryListMarkupBuilder ??= new ClassicQueryListMarkupBuilder();
    }

    private function captchaSupport(): CaptchaSupportBuilder
    {
        return $this->captchaSupport ??= new CaptchaSupportBuilder();
    }
}
