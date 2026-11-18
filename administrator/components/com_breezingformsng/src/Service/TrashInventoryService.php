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

/**
 * What the trash holds besides the submitted records: forms, scripts, pieces and
 * Integrator rules whose published state is -2, with the list that shows them.
 */
final class TrashInventoryService
{
    /** The trashed state of an item of the lists (the Joomla value). */
    public const TRASHED = -2;

    /** Language key of the type => [table, list view]. */
    public const TYPES = [
        'COM_BREEZINGFORMSNG_MANAGEFORMS' => ['#__facileforms_forms', 'forms'],
        'COM_BREEZINGFORMSNG_MANAGESCRIPTS' => ['#__facileforms_scripts', 'scripts'],
        'COM_BREEZINGFORMSNG_MANAGEPIECES' => ['#__facileforms_pieces', 'pieces'],
        'COM_BREEZINGFORMSNG_INTEGRATOR' => ['#__facileforms_integrator_rules', 'integrator'],
    ];

    public function __construct(private readonly DatabaseInterface $db)
    {
    }

    /**
     * Only the types that have something in the trash.
     *
     * @return list<array{label: string, view: string, count: int}>
     */
    public function getTrashedItems(): array
    {
        $items = [];

        foreach (self::TYPES as $label => [$table, $view]) {
            $trashed = self::TRASHED;
            $query = $this->db->createQuery()
                ->select('COUNT(*)')
                ->from($this->db->quoteName($table))
                ->where($this->db->quoteName('published') . ' = :trashed')
                ->bind(':trashed', $trashed, \Joomla\Database\ParameterType::INTEGER);
            $count = (int) $this->db->setQuery($query)->loadResult();

            if ($count > 0) {
                $items[] = ['label' => $label, 'view' => $view, 'count' => $count];
            }
        }

        return $items;
    }
}
