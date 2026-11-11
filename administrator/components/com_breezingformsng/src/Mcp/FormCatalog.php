<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Administrator\Mcp;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/**
 * Reads the forms and their elements for the MCP tools.
 *
 * Only the columns listed here are read: a form also holds the credentials of
 * its integrations (Mailchimp, Salesforce, Dropbox) and the addresses its
 * notifications go to, which never leave the site.
 */
final class FormCatalog
{
    /** Texts longer than this are cut, and said to be. */
    public const MAX_TEXT = 2000;

    private const FORM_COLUMNS = ['id', 'name', 'title', 'description', 'published', 'package', 'pages', 'created', 'modified'];

    private const FORM_SETTINGS = ['emailntf', 'emaillog', 'emailxml', 'dblog', 'double_opt', 'prevmode', 'width', 'widthmode', 'height', 'heightmode', 'autoheight'];

    private const FORM_CODE = [
        'script1cond', 'script1id', 'script1code', 'script2cond', 'script2id', 'script2code',
        'piece1cond', 'piece1id', 'piece1code', 'piece2cond', 'piece2id', 'piece2code',
        'piece3cond', 'piece3id', 'piece3code', 'piece4cond', 'piece4id', 'piece4code',
    ];

    private const ELEMENT_COLUMNS = ['id', 'name', 'title', 'type', 'page', 'ordering', 'published', 'logging', 'flag1', 'flag2', 'data1', 'data2', 'data3', 'mailback'];

    private const ELEMENT_CODE = [
        'script1cond', 'script1id', 'script1code', 'script2cond', 'script2id', 'script2code',
        'script3cond', 'script3id', 'script3code', 'script3msg',
    ];

    public function __construct(private readonly DatabaseInterface $db)
    {
    }

    /**
     * @return array{total: int, forms: list<array<string, mixed>>}
     */
    public function listForms(string $search, ?bool $published, int $limit, int $offset): array
    {
        $db = $this->db;
        $query = $db->createQuery()->from($db->quoteName('#__facileforms_forms', 'f'));
        if ($search !== '') {
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search) . '%';
            $query->where('(' . $db->quoteName('f.name') . ' LIKE :search1 OR ' . $db->quoteName('f.title') . ' LIKE :search2)')
                ->bind(':search1', $like)
                ->bind(':search2', $like);
        }
        if ($published !== null) {
            $state = $published ? 1 : 0;
            $query->where($db->quoteName('f.published') . ' = :published')->bind(':published', $state, ParameterType::INTEGER);
        }

        $count = clone $query;
        $total = (int) $db->setQuery($count->select('COUNT(*)'))->loadResult();

        $columns = array_map(static fn (string $column): string => 'f.' . $column, ['id', 'name', 'title', 'published', 'package', 'pages', 'modified']);
        $query->select($db->quoteName($columns))
            ->select('(CASE WHEN ' . $db->quoteName('f.template_code') . " <> '' THEN 1 ELSE 0 END) AS " . $db->quoteName('quickmode'))
            ->order($db->quoteName('f.ordering') . ', ' . $db->quoteName('f.id'));
        $query->setLimit($limit, $offset);

        $rows = $db->setQuery($query)->loadAssocList();
        $ids = array_values(array_map(static fn (array $row): int => (int) $row['id'], $rows));
        $elements = $this->countBy('#__facileforms_elements', $ids);
        $records = $this->countBy('#__facileforms_records', $ids);

        $forms = [];
        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $forms[] = [
                'id' => $id,
                'name' => (string) $row['name'],
                'title' => (string) $row['title'],
                'published' => (int) $row['published'] === 1,
                'package' => (string) $row['package'],
                'pages' => (int) $row['pages'],
                'editor' => (int) $row['quickmode'] === 1 ? 'quickmode' : 'classic',
                'element_count' => $elements[$id] ?? 0,
                'record_count' => $records[$id] ?? 0,
                'modified' => $row['modified'] === null ? null : (string) $row['modified'],
            ];
        }

        return ['total' => $total, 'forms' => $forms];
    }

    /**
     * @return array<string, mixed>|null The form, or null when there is none of this id or name.
     */
    public function getForm(?int $id, ?string $name, bool $withCode): ?array
    {
        $db = $this->db;
        $columns = [...self::FORM_COLUMNS, ...self::FORM_SETTINGS, ...($withCode ? self::FORM_CODE : [])];
        $query = $db->createQuery()
            ->select($db->quoteName($columns))
            ->select('(CASE WHEN ' . $db->quoteName('template_code') . " <> '' THEN 1 ELSE 0 END) AS " . $db->quoteName('quickmode'))
            ->select('(CASE WHEN ' . $db->quoteName('emailadr') . " <> '' THEN 1 ELSE 0 END) AS " . $db->quoteName('has_notification_address'))
            ->from($db->quoteName('#__facileforms_forms'));
        if ($id !== null) {
            $query->where($db->quoteName('id') . ' = :id')->bind(':id', $id, ParameterType::INTEGER);
        } else {
            $query->where($db->quoteName('name') . ' = :name')->bind(':name', $name);
        }
        // Names are unique only within a package. Never return an arbitrary
        // form if the same name identifies several forms on the site.
        $query->setLimit($id === null ? 2 : 1);
        $rows = $db->setQuery($query)->loadAssocList();
        if ($id === null && count($rows) > 1) {
            throw new ToolException(Text::_('COM_BREEZINGFORMSNG_MCP_FORM_NAME_AMBIGUOUS'));
        }
        $row = $rows[0] ?? null;
        if (!is_array($row)) {
            return null;
        }

        $form = [
            'id' => (int) $row['id'],
            'name' => (string) $row['name'],
            'title' => (string) $row['title'],
            'description' => self::text($row['description']),
            'published' => (int) $row['published'] === 1,
            'package' => (string) $row['package'],
            'pages' => (int) $row['pages'],
            'editor' => (int) $row['quickmode'] === 1 ? 'quickmode' : 'classic',
            'created' => $row['created'] === null ? null : (string) $row['created'],
            'modified' => $row['modified'] === null ? null : (string) $row['modified'],
            'settings' => [],
            'record_count' => $this->countBy('#__facileforms_records', [(int) $row['id']])[(int) $row['id']] ?? 0,
        ];
        foreach (self::FORM_SETTINGS as $setting) {
            $form['settings'][$setting] = $row[$setting] === null ? null : (int) $row[$setting];
        }
        $form['settings']['notification_address_set'] = (int) $row['has_notification_address'] === 1;
        if ($withCode) {
            $form['code'] = self::code($row, self::FORM_CODE);
        }
        $form['elements'] = $this->elements((int) $row['id'], $withCode);

        return $form;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function elements(int $formId, bool $withCode): array
    {
        $db = $this->db;
        $query = $db->createQuery()
            ->select($db->quoteName([...self::ELEMENT_COLUMNS, ...($withCode ? self::ELEMENT_CODE : [])]))
            ->from($db->quoteName('#__facileforms_elements'))
            ->where($db->quoteName('form') . ' = :form')
            ->bind(':form', $formId, ParameterType::INTEGER)
            ->order($db->quoteName('page') . ', ' . $db->quoteName('ordering') . ', ' . $db->quoteName('id'));

        $elements = [];
        foreach ($db->setQuery($query)->loadAssocList() as $row) {
            $element = [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
                'title' => (string) $row['title'],
                'type' => (string) $row['type'],
                'page' => (int) $row['page'],
                'ordering' => (int) $row['ordering'],
                'published' => (int) $row['published'] === 1,
                'logging' => (int) $row['logging'] === 1,
                'flag1' => (int) $row['flag1'],
                'flag2' => (int) $row['flag2'],
                'data1' => self::text($row['data1']),
                'data2' => self::text($row['data2']),
                'data3' => self::text($row['data3']),
                'mailback' => (int) $row['mailback'] === 1,
            ];
            if ($withCode) {
                $element['code'] = self::code($row, self::ELEMENT_CODE);
            }
            $elements[] = $element;
        }

        return $elements;
    }

    /**
     * @param list<int> $formIds
     * @return array<int, int>
     */
    private function countBy(string $table, array $formIds): array
    {
        if ($formIds === []) {
            return [];
        }
        $db = $this->db;
        $query = $db->createQuery()
            ->select([$db->quoteName('form'), 'COUNT(*) AS ' . $db->quoteName('total')])
            ->from($db->quoteName($table))
            ->whereIn($db->quoteName('form'), $formIds)
            ->group($db->quoteName('form'));

        $counts = [];
        foreach ($db->setQuery($query)->loadAssocList() as $row) {
            $counts[(int) $row['form']] = (int) $row['total'];
        }

        return $counts;
    }

    /**
     * @param array<string, mixed> $row
     * @param list<string>         $columns
     * @return array<string, mixed>
     */
    private static function code(array $row, array $columns): array
    {
        $code = [];
        foreach ($columns as $column) {
            $value = $row[$column];
            $code[$column] = str_ends_with($column, 'code') || $column === 'script3msg' ? (string) $value : ($value === null ? null : (int) $value);
        }

        return $code;
    }

    /** A text of the database, cut to MAX_TEXT characters with a marker. */
    public static function text(mixed $value): string
    {
        $text = (string) $value;

        return mb_strlen($text) > self::MAX_TEXT ? mb_substr($text, 0, self::MAX_TEXT) . ' [truncated]' : $text;
    }
}
