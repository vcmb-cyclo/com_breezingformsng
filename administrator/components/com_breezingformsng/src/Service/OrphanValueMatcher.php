<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Administrator\Service;

\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;

/**
 * Finds, for the stored values of a deleted element, the current element of the
 * same form that carries the same name, so the values can be reassigned to it.
 *
 * A target is only proposed when its name is unique within its form, so that
 * the match is never ambiguous, and when its type is compatible with the type of
 * the stored values: the types holding one plain value (text, hidden input, text
 * area, calendar, number) are interchangeable, every other type only matches
 * itself, so a multi-valued field is never moved to a single-valued one.
 */
final class OrphanValueMatcher
{
    /** Types holding one plain value: a value of one can be moved to a field of another. */
    private const SCALAR_TYPES = ['Text', 'Hidden Input', 'Textarea', 'Calendar', 'Number Input'];

    /**
     * The compatibility class of a field type.
     */
    public static function typeClass(string $type): string
    {
        return in_array($type, self::SCALAR_TYPES, true) ? 'scalar' : $type;
    }

    /**
     * SQL expression of the compatibility class of a type column, to compare two
     * columns (or one column with typeClass() of a known type).
     */
    public static function typeClassSql(DatabaseInterface $db, string $column): string
    {
        $scalar = implode(', ', array_map([$db, 'quote'], self::SCALAR_TYPES));

        return 'CASE WHEN ' . $db->quoteName($column) . ' IN (' . $scalar . ') THEN ' . $db->quote('scalar')
            . ' ELSE ' . $db->quoteName($column) . ' END';
    }

    /**
     * Base query, aliases: s = orphan value, r = its record, e = target
     * element, x = a value already stored for the target on the same record
     * (x.id IS NULL means the target is free for that record).
     *
     * @param ?list<int> $elementIds Restrict to these missing elements.
     */
    public static function query(DatabaseInterface $db, ?array $elementIds = null): QueryInterface
    {
        $query = $db->createQuery()
            ->from($db->quoteName('#__facileforms_subrecords', 's'))
            ->join('INNER', $db->quoteName('#__facileforms_records', 'r') . ' ON r.id = s.record')
            ->join('LEFT', $db->quoteName('#__facileforms_elements', 'o') . ' ON o.id = s.element')
            ->join('INNER', $db->quoteName('#__facileforms_elements', 'e') . ' ON e.form = r.form AND e.name = s.name AND '
                    . self::typeClassSql($db, 'e.type') . ' = ' . self::typeClassSql($db, 's.type'))
            ->join(
                'INNER',
                '(SELECT form, name FROM ' . $db->quoteName('#__facileforms_elements')
                    . ' GROUP BY form, name HAVING COUNT(*) = 1) AS u ON u.form = e.form AND u.name = e.name'
            )
            ->join('LEFT', $db->quoteName('#__facileforms_subrecords', 'x') . ' ON x.record = s.record AND x.element = e.id')
            ->where('o.id IS NULL')
            ->where($db->quoteName('s.name') . ' <> ' . $db->quote(''));

        if ($elementIds !== null) {
            $query->whereIn($db->quoteName('s.element'), array_map('intval', $elementIds));
        }

        return $query;
    }

    /**
     * Fields the values of missing elements could be moved to: published fields
     * of the form of the records whose type is compatible with the stored values.
     *
     * @param list<int> $elementIds Missing elements.
     *
     * @return array<int, list<array{id: int, name: string, title: string, form: int, form_title: string}>>
     */
    public static function candidates(DatabaseInterface $db, array $elementIds): array
    {
        if ($elementIds === []) {
            return [];
        }

        $query = $db->createQuery()
            ->select([
                'DISTINCT ' . $db->quoteName('s.element', 'old_id'),
                $db->quoteName('e.id', 'id'),
                $db->quoteName('e.name', 'name'),
                $db->quoteName('e.title', 'title'),
                $db->quoteName('e.form', 'form'),
                $db->quoteName('f.title', 'form_title'),
                $db->quoteName('e.ordering', 'ordering'),
            ])
            ->from($db->quoteName('#__facileforms_subrecords', 's'))
            ->join('INNER', $db->quoteName('#__facileforms_records', 'r') . ' ON r.id = s.record')
            ->join('LEFT', $db->quoteName('#__facileforms_elements', 'o') . ' ON o.id = s.element')
            ->join('INNER', $db->quoteName('#__facileforms_elements', 'e') . ' ON e.form = r.form AND '
                    . self::typeClassSql($db, 'e.type') . ' = ' . self::typeClassSql($db, 's.type'))
            ->join('LEFT', $db->quoteName('#__facileforms_forms', 'f') . ' ON f.id = e.form')
            ->where('o.id IS NULL')
            ->where($db->quoteName('e.published') . ' = 1')
            ->where($db->quoteName('s.type') . ' <> ' . $db->quote(''))
            ->whereIn($db->quoteName('s.element'), array_map('intval', $elementIds))
            ->whereNotIn(
                $db->quoteName('e.name'),
                ['bfFakeName', 'bfFakeName2', 'bfFakeName3', 'bfFakeName4', 'bfFakeName5'],
                ParameterType::STRING
            )
            // Alphabetical (the collation ignores case and accents): by form title, then by element name.
            ->order(
                $db->quoteName('f.title') . ' ASC, ' . $db->quoteName('e.form') . ' ASC, '
                . $db->quoteName('e.name') . ' ASC, ' . $db->quoteName('e.id') . ' ASC'
            );
        $result = [];

        foreach ($db->setQuery($query)->loadAssocList() as $row) {
            $result[(int) $row['old_id']][] = [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
                'title' => (string) $row['title'],
                'form' => (int) $row['form'],
                'form_title' => (string) $row['form_title'],
            ];
        }

        return $result;
    }
}
