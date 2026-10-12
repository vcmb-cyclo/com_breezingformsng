<?php

/**
 * BreezingForms NG - A Joomla Forms Application
 *
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 **/

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Site\Service\Rendering;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/**
 * Loads the latest published editable record for a user and form: archived
 * and trashed records are left alone.
 */
final class EditableRecordLoader
{
    public function __construct(private readonly DatabaseInterface $database)
    {
    }

    public function load(int $formId, int $userId): ?EditableRecord
    {
        $query = $this->database->getQuery(true)
            ->select(['id', 'form'])
            ->from($this->database->quoteName('#__facileforms_records'))
            ->where($this->database->quoteName('form') . ' = :formValue')
            ->where($this->database->quoteName('user_id') . ' = :userId')
            ->where($this->database->quoteName('user_id') . ' <> 0')
            ->where($this->database->quoteName('state') . ' = 1')
            ->order($this->database->quoteName('id') . ' DESC')
            ->bind(':formValue', $formId, ParameterType::INTEGER)
            ->bind(':userId', $userId, ParameterType::INTEGER);
        $this->database->setQuery($query, 0, 1);
        $records = $this->database->loadObjectList();

        if ($records === []) {
            return null;
        }

        $recordId = (int) $records[0]->id;
        $subrecordsQuery = $this->database->getQuery(true)
            ->select('*')
            ->from($this->database->quoteName('#__facileforms_subrecords'))
            ->where($this->database->quoteName('record') . ' = :recordId')
            ->bind(':recordId', $recordId, ParameterType::INTEGER);
        $this->database->setQuery($subrecordsQuery);

        return new EditableRecord($recordId, $this->database->loadObjectList());
    }
}
