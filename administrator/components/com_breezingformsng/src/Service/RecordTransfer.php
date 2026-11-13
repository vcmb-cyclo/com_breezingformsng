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

namespace Vcmb\Component\BreezingformsNG\Administrator\Service;

\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Vcmb\Component\BreezingformsNG\Administrator\Model\RecordModel;

/**
 * Copies records of a form to another form, field by field. A record is copied
 * rather than moved: what ContentBuilder NG keeps about a record (articles,
 * list states) is attached to the form and record it was created for.
 */
final class RecordTransfer
{
    public function __construct(
        private readonly DatabaseInterface $db,
        private readonly RecordModel $model,
    ) {
    }

    /**
     * The icon of each type of field, those of the QuickMode tree of the
     * form screen (file names in media/com_breezingformsng/images/quickmode).
     */
    private const ICONS = [
        'Text' => 'icon_text-field.png',
        'Number Input' => 'icon_text-field.png',
        'Textarea' => 'icon_text-area.png',
        'Radio Group' => 'icon_radio.png',
        'Radio Button' => 'icon_radio.png',
        'Signature' => 'icon_sign.png',
        'Checkbox' => 'icon_check-box.png',
        'Checkbox Group' => 'icon_check-box.png',
        'Select List' => 'icon_select.png',
        'Query List' => 'icon_select.png',
        'File Upload' => 'icon_file.png',
        'Regular Button' => 'icon_submit-button.png',
        'Hidden Input' => 'icon_hidden-input.png',
        'Captcha' => 'icon_captcha.png',
        'ReCaptcha' => 'icon_captcha.png',
        'Calendar' => 'icon_calendar.png',
        'PayPal' => 'icon_paypal.png',
        'Summarize' => 'icon_summarize.png',
        'Stripe' => 'icon_stripe.png',
    ];

    /**
     * @return string|null The icon file of a type of field, null when it has none.
     */
    public static function iconFor(string $type): ?string
    {
        return self::ICONS[$type] ?? null;
    }

    /**
     * The mapping to start from: each field of the target form takes the field
     * of the source form with the same name, ignoring the case.
     *
     * @param list<array{id: int|string, name: string}> $sourceElements
     * @param list<array{id: int|string, name: string}> $targetElements
     *
     * @return array<int, int> Source element id by target element id, 0 for none.
     */
    public static function suggestMapping(array $sourceElements, array $targetElements): array
    {
        $byName = [];

        foreach ($sourceElements as $element) {
            $byName[strtolower((string) $element['name'])] ??= (int) $element['id'];
        }

        $map = [];

        foreach ($targetElements as $element) {
            $map[(int) $element['id']] = $byName[strtolower((string) $element['name'])] ?? 0;
        }

        return $map;
    }

    /**
     * Keeps of a submitted mapping only the fields that exist in the target
     * form, and in the source form for the source side, and the default values
     * of the target fields that have no source field.
     *
     * @param array<mixed>                              $map             Source element id by target element id.
     * @param array<mixed>                              $defaults        Default value by target element id.
     * @param list<array{id: int|string, name: string}> $sourceElements
     * @param list<array{id: int|string, name: string}> $targetElements
     *
     * @return array{map: array<int, int>, defaults: array<int, string>}
     */
    public static function normalizeMapping(array $map, array $defaults, array $sourceElements, array $targetElements): array
    {
        $sourceIds = array_map(static fn (array $element): int => (int) $element['id'], $sourceElements);
        $cleanMap = [];
        $cleanDefaults = [];

        foreach ($targetElements as $element) {
            $targetId = (int) $element['id'];
            $sourceId = (int) ($map[$targetId] ?? 0);
            $default = $defaults[$targetId] ?? '';

            $cleanMap[$targetId] = in_array($sourceId, $sourceIds, true) ? $sourceId : 0;
            // A default value only fills a field that no source field fills.
            $cleanDefaults[$targetId] = $cleanMap[$targetId] === 0 && is_scalar($default) ? (string) $default : '';
        }

        return ['map' => $cleanMap, 'defaults' => $cleanDefaults];
    }

    /**
     * What the QuickMode template of a form says of its fields: whether each
     * is required and the value it starts with.
     *
     * @return array<int, array{required: bool, value: string}> By element id; empty for a form
     *                                                         without QuickMode template.
     */
    public static function fieldsFromTemplate(string $templateCode): array
    {
        $tree = json_decode((string) base64_decode($templateCode, true), true);

        if (!is_array($tree)) {
            return [];
        }

        $fields = [];
        $walk = static function (array $node) use (&$walk, &$fields): void {
            $properties = $node['properties'] ?? null;

            if (is_array($properties) && ($properties['type'] ?? '') === 'element' && (int) ($properties['dbId'] ?? 0) > 0) {
                $value = $properties['value'] ?? '';
                $fields[(int) $properties['dbId']] = [
                    'required' => filter_var($properties['required'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    'value' => is_scalar($value) ? (string) $value : '',
                ];
            }

            foreach ((array) ($node['children'] ?? []) as $child) {
                if (is_array($child)) {
                    $walk($child);
                }
            }
        };
        $walk($tree);

        return $fields;
    }

    /**
     * The required fields that get a value from neither a source field nor a
     * default value.
     *
     * @param array<int, int>    $map
     * @param array<int, string> $defaults
     * @param int[]              $requiredIds
     *
     * @return list<int>
     */
    public static function missingRequired(array $map, array $defaults, array $requiredIds): array
    {
        return array_values(array_filter(
            $requiredIds,
            static fn (int $id): bool => ($map[$id] ?? 0) === 0 && preg_match('/^\s*$/u', $defaults[$id] ?? '') === 1
        ));
    }

    /**
     * What a target field receives: the value of its source field, or its
     * default value when it has no source field.
     *
     * @param array<int, string> $sourceValues The values of the record, by source element id.
     */
    public static function composeValue(int $sourceId, array $sourceValues, string $default): string
    {
        return $sourceId > 0 ? ($sourceValues[$sourceId] ?? '') : $default;
    }

    /**
     * Copies the records, all or none.
     *
     * @param int[]                  $recordIds
     * @param array<int, int>        $map       Source element id by target element id, 0 for none.
     * @param array<int, string>     $defaults  Default value by target element id.
     *
     * @return array<int, int> The id of the copy by id of the source record.
     */
    public function transfer(
        int $sourceFormId,
        int $targetFormId,
        array $recordIds,
        array $map,
        array $defaults,
        bool $preservePayment = false,
        string $operation = ''
    ): array {
        $db = $this->db;
        $recordIds = array_values(array_unique(array_filter(array_map('intval', $recordIds))));

        if (
            $recordIds === [] || $sourceFormId < 0 || $targetFormId < 1 || $sourceFormId === $targetFormId
            || !preg_match('/\A[0-9a-f]{32}\z/', $operation)
        ) {
            throw new \InvalidArgumentException('Invalid transfer.');
        }

        $targetForm = $db->setQuery(
            $db->createQuery()
                ->select($db->quoteName(['title', 'name']))
                ->from($db->quoteName('#__facileforms_forms'))
                ->where($db->quoteName('id') . ' = :formId')
                ->bind(':formId', $targetFormId, ParameterType::INTEGER)
        )->loadObject();

        if ($targetForm === null) {
            throw new \InvalidArgumentException('The target form does not exist.');
        }

        $loadSources = fn (): array => $db->setQuery(
            $db->createQuery()
                ->select('*')
                ->from($db->quoteName('#__facileforms_records'))
                ->where($db->quoteName('form') . ' = :formId')
                ->whereIn($db->quoteName('id'), $recordIds, ParameterType::INTEGER)
                ->order($db->quoteName('id'))
                ->bind(':formId', $sourceFormId, ParameterType::INTEGER)
        )->loadObjectList('id');
        $sources = $loadSources();

        // A record that left the source form meanwhile must not be copied under
        // the mapping chosen for another form.
        if (count($sources) !== count($recordIds)) {
            throw new \RuntimeException('Some records are not in the source form.');
        }

        $sourceExists = $this->formExists($sourceFormId);
        $targetElements = $this->model->getEditableElements($targetFormId);
        $created = [];

        $db->transactionStart();

        try {
            // The source records are locked, then read again: a record deleted, moved to another
            // form or changed since the check above cannot be copied as it was found. The ids are
            // integers, cast above.
            $db->setQuery(
                'SELECT ' . $db->quoteName('id') . ' FROM ' . $db->quoteName('#__facileforms_records')
                . ' WHERE ' . $db->quoteName('id') . ' IN (' . implode(',', $recordIds) . ') FOR UPDATE'
            )->execute();
            $sources = $loadSources();

            if (count($sources) !== count($recordIds)) {
                throw new \RuntimeException('Some records are not in the source form.');
            }

            // A unique operation is committed atomically with its copies. Concurrent
            // requests with stale session data cannot claim it a second time.
            $claim = $db->createQuery()
                ->insert((string) $db->quoteName('#__facileforms_transfer_operations'))
                ->columns((string) $db->quoteName('operation'))
                ->values(':operation')
                ->bind(':operation', $operation);
            $db->setQuery($claim)->execute();

            foreach ($sources as $sourceId => $source) {
                $copy = new \stdClass();
                foreach (
                    [
                    'submitted' => $source->submitted,
                    'form' => $targetFormId,
                    'title' => (string) $targetForm->title,
                    'name' => (string) $targetForm->name,
                    'ip' => $source->ip,
                    'browser' => $source->browser,
                    'opsys' => $source->opsys,
                    'provider' => $source->provider,
                    'viewed' => $source->viewed,
                    'exported' => $source->exported,
                    'state' => $source->state,
                    'user_id' => $source->user_id,
                    'username' => $source->username,
                    'user_full_name' => $source->user_full_name,
                    'opted' => $source->opted,
                    'opt_ip' => $source->opt_ip,
                    'opt_date' => $source->opt_date,
                    ] as $column => $value
                ) {
                    $copy->$column = $value;
                }

                if ($preservePayment) {
                    $copy->paypal_tx_id = $source->paypal_tx_id;
                    $copy->paypal_payment_date = $source->paypal_payment_date;
                    $copy->paypal_testaccount = $source->paypal_testaccount;
                    $copy->paypal_download_tries = $source->paypal_download_tries;
                }

                $db->insertObject('#__facileforms_records', $copy, 'id');
                $copyId = $copy instanceof \stdClass ? (int) $copy->id : 0;

                $values = $sourceExists
                    ? $this->valuesOfForm((int) $sourceId, $sourceFormId, (string) $source->name)
                    : $this->valuesOfDeletedForm((int) $sourceId);

                foreach ($targetElements as $element) {
                    $targetId = (int) $element['id'];
                    $value = self::composeValue($map[$targetId] ?? 0, $values, $defaults[$targetId] ?? '');

                    if ($value !== '') {
                        $this->model->saveElementValue($copyId, $element, $value);
                    }
                }

                $created[(int) $sourceId] = $copyId;
            }

            $db->transactionCommit();
        } catch (\Throwable $exception) {
            $db->transactionRollback();
            throw $exception;
        }

        return $created;
    }

    /**
     * The values of a record, by element id of the form it was submitted with.
     *
     * @return array<int, string>
     */
    public function valuesOfRecord(int $recordId, int $formId, string $recordName): array
    {
        return $this->formExists($formId)
            ? $this->valuesOfForm($recordId, $formId, $recordName)
            : $this->valuesOfDeletedForm($recordId);
    }

    private function formExists(int $formId): bool
    {
        $db = $this->db;

        return (int) $db->setQuery(
            $db->createQuery()
                ->select('COUNT(*)')
                ->from($db->quoteName('#__facileforms_forms'))
                ->where($db->quoteName('id') . ' = :formId')
                ->bind(':formId', $formId, ParameterType::INTEGER)
        )->loadResult() > 0;
    }

    /**
     * @return array<int, string> The value of each element of the form.
     */
    private function valuesOfForm(int $recordId, int $formId, string $recordName): array
    {
        $values = [];

        foreach ($this->model->getEditableRows($recordId, $formId, $recordName) as $row) {
            $values[$row['element_id']] = $row['value'];
        }

        return $values;
    }

    /**
     * A deleted form has no elements to read the record by: the values are
     * those stored under the element ids the record was submitted with.
     *
     * @return array<int, string>
     */
    private function valuesOfDeletedForm(int $recordId): array
    {
        $db = $this->db;
        $rows = $db->setQuery(
            $db->createQuery()
                ->select($db->quoteName(['element', 'value']))
                ->from($db->quoteName('#__facileforms_subrecords'))
                ->where($db->quoteName('record') . ' = :recordId')
                ->order($db->quoteName('id'))
                ->bind(':recordId', $recordId, ParameterType::INTEGER)
        )->loadAssocList();
        $values = [];

        foreach ($rows as $row) {
            $values[(int) $row['element']][] = (string) $row['value'];
        }

        return array_map(static fn (array $lines): string => implode("\n", $lines), $values);
    }
}
