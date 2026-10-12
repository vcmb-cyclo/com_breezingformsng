<?php

declare(strict_types=1);

namespace Vcmb\Plugin\Privacy\Breezingformsng\Extension;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Event\Privacy\CanRemoveDataEvent;
use Joomla\CMS\Event\Privacy\ExportRequestEvent;
use Joomla\CMS\Event\Privacy\RemoveDataEvent;
use Joomla\CMS\Log\Log;
use Joomla\Component\Privacy\Administrator\Plugin\PrivacyPlugin;
use Joomla\Component\Privacy\Administrator\Removal\Status;
use Joomla\Database\ParameterType;
use Joomla\Event\SubscriberInterface;
use Joomla\Filesystem\Exception\FilesystemException;
use Joomla\Filesystem\File;
use Vcmb\Component\BreezingformsNG\Administrator\Service\SubmissionFileGuard;

\defined('_JEXEC') or die;

/**
 * Privacy plugin for the form submissions stored by BreezingForms NG.
 *
 * Submissions are tied to a person through the account that sent them
 * (records.user_id). Guest submissions carry no reliable identifier and are
 * therefore outside the scope of export and removal requests.
 *
 * Removal either deletes the user's submissions or anonymises them: the rows
 * stay, emptied of every submitted value and identifier. Either way the
 * uploaded files and signatures those submissions point to are deleted
 * first. Copies sent outside the site (Dropbox, e-mail attachments) are out
 * of reach.
 */
final class Breezingformsng extends PrivacyPlugin implements SubscriberInterface
{
    private const LOG_CATEGORY = 'plg_privacy_breezingformsng';

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
        $edits = $this->createDomain('breezingformsng_edits', 'breezingformsng_edited_submissions');

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

        // Submissions of other people the user edited: only the trace
        // naming them, never the content.
        $query = $db->createQuery()
            ->select($db->quoteName(['id', 'form', 'modified', 'modified_by']))
            ->from($db->quoteName('#__facileforms_records'))
            ->where($db->quoteName('modified_user_id') . ' = :editorId')
            ->where($db->quoteName('user_id') . ' <> :userId')
            ->order($db->quoteName('id') . ' ASC')
            ->bind(':editorId', $userId, ParameterType::INTEGER)
            ->bind(':userId', $userId, ParameterType::INTEGER);

        foreach ($db->setQuery($query)->loadAssocList() as $edit) {
            $edits->addItem($this->createItemFromArray($edit, (int) $edit['id']));
        }

        $event->addResult([$records, $values, $edits]);
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

        // While the values still say where the files are.
        $this->removeSubmittedFiles($recordIds);

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
     * Deletes the uploaded files and signatures the given records refer to.
     * A file still referenced by another submission is kept; one that cannot
     * be vouched for or deleted is written to the plugin log, the only trace
     * left of it once the values are gone.
     *
     * @param int[] $recordIds
     */
    private function removeSubmittedFiles(array $recordIds): void
    {
        $db = $this->getDatabase();
        $types = [SubmissionFileGuard::FILE_UPLOAD, SubmissionFileGuard::SIGNATURE];

        $query = $db->createQuery()
            ->select($db->quoteName(['value.record', 'value.type', 'value.value', 'element.data1']))
            ->from($db->quoteName('#__facileforms_subrecords', 'value'))
            ->join(
                'LEFT',
                $db->quoteName('#__facileforms_elements', 'element'),
                $db->quoteName('element.id') . ' = ' . $db->quoteName('value.element')
            )
            ->whereIn($db->quoteName('value.record'), $recordIds)
            ->whereIn($db->quoteName('value.type'), $types, ParameterType::STRING)
            ->where($db->quoteName('value.value') . ' <> ' . $db->quote(''));
        $references = $db->setQuery($query)->loadAssocList();

        if ($references === []) {
            return;
        }

        $query = $db->createQuery()
            ->select('DISTINCT ' . $db->quoteName('value'))
            ->from($db->quoteName('#__facileforms_subrecords'))
            ->whereNotIn($db->quoteName('record'), $recordIds)
            ->whereIn($db->quoteName('type'), $types, ParameterType::STRING)
            ->whereIn(
                $db->quoteName('value'),
                array_values(array_unique(array_column($references, 'value'))),
                ParameterType::STRING
            );
        $shared = array_flip($db->setQuery($query)->loadColumn());

        $uploads = ComponentHelper::getParams('com_breezingformsng')
            ->get('uploads', '{mospath}/media/breezingforms/uploads');
        $guard = new SubmissionFileGuard(JPATH_SITE, (string) $uploads);
        $done = [];
        $left = [];

        foreach ($references as $reference) {
            $value = (string) $reference['value'];
            $path = $guard->locate((string) $reference['type'], $value);

            if ($path === null || isset($shared[$value]) || isset($done[$path])) {
                continue;
            }

            $done[$path] = true;

            if (!file_exists($path) && !is_link($path)) {
                continue;
            }

            if ($guard->isRemovable((string) $reference['type'], $path, (string) $reference['data1'])) {
                try {
                    File::delete($path);

                    continue;
                } catch (FilesystemException) {
                    // Reported below.
                }
            }

            $left[$path] = (int) $reference['record'];
        }

        if ($left === []) {
            return;
        }

        Log::addLogger(['text_file' => self::LOG_CATEGORY . '.php'], Log::ALL, [self::LOG_CATEGORY]);

        foreach ($left as $path => $recordId) {
            Log::add(
                \sprintf('Submission %d: file left in place, to be handled manually: %s', $recordId, $path),
                Log::WARNING,
                self::LOG_CATEGORY
            );
        }
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
