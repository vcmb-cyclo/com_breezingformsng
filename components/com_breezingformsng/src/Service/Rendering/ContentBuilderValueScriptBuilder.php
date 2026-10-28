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

/**
 * Builds the JavaScript used to restore editable ContentBuilder values.
 *
 * The caller is responsible for cleaning the value before passing the entry.
 * This service only formats the historical control updates and has no Joomla
 * or processor dependency.
 */
final class ContentBuilderValueScriptBuilder
{
    /**
     * Build the value-restoration script for one ContentBuilder entry.
     *
     * @param \stdClass $entry
     */
    public function build(object $entry, int $formId): string
    {
        $type = (string) $entry->recType;
        $name = (string) $entry->recName;
        $elementId = (int) $entry->recElementId;
        $value = (string) $entry->recValue;

        return match ($type) {
            'Textarea', 'Text', 'Number Input', 'Hidden Input', 'Calendar' =>
                $this->buildSimpleValue($entry, $name, $elementId, $value),
            'Checkbox' => $value === '' ? '' : $this->buildCheckboxValues($name, $formId, $value),
            'Checkbox Group' => $this->buildCheckboxValues($name, $formId, $value),
            'Radio Button', 'Radio Group' => $this->buildRadioValues($name, $formId, $value),
            'Select List' => $this->buildSelectValues($elementId, $value),
            default => '',
        };
    }

    /** @param \stdClass $entry */
    private function buildSimpleValue(object $entry, string $name, int $elementId, string $value): string
    {
        $encodedValue = $this->encode($value);
        $selector = '[name=\"ff_nm_' . $name . '[]\"]';
        $element = 'document.getElementById("ff_elem' . $elementId . '")';
        $script = 'if(typeof JQuery != "undefined"){JQuery("' . $selector . '").val(' . $encodedValue . ');';
        $script .= 'if(typeof JQuery != "undefined")JQuery("' . $selector . '").trigger("change");}else{';
        $script .= 'if(' . $element . ')' . $element . '.value=' . $encodedValue . ';';
        $script .= 'if(typeof JQuery != "undefined")JQuery(' . $element . ').trigger("change");' . "\n";
        $script .= '}';

        if ($entry->recType === 'Calendar') {
            return 'setTimeout(function(){' . $script . '}, 100);';
        }

        return $script;
    }

    private function buildCheckboxValues(string $name, int $formId, string $value): string
    {
        $script = '';
        $elements = 'document.ff_form' . $formId . '.elements';
        foreach (explode(',', $value) as $selectedValue) {
            $selectedValue = trim($selectedValue);
            $selectedValueJson = $this->encode($selectedValue);
            $script .= 'for(var i = 0;i < ' . $elements . '.length;i++){' . "\n";
            $script .= 'if(' . $elements . '[i].type == "checkbox" && ';
            $script .= $elements . '[i].name == "ff_nm_' . $name . '[]" && ';
            $script .= $elements . '[i].value == ' . $selectedValueJson . '){' . "\n";
            $script .= 'if(typeof JQuery != "undefined" && !JQuery(' . $elements . '[i]).attr("checked")){';
            $script .= 'JQuery(' . $elements . '[i]).click();}' . "\n";
            $script .= '}' . "\n";
            $script .= '}' . "\n";
        }

        return $script;
    }

    private function buildRadioValues(string $name, int $formId, string $value): string
    {
        $script = '';
        $elements = 'document.ff_form' . $formId . '.elements';
        foreach (explode(',', $value) as $selectedValue) {
            $selectedValue = trim($selectedValue);
            $selectedValueJson = $this->encode($selectedValue);
            $script .= 'for(var i = 0;i < ' . $elements . '.length;i++){' . "\n";
            $script .= 'if(' . $elements . '[i].type == "radio" && ';
            $script .= $elements . '[i].name == "ff_nm_' . $name . '[]" && ';
            $script .= $elements . '[i].value == ' . $selectedValueJson . '){' . "\n";
            $script .= 'if(typeof JQuery != "undefined" && !JQuery(' . $elements . '[i]).attr("checked")){';
            $script .= 'JQuery(' . $elements . '[i]).click();}' . "\n";
            $script .= '}' . "\n";
            $script .= '}' . "\n";
        }

        return $script;
    }

    private function buildSelectValues(int $elementId, string $value): string
    {
        $script = '';
        $element = 'document.getElementById("ff_elem' . $elementId . '")';
        foreach (explode(',', $value) as $selectedValue) {
            $selectedValue = trim($selectedValue);
            $encodedValue = $this->encode($selectedValue);
            $option = $element . '.options[i]';
            $script .= 'for(var i = 0; i < ' . $element . '.options.length; i++){' . "\n";
            $script .= 'if(' . $option . '.value == ' . $encodedValue . '){' . "\n";
            $script .= 'if(typeof JQuery != "undefined" && !JQuery(' . $option . ').attr("selected")){';
            $script .= 'JQuery(' . $option . ').attr("selected", true).trigger("change");}' . "\n";
            $script .= '}' . "\n";
            $script .= '}' . "\n";
        }

        return $script;
    }

    private function encode(string $value): string
    {
        return (string) json_encode($value);
    }
}
