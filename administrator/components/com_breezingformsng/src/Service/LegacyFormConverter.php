<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Administrator\Service;

\defined('_JEXEC') or die;

/** Builds an editable QuickMode tree without modifying the source records. */
final class LegacyFormConverter
{
    /**
     * @param array<string, mixed> $form
     * @param list<array<string, mixed>> $elements
     * @return array<string, mixed>
     * @throws \DomainException When an element cannot be safely represented.
     */
    public function convert(array $form, array $elements): array
    {
        $pageCount = (int) ($form['pages'] ?? 1);
        if ($pageCount < 1 || $pageCount > 100 || $elements === []) {
            throw new \DomainException('invalid_form');
        }

        $title = (string) ($form['title'] ?? '');
        $root = [
            'attributes' => [
                'class' => 'bfQuickModeRootClass', 'id' => 'bfQuickModeRoot', 'mdata' => '{"type":"root"}',
            ],
            'properties' => [
                'type' => 'root', 'title' => $title, 'name' => (string) ($form['name'] ?? ''),
                'description' => (string) ($form['description'] ?? ''),
                'rollover' => true, 'rolloverColor' => '#ffc', 'toggleFields' => '',
                'mailNotification' => (bool) ($form['emailntf'] ?? false),
                'mailRecipient' => (string) ($form['emailadr'] ?? ''), 'submitInclude' => true,
                'submitLabel' => 'submit', 'cancelInclude' => false, 'cancelLabel' => 'reset',
                'pagingInclude' => true, 'pagingNextLabel' => 'next', 'pagingPrevLabel' => 'back',
                'theme' => 'default', 'themebootstrap' => '', 'themebootstrapbefore' => '',
                'themebootstrapLabelTop' => false, 'themebootstrapThemeEngine' => 'bootstrap',
                'themebootstrapUseHeroUnit' => false, 'themebootstrapUseWell' => false,
                'themebootstrapUseProgress' => false, 'fadeIn' => false, 'lastPageThankYou' => false,
                'submittedScriptCondidtion' => 0, 'submittedScriptCode' => '',
                'useErrorAlerts' => false, 'useDefaultErrors' => true, 'useBalloonErrors' => false,
                'joomlaHint' => false,
            ],
            'state' => 'open', 'data' => ['title' => $title], 'children' => [],
        ];
        for ($page = 1; $page <= $pageCount; $page++) {
            $root['children'][] = [
                'attributes' => ['class' => 'bfQuickModePageClass', 'id' => 'bfQuickModePage' . $page,
                    'mdata' => '{"deletable":true,"type":"page"}'],
                'properties' => ['type' => 'page', 'pageNumber' => $page, 'pageIntro' => ''],
                'state' => 'open', 'data' => ['title' => 'Page ' . $page], 'children' => [],
            ];
        }

        usort($elements, static fn (array $a, array $b): int =>
            [(int) ($a['page'] ?? 0), (int) ($a['ordering'] ?? 0), (int) ($a['id'] ?? 0)]
            <=> [(int) ($b['page'] ?? 0), (int) ($b['ordering'] ?? 0), (int) ($b['id'] ?? 0)]);
        $names = [];
        foreach ($elements as $index => $element) {
            $page = (int) ($element['page'] ?? 0);
            $name = (string) ($element['name'] ?? '');
            $nameKey = mb_strtolower($name, 'UTF-8');
            if (
                $page < 1 || $page > $pageCount || $name === '' || isset($names[$nameKey])
                || in_array($nameKey, ['bffakename', 'bffakename2', 'bffakename3', 'bffakename4'], true)
                || (int) ($element['published'] ?? 0) !== 1
            ) {
                throw new LegacyFormConversionException(
                    'invalid_element',
                    (int) ($element['id'] ?? 0),
                    $name,
                    (string) ($element['type'] ?? '')
                );
            }
            $names[$nameKey] = true;
            try {
                $root['children'][$page - 1]['children'][] = $this->convertElement($element, $index + 1);
            } catch (\DomainException $exception) {
                throw new LegacyFormConversionException(
                    $exception->getMessage(),
                    (int) ($element['id'] ?? 0),
                    $name,
                    (string) ($element['type'] ?? ''),
                );
            }
        }

        return $root;
    }

    /**
     * @param array<string, mixed> $element
     * @return array<string, mixed>
     */
    private function convertElement(array $element, int $number): array
    {
        $types = ['Text' => 'bfTextfield', 'Textarea' => 'bfTextarea',
            'Hidden Input' => 'bfHidden', 'Checkbox' => 'bfCheckbox', 'Select List' => 'bfSelect'];
        $type = (string) ($element['type'] ?? '');
        if (!isset($types[$type])) {
            throw new \DomainException('unsupported_type');
        }

        foreach (
            ['script1cond', 'script1id', 'script1code', 'script1flag1', 'script1flag2',
            'script2cond', 'script2id', 'script2code', 'script2flag1', 'script2flag2',
            'script2flag3', 'script2flag4', 'script2flag5',
            'script3cond', 'script3id', 'script3code', 'script3msg'] as $key
        ) {
            if (!empty($element[$key])) {
                throw new \DomainException('unsupported_scripts');
            }
        }
        foreach (['data3', 'class1', 'class2'] as $key) {
            if (isset($element[$key]) && (string) $element[$key] !== '') {
                throw new \DomainException('unsupported_options');
            }
        }
        if ($type !== 'Select List' && isset($element['data2']) && (string) $element['data2'] !== '') {
            throw new \DomainException('unsupported_options');
        }
        if ($type !== 'Text' && !empty($element['mailbackfile'])) {
            throw new \DomainException('unsupported_options');
        }
        if (!in_array($type, ['Text', 'Select List'], true) && !empty($element['mailback'])) {
            throw new \DomainException('unsupported_options');
        }
        if (($type === 'Textarea' || $type === 'Hidden Input') && !empty($element['flag1'])) {
            throw new \DomainException('unsupported_options');
        }
        // The meaning of flag2 differs between historical editors and types;
        // readonly in QuickMode does not preserve disabled-field submission semantics.
        if (!empty($element['flag2'])) {
            throw new \DomainException('unsupported_options');
        }
        if (str_contains((string) ($element['data1'] ?? ''), '<?php')) {
            throw new \DomainException('unsupported_options');
        }

        $id = 'bfLegacyElement' . $number;
        $label = (string) ($element['title'] ?? '');
        $properties = [
            'type' => 'element', 'bfType' => $types[$type], 'label' => $label,
            'labelPosition' => 'left', 'bfName' => (string) $element['name'], 'dbId' => 0,
            'orderNumber' => -1, 'tabIndex' => -1, 'logging' => (bool) ($element['logging'] ?? true),
            'hideLabel' => $type === 'Hidden Input', 'required' => false, 'hint' => '', 'off' => false,
            'readonly' => (bool) ($element['flag2'] ?? false), 'value' => (string) ($element['data1'] ?? ''),
            'validationCondition' => 0, 'validationId' => 0, 'validationCode' => '',
            'validationMessage' => '',
            'validationFunctionName' => '', 'initCondition' => 0, 'initId' => 0, 'initCode' => '',
            'initFunctionName' => '', 'initFormEntry' => 0, 'initPageEntry' => 0, 'actionCondition' => 0,
            'actionId' => 0, 'actionCode' => '', 'actionFunctionName' => '', 'actionClick' => 0,
            'actionBlur' => 0, 'actionChange' => 0, 'actionFocus' => 0,
            'actionSelect' => 0, 'hideInMailback' => false,
        ];
        if ($type === 'Text') {
            $properties += ['placeholder' => '', 'maxLength' => '', 'size' => '',
                'password' => (bool) ($element['flag1'] ?? false),
                'mailback' => (bool) ($element['mailback'] ?? false), 'mailbackAsSender' => false,
                'mailbackfile' => (string) ($element['mailbackfile'] ?? '')];
        } elseif ($type === 'Textarea') {
            $properties += ['placeholder' => '', 'is_html' => false, 'width' => '', 'height' => '',
                'maxlength' => 0, 'showMaxlengthCounter' => true];
        } elseif ($type === 'Checkbox') {
            $properties += ['checked' => (bool) ($element['flag1'] ?? false),
                'mailbackAccept' => false, 'mailbackConnectWith' => ''];
        } elseif ($type === 'Select List') {
            $size = (string) ($element['data1'] ?? '');
            if ($size !== '' && $size !== '1') {
                throw new \DomainException('unsupported_options');
            }
            $properties += ['list' => $this->convertSelectOptions((string) ($element['data2'] ?? '')),
                'multiple' => (bool) ($element['flag1'] ?? false),
                'mailback' => (bool) ($element['mailback'] ?? false), 'width' => '', 'height' => ''];
        }

        return ['attributes' => ['class' => 'bfQuickModeElementClass', 'id' => $id,
            'mdata' => '{"deletable":true,"type":"element"}'],
            'data' => ['title' => $label], 'properties' => $properties];
    }

    private function convertSelectOptions(string $source): string
    {
        if ($source === '' || str_contains($source, '<?php')) {
            throw new \DomainException('unsupported_options');
        }

        $options = [];
        foreach (preg_split('/\r\n|\r|\n/', $source) ?: [] as $line) {
            if (trim($line) === '') {
                continue;
            }
            $parts = explode(';', $line);
            // Classic accepts one and two columns whereas EasyMode ignores them.
            if (count($parts) !== 3) {
                throw new \DomainException('unsupported_options');
            }
            [$selected, $label, $value] = $parts;
            // Empty values and quoted empty values differ between the two modes.
            if ($value === '' || $value === '""' || $value === "''") {
                throw new \DomainException('unsupported_options');
            }
            if (!in_array($selected, ['0', '1'], true) || $value !== trim($value)) {
                throw new \DomainException('unsupported_options');
            }
            $options[] = $selected . ';' . trim($label) . ';' . $value;
        }
        if ($options === []) {
            throw new \DomainException('unsupported_options');
        }
        return implode("\n", $options);
    }
}
