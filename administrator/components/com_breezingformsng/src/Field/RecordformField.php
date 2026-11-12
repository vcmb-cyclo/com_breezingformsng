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

namespace Vcmb\Component\BreezingformsNG\Administrator\Field;

\defined('_JEXEC') or die;

use Joomla\CMS\Form\Field\GroupedlistField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

/**
 * Form filter of the records list: the existing forms, then the deleted forms
 * that still have records, named after the title the records kept.
 */
final class RecordformField extends GroupedlistField
{
    protected $type = 'Recordform';

    /** Prefix of a deleted form: a native select option holds text only. */
    private const DELETED_MARK = "\u{26A0}\u{FE0F} ";

    /**
     * @return array<array<mixed>> The options by group, as GroupedlistField::getGroups() returns them.
     */
    protected function getGroups()
    {
        $groups = parent::getGroups();
        $db = $this->getDatabase();

        $query = $db->createQuery()
            ->select($db->quoteName(['id', 'title']))
            ->from($db->quoteName('#__facileforms_forms'))
            ->order($db->quoteName('title') . ' ASC');

        foreach ($db->setQuery($query)->loadObjectList() as $form) {
            $groups[0][] = HTMLHelper::_('select.option', (string) $form->id, (string) $form->title);
        }

        // The records keep the id, title and name of their form.
        $query = $db->createQuery()
            ->select([
                $db->quoteName('records.form', 'id'),
                'MAX(' . $db->quoteName('records.title') . ') AS ' . $db->quoteName('title'),
                'MAX(' . $db->quoteName('records.name') . ') AS ' . $db->quoteName('name'),
            ])
            ->from($db->quoteName('#__facileforms_records', 'records'))
            ->join(
                'LEFT',
                $db->quoteName('#__facileforms_forms', 'forms'),
                $db->quoteName('forms.id') . ' = ' . $db->quoteName('records.form')
            )
            ->where($db->quoteName('forms.id') . ' IS NULL')
            ->group($db->quoteName('records.form'))
            ->order($db->quoteName('title') . ' ASC');

        $deleted = [];

        foreach ($db->setQuery($query)->loadObjectList() as $form) {
            $label = trim((string) $form->title) ?: trim((string) $form->name) ?: '#' . (int) $form->id;
            $deleted[] = HTMLHelper::_('select.option', (string) (int) $form->id, self::DELETED_MARK . $label);
        }

        if ($deleted !== []) {
            $groups[Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_RECORDS_WITHOUT_FORM')] = $deleted;
        }

        return $groups;
    }
}
