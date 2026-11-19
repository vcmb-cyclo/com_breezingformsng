<?php

/**
 * BreezingForms NG - A Joomla Forms Application
 *
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL - EVH
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 *
 * Second cut of the shared field-type Strategy layer: these 8 field types
 * are byte-for-byte identical (modulo a trailing blank line, confirmed by
 * diffing the two production files directly) between BootstrapRenderer and
 * OnePageRenderer - both build on the same $this->bsClass()/Bootstrap-5
 * markup convention, unlike Classic (plain HTML) or Mobile (its own
 * simplified markup). Not shared with those two.
 *
 * Each method takes $label and the preamble-computed onclick/onblur/etc and
 * tabIndex/readonly strings as parameters rather than reading them off
 * $this, because they
 * are process()'s local variables, not renderer instance state - a trait
 * has access to $this and its properties, not to the including method's
 * locals.
 */

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Site\Service\Rendering\QuickMode;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Vcmb\Component\BreezingformsNG\Site\Service\Runtime\RuntimeAssetLoader;
use Joomla\CMS\Uri\Uri;

\defined('_JEXEC') or die;

/**
 * @phpstan-type QuickModeTreeNode array{
 *     properties?: array<string, mixed>,
 *     attributes?: array{id: string},
 *     children?: list<array<string, mixed>>
 * }
 * @phpstan-type QuickModeProcessNode array{
 *     properties: array<string, mixed>,
 *     attributes?: array{id: string},
 *     children?: list<array<string, mixed>>
 * }
 * @phpstan-type QuickModeRootNode array{
 *     properties: array<string, mixed>,
 *     attributes?: array{id: string},
 *     children: list<array<string, mixed>>
 * }
 * @phpstan-type BootstrapStyleFieldData array<string, mixed>
 */
trait BootstrapStyleFieldTrait
{
    private ?QuickModeBootstrapChoiceGroupBuilder $quickModeBootstrapChoiceGroupBuilderService = null;

    /**
     * @param array<string, mixed> $mdata
     * @return BootstrapStyleFieldData
     */
    private function normalizeBootstrapStyleFieldData(array $mdata): array
    {
        $normalized = $mdata;
        $normalized['bfName'] = $this->bootstrapStyleString($mdata['bfName'] ?? null, 'bfName');
        $normalized['dbId'] = $this->bootstrapStyleInteger($mdata['dbId'] ?? null, 'dbId');
        $normalized['value'] = $this->bootstrapStyleString($mdata['value'] ?? null, 'value');
        $normalized['format'] = $this->bootstrapStyleString($mdata['format'] ?? null, 'format');
        $normalized['size'] = $this->bootstrapStyleString($mdata['size'] ?? null, 'size');
        $normalized['icon'] = $this->bootstrapStyleString($mdata['icon'] ?? null, 'icon');
        $normalized['firstDay'] = $this->bootstrapStyleString($mdata['firstDay'] ?? null, 'firstDay');
        $normalized['minYear'] = $this->bootstrapStyleString($mdata['minYear'] ?? null, 'minYear');
        $normalized['maxYear'] = $this->bootstrapStyleString($mdata['maxYear'] ?? null, 'maxYear');
        $normalized['emptyMessage'] = $this->bootstrapStyleString(
            $mdata['emptyMessage'] ?? null,
            'emptyMessage'
        );
        $normalized['connectWith'] = $this->bootstrapStyleStringOrList(
            $mdata['connectWith'] ?? null,
            'connectWith'
        );
        $normalized['connectType'] = $this->bootstrapStyleString($mdata['connectType'] ?? null, 'connectType');
        $normalized['hideIfEmpty'] = $this->bootstrapStyleBoolean($mdata['hideIfEmpty'] ?? null, 'hideIfEmpty');
        $normalized['fieldCalc'] = $this->bootstrapStyleString($mdata['fieldCalc'] ?? null, 'fieldCalc');
        $normalized['mailbackAsSender'] = $this->bootstrapStyleBoolean(
            $mdata['mailbackAsSender'] ?? null,
            'mailbackAsSender'
        );
        $normalized['mailbackAccept'] = $this->bootstrapStyleBoolean(
            $mdata['mailbackAccept'] ?? null,
            'mailbackAccept'
        );
        $normalized['mailbackConnectWith'] = $this->bootstrapStyleString(
            $mdata['mailbackConnectWith'] ?? null,
            'mailbackConnectWith'
        );
        $normalized['list'] = $this->bootstrapStyleString($mdata['list'] ?? null, 'list');
        $normalized['width'] = $this->bootstrapStyleString($mdata['width'] ?? null, 'width');
        $normalized['height'] = $this->bootstrapStyleString($mdata['height'] ?? null, 'height');
        $normalized['multiple'] = $this->bootstrapStyleBoolean($mdata['multiple'] ?? null, 'multiple');
        $normalized['src'] = $this->bootstrapStyleString($mdata['src'] ?? null, 'src');
        $normalized['actionClick'] = $this->bootstrapStyleBoolean($mdata['actionClick'] ?? null, 'actionClick');
        $normalized['actionFunctionName'] = $this->bootstrapStyleString(
            $mdata['actionFunctionName'] ?? null,
            'actionFunctionName'
        );
        $normalized['image'] = $this->bootstrapStyleString($mdata['image'] ?? null, 'image');
        $normalized['group'] = $this->bootstrapStyleString($mdata['group'] ?? null, 'group');
        $normalized['wrap'] = $this->bootstrapStyleBoolean($mdata['wrap'] ?? null, 'wrap');

        foreach (
            [
                'type', 'bfType', 'name', 'pageIntro', 'label', 'hint', 'labelPosition', 'title', 'description',
                'connectType', 'emptyMessage', 'toggleFields', 'theme', 'placeholder', 'mailbackConnectWith',
                'actionFunctionName', 'fieldCalc', 'format', 'firstDay', 'minYear', 'maxYear', 'pagingPrevLabel',
                'pagingNextLabel', 'submitLabel', 'cancelLabel',
            ] as $key
        ) {
            $normalized[$key] = $this->bootstrapStyleString($mdata[$key] ?? null, $key);
        }
        foreach (['dbId', 'pageNumber', 'tabIndex', 'maxlength'] as $key) {
            $normalized[$key] = $this->bootstrapStyleInteger($mdata[$key] ?? null, $key);
        }
        foreach (
            [
                'actionBlur', 'actionChange', 'actionFocus', 'actionSelect', 'hideIfEmpty', 'hideLabel', 'off',
                'required', 'readonly', 'wrap', 'is_html', 'flashUploader', 'html5', 'invisibleCaptcha',
                'showMaxlengthCounter', 'attachToAdminMail', 'attachToUserMail', 'fadeIn', 'useErrorAlerts',
                'useDefaultErrors', 'useBalloonErrors', 'rollover', 'lastPageThankYou', 'pagingInclude',
                'submitInclude', 'cancelInclude', 'joomlaHint',
            ] as $key
        ) {
            $normalized[$key] = $this->bootstrapStyleBoolean($mdata[$key] ?? null, $key);
        }

        return $this->validatedBootstrapStyleFieldData($normalized);
    }

    /**
     * @return BootstrapStyleFieldData
     */
    private function validatedBootstrapStyleFieldData(mixed $mdata): array
    {
        $this->assertBootstrapStyleFieldData($mdata);

        return $mdata;
    }

    /**
     * @phpstan-assert QuickModeTreeNode $dataObject
     */
    private function assertQuickModeTreeNode(mixed $dataObject): void
    {
        if (!is_array($dataObject)) {
            throw new \UnexpectedValueException('QuickMode tree node has an invalid shape.');
        }

        if (array_key_exists('properties', $dataObject) && !is_array($dataObject['properties'])) {
            throw new \UnexpectedValueException('QuickMode tree node properties have an invalid shape.');
        }

        if (
            array_key_exists('attributes', $dataObject)
            && (!is_array($dataObject['attributes'])
                || !isset($dataObject['attributes']['id'])
                || !is_string($dataObject['attributes']['id']))
        ) {
            throw new \UnexpectedValueException('QuickMode tree node attributes have an invalid shape.');
        }

        if (
            array_key_exists('children', $dataObject)
            && (!is_array($dataObject['children']) || !array_is_list($dataObject['children']))
        ) {
            throw new \UnexpectedValueException('QuickMode tree children must be a list.');
        }
    }

    /**
     * @phpstan-assert QuickModeProcessNode $dataObject
     */
    private function assertQuickModeProcessNode(mixed $dataObject): void
    {
        $this->assertQuickModeTreeNode($dataObject);

        if (!isset($dataObject['properties']) || !is_array($dataObject['properties'])) {
            throw new \UnexpectedValueException('QuickMode process node properties have an invalid shape.');
        }
    }

    /**
     * @phpstan-assert QuickModeRootNode $dataObject
     */
    private function assertQuickModeRootNode(mixed $dataObject): void
    {
        $this->assertQuickModeProcessNode($dataObject);

        if (!is_array($dataObject['children'] ?? null) || !array_is_list($dataObject['children'])) {
            throw new \UnexpectedValueException('QuickMode root children must be a list.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function quickModeNodeProperties(mixed $dataObject): array
    {
        $this->assertQuickModeProcessNode($dataObject);

        return $dataObject['properties'];
    }

    /**
     * @param QuickModeRootNode $dataObject
     * @return list<array<string, mixed>>
     */
    private function quickModeRootChildren(array $dataObject): array
    {
        $this->assertQuickModeTreeNode($dataObject);

        return $dataObject['children'];
    }

    private function bootstrapStyleString(mixed $value, string $key): string
    {
        if ($value === null) {
            return '';
        }

        if (!is_scalar($value)) {
            throw new \UnexpectedValueException(sprintf('QuickMode field metadata "%s" must be scalar.', $key));
        }

        return (string) $value;
    }

    private function bootstrapStyleInteger(mixed $value, string $key): int
    {
        if ($value === null) {
            return 0;
        }

        if (!is_scalar($value)) {
            throw new \UnexpectedValueException(sprintf('QuickMode field metadata "%s" must be scalar.', $key));
        }

        return (int) $value;
    }

    /**
     * @return string|list<string>
     */
    private function bootstrapStyleStringOrList(mixed $value, string $key): string|array
    {
        if ($value === null || is_scalar($value)) {
            return $this->bootstrapStyleString($value, $key);
        }

        if (!is_array($value) || !array_is_list($value)) {
            throw new \UnexpectedValueException(
                sprintf('QuickMode field metadata "%s" must be a string or a list.', $key)
            );
        }

        $normalized = [];
        foreach ($value as $index => $item) {
            $normalized[] = $this->bootstrapStyleString($item, $key . '[' . $index . ']');
        }

        return $normalized;
    }

    private function bootstrapStyleBoolean(mixed $value, string $key): bool
    {
        if ($value === null) {
            return false;
        }

        if (!is_scalar($value)) {
            throw new \UnexpectedValueException(sprintf('QuickMode field metadata "%s" must be scalar.', $key));
        }

        return (bool) $value;
    }

    /**
     * @param mixed $mdata
     * @phpstan-assert BootstrapStyleFieldData $mdata
     */
    private function assertBootstrapStyleFieldData(mixed $mdata): void
    {
        if (!is_array($mdata)) {
            throw new \UnexpectedValueException('QuickMode field metadata must be an array.');
        }

        foreach (
            [
                'type', 'bfType', 'bfName', 'name', 'pageIntro', 'label', 'hint', 'labelPosition', 'title',
                'description', 'actionFunctionName', 'connectType', 'emptyMessage', 'fieldCalc', 'format',
                'firstDay', 'minYear', 'maxYear', 'width', 'height', 'size', 'value', 'placeholder', 'group',
                'list', 'src', 'image', 'mailbackConnectWith', 'theme', 'toggleFields', 'pagingPrevLabel',
                'pagingNextLabel', 'submitLabel', 'cancelLabel',
            ] as $key
        ) {
            if (!is_string($mdata[$key] ?? null)) {
                throw new \UnexpectedValueException(
                    sprintf('QuickMode field metadata "%s" must be a string.', $key)
                );
            }
        }

        foreach (['dbId', 'pageNumber', 'tabIndex', 'maxlength'] as $key) {
            if (!is_int($mdata[$key] ?? null)) {
                throw new \UnexpectedValueException(
                    sprintf('QuickMode field metadata "%s" must be an integer.', $key)
                );
            }
        }

        foreach (
            [
                'actionClick', 'actionBlur', 'actionChange', 'actionFocus', 'actionSelect', 'hideIfEmpty',
                'hideLabel', 'off', 'required', 'readonly', 'multiple', 'wrap', 'mailbackAccept', 'mailbackAsSender',
                'is_html', 'flashUploader', 'html5', 'invisibleCaptcha', 'showMaxlengthCounter', 'attachToAdminMail',
                'attachToUserMail', 'fadeIn', 'useErrorAlerts', 'useDefaultErrors', 'useBalloonErrors', 'rollover',
                'lastPageThankYou', 'pagingInclude', 'submitInclude', 'cancelInclude', 'joomlaHint',
            ] as $key
        ) {
            if (!is_bool($mdata[$key] ?? null)) {
                throw new \UnexpectedValueException(
                    sprintf('QuickMode field metadata "%s" must be a boolean.', $key)
                );
            }
        }

        if (!is_string($mdata['connectWith'] ?? null) && !is_array($mdata['connectWith'] ?? null)) {
            throw new \UnexpectedValueException('QuickMode field metadata "connectWith" has an invalid type.');
        }

        if (is_array($mdata['connectWith'] ?? null)) {
            if (!array_is_list($mdata['connectWith'])) {
                throw new \UnexpectedValueException('QuickMode field metadata "connectWith" must be a list.');
            }

            foreach ($mdata['connectWith'] as $value) {
                if (!is_string($value)) {
                    throw new \UnexpectedValueException(
                        'QuickMode field metadata "connectWith" must contain only strings.'
                    );
                }
            }
        }
    }

    /**
     * @param array<string, mixed> $mdata
     */
    private function bootstrapStyleTranslation(array $mdata, string $key): ?string
    {
        if (!array_key_exists($key, $mdata)) {
            return null;
        }

        $value = $this->bootstrapStyleString($mdata[$key], $key);

        return $value !== '' ? $value : null;
    }

    private function quickModeBootstrapChoiceGroupBuilder(): QuickModeBootstrapChoiceGroupBuilder
    {
        return $this->quickModeBootstrapChoiceGroupBuilderService ??= new QuickModeBootstrapChoiceGroupBuilder(
            $this->quickModeGroupOptionBuilder(),
            fn(string $class): string => $this->bsClass($class)
        );
    }

    /**
     * @param array<string, mixed> $mdata
     */
    private function renderBootstrapStyleSummarizeField(array $mdata, string $label): void
    {
        $mdata = $this->normalizeBootstrapStyleFieldData($mdata);
        /* translatables */
        $translatedEmptyMessage = $this->bootstrapStyleTranslation(
            $mdata,
            'emptyMessage_translation' . $this->language_tag
        );
        if ($translatedEmptyMessage !== null) {
            $mdata['emptyMessage'] = $translatedEmptyMessage;
        }
        /* translatables end */
        echo '<div class="' . $this->bsClass('controls') . ' ' . $this->bsClass('form-inline') . '">';
        echo '<div class="' . $this->bsClass('form-group') . ' ' . $this->bsClass('other-form-group') . '">';
        echo $label;
        echo '<span class="' . $this->bsClass('nonform-control') . '">';
        echo '<div style="display: inline-block; vertical-align: top;" class="ff_elem bfSummarize" id="ff_elem'
            . $mdata['dbId'] . '"></div>' . "\n";
        echo '<script type="text/javascript">bfRegisterSummarize('
            . json_encode('ff_elem' . $mdata['dbId']) . ', '
            . json_encode($mdata['connectWith']) . ', '
            . json_encode($mdata['connectType']) . ', '
            . json_encode($mdata['emptyMessage']) . ', '
            . json_encode((bool) $mdata['hideIfEmpty']) . ');</script>';
        if (trim($mdata['fieldCalc']) != '') {
            echo '<script type="text/javascript">
                                                        <!--
					function bfFieldCalcff_elem' . $mdata['dbId'] . '(value){
						if(!isNaN(value)){
							value = Number(value);
						}
						' . $mdata['fieldCalc'] . '
						return value;
					}
                                                        //-->
					</script>';
        }
        echo '</span>';
        echo '</div>';
        echo '</div>';
    }

    /**
     * @param array<string, mixed> $mdata
     */
    private function renderBootstrapStyleCalendarField(array $mdata, string $label): void
    {
        $mdata = $this->normalizeBootstrapStyleFieldData($mdata);
        /* translatables */
        $translatedValue = $this->bootstrapStyleTranslation($mdata, 'value_translation' . $this->language_tag);
        if ($translatedValue !== null) {
            $mdata['value'] = $translatedValue;
        }
        $translatedFormat = $this->bootstrapStyleTranslation($mdata, 'format_translation' . $this->language_tag);
        if ($translatedFormat !== null) {
            $mdata['format'] = $translatedFormat;
        }
        $icon = '';
        if (
            $this->rootMdata['themebootstrapThemeEngine'] == 'bootstrap'
            && $this->rootMdata['themebootstrap'] == 'Azure'
        ) {
            if (!isset($mdata['icon']) || $mdata['icon'] == '') {
                $icon = '<i class="fa-solid fa-calendar iconf--fumi" aria-hidden="true"></i>';
            } else {
                $icon = '<i class="fa-solid ' . htmlentities($mdata['icon'], ENT_QUOTES, 'UTF-8')
                    . ' iconf--fumi" aria-hidden="true"></i>';
            }
        }
        /* translatables end */
        echo '<div class="' . $this->bsClass('controls') . ' ' . $this->bsClass('form-inline') . '">';
        echo '<div class="' . $this->bsClass('form-group') . ' ' . $this->bsClass('other-form-group') . '">';
        echo $label;
        echo $icon;
        echo '<span class="' . $this->bsClass('nonform-control') . '">';

        $exploded = explode('::', trim((string) $mdata['value']));
        $left = '';

        if (count($exploded) == 2) {
            $left = trim($exploded[0]);
        } elseif (count($exploded) == 1) {
            $left = trim($exploded[0]);

            if ($left === '...') {
                $left = '';
            }
        }

        $calAttr = [
            'class' => 'ff_elem bfCalendar',
            'showTime' => $this->bfCalendarShowTimeEnabled($mdata),
            'timeFormat' => $this->bfCalendarIsTruthy($mdata, 'timeFormat') ? '24' : '12',
            'singleHeader' => $this->bfCalendarIsTruthy($mdata, 'singleHeader'),
            'todayBtn' => $this->bfCalendarIsTruthy($mdata, 'todayButton'),
            'weekNumbers' => $this->bfCalendarIsTruthy($mdata, 'weekNumbers'),
            'minYear' => (isset($mdata['minYear']) && $mdata['minYear'] != '')
                ? '-' . $mdata['minYear'] : '',
            'maxYear' => (isset($mdata['maxYear']) && $mdata['maxYear'] != '')
                ? '+' . $mdata['maxYear'] : '',
            'firstDay' => (isset($mdata['firstDay']) && $mdata['firstDay'] != '')
                ? $mdata['firstDay'] : '7',
        ];

        echo HTMLHelper::_(
            'calendar',
            $left,
            "ff_nm_" . $mdata['bfName'] . "[]",
            "ff_elem" . $mdata['dbId'],
            $mdata['format'],
            $calAttr
        );

        echo '</span>';
        echo '</div>';
        echo '</div>';
    }

    /**
     * @param array<string, mixed> $mdata
     */
    private function renderBootstrapStyleCalendarResponsiveField(array $mdata, string $label): void
    {
        $mdata = $this->normalizeBootstrapStyleFieldData($mdata);
        /* translatables */
        $translatedValue = $this->bootstrapStyleTranslation($mdata, 'value_translation' . $this->language_tag);
        if ($translatedValue !== null) {
            $mdata['value'] = $translatedValue;
        }
        $translatedFormat = $this->bootstrapStyleTranslation($mdata, 'format_translation' . $this->language_tag);
        if ($translatedFormat !== null) {
            $mdata['format'] = $translatedFormat;
        }
        $icon = '';
        if (
            $this->rootMdata['themebootstrapThemeEngine'] == 'bootstrap'
            && $this->rootMdata['themebootstrap'] == 'Azure'
        ) {
            if (!isset($mdata['icon']) || $mdata['icon'] == '') {
                $icon = '<i class="fa-solid fa-calendar iconf--fumi" aria-hidden="true"></i>';
            } else {
                $icon = '<i class="fa-solid ' . htmlentities($mdata['icon'], ENT_QUOTES, 'UTF-8')
                    . ' iconf--fumi" aria-hidden="true"></i>';
            }
        }
        /* translatables end */
        $mdata['format'] = $this->bfCalendarToPickadateFormat($mdata['format']);
        $pickerFirstDay = $this->bfCalendarToPickadateFirstDay(isset($mdata['firstDay']) ? $mdata['firstDay'] : '');
        $pickerSelectYears = $this->bfCalendarSelectYears($mdata);
        echo '<div class="' . $this->bsClass('controls') . ' ' . $this->bsClass('form-inline') . '">';
        echo '<div class="' . $this->bsClass('form-group') . ' ' . $this->bsClass('other-form-group') . '">';
        echo $label;
        echo $icon;
        echo '<span class="' . $this->bsClass('nonform-control') . '">';

        $size = '';
        if ($mdata['size'] != '') {
            $size = 'style="width:' . htmlentities(strip_tags($mdata['size'])) . '" ';
        }

        $exploded = explode('::', trim($mdata['value']));

        $left = '';
        $right = '';
        if (count($exploded) == 2) {
            $left = trim($exploded[0]);
            $right = trim($exploded[1]);
        } else {
            $right = trim($exploded[0]);
        }
        if ($right === '') {
            $right = '...';
        }

        echo '<div class="' . $this->bsClass('input-append') . '">';
        echo $this->quickModeCalendarInputBuilder()->build(
            $this->bsClass('form-control') . ' ' . $this->bsClass('custom-form-control') . ' ff_elem',
            (string) $mdata['bfName'],
            (int) $mdata['dbId'],
            (string) $left,
            $size
        );
        echo $this->quickModeCalendarButtonBuilder()->build(
            'style="cursor:pointer !important;" type="button"',
            'ff_elem' . $mdata['dbId'] . '_calendarButton',
            'bfCalendar ' . $this->bsClass('btn') . ' ' . $this->bsClass('btn-primary') . ' button',
            (string) $right,
            '<i class="' . $this->bsClass('icon-calendar') . '"></i>'
                . htmlentities($right == '...' ? '' : $right, ENT_QUOTES, 'UTF-8')
        );
        echo '</div>' . "\n";

        if (!$this->hasResponsiveDatePicker) {
            $document = $this->p->app->getDocument() ?? throw new \RuntimeException('The document is not available.');
            $document->getWebAssetManager()->addInlineScript(
                'var bfPickerMinusYearIcon = ' . json_encode(
                    Uri::root(true) . '/components/com_breezingformsng/libraries/jquery/pickadate/minusyear.png'
                ) . ';'
                . "\n" . 'var bfPickerPlusYearIcon = ' . json_encode(
                    Uri::root(true) . '/components/com_breezingformsng/libraries/jquery/pickadate/plusyear.png'
                ) . ';'
            );
            RuntimeAssetLoader::script(
                $this->p->app,
                Uri::root(true) . '/media/com_breezingformsng/js/site/quickmode-calendar-responsive-legacy-style.js'
            );
            RuntimeAssetLoader::script(
                $this->p->app,
                Uri::root(true) . '/media/com_breezingformsng/js/site/quickmode-calendar-responsive-init.js'
            );
        }

        echo $this->quickModeCalendarInitScriptBuilder()->buildResponsive(
            (int) $mdata['dbId'],
            (string) $mdata['format'],
            (int) $pickerSelectYears,
            (int) $pickerFirstDay,
            true
        );

        $this->hasResponsiveDatePicker = true;

        echo '</span>';
        echo '</div>';
        echo '</div>';
    }

    /**
     * @param array<string, mixed> $mdata
     */
    private function renderBootstrapStyleTextfieldField(
        array $mdata,
        string $label,
        string $tabIndex,
        string $onclick,
        string $onblur,
        string $onchange,
        string $onfocus,
        string $onselect,
        string $readonly
    ): void {
        $mdata = $this->normalizeBootstrapStyleFieldData($mdata);
        $size = '';

        if ($mdata['size'] != '') {
            $size = 'style="width:' . htmlentities(strip_tags($mdata['size']))
                . ' !important; min-width:' . htmlentities(strip_tags($mdata['size'])) . ' !important;" ';
        }
        $icon = '';
        if (
            $this->rootMdata['themebootstrapThemeEngine'] == 'bootstrap'
            && $this->rootMdata['themebootstrap'] == 'Azure'
        ) {
            if (!isset($mdata['icon']) || $mdata['icon'] == '') {
                $icon = '<i class="fa-solid fa-pencil iconf--fumi" aria-hidden="true"></i>';
            } else {
                $icon = '<i class="fa-solid ' . htmlentities($mdata['icon'], ENT_QUOTES, 'UTF-8')
                    . ' iconf--fumi" aria-hidden="true"></i>';
            }
        }
        echo '<div class="' . $this->bsClass('controls') . ' ' . $this->bsClass('form-inline') . '">';
        echo '<div class="' . $this->bsClass('form-group') . '">';
        echo $label;
        echo $icon;
        echo $this->quickModeTextFieldStrategy()->textfield(
            $mdata,
            $this->language_tag,
            $this->bsClass('form-control') . ' ff_elem inputbox',
            $size . $tabIndex,
            $onclick . $onblur . $onchange . $onfocus . $onselect . $readonly
        );
        echo '</div>';
        echo '</div>';
        if ($mdata['mailbackAsSender']) {
            echo '<input type="hidden" name="mailbackSender[' . $mdata['bfName'] . ']" value="true"/>' . "\n";
        }
    }

    /**
     * @param array<string, mixed> $mdata
     */
    private function renderBootstrapStyleNumberInputField(
        array $mdata,
        string $label,
        string $tabIndex,
        string $onclick,
        string $onblur,
        string $onchange,
        string $onfocus,
        string $onselect,
        string $readonly
    ): void {
        $mdata = $this->normalizeBootstrapStyleFieldData($mdata);
        $icon = '';
        if (
            $this->rootMdata['themebootstrapThemeEngine'] == 'bootstrap'
            && $this->rootMdata['themebootstrap'] == 'Azure'
        ) {
            if (!isset($mdata['icon']) || $mdata['icon'] == '') {
                $icon = '<i class="fa-solid fa-pencil iconf--fumi" aria-hidden="true"></i>';
            } else {
                $icon = '<i class="fa-solid ' . htmlentities($mdata['icon'], ENT_QUOTES, 'UTF-8')
                    . ' iconf--fumi" aria-hidden="true"></i>';
            }
        }
        echo '<div class="' . $this->bsClass('controls') . ' ' . $this->bsClass('form-inline') . '">';
        echo '<div class="' . $this->bsClass('form-group') . '">';
        echo $label;
        echo $icon;
        echo $this->quickModeTextFieldStrategy()->numberInput(
            $mdata,
            $this->language_tag,
            $this->bsClass('form-control') . ' ff_elem inputbox',
            $tabIndex,
            $onclick . $onblur . $onchange . $onfocus . $onselect . $readonly,
            'maxlength',
            true
        );
        echo '</div>';
        echo '</div>';

        // set size of element, number input doesn't allow size attr
        if ($mdata['size'] != '') {
            RuntimeAssetLoader::script(
                $this->p->app,
                Uri::root(true) . '/media/com_breezingformsng/js/site/quickmode-number-input.js'
            );
            echo '<script type="text/javascript">bfSetNumberInputWidth('
                . json_encode((int) $mdata['dbId']) . ', ' . json_encode($mdata['size']) . ');</script>';
        }
    }

    /**
     * @param array<string, mixed> $mdata
     */
    private function renderBootstrapStyleCheckboxField(
        array $mdata,
        string $label,
        string $tabIndex,
        string $onclick,
        string $onblur,
        string $onchange,
        string $onfocus,
        string $onselect,
        string $readonly
    ): void {
        $mdata = $this->normalizeBootstrapStyleFieldData($mdata);
        echo '<div class="' . $this->bsClass('controls') . ' ' . $this->bsClass('form-inline') . '">';
        echo '<div class="' . $this->bsClass('form-group') . '">';
        echo $label;
        echo '<span class="' . $this->bsClass('nonform-control') . '">';
        echo $this->quickModeCheckboxStrategy()->build(
            $mdata,
            'ff_elem form-check-input',
            $tabIndex . $onclick . $onblur . $onchange . $onfocus . $onselect,
            $readonly !== ''
        );
        echo '</span>';
        echo '</div>';
        echo '</div>';
        if ($mdata['mailbackAccept']) {
            echo '<input type="hidden" class="ff_elem" name="mailbackConnectWith['
                . $mdata['mailbackConnectWith'] . ']" value="true_' . $mdata['bfName'] . '"/>' . "\n";
        }
    }

    /**
     * @param array<string, mixed> $mdata
     */
    private function renderBootstrapStyleSelectField(
        array $mdata,
        string $label,
        string $tabIndex,
        string $onclick,
        string $onblur,
        string $onchange,
        string $onfocus,
        string $onselect,
        string $readonly
    ): void {
        $mdata = $this->normalizeBootstrapStyleFieldData($mdata);
        /* translatables */
        $translatedList = $this->bootstrapStyleTranslation($mdata, 'list_translation' . $this->language_tag);
        if ($translatedList !== null) {
            $mdata['list'] = $translatedList;
        }
        /* translatables end */
        if ($mdata['list'] != '') {
            $width = '';
            if (isset($mdata['width']) && $mdata['width'] != '') {
                $width = 'width:' . htmlentities(strip_tags($mdata['width']))
                    . ' !important; min-width:' . htmlentities(strip_tags($mdata['width'])) . ' !important;';
            }
            $height = '';
            if (isset($mdata['height']) && $mdata['height'] != '') {
                $height = 'height:' . htmlentities(strip_tags($mdata['height'])) . ';';
            }
            $size = '';
            if ($height != '' || $width != '') {
                $size = 'style="' . $width . $height . '" ';
            }

            echo '<div class="' . $this->bsClass('controls') . ' ' . $this->bsClass('form-inline') . '">';
            echo '<div class="' . $this->bsClass('form-group') . '">';
            echo $label;
            echo $this->quickModeSelectBuilder()->build(
                $this->bsClass('form-select') . ' ff_elem chzn-done',
                (string) $mdata['bfName'],
                (int) $mdata['dbId'],
                (string) $mdata['list'],
                (bool) $mdata['multiple'],
                $tabIndex . $onclick . $onblur . $onchange . $onfocus . $onselect . $readonly,
                $size
            );
            echo '</div>';
            echo '</div>';
        }
    }

    /**
     * @param array<string, mixed> $mdata
     */
    private function renderBootstrapStyleSubmitButtonField(
        array $mdata,
        string $label,
        string $tabIndex,
        string $onblur,
        string $onchange,
        string $onfocus,
        string $onselect,
        string $readonly
    ): void {
        $mdata = $this->normalizeBootstrapStyleFieldData($mdata);
        /* translatables */
        $translatedSource = $this->bootstrapStyleTranslation($mdata, 'src_translation' . $this->language_tag);
        if ($translatedSource !== null) {
            $mdata['src'] = $translatedSource;
        }
        $translatedValue = $this->bootstrapStyleTranslation($mdata, 'value_translation' . $this->language_tag);
        if ($translatedValue !== null) {
            $mdata['value'] = $translatedValue;
        }
        /* translatables end */

        echo '<div class="' . $this->bsClass('controls') . ' ' . $this->bsClass('form-inline') . '">';
        echo '<div class="' . $this->bsClass('form-group') . ' ' . $this->bsClass('other-form-group') . '">';
        echo $label;
        echo '<span class="' . $this->bsClass('nonform-control') . '">';
        $value = '';
        $type = 'submit';
        $src = '';

        if ($mdata['src'] != '') {
            $type = 'image';
            $src = 'src="' . $mdata['src'] . '" ';
        }
        if ($mdata['value'] != '') {
            $value = 'value="' . htmlentities(trim($mdata['value']), ENT_QUOTES, 'UTF-8') . '" ';
        }
        if (isset($mdata['actionClick']) && $mdata['actionClick'] == 1) {
            $onclick = 'onclick="if(typeof bf_htmltextareainit != \'undefined\'){ bf_htmltextareainit() }'
                . 'populateSummarizers();'
                . 'if(document.getElementById(\'bfPaymentMethod\')){'
                . 'document.getElementById(\'bfPaymentMethod\').value=\'\';};'
                . $mdata['actionFunctionName'] . '(this,\'click\');return false;" ';
        } else {
            $onclick = 'onclick="if(typeof bf_htmltextareainit != \'undefined\'){ bf_htmltextareainit() }'
                . 'populateSummarizers();'
                . 'if(document.getElementById(\'bfPaymentMethod\')){'
                . 'document.getElementById(\'bfPaymentMethod\').value=\'\';};'
                . 'return false;" ';
        }
        if ($src == '') {
            echo $this->quickModeSubmitButtonBuilder()->build(
                'button',
                'type="button" class="ff_elem ' . $this->bsClass('btn') . ' '
                    . $this->bsClass('btn-primary') . ' button bfCustomSubmitButton"',
                $value . $src . $tabIndex . $onclick . $onblur . $onchange . $onfocus . $onselect . $readonly,
                $type,
                (string) $mdata['bfName'],
                (int) $mdata['dbId'],
                (string) $mdata['value']
            );
        } else {
            echo $this->quickModeSubmitButtonBuilder()->build(
                'input',
                'type="button" class="ff_elem bfCustomSubmitButton"',
                $value . $src . $tabIndex . $onclick . $onblur . $onchange . $onfocus . $onselect . $readonly,
                $type,
                (string) $mdata['bfName'],
                (int) $mdata['dbId'],
                '',
                ' alt=""',
                ' value="' . $mdata['value'] . '"'
            );
        }
        echo '</span>';
        echo '</div>';
        echo '</div>';
    }

    /**
     * @param array<string, mixed> $mdata
     */
    private function renderBootstrapStyleStripeField(
        array $mdata,
        string $label,
        string $tabIndex,
        string $onblur,
        string $onchange,
        string $onfocus,
        string $onselect,
        string $readonly
    ): void {
        $mdata = $this->normalizeBootstrapStyleFieldData($mdata);
        /* translatables */
        $translatedImage = $this->bootstrapStyleTranslation($mdata, 'image_translation' . $this->language_tag);
        if ($translatedImage !== null) {
            $mdata['image'] = $translatedImage;
        }
        /* translatables end */
        echo '<div class="' . $this->bsClass('controls') . ' ' . $this->bsClass('form-inline') . '">';
        echo '<div class="' . $this->bsClass('form-group') . ' ' . $this->bsClass('other-form-group') . '">';
        echo $label;
        echo '<span class="' . $this->bsClass('nonform-control') . '">';

        echo QuickModePaymentButtonBuilder::build(
            'Stripe',
            (string) $mdata['bfName'],
            (int) $mdata['dbId'],
            (string) $mdata['image'],
            'Stripe',
            'Stripe',
            $tabIndex,
            $onblur,
            $onchange,
            $onfocus,
            $onselect,
            $readonly,
            isset($mdata['actionClick']) && $mdata['actionClick'] == 1,
            (string) ($mdata['actionFunctionName'] ?? ''),
            $this->quickModeSubmitActionBuilder()->buildForPaymentButton($this instanceof OnePageRenderer)
        );
        echo '</span>';
        echo '</div>';
        echo '</div>';
    }

    /**
     * @param array<string, mixed> $mdata
     */
    private function renderBootstrapStylePayPalField(
        array $mdata,
        string $label,
        string $tabIndex,
        string $onblur,
        string $onchange,
        string $onfocus,
        string $onselect,
        string $readonly
    ): void {
        $mdata = $this->normalizeBootstrapStyleFieldData($mdata);
        /* translatables */
        $translatedImage = $this->bootstrapStyleTranslation($mdata, 'image_translation' . $this->language_tag);
        if ($translatedImage !== null) {
            $mdata['image'] = $translatedImage;
        }
        /* translatables end */
        echo '<div class="' . $this->bsClass('controls') . ' ' . $this->bsClass('form-inline') . '">';
        echo '<div class="' . $this->bsClass('form-group') . ' ' . $this->bsClass('other-form-group') . '">';
        echo $label;
        echo '<span class="' . $this->bsClass('nonform-control') . '">';

        echo QuickModePaymentButtonBuilder::build(
            'PayPal',
            (string) $mdata['bfName'],
            (int) $mdata['dbId'],
            (string) $mdata['image'],
            'PayPal',
            'PayPal',
            $tabIndex,
            $onblur,
            $onchange,
            $onfocus,
            $onselect,
            $readonly,
            isset($mdata['actionClick']) && $mdata['actionClick'] == 1,
            (string) ($mdata['actionFunctionName'] ?? ''),
            $this->quickModeSubmitActionBuilder()->buildForPaymentButton($this instanceof OnePageRenderer)
        );
        echo '</span>';
        echo '</div>';
        echo '</div>';
    }

    /**
     * @param array<string, mixed> $mdata
     */
    private function renderBootstrapStyleSignatureField(array $mdata, string $label): void
    {
        $mdata = $this->normalizeBootstrapStyleFieldData($mdata);
        RuntimeAssetLoader::script(
            $this->p->app,
            Uri::root(true) . '/components/com_breezingformsng/libraries/js/signature.js'
        );
        RuntimeAssetLoader::script(
            $this->p->app,
            Uri::root(true) . '/media/com_breezingformsng/js/site/quickmode-signature.js'
        );
        $document = $this->p->app->getDocument() ?? throw new \RuntimeException('The document is not available.');
        $document->getWebAssetManager()->addInlineScript(
            'bfSignatureInit(' . json_encode((int) $mdata['dbId']) . ');'
        );

        echo '<div class="' . $this->bsClass('controls') . ' ' . $this->bsClass('form-inline') . ' bfSignatureWrap">';
        echo '<div class="' . $this->bsClass('form-group') . ' ' . $this->bsClass('other-form-group') . '">';
        echo $label;
        echo '<span class="' . $this->bsClass('nonform-control') . '">';

        echo QuickModeSignatureMarkupBuilder::build(
            (int) $mdata['dbId'],
            (string) $mdata['bfName'],
            'onclick="bfSignatureReset(' . json_encode((int) $mdata['dbId'])
                . ');" class="bfSignatureResetButton button ' . $this->bsClass('btn') . ' '
                . $this->bsClass('btn-primary') . '"',
            Text::_('COM_BREEZINGFORMSNG_SIGNATURE_RESET_BUTTON'),
            false
        );
        echo '</span>';
        echo '</div>';
        echo '</div>';
        echo QuickModeHiddenFieldBuilder::build([
            'bfName' => $mdata['bfName'],
            'value' => '',
            'dbId' => $mdata['dbId'],
        ]);
    }

    /**
     * bfRadioGroup and bfCheckboxGroup are identical between the two
     * renderers except for one CSS class on the optional wrap <div>
     * (`bfRadioGroupWrap`/`bfCheckboxGroupWrap` on OnePage, absent on
     * Bootstrap) - passed explicitly as $wrapClass rather than silently
     * dropped or added, so each renderer's current output is preserved
     * exactly.
     *
     * @param array<string, mixed> $mdata
     */
    private function renderBootstrapStyleRadioGroupField(
        array $mdata,
        string $label,
        string $tabIndex,
        string $onclick,
        string $onblur,
        string $onchange,
        string $onfocus,
        string $onselect,
        string $readonly,
        string $wrapClass = ''
    ): void {
        $mdata = $this->normalizeBootstrapStyleFieldData($mdata);
        echo $this->quickModeBootstrapChoiceGroupBuilder()->build(
            'radio',
            $mdata,
            $this->language_tag,
            $label,
            $tabIndex,
            $onclick . $onblur . $onchange . $onfocus . $onselect,
            $readonly,
            $wrapClass
        );
    }

    /**
     * @param array<string, mixed> $mdata
     */
    private function renderBootstrapStyleCheckboxGroupField(
        array $mdata,
        string $label,
        string $tabIndex,
        string $onclick,
        string $onblur,
        string $onchange,
        string $onfocus,
        string $onselect,
        string $readonly,
        string $wrapClass = ''
    ): void {
        $mdata = $this->normalizeBootstrapStyleFieldData($mdata);
        echo $this->quickModeBootstrapChoiceGroupBuilder()->build(
            'checkbox',
            $mdata,
            $this->language_tag,
            $label,
            $tabIndex,
            $onclick . $onblur . $onchange . $onfocus . $onselect,
            $readonly,
            $wrapClass
        );
    }
}
