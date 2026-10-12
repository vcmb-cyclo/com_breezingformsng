<?php

declare(strict_types=1);

namespace Vcmb\Plugin\Privacy\Breezingformsng\Extension;

use Joomla\CMS\Event\Privacy\CanRemoveDataEvent;
use Joomla\CMS\Event\Privacy\ExportRequestEvent;
use Joomla\CMS\Event\Privacy\RemoveDataEvent;
use Joomla\Component\Privacy\Administrator\Plugin\PrivacyPlugin;
use Joomla\Component\Privacy\Administrator\Removal\Status;
use Joomla\Database\ParameterType;
use Joomla\Event\SubscriberInterface;

\defined('_JEXEC') or die;

/**
 * Privacy plugin for the form submissions stored by BreezingForms NG.
 *
 * Submissions are tied to a person through the account that sent them
 * (records.user_id). Guest submissions carry no reliable identifier and are
 * therefore outside the scope of export and removal requests.
 *
 * Removal either deletes the user's submissions or anonymises them: the rows
 * stay, emptied of every submitted value and identifier.
 */
final class Breezingformsng extends PrivacyPlugin implements SubscriberInterface
{
    /** Record columns identifying the person or their device. */
    public const IDENTITY_COLUMNS = [
        'ip' => '',
        'browser' => '',
        'opsys' => '',
        'provider' => '',
        'username' => '',
        'user_full_name' => '',
        'opt_ip' => '',
        'opt_token' => '',
        'paypal_tx_id' => '',
    ];

    public static function getSubscribedEvents(): array
    {
        return [
            'onPrivacyExportRequest' => 'onPrivacyExportRequest',
            'onPrivacyCanRemoveData' => 'onPrivacyCanRemoveData',
            'onPrivacyRemoveData' => 'onPrivacyRemoveData',
        ];
    }

    public function onPrivacyExportRequest(ExportRequestEvent $event): void
    {
        $user = $event->getUser();

        if (!$user) {
            return;
        }

        $db = $this->getDatabase();
        $userId = (int) $user->id;
        $records = $this->createDomain('breezingformsng_records', 'breezingformsng_form_submissions');
        $values = $this->createDomain('breezingformsng_values', 'breezingformsng_submitted_values');

        $query = $db->createQuery()
            ->select($db->quoteName([
                'id', 'submitted', 'modified', 'modified_by', 'form', 'title', 'name', 'ip', 'browser', 'opsys',
                'provider', 'user_id', 'username', 'user_full_name', 'paypal_tx_id', 'paypal_payment_date',
                'opted', 'opt_ip', 'opt_date',
            ]))
            ->from($db->quoteName('#__facileforms_records'))
            ->where($db->quoteName('user_id') . ' = :userId')
            ->order($db->quoteName('id') . ' ASC')
            ->bind(':userId', $userId, ParameterType::INTEGER);

        foreach ($db->setQuery($query)->loadAssocList() as $record) {
            $records->addItem($this->createItemFromArray($record, (int) $record['id']));
        }

        $query = $db->createQuery()
            ->select($db->quoteName(['value.id', 'value.record', 'value.title', 'value.name', 'value.value']))
            ->from($db->quoteName('#__facileforms_subrecords', 'value'))
            ->join(
                'INNER',
                $db->quoteName('#__facileforms_records', 'record'),
                $db->quoteName('record.id') . ' = ' . $db->quoteName('value.record')
            )
            ->where($db->quoteName('record.user_id') . ' = :userId')
            ->order($db->quoteName('value.id') . ' ASC')
            ->bind(':userId', $userId, ParameterType::INTEGER);

        foreach ($db->setQuery($query)->loadAssocList() as $value) {
            $values->addItem($this->createItemFromArray($value, (int) $value['id']));
        }

        $event->addResult([$records, $values]);
    }

    public function onPrivacyCanRemoveData(CanRemoveDataEvent $event): void
    {
        $event->addResult(new Status());
    }

    public function onPrivacyRemoveData(RemoveDataEvent $event): void
    {
        $user = $event->getUser();

        if (!$user) {
            return;
        }

        $db = $this->getDatabase();
        $userId = (int) $user->id;

        $query = $db->createQuery()
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__facileforms_records'))
            ->where($db->quoteName('user_id') . ' = :userId')
            ->bind(':userId', $userId, ParameterType::INTEGER);
        $recordIds = array_map('intval', $db->setQuery($query)->loadColumn());

        $this->clearEditorTraces($userId);

        if ($recordIds === []) {
            return;
        }

        if ((int) $this->params->get('remove_records', 0) === 1) {
            $query = $db->createQuery()
                ->delete($db->quoteName('#__facileforms_subrecords'))
                ->whereIn($db->quoteName('record'), $recordIds);
            $db->setQuery($query)->execute();

            $query = $db->createQuery()
                ->delete($db->quoteName('#__facileforms_records'))
                ->whereIn($db->quoteName('id'), $recordIds);
            $db->setQuery($query)->execute();

            return;
        }

        // Keep the fact that a submission existed (form and dates) but
        // nothing that identifies the person: once user_id is
        // cleared these rows can no longer be found by a later request, so
        // every submitted value and identifier is erased first.
        $query = $db->createQuery()
            ->update($db->quoteName('#__facileforms_subrecords'))
            ->set($db->quoteName('value') . ' = ' . $db->quote(''))
            ->whereIn($db->quoteName('record'), $recordIds);
        $db->setQuery($query)->execute();

        $query = $db->createQuery()
            ->update($db->quoteName('#__facileforms_records'))
            ->set($db->quoteName('user_id') . ' = 0')
            ->set($db->quoteName('opt_date') . ' = NULL')
            ->whereIn($db->quoteName('id'), $recordIds);

        foreach (self::IDENTITY_COLUMNS as $column => $blank) {
            $query->set($db->quoteName($column) . ' = ' . $db->quote($blank));
        }

        $db->setQuery($query)->execute();
    }

    /**
     * Records edited by the user keep their content (it belongs to whoever
     * submitted them) but no longer name the user as their last editor.
     */
    private function clearEditorTraces(int $userId): void
    {
        $db = $this->getDatabase();

        $query = $db->createQuery()
            ->update($db->quoteName('#__facileforms_records'))
            ->set($db->quoteName('modified_by') . ' = ' . $db->quote(''))
            ->set($db->quoteName('modified_user_id') . ' = 0')
            ->where($db->quoteName('modified_user_id') . ' = :userId')
            ->bind(':userId', $userId, ParameterType::INTEGER);
        $db->setQuery($query)->execute();
    }
}
