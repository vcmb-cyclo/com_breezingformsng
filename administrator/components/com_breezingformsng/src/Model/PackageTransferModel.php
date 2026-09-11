<?php
/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

final class PackageTransferModel extends BaseDatabaseModel
{
    private const FORMAT = 'breezingformsng-package';
    private const VERSION = 1;
    private const LIBRARY_COLUMNS = ['published', 'package', 'name', 'title', 'description', 'type', 'code', 'unit_tests'];

    public function getPackages(): array
    {
        $db = $this->getDatabase();
        $packages = [];

        foreach (['#__facileforms_forms', '#__facileforms_scripts', '#__facileforms_pieces'] as $table) {
            $query = $db->getQuery(true)
                ->select('DISTINCT ' . $db->quoteName('package'))
                ->from($db->quoteName($table))
                ->where($db->quoteName('package') . ' != ' . $db->quote(''));
            $packages = array_merge($packages, array_column($db->setQuery($query)->loadAssocList() ?: [], 'package'));
        }

        $packages = array_values(array_unique(array_filter($packages, 'is_string')));
        sort($packages, SORT_NATURAL | SORT_FLAG_CASE);

        return $packages;
    }

    public function export(string $package): string
    {
        if ($package === '') {
            throw new \RuntimeException(Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_PACKAGE_REQUIRED'));
        }

        $db = $this->getDatabase();
        $forms = $this->loadPackageRows('#__facileforms_forms', $package);
        $formIds = array_map(static fn(array $form): int => (int) $form['id'], $forms);
        $elements = $formIds === [] ? [] : $this->loadElements($formIds);
        $elementsByForm = [];

        foreach ($elements as $element) {
            $elementsByForm[(int) $element['form']][] = $this->without($element, ['id', 'form']);
        }

        $payloadForms = [];
        foreach ($forms as $form) {
            $sourceId = (int) $form['id'];
            $payloadForms[] = [
                'source_id' => $sourceId,
                'data' => $this->without($form, ['id', 'created', 'created_by', 'modified', 'modified_by']),
                'elements' => $elementsByForm[$sourceId] ?? [],
            ];
        }

        $payload = [
            'format' => self::FORMAT,
            'version' => self::VERSION,
            'package' => $package,
            'exported_at' => (new \Joomla\CMS\Date\Date())->toSql(),
            'scripts' => array_map(fn(array $row): array => $this->libraryExportRow($row), $this->loadPackageRows('#__facileforms_scripts', $package)),
            'pieces' => array_map(fn(array $row): array => $this->libraryExportRow($row), $this->loadPackageRows('#__facileforms_pieces', $package)),
            'forms' => $payloadForms,
        ];
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        return $json . "\n";
    }

    public function import(string $filename): void
    {
        $contents = file_get_contents($filename);
        if ($contents === false) {
            throw new \RuntimeException(Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_FILE_READ_ERROR'));
        }

        try {
            $payload = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \RuntimeException(Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_INVALID_FILE'), 0, $exception);
        }

        if (!is_array($payload) || ($payload['format'] ?? '') !== self::FORMAT || ($payload['version'] ?? null) !== self::VERSION || !is_string($payload['package'] ?? null) || $payload['package'] === '') {
            throw new \RuntimeException(Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_INVALID_FILE'));
        }

        $db = $this->getDatabase();
        $package = $payload['package'];
        $db->transactionStart();

        try {
            $scriptIds = $this->importLibraries('#__facileforms_scripts', (array) ($payload['scripts'] ?? []), $package);
            $pieceIds = $this->importLibraries('#__facileforms_pieces', (array) ($payload['pieces'] ?? []), $package);
            $this->importForms((array) ($payload['forms'] ?? []), $package, $scriptIds, $pieceIds);
            $db->transactionCommit();
        } catch (\Throwable $exception) {
            $db->transactionRollback();
            throw $exception;
        }
    }

    private function importLibraries(string $table, array $rows, string $package): array
    {
        $db = $this->getDatabase();
        $sourceIds = [];

        foreach ($rows as $row) {
            if (!is_array($row) || !is_array($row['data'] ?? null) || !is_int($row['source_id'] ?? null)) {
                throw new \RuntimeException(Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_INVALID_FILE'));
            }
            $data = $this->only($row['data'], self::LIBRARY_COLUMNS);
            $data['package'] = $package;
            $name = trim((string) ($data['name'] ?? ''));
            if ($name === '') {
                throw new \RuntimeException(Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_INVALID_FILE'));
            }
            $id = $this->findId($table, $package, $name);
            if ($id > 0) {
                $data['id'] = $id;
                $db->updateObject($table, (object) $data, 'id');
            } else {
                $db->insertObject($table, (object) $data, 'id');
                $id = (int) $db->insertid();
            }
            $sourceIds[$row['source_id']] = $id;
        }

        return $sourceIds;
    }

    private function importForms(array $forms, string $package, array $scriptIds, array $pieceIds): void
    {
        $db = $this->getDatabase();
        $formColumns = $this->tableColumns('#__facileforms_forms');
        $elementColumns = $this->tableColumns('#__facileforms_elements');

        foreach ($forms as $form) {
            if (!is_array($form) || !is_array($form['data'] ?? null) || !is_array($form['elements'] ?? null)) {
                throw new \RuntimeException(Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_INVALID_FILE'));
            }
            $data = $this->only($form['data'], $formColumns, ['id', 'created', 'created_by', 'modified', 'modified_by']);
            $data['package'] = $package;
            $this->remapReferences($data, $scriptIds, $pieceIds);
            $name = trim((string) ($data['name'] ?? ''));
            if ($name === '') {
                throw new \RuntimeException(Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_INVALID_FILE'));
            }
            $id = $this->findId('#__facileforms_forms', $package, $name);
            if ($id > 0) {
                $data['id'] = $id;
                $db->updateObject('#__facileforms_forms', (object) $data, 'id');
                $query = $db->getQuery(true)->delete($db->quoteName('#__facileforms_elements'))->where($db->quoteName('form') . ' = ' . $id);
                $db->setQuery($query)->execute();
            } else {
                $db->insertObject('#__facileforms_forms', (object) $data, 'id');
                $id = (int) $db->insertid();
            }

            foreach ($form['elements'] as $element) {
                if (!is_array($element)) {
                    throw new \RuntimeException(Text::_('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_INVALID_FILE'));
                }
                $elementData = $this->only($element, $elementColumns, ['id', 'form']);
                $elementData['form'] = $id;
                $this->remapReferences($elementData, $scriptIds, $pieceIds);
                $db->insertObject('#__facileforms_elements', (object) $elementData, 'id');
            }
        }
    }

    private function remapReferences(array &$data, array $scriptIds, array $pieceIds): void
    {
        foreach ($data as $column => $value) {
            if (preg_match('/^script[1-3]id$/', $column) === 1 && isset($scriptIds[(int) $value])) {
                $data[$column] = $scriptIds[(int) $value];
            }
            if (preg_match('/^piece[1-4]id$/', $column) === 1 && isset($pieceIds[(int) $value])) {
                $data[$column] = $pieceIds[(int) $value];
            }
        }
    }

    private function loadPackageRows(string $table, string $package): array
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)->select('*')->from($db->quoteName($table))->where($db->quoteName('package') . ' = :package')->order($db->quoteName('id'));
        $query->bind(':package', $package);
        return $db->setQuery($query)->loadAssocList() ?: [];
    }

    private function loadElements(array $formIds): array
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)->select('*')->from($db->quoteName('#__facileforms_elements'))->whereIn($db->quoteName('form'), $formIds)->order([$db->quoteName('form'), $db->quoteName('ordering'), $db->quoteName('id')]);
        return $db->setQuery($query)->loadAssocList() ?: [];
    }

    private function findId(string $table, string $package, string $name): int
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)->select($db->quoteName('id'))->from($db->quoteName($table))->where($db->quoteName('package') . ' = :package')->where($db->quoteName('name') . ' = :name')->order($db->quoteName('id'));
        $query->bind(':package', $package)->bind(':name', $name);
        return (int) $db->setQuery($query, 0, 1)->loadResult();
    }

    private function libraryExportRow(array $row): array
    {
        return ['source_id' => (int) $row['id'], 'data' => $this->only($row, self::LIBRARY_COLUMNS)];
    }

    private function tableColumns(string $table): array
    {
        return array_keys($this->getDatabase()->getTableColumns($table));
    }

    private function only(array $data, array $allowed, array $excluded = []): array
    {
        return array_diff_key(array_intersect_key($data, array_flip($allowed)), array_flip($excluded));
    }

    private function without(array $data, array $excluded): array
    {
        return array_diff_key($data, array_flip($excluded));
    }
}
