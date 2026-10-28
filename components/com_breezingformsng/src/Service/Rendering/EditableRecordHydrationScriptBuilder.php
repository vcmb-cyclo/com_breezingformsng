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

use Closure;

/**
 * Builds the JavaScript that restores an editable BreezingForms record.
 *
 * The optional cleaner allows the caller to provide its runtime-specific
 * sanitization without making this service depend on Joomla.
 */
final class EditableRecordHydrationScriptBuilder
{
    public function __construct(
        private readonly ContentBuilderValueScriptBuilder $valueScriptBuilder = new ContentBuilderValueScriptBuilder(),
        private readonly ?Closure $cleanValue = null
    ) {
    }

    /**
     * @param iterable<\stdClass> $entries
     */
    public function build(iterable $entries, int $formId): string
    {
        $script = '';

        foreach ($entries as $entry) {
            $type = (string) $entry->type;
            $name = (string) $entry->name;
            $elementId = (int) $entry->element;
            $value = (string) $entry->value;
            if ($this->cleanValue !== null) {
                $value = ($this->cleanValue)($value);
            }

            switch ($type) {
                case 'Textarea':
                case 'Text':
                case 'Hidden Input':
                case 'Number Input':
                case 'Calendar':
                    $script .= $this->valueScriptBuilder->build((object) [
                        'recType' => $type,
                        'recName' => $name,
                        'recElementId' => $elementId,
                        'recValue' => $value,
                    ], $formId);
                    break;
                case 'Checkbox':
                    if ($value !== '') {
                        $script .= 'if(document.getElementById("ff_elem' . $elementId
                            . '") && !JQuery(document.getElementById("ff_elem' . $elementId
                            . '")).attr("checked"))JQuery(document.getElementById("ff_elem' . $elementId
                            . '")).click();' . "\n";
                    }
                    break;
                case 'Checkbox Group':
                    $script .= $this->buildChoiceScript('checkbox', $name, $formId, $value);
                    break;
                case 'Radio Button':
                case 'Radio Group':
                    $script .= $this->buildChoiceScript('radio', $name, $formId, $value);
                    break;
                case 'Select List':
                    $script .= $this->buildSelectScript($elementId, $value);
                    break;
            }
        }

        return $script;
    }

    private function buildChoiceScript(string $type, string $name, int $formId, string $value): string
    {
        $escapedName = $this->encodeJavaScriptValue('ff_nm_' . $name . '[]');

        return '
							for(var i = 0;i < document.ff_form' . $formId . '.elements.length;i++){
						if(document.ff_form' . $formId . '.elements[i].type == "' . $type
            . '" && document.ff_form' . $formId . '.elements[i].name == ' . $escapedName
            . ' && document.ff_form' . $formId . '.elements[i].value == ' . $this->encodeJavaScriptValue($value) . '){
										if(typeof JQuery != "undefined" && !JQuery(document.ff_form' . $formId
                . '.elements[i]).attr("checked")){
									    JQuery(document.ff_form' . $formId . '.elements[i]).click();
									}
								}
							}' . "\n";
    }

    private function buildSelectScript(int $elementId, string $value): string
    {
        $elementSelector = 'document.getElementById("ff_elem' . $elementId . '")';
        $optionSelector = $elementSelector . '.options[i]';
        $jqueryOption = 'JQuery(' . $optionSelector . ')';
        $encodedValue = $this->encodeJavaScriptValue($value);

        return 'for(var i = 0; i < ' . $elementSelector . '.options.length; i++){
					if(' . $optionSelector . '.value == ' . $encodedValue . '){
									if(typeof JQuery != "undefined" && !' . $jqueryOption . '.attr("selected")){
									    ' . $jqueryOption . '.attr("selected", true).trigger("change");
									}
								}
				}' . "\n";
    }

    private function encodeJavaScriptValue(string $value): string
    {
        return (string) json_encode(
            $value,
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE
        );
    }
}
