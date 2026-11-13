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

namespace Vcmb\Component\BreezingformsNG\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Vcmb\Component\BreezingformsNG\Administrator\Service\RecordTransfer;

/**
 * What the screens of the transfer of records to another form read, and the
 * transfer in progress, which the session keeps from one screen to the next.
 */
class RecordstransferModel extends BaseDatabaseModel
{
    public const STATE_KEY = 'com_breezingformsng.recordstransfer';

    /**
     * @return array<string, mixed> Empty when no transfer is in progress.
     */
    public function getTransfer(): array
    {
        return (array) $this->getApplication()->getUserState(self::STATE_KEY, []);
    }

    /**
     * @param array<string, mixed>|null $transfer
     */
    public function setTransfer(?array $transfer): void
    {
        $this->getApplication()->setUserState(self::STATE_KEY, $transfer);
    }

    /**
     * @param int[] $ids
     *
     * @return list<\stdClass> The records that exist, with the title of their form
     *                         (form_id is null for a deleted form).
     */
    public function getRecords(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));

        if ($ids === []) {
            return [];
        }

        $db = $this->getDatabase();
        $query = $db->createQuery()
            ->select([
                'records.id', 'records.form', 'records.name', 'records.submitted', 'records.ip', 'records.state',
                'records.username', 'records.user_full_name',
                'COALESCE(forms.title, records.title) AS form_title',
                'forms.id AS form_id',
            ])
            ->from($db->quoteName('#__facileforms_records', 'records'))
            ->join('LEFT', $db->quoteName('#__facileforms_forms', 'forms') . ' ON forms.id = records.form')
            ->whereIn($db->quoteName('records.id'), $ids, ParameterType::INTEGER)
            ->order($db->quoteName('records.id'));

        return array_values($db->setQuery($query)->loadObjectList() ?: []);
    }

    /**
     * @return list<\stdClass> id and title of every form.
     */
    public function getForms(): array
    {
        $db = $this->getDatabase();
        $query = $db->createQuery()
            ->select($db->quoteName(['id', 'title']))
            ->from($db->quoteName('#__facileforms_forms'))
            ->order($db->quoteName('title'));

        return array_values($db->setQuery($query)->loadObjectList() ?: []);
    }

    /**
     * @return list<array{id: int|string, name: string, title: string, type: string}>
     */
    public function getElements(int $formId): array
    {
        return $this->getRecordModel()->getEditableElements($formId);
    }

    /**
     * @return array<int, array{required: bool, value: string}> The required flag and the starting
     *                                                         value of the fields of a form, by element id.
     */
    public function getTargetFields(int $formId): array
    {
        $db = $this->getDatabase();
        $query = $db->createQuery()
            ->select($db->quoteName('template_code'))
            ->from($db->quoteName('#__facileforms_forms'))
            ->where($db->quoteName('id') . ' = :formId')
            ->bind(':formId', $formId, ParameterType::INTEGER);

        return RecordTransfer::fieldsFromTemplate((string) $db->setQuery($query)->loadResult());
    }

    /**
     * @return array<int, string> The values of a record, by element id of its form.
     */
    public function getRecordValues(int $recordId, int $formId, string $recordName): array
    {
        return (new RecordTransfer($this->getDatabase(), $this->getRecordModel()))
            ->valuesOfRecord($recordId, $formId, $recordName);
    }

    public function formExists(int $formId): bool
    {
        $db = $this->getDatabase();
        $query = $db->createQuery()
            ->select('COUNT(*)')
            ->from($db->quoteName('#__facileforms_forms'))
            ->where($db->quoteName('id') . ' = :formId')
            ->bind(':formId', $formId, ParameterType::INTEGER);

        return (int) $db->setQuery($query)->loadResult() > 0;
    }

    /**
     * The fields the records come from. A deleted form has no elements any
     * more: the records still hold the id, name, title and type of each field
     * they were submitted with.
     *
     * @param int[] $recordIds
     *
     * @return list<array{id: int|string, name: string, title: string, type: string}>
     */
    public function getSourceElements(int $formId, array $recordIds): array
    {
        if ($this->formExists($formId)) {
            return $this->getElements($formId);
        }

        $recordIds = array_values(array_unique(array_filter(array_map('intval', $recordIds))));

        if ($recordIds === []) {
            return [];
        }

        $db = $this->getDatabase();
        $query = $db->createQuery()
            ->select([
                $db->quoteName('element', 'id'),
                'MIN(' . $db->quoteName('name') . ') AS ' . $db->quoteName('name'),
                'MIN(' . $db->quoteName('title') . ') AS ' . $db->quoteName('title'),
                'MIN(' . $db->quoteName('type') . ') AS ' . $db->quoteName('type'),
            ])
            ->from($db->quoteName('#__facileforms_subrecords'))
            ->whereIn($db->quoteName('record'), $recordIds, ParameterType::INTEGER)
            ->group($db->quoteName('element'))
            ->order($db->quoteName('element'));

        return array_values($db->setQuery($query)->loadAssocList() ?: []);
    }

    public function getConnection(): DatabaseInterface
    {
        return $this->getDatabase();
    }

    public function getRecordModel(): RecordModel
    {
        $model = $this->getMVCFactory()->createModel('Record', 'Administrator', ['ignore_request' => true]);

        if (!$model instanceof RecordModel) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }

        return $model;
    }

    private function getApplication(): CMSApplication
    {
        $application = Factory::getApplication();

        if (!$application instanceof CMSApplication) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }

        return $application;
    }
}
