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

namespace Vcmb\Component\BreezingformsNG\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\Utilities\ArrayHelper;
use Vcmb\Component\BreezingformsNG\Administrator\Model\RecordstransferModel;
use Vcmb\Component\BreezingformsNG\Administrator\Service\ActionLogger;
use Vcmb\Component\BreezingformsNG\Administrator\Service\RecordState;
use Vcmb\Component\BreezingformsNG\Administrator\Service\RecordTransfer;

/**
 * Transfer of records to another form, in four screens: the records and the
 * target form, the mapping of the fields, the confirmation, then the report.
 * Every step is a POST with a token; the choices wait in the session.
 *
 * @property CMSApplication $app
 */
class RecordstransferController extends BaseController
{
    private const VIEW_URL = 'index.php?option=com_breezingformsng&view=recordstransfer&layout=';
    private const LIST_URL = 'index.php?option=com_breezingformsng&view=records';

    /**
     * Transferring reads the records and creates new ones.
     */
    public function execute($task)
    {
        $user = $this->app->getIdentity() ?? throw new \RuntimeException('The current user is not loaded.');

        if (
            !$user->authorise('core.manage', 'com_breezingformsng')
            || !$user->authorise('core.create', 'com_breezingformsng')
        ) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        return parent::execute($task);
    }

    /**
     * Opens the transfer on the records checked in the list.
     */
    public function start(): void
    {
        $this->checkToken();

        $model = $this->getTransferModel();
        $ids = array_values(array_unique(array_filter(ArrayHelper::toInteger((array) $this->input->post->get('cid', [], 'array')))));

        if ($ids === []) {
            $this->back(self::LIST_URL, Text::_('COM_BREEZINGFORMSNG_RECORDS_NO_ITEM_SELECTED'), 'warning');

            return;
        }

        $records = $model->getRecords($ids);
        $forms = array_unique(array_map(static fn (\stdClass $record): int => (int) $record->form, $records));

        if (count($records) !== count($ids) || count($forms) !== 1) {
            $this->back(self::LIST_URL, Text::_('COM_BREEZINGFORMSNG_TRANSFER_ERROR_ONE_FORM'), 'warning');

            return;
        }

        $model->setTransfer([
            'all' => $ids,
            'ids' => $ids,
            'source' => reset($forms),
            'target' => 0,
            'map' => [],
            'defaults' => [],
            'trash' => false,
            'report' => null,
            'operation' => bin2hex(random_bytes(16)),
        ]);

        $this->back(self::VIEW_URL . 'select');
    }

    /**
     * Screen 1: the records kept and the target form.
     */
    public function saveSelection(): void
    {
        $this->checkToken();

        $model = $this->getTransferModel();
        $transfer = $this->requireTransfer($model);

        if ($transfer === null) {
            return;
        }

        $kept = array_values(array_intersect(
            $transfer['all'],
            array_filter(ArrayHelper::toInteger((array) $this->input->post->get('cid', [], 'array')))
        ));
        $target = (int) $this->input->post->getInt('target', 0);
        $targetIds = array_map(static fn (\stdClass $form): int => (int) $form->id, $model->getForms());
        $error = match (true) {
            $kept === [] => 'COM_BREEZINGFORMSNG_TRANSFER_ERROR_NO_SELECTION',
            $target < 1 || !in_array($target, $targetIds, true) => 'COM_BREEZINGFORMSNG_TRANSFER_ERROR_TARGET_REQUIRED',
            $target === $transfer['source'] => 'COM_BREEZINGFORMSNG_TRANSFER_ERROR_SAME_FORM',
            default => null,
        };

        $transfer['ids'] = $kept;

        if ($error !== null) {
            $model->setTransfer($transfer);
            $this->back(self::VIEW_URL . 'select', Text::_($error), 'warning');

            return;
        }

        // Another target form means another mapping: start again from the
        // fields that share a name.
        if ($target !== $transfer['target']) {
            $transfer['target'] = $target;
            $transfer['map'] = RecordTransfer::suggestMapping(
                $model->getSourceElements($transfer['source'], $transfer['all']),
                $model->getElements($target)
            );
            // Each field of the target form starts with its own default value.
            $transfer['defaults'] = array_map(
                static fn (array $field): string => $field['value'],
                array_filter($model->getTargetFields($target), static fn (array $field): bool => $field['value'] !== '')
            );
        }

        $model->setTransfer($transfer);
        $this->back(self::VIEW_URL . 'mapping');
    }

    /**
     * Screen 2: the source field and the default value of each target field.
     */
    public function saveMapping(): void
    {
        $this->checkToken();

        $model = $this->getTransferModel();
        $transfer = $this->requireTransfer($model, true);

        if ($transfer === null) {
            return;
        }

        $normalized = RecordTransfer::normalizeMapping(
            (array) $this->input->post->get('map', [], 'array'),
            (array) $this->input->post->get('defaults', [], 'array'),
            $model->getSourceElements($transfer['source'], $transfer['all']),
            $model->getElements($transfer['target'])
        );
        $transfer['map'] = $normalized['map'];
        $transfer['defaults'] = $normalized['defaults'];

        $model->setTransfer($transfer);

        if ($this->refuseMissingRequired($model, $transfer)) {
            return;
        }

        $this->back(self::VIEW_URL . 'confirm');
    }

    /**
     * Screen 3: the confirmation runs the transfer.
     */
    public function transfer(): void
    {
        $this->checkToken();

        $model = $this->getTransferModel();
        if (is_array($model->getTransfer()['report'] ?? null)) {
            $this->back(self::VIEW_URL . 'report');

            return;
        }

        $transfer = $this->requireTransfer($model, true);

        if ($transfer === null) {
            return;
        }

        $db = $model->getConnection();
        $session = $this->app->getSession();
        $lockName = 'bfngt_' . substr(hash('sha256', $db->getPrefix() . ':' . $transfer['operation']), 0, 48);
        $locked = (int) $db->setQuery('SELECT GET_LOCK(' . $db->quote($lockName) . ', 30)')->loadResult() === 1;

        if (!$locked) {
            // This request may have read an old session while another is writing it.
            $session->abort();
            $this->back(self::LIST_URL);

            return;
        }

        try {
            $operation = $transfer['operation'];
            $claim = $db->createQuery()
                ->select('1')
                ->from($db->quoteName('#__facileforms_transfer_operations'))
                ->where($db->quoteName('operation') . ' = :operation')
                ->bind(':operation', $operation);

            if ($db->setQuery($claim)->loadResult() !== null) {
                // Discard the stale session snapshot; the first request has already
                // saved its report before releasing this operation's lock.
                $session->abort();
                $this->back(self::VIEW_URL . 'report');

                return;
            }

            $this->performTransfer($model, $transfer);
        } finally {
            try {
                if ($session->isActive()) {
                    $session->close();
                }
            } finally {
                try {
                    $db->setQuery('SELECT RELEASE_LOCK(' . $db->quote($lockName) . ')')->execute();
                } catch (\Throwable $exception) {
                    // The DB connection also releases the lock when it closes.
                    Log::add('Transfer lock release failed: ' . $exception::class, Log::ERROR, 'com_breezingformsng');
                }
            }
        }
    }

    /**
     * @param array{all: int[], ids: int[], source: int, target: int, map: array<int, int>, defaults: array<int, string>, trash: bool, report: ?array<string, mixed>, operation: string} $transfer
     */
    private function performTransfer(RecordstransferModel $model, array $transfer): void
    {
        if ($this->refuseMissingRequired($model, $transfer)) {
            return;
        }

        $user = $this->app->getIdentity() ?? throw new \RuntimeException('The current user is not loaded.');
        $trash = $this->input->post->getBool('trash', false) && $user->authorise('core.edit.state', 'com_breezingformsng');
        $transfer['trash'] = $trash;

        $recordModel = $model->getRecordModel();
        $db = $model->getConnection();
        $transferer = new RecordTransfer($db, $recordModel);

        try {
            $created = $transferer->transfer(
                $transfer['source'],
                $transfer['target'],
                $transfer['ids'],
                $transfer['map'],
                $transfer['defaults'],
                $this->input->post->getBool('preserve_payment', false)
                    && $this->input->post->getBool('confirm_payment', false),
                $transfer['operation']
            );
        } catch (\Throwable $e) {
            // Nothing was copied: the transaction was rolled back.
            Log::add('Records transfer failed: ' . $e::class . ' (' . (int) $e->getCode() . ')', Log::ERROR, 'com_breezingformsng');
            $model->setTransfer($transfer);
            $this->back(self::VIEW_URL . 'confirm', Text::_('COM_BREEZINGFORMSNG_TRANSFER_FAILED'), 'error');

            return;
        }

        // Consume the operation before any subsequent side effects, so a repeated
        // confirmation cannot create another set of records.
        $transfer['report'] = [
            'copied' => count($created),
            'trashed' => 0,
            'trashFailed' => false,
        ];
        $model->setTransfer($transfer);

        (new ActionLogger($this->app))->log(ActionLogger::COPIED, ActionLogger::RECORD, array_values($created));

        $trashed = 0;
        $trashFailed = false;

        if ($trash) {
            $sourceIds = array_keys($created);

            try {
                $ok = $recordModel->publish($sourceIds, RecordState::TRASHED);
            } catch (\Throwable) {
                $ok = false;
            }

            $trashed = $ok ? count($sourceIds) : 0;
            $trashFailed = !$ok;
        }

        $transfer['report'] = [
            'copied' => count($created),
            'trashed' => $trashed,
            'trashFailed' => $trashFailed,
        ];
        $model->setTransfer($transfer);

        $this->back(self::VIEW_URL . 'report');
    }

    /**
     * Leaves the transfer, at any screen, or after the report.
     */
    public function cancel(): void
    {
        $this->checkToken();
        $this->getTransferModel()->setTransfer(null);
        $this->back(self::LIST_URL);
    }

    /**
     * A required field of the target form without source field or default
     * value would leave the copies incomplete: back to the mapping.
     *
     * @param array{target: int, map: array<int, int>, defaults: array<int, string>} $transfer
     */
    private function refuseMissingRequired(RecordstransferModel $model, array $transfer): bool
    {
        $required = array_keys(array_filter($model->getTargetFields($transfer['target']), static fn (array $field): bool => $field['required']));
        $titles = [];

        foreach ($model->getElements($transfer['target']) as $element) {
            $titles[(int) $element['id']] = trim((string) $element['title']) !== '' ? (string) $element['title'] : (string) $element['name'];
        }

        $missing = RecordTransfer::missingRequired($transfer['map'], $transfer['defaults'], array_values(array_intersect($required, array_keys($titles))));

        if ($missing === []) {
            return false;
        }

        $names = array_map(static fn (int $id): string => htmlspecialchars($titles[$id], ENT_QUOTES, 'UTF-8'), $missing);
        $this->back(
            self::VIEW_URL . 'mapping',
            Text::sprintf('COM_BREEZINGFORMSNG_TRANSFER_ERROR_REQUIRED_MISSING', implode(', ', $names)),
            'warning'
        );

        return true;
    }

    private function getTransferModel(): RecordstransferModel
    {
        $model = $this->getModel('Recordstransfer', 'Administrator', ['ignore_request' => true]);

        if (!$model instanceof RecordstransferModel) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }

        return $model;
    }

    /**
     * The transfer in progress, or null after sending the user back to the list.
     *
     * @return array{all: int[], ids: int[], source: int, target: int, map: array<int, int>, defaults: array<int, string>, trash: bool, report: ?array<string, mixed>, operation: string}|null
     */
    private function requireTransfer(RecordstransferModel $model, bool $needsTarget = false): ?array
    {
        $transfer = $model->getTransfer();

        if (
            !isset($transfer['all'], $transfer['ids'], $transfer['source'])
            || is_array($transfer['report'] ?? null)
            || !is_string($transfer['operation'] ?? null)
            || !preg_match('/\A[0-9a-f]{32}\z/', $transfer['operation'])
            || ($needsTarget && (int) ($transfer['target'] ?? 0) < 1)
            || ($needsTarget && (array) $transfer['ids'] === [])
        ) {
            $this->back(self::LIST_URL, Text::_('COM_BREEZINGFORMSNG_TRANSFER_ERROR_EXPIRED'), 'warning');

            return null;
        }

        return [
            'all' => array_map('intval', (array) $transfer['all']),
            'ids' => array_map('intval', (array) $transfer['ids']),
            'source' => (int) $transfer['source'],
            'target' => (int) ($transfer['target'] ?? 0),
            'map' => array_map('intval', (array) ($transfer['map'] ?? [])),
            'defaults' => array_map('strval', (array) ($transfer['defaults'] ?? [])),
            'trash' => (bool) ($transfer['trash'] ?? false),
            'report' => null,
            'operation' => $transfer['operation'],
        ];
    }

    private function back(string $url, string $message = '', string $type = 'message'): void
    {
        $this->setRedirect(Route::_($url, false), $message !== '' ? $message : null, $type);
    }
}
