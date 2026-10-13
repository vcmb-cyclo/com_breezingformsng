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

namespace Vcmb\Plugin\Privacy\Breezingformsng\Extension;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Event\Model\AfterDeleteEvent;
use Joomla\CMS\Event\Model\BeforeDeleteEvent;
use Joomla\CMS\Event\Privacy\CanRemoveDataEvent;
use Joomla\CMS\Event\Privacy\ExportRequestEvent;
use Joomla\CMS\Event\Privacy\RemoveDataEvent;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Factory\MVCFactoryServiceInterface;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Component\Privacy\Administrator\Plugin\PrivacyPlugin;
use Joomla\Component\Privacy\Administrator\Removal\Status;
use Joomla\Database\ParameterType;
use Joomla\Event\SubscriberInterface;
use Joomla\Filesystem\Exception\FilesystemException;
use Joomla\Filesystem\File;
use Vcmb\Component\BreezingformsNG\Administrator\Service\SubmissionFileGuard;
use Vcmb\Component\BreezingformsNG\Site\Service\Integration\ContentBuilderInstallation;

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
 * first, as are the articles ContentBuilder NG generated from them when its
 * form deletes articles with their records. Copies sent outside the site
 * (Dropbox, e-mail attachments) are out of reach.
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

    /** ContentBuilder NG record columns identifying the person or their device. */
    private const CONTENTBUILDER_IDENTITY_COLUMNS = [
        'lastip', 'session_id', 'metakey', 'metadesc', 'author', 'rights', 'xreference',
    ];

    private bool $logging = false;

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

        $removeRecords = (int) $this->params->get('remove_records', 0) === 1;

        // While the values still say where the files are.
        $this->removeSubmittedFiles($recordIds);
        $this->removeContentBuilderCopies($recordIds, $removeRecords);

        if ($removeRecords) {
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

        $uploads = ComponentHelper::getParams('com_breezingformsng')
            ->get('uploads', '{mospath}/media/breezingforms/uploads');
        $plan = (new SubmissionFileGuard(JPATH_SITE, (string) $uploads))
            ->plan($references, $this->keptFileReferences($recordIds, $types));
        $left = $plan['leave'];

        foreach ($plan['remove'] as $path => $recordId) {
            try {
                File::delete($path);
            } catch (FilesystemException) {
                $left[$path] = $recordId;
            }
        }

        foreach ($left as $path => $recordId) {
            $this->logLeftover(
                \sprintf('Submission %d: file left in place, to be handled manually: %s', $recordId, $path)
            );
        }
    }

    /**
     * The plugin log is the only trace of what a removal could not erase.
     */
    private function logLeftover(string $message): void
    {
        if (!$this->logging) {
            Log::addLogger(['text_file' => self::LOG_CATEGORY . '.php'], Log::ALL, [self::LOG_CATEGORY]);
            $this->logging = true;
        }

        Log::add($message, Log::WARNING, self::LOG_CATEGORY);
    }

    /**
     * File references held by the submissions that stay. Queried only when
     * a file is about to be deleted.
     *
     * @param int[]    $recordIds
     * @param string[] $types
     *
     * @return \Generator<array{type: string, value: ?string}>
     */
    private function keptFileReferences(array $recordIds, array $types): \Generator
    {
        $db = $this->getDatabase();

        $query = $db->createQuery()
            ->select('DISTINCT ' . $db->quoteName('type') . ', ' . $db->quoteName('value'))
            ->from($db->quoteName('#__facileforms_subrecords'))
            ->whereNotIn($db->quoteName('record'), $recordIds)
            ->whereIn($db->quoteName('type'), $types, ParameterType::STRING)
            ->where($db->quoteName('value') . ' <> ' . $db->quote(''));

        yield from $db->setQuery($query)->loadAssocList();
    }

    /**
     * ContentBuilder NG keeps its own data about a submission: an article
     * generated from the submitted values, and a row of record metadata.
     * The article is a copy of those values. It is deleted when the form
     * deletes its articles along with their records; otherwise it is left in
     * place, as for an ordinary deletion, and written to the plugin log.
     *
     * @param int[] $recordIds
     */
    private function removeContentBuilderCopies(array $recordIds, bool $removeRecords): void
    {
        if (!ContentBuilderInstallation::isInstalled()) {
            return;
        }

        $db = $this->getDatabase();
        $type = 'com_breezingformsng';

        $query = $db->createQuery()
            ->select($db->quoteName(['id', 'delete_articles']))
            ->from($db->quoteName('#__contentbuilderng_forms'))
            ->where($db->quoteName('type') . ' = :type')
            ->bind(':type', $type);
        $deletesArticles = $db->setQuery($query)->loadAssocList('id', 'delete_articles');
        $formIds = array_map('intval', array_keys($deletesArticles));

        if ($formIds === []) {
            return;
        }

        $query = $db->createQuery()
            ->select($db->quoteName(['id', 'article_id', 'form_id', 'record_id']))
            ->from($db->quoteName('#__contentbuilderng_articles'))
            ->whereIn($db->quoteName('form_id'), $formIds)
            ->whereIn($db->quoteName('record_id'), $recordIds);
        $deleted = [];
        $links = [];

        foreach ($db->setQuery($query)->loadAssocList() as $article) {
            if ((int) $deletesArticles[$article['form_id']] === 1) {
                $deleted[] = (int) $article['article_id'];
                $links[] = (int) $article['id'];

                continue;
            }

            $this->logLeftover(\sprintf(
                'Submission %d: ContentBuilder NG article %d left in place, to be handled manually:'
                . ' its form keeps the articles of deleted records',
                $article['record_id'],
                $article['article_id']
            ));

            // The submission itself goes: nothing is left to link the article to.
            if ($removeRecords) {
                $links[] = (int) $article['id'];
            }
        }

        if ($deleted !== []) {
            $this->deleteArticles(array_values(array_unique($deleted)));
        }

        if ($links !== []) {
            $query = $db->createQuery()
                ->delete($db->quoteName('#__contentbuilderng_articles'))
                ->whereIn($db->quoteName('id'), $links);
            $db->setQuery($query)->execute();
        }

        if (!$removeRecords) {
            $query = $db->createQuery()
                ->update($db->quoteName('#__contentbuilderng_records'))
                ->where($db->quoteName('type') . ' = :type')
                ->whereIn($db->quoteName('record_id'), $recordIds)
                ->bind(':type', $type);

            foreach (self::CONTENTBUILDER_IDENTITY_COLUMNS as $column) {
                $query->set($db->quoteName($column) . ' = ' . $db->quote(''));
            }

            $db->setQuery($query)->execute();

            return;
        }

        $query = $db->createQuery()
            ->delete($db->quoteName('#__contentbuilderng_list_records'))
            ->whereIn($db->quoteName('form_id'), $formIds)
            ->whereIn($db->quoteName('record_id'), $recordIds);
        $db->setQuery($query)->execute();

        $query = $db->createQuery()
            ->delete($db->quoteName('#__contentbuilderng_records'))
            ->where($db->quoteName('type') . ' = :type')
            ->whereIn($db->quoteName('record_id'), $recordIds)
            ->bind(':type', $type);
        $db->setQuery($query)->execute();
    }

    /**
     * @param int[] $articleIds
     */
    private function deleteArticles(array $articleIds): void
    {
        $app = $this->getApplication();
        $component = $app->bootComponent('com_content');

        if (!$component instanceof MVCFactoryServiceInterface) {
            throw new \RuntimeException('com_content is not available.');
        }

        PluginHelper::importPlugin('content');
        $dispatcher = $app->getDispatcher();
        $context = 'com_content.article';

        foreach ($articleIds as $articleId) {
            $table = $component->getMVCFactory()->createTable('Article', 'Administrator');

            if (!$table->load($articleId)) {
                continue;
            }

            $dispatcher->dispatch(
                'onContentBeforeDelete',
                new BeforeDeleteEvent('onContentBeforeDelete', ['context' => $context, 'subject' => $table])
            );
            $table->delete();
            $dispatcher->dispatch(
                'onContentAfterDelete',
                new AfterDeleteEvent('onContentAfterDelete', ['context' => $context, 'subject' => $table])
            );
        }

        $db = $this->getDatabase();
        $query = $db->createQuery()
            ->delete($db->quoteName('#__workflow_associations'))
            ->where($db->quoteName('extension') . ' = :extension')
            ->whereIn($db->quoteName('item_id'), $articleIds)
            ->bind(':extension', $context);
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
