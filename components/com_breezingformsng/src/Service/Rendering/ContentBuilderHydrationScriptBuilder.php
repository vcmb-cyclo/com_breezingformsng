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

/** Builds ContentBuilder hydration scripts for editable and read-only records. */
final class ContentBuilderHydrationScriptBuilder
{
    public function __construct(
        private readonly Closure $cleanValue,
        private readonly Closure $wordWrap
    ) {
    }

    /**
     * @param iterable<\stdClass> $records
     * @param array<int|string> $nonEditableFields
     * @return array{contentBuilderScript: string, javascript: string}
     */
    public function buildEditable(
        iterable $records,
        array $nonEditableFields,
        bool $quickMode,
        int $formId,
        string $signatureDirectory
    ): array {
        $contentBuilderScript = '';
        $javascript = '';
        $flashUploadValidationAdded = false;
        $fileSupport = new ContentBuilderFileSupportBuilder();

        foreach ($records as $entry) {
            if (in_array($entry->recElementId, $nonEditableFields)) {
                continue;
            }

            $recordValue = ($this->cleanValue)((string) $entry->recValue);

            switch ($entry->recType) {
                case 'File Upload':
                    if (!$quickMode) {
                        break;
                    }

                    if (!$flashUploadValidationAdded) {
                        $contentBuilderScript .= $this->flashUploadValidation();
                        $flashUploadValidationAdded = true;
                    }

                    $fileValue = $fileSupport->parseValue($recordValue);
                    $count = $fileValue['count'];
                    $contentBuilderScript .= '\n'
                        . '                                    cbFlashElemCnt["ff_elem' . $entry->recElementId . '"] = '
                        . $count . ";\n                                ";
                    $displayNames = [];
                    foreach ($fileValue['files'] as $file) {
                        if (trim($file)) {
                            $displayNames[] = $fileSupport->displayName(
                                ($this->wordWrap)($file, 150, '<br>', true)
                            );
                        }
                    }
                    $uploadControls = $this->fileUploadControls(
                        (int) $entry->recElementId,
                        (string) $entry->recName,
                        $count,
                        $displayNames
                    );
                    $javascript .= $uploadControls['deactivation'];
                    $javascript .= $this->fileHydration(
                        (int) $entry->recElementId,
                        $uploadControls['html']
                    );
                    break;

                case 'Signature':
                    $signaturePath = $fileSupport->resolveSignature(
                        $signatureDirectory,
                        $recordValue
                    );
                    if ($signaturePath !== null) {
                        $encoded = (new ContentBuilderSignatureImageEncoder())->encode($signaturePath);
                        $javascript .= $this->signatureHydration(
                            (string) $entry->recName,
                            (int) $entry->recElementId,
                            (string) $encoded
                        );
                    }
                    break;

                case 'Textarea':
                case 'Text':
                case 'Number Input':
                case 'Hidden Input':
                case 'Calendar':
                    $javascript .= $this->valueHydration(
                        (string) $entry->recType,
                        (string) $entry->recName,
                        (int) $entry->recElementId,
                        $recordValue
                    );
                    break;

                case 'Checkbox':
                case 'Checkbox Group':
                    $javascript .= $this->choiceHydration(
                        'checkbox',
                        (string) $entry->recName,
                        $formId,
                        $recordValue
                    );
                    break;

                case 'Radio Button':
                case 'Radio Group':
                    $javascript .= $this->choiceHydration(
                        'radio',
                        (string) $entry->recName,
                        $formId,
                        $recordValue
                    );
                    break;

                case 'Select List':
                    $javascript .= $this->selectHydration(
                        (int) $entry->recElementId,
                        $recordValue
                    );
                    break;
            }
        }

        return [
            'contentBuilderScript' => $contentBuilderScript,
            'javascript' => $javascript,
        ];
    }

    /**
     * Build the function body used to deactivate the supplied field IDs.
     *
     * @param array<int|string> $fieldIds
     */
    public function buildReadonly(array $fieldIds): string
    {
        if ($fieldIds === []) {
            return '';
        }

        $script = 'function bfContentBuilderFieldHasVisibleControl(fieldId){' . "\n";
        $script .= 'var wrap = JQuery("#bfElemWrap" + fieldId);' . "\n";
        $script .= 'if(!wrap.length){ return false; }' . "\n";
        $script .= 'var hasVisibleControl = false;' . "\n";
        $script .= 'wrap.find(".ff_elem").each(function(){' . "\n";
        $script .= 'if(typeof this.type != "undefined" && this.type != "hidden"){';
        $script .= ' hasVisibleControl = true; return false; }' . "\n";
        $script .= '});' . "\n";
        $script .= 'return hasVisibleControl;' . "\n";
        $script .= '}' . "\n";
        $script .= 'function bfDisableContentBuilderFields(){' . "\n";

        foreach ($fieldIds as $fieldId) {
            $fieldId = (string) $fieldId;
            $element = 'document.getElementById("ff_elem' . $fieldId . '")';
            $visibleControl = 'bfContentBuilderFieldHasVisibleControl("' . $fieldId . '")';
            $hideControl = 'if(typeof JQuery != "undefined" && !' . $visibleControl . '){';
            $hideControl .= ' JQuery("#bfElemWrap' . $fieldId . '").css("display", "none"); }';
            $script .= 'if(typeof ' . $element . '.disabled != "undefined"){' . "\n";
            $script .= 'bfCbMainElement = ' . $element . ';' . "\n";
            $script .= 'bfCbRespectReadonly = (bfCbMainElement && ';
            $script .= 'typeof bfCbMainElement.readOnly != "undefined" && ';
            $script .= 'bfCbMainElement.readOnly);' . "\n";
            $script .= 'bfCbName = ' . $element . '.name;' . "\n";
            $script .= 'if(typeof document.getElementsByName != "undefined"){' . "\n";
            $script .= 'bfCbElements = document.getElementsByName(bfCbName);' . "\n";
            $script .= 'for(var i = 0; i < bfCbElements.length; i++){' . "\n";
            $script .= 'if(typeof bfCbElements[i].disabled != "undefined" && !bfCbRespectReadonly){' . "\n";
            $script .= 'bfCbElements[i].disabled = true;' . "\n";
            $script .= '}' . "\n";
            $script .= 'bfDeactivateField[bfCbName]=true;' . "\n";
            $script .= $hideControl . "\n";
            $script .= '}' . "\n";
            $script .= '}else{' . "\n";
            $script .= 'if(!bfCbRespectReadonly){ ' . $element . '.disabled = true; }' . "\n";
            $script .= 'bfDeactivateField[bfCbName]=true;' . "\n";
            $script .= $hideControl . "\n";
            $script .= '}' . "\n";
            $script .= '}' . "\n";
        }

        return $script . '}' . "\n";
    }

    private function flashUploadValidation(): string
    {
        return '
                                            function ff_flashupload_not_empty(element, message)
                                            {
                                                if(typeof bfSummarizers == "undefined") { '
                                                . 'alert("Flash upload validation only available in QuickMode!"); '
                                                . 'return ""}
                                                if(JQuery("#bfFlashFileQueue"+element.id.split("ff_elem")[1])'
                                                . '.html() != "" || '
                                                . 'cbFlashElemCnt[element.id] != 0 ) return "";
                                                if (message=="") message = "Please enter "+element.name+".\n";
                                                ff_validationFocus(element.name);
                                                return message;
                                            }
                                            ';
    }

    /**
     * @param list<string> $displayNames
     * @return array{count: int, html: string, deactivation: string}
     */
    private function fileUploadControls(int $elementId, string $recordName, int $fileCount, array $displayNames): array
    {
        $html = '';
        foreach ($displayNames as $index => $displayName) {
            $html .= '<div><input type=\"checkbox\" onchange=\"bfCheckUploadValidation(\'ff_elem'
                . $elementId . '\', this, \'ff_nm_' . $recordName . '[]\')\" value=\"1\" name=\"cb_delete_'
                . $elementId . '[' . $index . ']\" id=\"cb_delete_' . $elementId . '_' . $index
                . '\"/> <label style=\"margin-left: 5px; float: none !important; display: inline !important;\" '
                . 'for=\"cb_delete_'
                . $elementId . '_' . $index . '\">' . $displayName . '</label></div>';
        }

        return [
            'count' => $fileCount,
            'html' => $html,
            'deactivation' => $displayNames === []
                ? ''
                : 'bfDeactivateField["ff_nm_' . $recordName . '[]"]=true;' . "\n",
        ];
    }

    private function fileHydration(int $elementId, string $controlsHtml): string
    {
        // phpcs:disable Generic.Files.LineLength.TooLong -- Preserve legacy JavaScript lines verbatim.
        return '
                                                    if (document.createTextNode){
                                                        if(!document.getElementById("bfFlashFileQueue' . $elementId . '")){
                                                           var mydiv = document.createElement("div");
                                                           mydiv.innerHTML = "<br/>' . $controlsHtml . '";
                                                           JQuery("#ff_elem' . $elementId . '_files").append(mydiv);
                                                        } else {
                                                           var mydiv = document.createElement("div");
                                                           mydiv.innerHTML = "' . $controlsHtml . '";
                                                           mydiv.innerHTML = "<br/>" + mydiv.innerHTML;
                                                           JQuery("#bfFlashFileQueue' . $elementId . '").after(mydiv);
                                                        }
                                                    }' . "\n";
        // phpcs:enable Generic.Files.LineLength.TooLong
    }

    private function signatureHydration(string $recordName, int $elementId, string $encodedImage): string
    {
        $dataUrl = 'data:image/png;base64,' . $encodedImage;
        $fieldSelector = json_encode(
            '[name="ff_nm_' . $recordName . '[]"]',
            JSON_THROW_ON_ERROR
        );

        return 'JQuery(document).ready(function(){'
            . 'if(typeof bf_signaturePad' . $elementId . ' != "undefined"){'
            . 'if(' . (strlen($encodedImage) > 0 ? 'true' : 'false') . '){'
            . 'JQuery(' . $fieldSelector . ').val(' . json_encode($dataUrl, JSON_THROW_ON_ERROR) . ')' . "\n"
            . 'bf_signaturePad' . $elementId . '.fromDataURL(' . json_encode($dataUrl, JSON_THROW_ON_ERROR) . ');'
            . '}'
            . '}'
            . '});';
    }

    private function valueHydration(string $type, string $recordName, int $elementId, mixed $value): string
    {
        $script = $type === 'Calendar' ? 'setTimeout(function(){' : '';
        $encodedValue = json_encode($value);

        $script .= 'if(typeof JQuery != "undefined"){';
        $script .= 'JQuery("[name=\"ff_nm_' . $recordName . '[]\"]").val(' . $encodedValue
            . ');if(typeof JQuery != "undefined")JQuery("[name=\"ff_nm_' . $recordName
            . '[]\"]").trigger("change");';
        $script .= '}else{if(document.getElementById("ff_elem' . $elementId . '"))'
            . 'document.getElementById("ff_elem' . $elementId . '").value=' . $encodedValue
            . ';if(typeof JQuery != "undefined")JQuery(document.getElementById("ff_elem'
            . $elementId . '")).trigger("change");}' . "\n";

        if ($type === 'Calendar') {
            $script .= '}, 100);';
        }

        return $script;
    }

    private function choiceHydration(string $controlType, string $recordName, int $formId, string $value): string
    {
        $script = '';

        foreach (explode(',', $value) as $choice) {
            $choice = trim($choice);
            $encodedChoice = json_encode($choice);
            // phpcs:disable Generic.Files.LineLength.TooLong -- Preserve legacy JavaScript lines verbatim.
            $script .= '
                                                for(var i = 0;i < document.ff_form' . $formId . '.elements.length;i++){
                                                        if(document.ff_form' . $formId . '.elements[i].type == "' . $controlType . '" && document.ff_form' . $formId . '.elements[i].name == "ff_nm_' . $recordName . '[]" && document.ff_form' . $formId . '.elements[i].value == ' . $encodedChoice . '){
                                                                if(typeof JQuery != "undefined" && !JQuery(document.ff_form' . $formId . '.elements[i]).attr("checked")){
                                                                    JQuery(document.ff_form' . $formId . '.elements[i]).click();
                                                                }
                                                        }
                                                }' . "\n";
            // phpcs:enable Generic.Files.LineLength.TooLong
        }

        return $script;
    }

    private function selectHydration(int $elementId, string $value): string
    {
        $script = '';

        foreach (explode(',', $value) as $choice) {
            $choice = trim($choice);
            $encodedChoice = json_encode($choice);
            // phpcs:disable Generic.Files.LineLength.TooLong -- Preserve legacy JavaScript lines verbatim.
            $script .= 'for(var i = 0; i < document.getElementById("ff_elem' . $elementId . '").options.length; i++){
                                                        if(document.getElementById("ff_elem' . $elementId . '").options[i].value == ' . $encodedChoice . '){
                                                                if(typeof JQuery != "undefined" && !JQuery(document.getElementById("ff_elem' . $elementId . '").options[i]).attr("selected")){
                                                                    JQuery(document.getElementById("ff_elem' . $elementId . '").options[i]).attr("selected", true).trigger("change");
                                                                }
                                                        }
                                                }' . "\n";
            // phpcs:enable Generic.Files.LineLength.TooLong
        }

        return $script;
    }
}
