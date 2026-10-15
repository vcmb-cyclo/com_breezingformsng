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

use Joomla\CMS\Date\Date;
use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;
use Vcmb\Component\BreezingformsNG\Administrator\Model\RecordModel;

/**
 * Imports the records of a CSV file into a form. The file has the layout the
 * CSV export writes: the fixed columns of a record, then one column per form
 * element, named after the element.
 */
final class RecordImporter
{
    public function __construct(
        private readonly DatabaseInterface $db,
        private readonly RecordModel $model,
        private readonly User $user,
    ) {
    }

    /**
     * @return int The number of records imported.
     */
    public function import(int $formId, string $file, string $encoding = '0'): int
    {
        if ($formId < 1 || !is_file($file) || !is_readable($file)) {
            return 0;
        }

        $db = $this->db;
        $db->setQuery(
            $db->createQuery()
                ->select($db->quoteName(['title', 'name']))
                ->from($db->quoteName('#__facileforms_forms'))
                ->where($db->quoteName('id') . ' = ' . $formId)
        );
        $form = $db->loadObject();

        if ($form === null) {
            return 0;
        }

        $handle = fopen($file, 'rb');

        if ($handle === false) {
            return 0;
        }

        if ($encoding !== '0') {
            $content = stream_get_contents($handle);
            fclose($handle);
            $converted = iconv($encoding, 'UTF-8//TRANSLIT', $content === false ? '' : $content);

            if ($converted === false) {
                throw new \RuntimeException('Unable to convert CSV input to UTF-8.');
            }

            $handle = fopen('php://temp', 'w+b');
            fwrite($handle, $converted);
            rewind($handle);
        }

        $config = $this->model->getExportConfig();
        $delimiter = stripslashes((string) $config->csvdelimiter);
        $enclosure = stripslashes((string) $config->csvquote);
        $delimiter = strlen($delimiter) === 1 ? $delimiter : ';';
        $enclosure = strlen($enclosure) === 1 ? $enclosure : '"';
        $header = fgetcsv($handle, null, $delimiter, $enclosure, '');

        if (!is_array($header) || count($header) < 2) {
            fclose($handle);
            return 0;
        }

        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]);
        $header = array_map(static fn ($value): string => trim((string) $value), $header);
        $normalizedHeader = array_map('strtolower', $header);
        $elements = $this->model->getEditableElements($formId);
        $elementsByName = [];

        foreach ($elements as $element) {
            $elementsByName[(string) $element['name']] = $element;
            $elementsByName[strtolower((string) $element['name'])] = $element;
        }

        $fixedHeaders = [
            'id', 'submitted', 'form', 'name', 'bf_form_name', 'title', 'bf_form_title',
            'ip', 'browser', 'opsys', 'provider', 'viewed', 'exported', 'state', 'archived',
            'user_id', 'username', 'user_full_name', 'paypal_tx_id', 'paypal_payment_date',
            'paypal_testaccount', 'paypal_download_tries', 'double_opt_in', 'opted',
        ];
        $identity = $this->user;
        $valueAt = static function (array $row, array $keys, string $key, mixed $default = ''): mixed {
            $index = array_search($key, $keys, true);
            return $index === false ? $default : ($row[$index] ?? $default);
        };
        $decodeCell = static fn (mixed $value): string => (int) $config->cellnewline === 0
            ? (string) $value
            : str_replace('\\n', "\n", (string) $value);
        $imported = 0;
        $db->transactionStart();

        try {
            while (($row = fgetcsv($handle, null, $delimiter, $enclosure, '')) !== false) {
                if ($row === [null] || !array_filter($row, static fn ($value): bool => trim((string) $value) !== '')) {
                    continue;
                }

                $submitted = trim((string) $valueAt($row, $normalizedHeader, 'submitted', ''));
                $paymentDate = trim((string) $valueAt($row, $normalizedHeader, 'paypal_payment_date', ''));
                $record = (object) [
                    'submitted' => preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $submitted)
                        ? $submitted
                        : (new Date())->toSql(),
                    'form' => $formId,
                    'title' => $decodeCell($valueAt($row, $normalizedHeader, 'bf_form_title', $form->title)),
                    'name' => (string) $form->name,
                    'ip' => (string) $valueAt($row, $normalizedHeader, 'ip', ''),
                    'browser' => $decodeCell($valueAt($row, $normalizedHeader, 'browser', '')),
                    'opsys' => (string) $valueAt($row, $normalizedHeader, 'opsys', ''),
                    'provider' => (string) $valueAt($row, $normalizedHeader, 'provider', ''),
                    'viewed' => (int) (bool) $valueAt($row, $normalizedHeader, 'viewed', 0),
                    'exported' => (int) (bool) $valueAt($row, $normalizedHeader, 'exported', 0),
                    'state' => RecordState::fromImport(
                        $valueAt($row, $normalizedHeader, 'state', null),
                        $valueAt($row, $normalizedHeader, 'archived', null)
                    ),
                    'user_id' => (int) $valueAt($row, $normalizedHeader, 'user_id', $identity->id),
                    'username' => (string) $valueAt($row, $normalizedHeader, 'username', $identity->username),
                    'user_full_name' => (string) $valueAt($row, $normalizedHeader, 'user_full_name', $identity->name),
                    'paypal_tx_id' => (string) $valueAt($row, $normalizedHeader, 'paypal_tx_id', ''),
                    'paypal_payment_date' => preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $paymentDate)
                        ? $paymentDate
                        : '1970-01-01 00:00:00',
                    'paypal_testaccount' => (int) (bool) $valueAt($row, $normalizedHeader, 'paypal_testaccount', 0),
                    'paypal_download_tries' => (int) $valueAt($row, $normalizedHeader, 'paypal_download_tries', 0),
                    'opted' => (int) (bool) $valueAt(
                        $row,
                        $normalizedHeader,
                        'double_opt_in',
                        $valueAt($row, $normalizedHeader, 'opted', 0)
                    ),
                ];
                $db->insertObject('#__facileforms_records', $record, 'id');
                $recordId = (int) $record->id;

                foreach ($header as $index => $fieldName) {
                    if (in_array($normalizedHeader[$index], $fixedHeaders, true)) {
                        continue;
                    }

                    $element = $elementsByName[$fieldName] ?? $elementsByName[strtolower($fieldName)] ?? null;

                    if ($element === null) {
                        continue;
                    }

                    $value = $decodeCell($row[$index] ?? '');
                    if (in_array((string) $element['type'], ['Checkbox', 'Checkbox Group', 'Select List'], true)) {
                        $value = str_replace('|', "\n", $value);
                    }
                    $this->model->saveElementValue($recordId, $element, $value);
                }

                $imported++;
            }

            $db->transactionCommit();
        } catch (\Throwable $exception) {
            $db->transactionRollback();
            throw $exception;
        } finally {
            fclose($handle);
        }

        return $imported;
    }
}
