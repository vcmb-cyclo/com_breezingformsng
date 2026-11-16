<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Administrator\Service;

\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\QueryInterface;

/**
 * Finds, for the stored values of a deleted element, the current element of the
 * same form that carries the same name, so the values can be reassigned to it.
 *
 * A target is only proposed when its name is unique within its form, so that
 * the match is never ambiguous.
 */
final class OrphanValueMatcher
{
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
            ->join('INNER', $db->quoteName('#__facileforms_elements', 'e') . ' ON e.form = r.form AND e.name = s.name')
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
}
