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

trait BootstrapStyleFieldTrait
{
    private ?QuickModeBootstrapChoiceGroupBuilder $quickModeBootstrapChoiceGroupBuilderService = null;

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
        /* translatables */
        if (
            isset($mdata['emptyMessage_translation' . $this->language_tag])
            && $mdata['emptyMessage_translation' . $this->language_tag] != ''
        ) {
            $mdata['emptyMessage'] = $mdata['emptyMessage_translation' . $this->language_tag];
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
        /* translatables */
        if (
            isset($mdata['value_translation' . $this->language_tag])
            && $mdata['value_translation' . $this->language_tag] != ''
        ) {
            $mdata['value'] = $mdata['value_translation' . $this->language_tag];
        }
        if (
            isset($mdata['format_translation' . $this->language_tag])
            && $mdata['format_translation' . $this->language_tag] != ''
        ) {
            $mdata['format'] = $mdata['format_translation' . $this->language_tag];
        }
        $icon = '';
        if (
            $this->rootMdata['themebootstrapThemeEngine'] == 'bootstrap'
            && $this->rootMdata['themebootstrap'] == 'Azure'
        ) {
            if (!isset($mdata['icon']) || $mdata['icon'] == '') {
                $icon = '<i class="fas fa-calendar iconf--fumi" aria-hidden="true"></i>';
            } else {
                $icon = '<i class="fas ' . htmlentities($mdata['icon'], ENT_QUOTES, 'UTF-8')
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
        /* translatables */
        if (
            isset($mdata['value_translation' . $this->language_tag])
            && $mdata['value_translation' . $this->language_tag] != ''
        ) {
            $mdata['value'] = $mdata['value_translation' . $this->language_tag];
        }
        if (
            isset($mdata['format_translation' . $this->language_tag])
            && $mdata['format_translation' . $this->language_tag] != ''
        ) {
            $mdata['format'] = $mdata['format_translation' . $this->language_tag];
        }
        $icon = '';
        if (
            $this->rootMdata['themebootstrapThemeEngine'] == 'bootstrap'
            && $this->rootMdata['themebootstrap'] == 'Azure'
        ) {
            if (!isset($mdata['icon']) || $mdata['icon'] == '') {
                $icon = '<i class="fas fa-calendar iconf--fumi" aria-hidden="true"></i>';
            } else {
                $icon = '<i class="fas ' . htmlentities($mdata['icon'], ENT_QUOTES, 'UTF-8')
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
            $this->p->app->getDocument()->getWebAssetManager()->addInlineScript(
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
                $icon = '<i class="fas fa-pencil iconf--fumi" aria-hidden="true"></i>';
            } else {
                $icon = '<i class="fas ' . htmlentities($mdata['icon'], ENT_QUOTES, 'UTF-8')
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
        $icon = '';
        if (
            $this->rootMdata['themebootstrapThemeEngine'] == 'bootstrap'
            && $this->rootMdata['themebootstrap'] == 'Azure'
        ) {
            if (!isset($mdata['icon']) || $mdata['icon'] == '') {
                $icon = '<i class="fas fa-pencil iconf--fumi" aria-hidden="true"></i>';
            } else {
                $icon = '<i class="fas ' . htmlentities($mdata['icon'], ENT_QUOTES, 'UTF-8')
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
        /* translatables */
        if (
            isset($mdata['list_translation' . $this->language_tag])
            && $mdata['list_translation' . $this->language_tag] != ''
        ) {
            $mdata['list'] = $mdata['list_translation' . $this->language_tag];
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
        /* translatables */
        if (
            isset($mdata['src_translation' . $this->language_tag])
            && $mdata['src_translation' . $this->language_tag] != ''
        ) {
            $mdata['src'] = $mdata['src_translation' . $this->language_tag];
        }
        if (
            isset($mdata['value_translation' . $this->language_tag])
            && $mdata['value_translation' . $this->language_tag] != ''
        ) {
            $mdata['value'] = $mdata['value_translation' . $this->language_tag];
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
        /* translatables */
        if (
            isset($mdata['image_translation' . $this->language_tag])
            && $mdata['image_translation' . $this->language_tag] != ''
        ) {
            $mdata['image'] = $mdata['image_translation' . $this->language_tag];
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
            (string) ($mdata['actionFunctionName'] ?? '')
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
        /* translatables */
        if (
            isset($mdata['image_translation' . $this->language_tag])
            && $mdata['image_translation' . $this->language_tag] != ''
        ) {
            $mdata['image'] = $mdata['image_translation' . $this->language_tag];
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
            (string) ($mdata['actionFunctionName'] ?? '')
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
        RuntimeAssetLoader::script(
            $this->p->app,
            Uri::root(true) . '/components/com_breezingformsng/libraries/js/signature.js'
        );
        RuntimeAssetLoader::script(
            $this->p->app,
            Uri::root(true) . '/media/com_breezingformsng/js/site/quickmode-signature.js'
        );
        $this->p->app->getDocument()->getWebAssetManager()->addInlineScript(
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
