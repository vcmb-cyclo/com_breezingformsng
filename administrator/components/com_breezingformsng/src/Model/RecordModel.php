<?php
/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\Utilities\ArrayHelper;
use Vcmb\Component\BreezingformsNG\Administrator\Service\RecordState;
use Vcmb\Component\BreezingformsNG\Site\Service\Integration\ContentBuilderRecordCleanup;

class RecordModel extends AdminModel
{
    private const BULK_ID_BATCH_SIZE = 500;

    /**
     * Form group and field name prefix of the submitted values: the field of
     * element 12 is element.e12.
     */
    private const ELEMENT_GROUP = 'element';
    private const ELEMENT_PREFIX = 'e';

    /** @var array<int, \stdClass> */
    private array $items = [];

    public function getDatabaseConnection(): DatabaseInterface
    {
        return $this->getDatabase();
    }

    /**
     * The record with the title and name of its form, and its submitted
     * values keyed by form field name.
     */
    public function getItem($pk = null)
    {
        $pk = (int) ($pk ?: $this->getState($this->getName() . '.id'));

        if (isset($this->items[$pk])) {
            return $this->items[$pk];
        }

        $item = $pk > 0 ? parent::getItem($pk) : false;

        if ($item === false) {
            return false;
        }

        $db = $this->getDatabase();
        $formId = (int) $item->form;
        $query = $db->getQuery(true)
            ->select($db->quoteName(['title', 'name']))
            ->from($db->quoteName('#__facileforms_forms'))
            ->where($db->quoteName('id') . ' = :formId')
            ->bind(':formId', $formId, ParameterType::INTEGER);
        $form = $db->setQuery($query)->loadObject();

        $item->form_title = (string) ($form->title ?? '');
        $item->form_name = (string) ($form->name ?? '');
        $item->{self::ELEMENT_GROUP} = [];

        foreach ($this->getEditableRows($pk, $formId, (string) $item->name) as $row) {
            $item->{self::ELEMENT_GROUP}[self::ELEMENT_PREFIX . $row['element_id']] = $row['value'];
        }

        return $this->items[$pk] = $item;
    }

    public function getEditableElements(int $formId): array
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select(['id', 'title', 'name', 'type'])
            ->from($db->quoteName('#__facileforms_elements'))
            ->where($db->quoteName('published') . ' = 1')
            ->whereNotIn($db->quoteName('name'), ['bfFakeName', 'bfFakeName2', 'bfFakeName3', 'bfFakeName4', 'bfFakeName5'], ParameterType::STRING)
            ->where($db->quoteName('form') . ' = :formId')
            ->order($db->quoteName('ordering'))
            ->bind(':formId', $formId, ParameterType::INTEGER);
        $db->setQuery($query);
        return $db->loadAssocList();
    }

    public function getEditableRows(int $recordId, int $formId, string $recordName): array
    {
        $elements = $this->getEditableElements($formId);
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select(['id', 'record', 'element', 'title', 'name', 'type', 'value'])
            ->from($db->quoteName('#__facileforms_subrecords'))
            ->where($db->quoteName('record') . ' = :recordId')
            ->order($db->quoteName('id'))
            ->bind(':recordId', $recordId, ParameterType::INTEGER);
        $db->setQuery($query);
        $subrecords = $db->loadAssocList();

        $byElement = [];
        $byName = [];
        foreach ($subrecords as $sub) {
            $byElement[(int) $sub['element']][] = $sub;
            $byName[(string) $sub['name']][] = $sub;
        }

        $rows = [];
        foreach ($elements as $element) {
            $elementId = (int) $element['id'];
            $name = (string) $element['name'];
            $matches = $byElement[$elementId] ?? $byName[$name] ?? [];
            $values = array_map(fn($m) => (string) $m['value'], $matches);

            if (!count($values) && $name === 'Formulaire') {
                $values[] = $recordName;
            }

            $rows[] = [
                'element_id' => $elementId,
                'title'      => (string) $element['title'],
                'name'       => $name,
                'type'       => (string) $element['type'],
                'value'      => implode("\n", $values),
            ];
        }

        return $rows;
    }

    private function saveRecord(int $recordId, array $values, \DateTimeZone $timezone): void
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName('form'))
            ->from($db->quoteName('#__facileforms_records'))
            ->where($db->quoteName('id') . ' = :recordId')
            ->bind(':recordId', $recordId, ParameterType::INTEGER);
        $db->setQuery($query);
        $formId = (int) $db->loadResult();

        if ($formId < 1) {
            return;
        }

        foreach ($this->getEditableElements($formId) as $element) {
            $elementId = (int) $element['id'];
            if (!array_key_exists($elementId, $values)) {
                continue;
            }
            $this->saveElementValue($recordId, $element, (string) $values[$elementId]);
        }

        $user = $this->getCurrentUser();
        $now  = (new \Joomla\CMS\Date\Date('now', $timezone))->format('Y-m-d H:i:s', true);
        $username = (string) $user->username;
        $userId = (int) $user->id;
        $query = $db->getQuery(true)
            ->update($db->quoteName('#__facileforms_records'))
            ->set($db->quoteName('modified') . ' = :now')
            ->set($db->quoteName('modified_by') . ' = :username')
            ->set($db->quoteName('modified_user_id') . ' = :userId')
            ->where($db->quoteName('id') . ' = :recordId')
            ->bind(':now', $now, ParameterType::STRING)
            ->bind(':username', $username, ParameterType::STRING)
            ->bind(':userId', $userId, ParameterType::INTEGER)
            ->bind(':recordId', $recordId, ParameterType::INTEGER);
        $db->setQuery($query)->execute();
    }

    private function saveElementValue(int $recordId, array $element, string $value): void
    {
        $db = $this->getDatabase();
        $elementId = (int) $element['id'];
        $name = (string) $element['name'];
        $title = (string) $element['title'];
        $type = (string) $element['type'];

        $query = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__facileforms_subrecords'))
            ->where($db->quoteName('record') . ' = :recordId')
            ->extendWhere('AND', [
                $db->quoteName('element') . ' = :elementId',
                $db->quoteName('name') . ' = :name',
            ], 'OR')
            ->order($db->quoteName('id'))
            ->bind(':recordId', $recordId, ParameterType::INTEGER)
            ->bind(':elementId', $elementId, ParameterType::INTEGER)
            ->bind(':name', $name, ParameterType::STRING);
        $db->setQuery($query);
        $subrecordIds = array_map('intval', $db->loadColumn());
        $values = $this->splitValue($value, $type) ?: [''];

        foreach ($subrecordIds as $index => $subrecordId) {
            if (!array_key_exists($index, $values)) {
                break;
            }
            $rowValue = $values[$index];
            $updateQuery = $db->getQuery(true)
                ->update($db->quoteName('#__facileforms_subrecords'))
                ->set($db->quoteName('element') . ' = :elementId')
                ->set($db->quoteName('title') . ' = :title')
                ->set($db->quoteName('name') . ' = :name')
                ->set($db->quoteName('type') . ' = :type')
                ->set($db->quoteName('value') . ' = :value')
                ->where($db->quoteName('id') . ' = :subrecordId')
                ->where($db->quoteName('record') . ' = :recordId')
                ->bind(':elementId', $elementId, ParameterType::INTEGER)
                ->bind(':title', $title, ParameterType::STRING)
                ->bind(':name', $name, ParameterType::STRING)
                ->bind(':type', $type, ParameterType::STRING)
                ->bind(':value', $rowValue, ParameterType::STRING)
                ->bind(':subrecordId', $subrecordId, ParameterType::INTEGER)
                ->bind(':recordId', $recordId, ParameterType::INTEGER);
            $db->setQuery($updateQuery)->execute();
        }

        for ($i = count($subrecordIds); $i < count($values); $i++) {
            if ($values[$i] === '') {
                continue;
            }
            $rowValue = $values[$i];
            $insertQuery = $db->getQuery(true)
                ->insert($db->quoteName('#__facileforms_subrecords'))
                ->columns($db->quoteName(['record', 'element', 'title', 'name', 'type', 'value']))
                ->values(':recordId, :elementId, :title, :name, :type, :value')
                ->bind(':recordId', $recordId, ParameterType::INTEGER)
                ->bind(':elementId', $elementId, ParameterType::INTEGER)
                ->bind(':title', $title, ParameterType::STRING)
                ->bind(':name', $name, ParameterType::STRING)
                ->bind(':type', $type, ParameterType::STRING)
                ->bind(':value', $rowValue, ParameterType::STRING);
            $db->setQuery($insertQuery)->execute();
        }
    }

    private function splitValue(string $value, string $type): array
    {
        $value = str_replace("\r", '', $value);
        if (!in_array($type, ['Checkbox', 'Checkbox Group', 'Select List'], true)) {
            return [$value];
        }
        return array_values(array_map(
            'trim',
            str_contains($value, "\n") ? explode("\n", $value) : explode(', ', $value)
        ));
    }

    public function getTable($name = 'Record', $prefix = 'Site', $options = [])
    {
        return parent::getTable($name, $prefix, $options);
    }

    public function getForm($data = [], $loadData = true)
    {
        // The fields depend on the record: keep the id of the submitted one.
        if ((int) ($data['id'] ?? 0) > 0) {
            $this->setState($this->getName() . '.id', (int) $data['id']);
        }

        return $this->loadForm('com_breezingformsng.record', 'record', ['control' => 'jform', 'load_data' => $loadData]);
    }

    protected function loadFormData()
    {
        $data = $this->getApplication()->getUserState('com_breezingformsng.edit.record.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        $this->preprocessData('com_breezingformsng.record', $data);

        return $data;
    }

    /**
     * Adds one field per editable element of the form the record belongs to.
     */
    protected function preprocessForm(Form $form, $data, $group = 'content')
    {
        $record = (array) $data;
        $recordId = (int) ($record['id'] ?? 0) ?: (int) $this->getState($this->getName() . '.id');
        $formId = $this->getFormIds([$recordId])[$recordId] ?? 0;
        $values = (array) ($record[self::ELEMENT_GROUP] ?? []);

        $xml = new \SimpleXMLElement('<form><fields name="' . self::ELEMENT_GROUP . '"><fieldset name="values"/></fields></form>');
        $fieldset = $xml->fields->fieldset;

        foreach ($this->getEditableElements($formId) as $element) {
            $name = self::ELEMENT_PREFIX . (int) $element['id'];
            $lines = substr_count((string) ($values[$name] ?? ''), "\n");

            $field = $fieldset->addChild('field');
            $field->addAttribute('name', $name);
            $field->addAttribute('type', 'textarea');
            $field->addAttribute('label', (string) $element['title']);
            $field->addAttribute('description', (string) $element['name']);
            $field->addAttribute('translateLabel', 'false');
            $field->addAttribute('translateDescription', 'false');
            $field->addAttribute('rows', (string) ($lines > 0 ? min(10, $lines + 2) : 1));
            $field->addAttribute('filter', 'raw');
        }

        $form->load($xml);

        parent::preprocessForm($form, $data, $group);
    }

    /**
     * Saves the submitted values of an existing record.
     */
    public function save($data)
    {
        $recordId = (int) ($data['id'] ?? 0);

        if ($recordId < 1 || !isset($this->getFormIds([$recordId])[$recordId])) {
            $this->setError(Text::_('JLIB_APPLICATION_ERROR_NOT_EXIST'));

            return false;
        }

        $values = [];

        foreach ((array) ($data[self::ELEMENT_GROUP] ?? []) as $name => $value) {
            $values[(int) substr((string) $name, strlen(self::ELEMENT_PREFIX))] = (string) $value;
        }

        $timezone = new \DateTimeZone((string) $this->getApplication()->get('offset', 'UTC'));
        $this->saveRecord($recordId, $values, $timezone);
        unset($this->items[$recordId]);
        $this->setState($this->getName() . '.id', $recordId);

        return true;
    }

    private function getApplication(): CMSApplication
    {
        $application = Factory::getApplication();

        if (!$application instanceof CMSApplication) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }

        return $application;
    }

    /**
     * As in Joomla content, a record is deleted for good only from the trash.
     */
    protected function canDelete($record)
    {
        return (int) $record->state === RecordState::TRASHED && parent::canDelete($record);
    }

    public function publish(&$pks, $value = 1)
    {
        if (!RecordState::isValid((int) $value)) {
            throw new \InvalidArgumentException(Text::_('JLIB_APPLICATION_ERROR_EDITSTATE_NOT_PERMITTED'));
        }

        return parent::publish($pks, $value);
    }

    /**
     * Deletes trashed records, then what ContentBuilder NG keeps about them.
     * The selected records are locked, then checked as a whole before anything
     * is deleted, and everything is undone when a step fails or when a
     * selected record is still there afterwards: $pks ends up holding the ids
     * actually deleted.
     */
    public function delete(&$pks)
    {
        $ids = array_values(array_unique(ArrayHelper::toInteger((array) $pks)));
        $pks = [];
        $db = $this->getDatabase();
        $db->transactionStart();

        try {
            $this->lockRecords($ids);
            $records = $this->getFormsAndStates($ids);
            $refusal = RecordState::deletionRefusal($ids, array_column($records, 'state', 'id'));

            if ($refusal === null && !$this->getCurrentUser()->authorise('core.delete', $this->option)) {
                $refusal = 'JLIB_APPLICATION_ERROR_DELETE_NOT_PERMITTED';
            }

            if ($refusal !== null) {
                throw new \RuntimeException(Text::_($refusal));
            }

            if (!parent::delete($ids)) {
                throw new \RuntimeException((string) $this->getError());
            }

            // Joomla stops without an error at an id it no longer finds, as
            // when someone else deleted it meanwhile: the records after it are
            // then still there, and their ContentBuilder NG data has to stay.
            if ($this->getFormsAndStates($ids) !== []) {
                throw new \RuntimeException(Text::_('JLIB_APPLICATION_ERROR_NOT_EXIST'));
            }

            $idsByForm = [];

            foreach ($records as $record) {
                $idsByForm[$record['form']][] = $record['id'];
            }

            $cleanup = new ContentBuilderRecordCleanup($db, $this->getApplication(), $this->getCacheControllerFactory());

            foreach ($idsByForm as $formId => $recordIds) {
                $cleanup->remove($formId, $recordIds);
            }

            $db->transactionCommit();
        } catch (\Throwable $exception) {
            $db->transactionRollback();
            $this->setError($exception->getMessage());

            return false;
        }

        $pks = $ids;

        return true;
    }

    /**
     * Empties the whole trash. Returns the ids actually deleted.
     *
     * @return int[]
     */
    public function emptyTrash(): array
    {
        $db = $this->getDatabase();
        $trashed = RecordState::TRASHED;
        $query = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__facileforms_records'))
            ->where($db->quoteName('state') . ' = :trashed')
            ->bind(':trashed', $trashed, ParameterType::INTEGER);
        $trashedIds = array_map('intval', $db->setQuery($query)->loadColumn());

        if ($trashedIds !== [] && !$this->delete($trashedIds)) {
            throw new \RuntimeException((string) $this->getError());
        }

        return $trashedIds;
    }

    /**
     * Locks the records until the end of the transaction, with an update that
     * changes nothing: they are then read as they are now, and a change made
     * by someone else, such as restoring one from the trash, waits for the end.
     *
     * @param int[] $ids
     */
    private function lockRecords(array $ids): void
    {
        if ($ids === []) {
            return;
        }

        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->update($db->quoteName('#__facileforms_records'))
            ->set($db->quoteName('state') . ' = ' . $db->quoteName('state'))
            ->whereIn($db->quoteName('id'), $ids, ParameterType::INTEGER);
        $db->setQuery($query)->execute();
    }

    /**
     * @param int[] $ids
     *
     * @return list<array{id: int, form: int, state: int}> The records that exist.
     */
    private function getFormsAndStates(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName(['id', 'form', 'state']))
            ->from($db->quoteName('#__facileforms_records'))
            ->whereIn($db->quoteName('id'), $ids, ParameterType::INTEGER);

        return array_map(
            static fn (array $row): array => ['id' => (int) $row['id'], 'form' => (int) $row['form'], 'state' => (int) $row['state']],
            $db->setQuery($query)->loadAssocList()
        );
    }

    /**
     * @param int[] $ids
     *
     * @return array<int, int> Form id by record id.
     */
    private function getFormIds(array $ids): array
    {
        return array_column($this->getFormsAndStates($ids), 'form', 'id');
    }

    public function setFlagsBatch(array $ids, string $column, int $value = 1): void
    {
        if (!in_array($column, ['viewed', 'exported'], true)) {
            return;
        }
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (!$ids) {
            return;
        }
        $db = $this->getDatabase();
        $flag = $value ? 1 : 0;
        $query = $db->getQuery(true)
            ->update($db->quoteName('#__facileforms_records'))
            ->set($db->quoteName($column) . ' = :flag')
            ->whereIn($db->quoteName('id'), $ids, ParameterType::INTEGER)
            ->bind(':flag', $flag, ParameterType::INTEGER);
        $db->setQuery($query)->execute();
    }

    public function setFlagSingle(int $recordId, string $column, int $value): void
    {
        $col = preg_replace('/^bfrecord_/', '', $column);
        if (!in_array($col, ['viewed', 'exported'], true)) {
            return;
        }
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->update($db->quoteName('#__facileforms_records'))
            ->set($db->quoteName($col) . ' = :value')
            ->where($db->quoteName('id') . ' = :recordId')
            ->bind(':value', $value, ParameterType::INTEGER)
            ->bind(':recordId', $recordId, ParameterType::INTEGER);
        $db->setQuery($query)->execute();
    }

    public function importCsv(int $formId, string $file, string $encoding = '0'): int
    {
        if ($formId < 1 || !is_file($file) || !is_readable($file)) {
            return 0;
        }

        $db = $this->getDatabase();
        $db->setQuery(
            $db->getQuery(true)
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

        $config = $this->getExportConfig();
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
        $elements = $this->getEditableElements($formId);
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
        $identity = $this->getCurrentUser();
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
                        : (new \Joomla\CMS\Date\Date())->toSql(),
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
                    $this->saveElementValue($recordId, $element, $value);
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

    public function getExportConfig(): \stdClass
    {
        $params = ComponentHelper::getParams('com_breezingformsng');

        $config = new \stdClass();
        $config->csvdelimiter = $params->get('csvdelimiter', ';');
        $config->csvquote     = $params->get('csvquote', '"');
        $config->cellnewline  = (int) $params->get('cellnewline', 1);
        $config->csvinverted  = false;

        return $config;
    }

    public function getSubrecords(int $recordId): array
    {
        return $this->getSubrecordsByRecordIds([$recordId])[$recordId] ?? [];
    }

    /**
     * Load subrecords for a bounded set of records in one query.
     *
     * @param list<int> $recordIds
     * @return array<int, list<object>>
     */
    public function getSubrecordsByRecordIds(array $recordIds): array
    {
        $recordIds = array_values(array_filter(
            array_map('intval', $recordIds),
            static fn (int $recordId): bool => $recordId > 0
        ));

        if ($recordIds === []) {
            return [];
        }

        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName('subs') . '.*')
            ->from($db->quoteName('#__facileforms_subrecords', 'subs'))
            ->join(
                'INNER',
                $db->quoteName('#__facileforms_elements', 'els')
                . ' ON ' . $db->quoteName('els.id') . ' = ' . $db->quoteName('subs.element')
            )
            ->whereIn($db->quoteName('subs.record'), $recordIds, ParameterType::INTEGER)
            ->order([
                $db->quoteName('subs.record'),
                $db->quoteName('els.ordering'),
                $db->quoteName('subs.id'),
            ]);
        $db->setQuery($query);
        $subrecordsByRecord = [];

        foreach ($db->loadObjectList() as $subrecord) {
            $subrecordsByRecord[(int) $subrecord->record][] = $subrecord;
        }

        return $subrecordsByRecord;
    }

    public function markExported(array $ids): void
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (!$ids) {
            return;
        }
        $db = $this->getDatabase();

        foreach (array_chunk($ids, self::BULK_ID_BATCH_SIZE) as $idBatch) {
            $query = $db->getQuery(true)
                ->update($db->quoteName('#__facileforms_records'))
                ->set($db->quoteName('exported') . ' = 1')
                ->whereIn($db->quoteName('id'), $idBatch, ParameterType::INTEGER);
            $db->setQuery($query)->execute();
        }
    }
}
