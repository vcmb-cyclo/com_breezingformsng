<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Access\Exception\NotAllowed;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Factory\MVCFactoryServiceInterface;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Router\Route;
use Joomla\Database\DatabaseInterface;
use Vcmb\Component\BreezingformsNG\Administrator\Model\FormModel;
use Vcmb\Component\BreezingformsNG\Administrator\Model\IntegratorModel;
use Vcmb\Component\BreezingformsNG\Administrator\Model\RecordModel;
use Vcmb\Component\BreezingformsNG\Administrator\Service\ActionLogger;
use Vcmb\Component\BreezingformsNG\Administrator\Service\AreaAccess;
use Vcmb\Component\BreezingformsNG\Administrator\Service\DatabaseAuditService;
use Vcmb\Component\BreezingformsNG\Administrator\Service\DatabaseRepairService;
use Vcmb\Component\BreezingformsNG\Administrator\Service\TrashInventoryService;

/** @property CMSApplication $app */
class AboutController extends BaseController
{
    /**
     * @param bool                 $cachable
     * @param array<string, mixed> $urlparams
     */
    public function display($cachable = false, $urlparams = [])
    {
        $application = $this->app;

        $user = $application->getIdentity() ?? throw new \RuntimeException('The current user is not loaded.');

        if (!$user->authorise('core.manage', 'com_breezingformsng')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        $application->getInput()->set('view', 'about');

        return parent::display($cachable, $urlparams);
    }

    public function runAudit(): void
    {
        $this->checkToken();

        try {
            $this->getAuthorizedApplication();

            $report = $this->getAuditService()->run();
            $summary = (array) ($report['summary'] ?? []);
            $this->app->setUserState('com_breezingformsng.about.audit', $report);

            if ((int) ($summary['audit_errors'] ?? 0) > 0) {
                $this->setMessage(Text::sprintf(
                    'COM_BREEZINGFORMSNG_ABOUT_AUDIT_SUMMARY_ERRORS',
                    (int) ($summary['audit_errors'] ?? 0),
                    (int) ($summary['issues_total'] ?? 0)
                ), 'warning');
            } elseif ((int) ($summary['issues_total'] ?? 0) === 0) {
                $this->setMessage(Text::sprintf('COM_BREEZINGFORMSNG_ABOUT_AUDIT_SUMMARY_CLEAN', $summary['scanned_tables'], $summary['total_rows']), 'message');
            } else {
                $this->setMessage(Text::sprintf('COM_BREEZINGFORMSNG_ABOUT_AUDIT_SUMMARY_ISSUES', $summary['issues_total'], $summary['scanned_tables']), 'warning');
            }
        } catch (\Throwable $exception) {
            $this->app->setUserState('com_breezingformsng.about.audit', []);
            $this->setMessage(Text::sprintf('COM_BREEZINGFORMSNG_ABOUT_AUDIT_FAILED', $exception->getMessage()), 'error');
        }

        $this->setRedirect(Route::_('index.php?option=com_breezingformsng&task=about.display&view=about&layout=audit', false));
    }

    /**
     * Number of issues of the audit, read asynchronously by the Tools page badge.
     * Read-only: nothing is stored, the report itself is built by the Audit button.
     */
    public function auditSummary(): void
    {
        if (!$this->checkToken('post', false)) {
            $this->sendJson(403, Text::_('JINVALID_TOKEN'));

            return;
        }

        try {
            $this->getAuthorizedApplication();
            $summary = (array) ($this->getAuditService()->run()['summary'] ?? []);
        } catch (\Throwable $exception) {
            $this->sendJson(
                $exception->getCode() === 403 ? 403 : 500,
                Text::sprintf('COM_BREEZINGFORMSNG_ABOUT_AUDIT_FAILED', $exception->getMessage())
            );

            return;
        }

        $this->sendJson(200, '', [
            'issues' => (int) ($summary['issues_total'] ?? 0),
            'errors' => (int) ($summary['audit_errors'] ?? 0),
        ]);
    }

    public function repairDuplicateIndexes(): void
    {
        $this->checkToken();

        try {
            $user = $this->getAuthorizedApplication()->getIdentity() ?? throw new \RuntimeException('The current user is not loaded.');
            AreaAccess::assertRepair($user);
            $singleToken = trim((string) $this->input->post->getString('duplicate_index_group', ''));
            $selectedTokens = $singleToken !== ''
                ? [$singleToken]
                : array_values(array_filter(
                    array_map(
                        static fn($value): string => trim((string) $value),
                        (array) $this->input->post->get('duplicate_index_groups', [], 'array')
                    ),
                    static fn(string $value): bool => $value !== ''
                ));

            if ($selectedTokens === []) {
                throw new \RuntimeException(Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_DUPLICATE_INDEX_NO_SELECTION'));
            }

            $summary = $this->getRepairService()->repairDuplicateIndexes($selectedTokens);

            if ((int) ($summary['selected_groups'] ?? 0) === 0) {
                throw new \RuntimeException(Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_DUPLICATE_INDEX_NO_SELECTION'));
            }

            $this->app->setUserState('com_breezingformsng.about.audit', $this->getAuditService()->run());
            $failedIndexes = (int) ($summary['failed_indexes'] ?? 0);
            $message = Text::sprintf(
                'COM_BREEZINGFORMSNG_ABOUT_AUDIT_DUPLICATE_INDEX_REPAIR_SUMMARY',
                (int) ($summary['selected_groups'] ?? 0),
                (int) ($summary['repaired_groups'] ?? 0),
                (int) ($summary['removed_indexes'] ?? 0),
                $failedIndexes
            );
            $this->setMessage($message, $failedIndexes > 0 ? 'warning' : 'message');
        } catch (\Throwable $exception) {
            $this->setMessage(
                Text::sprintf('COM_BREEZINGFORMSNG_ABOUT_AUDIT_DUPLICATE_INDEX_REPAIR_FAILED', $exception->getMessage()),
                'error'
            );
        }

        $this->setRedirect(Route::_('index.php?option=com_breezingformsng&task=about.display&view=about&layout=audit#bf-audit-section', false));
    }

    public function repairColumnCollations(): void
    {
        $this->checkToken();

        try {
            $user = $this->getAuthorizedApplication()->getIdentity() ?? throw new \RuntimeException('The current user is not loaded.');
            AreaAccess::assertRepair($user);
            $singleToken = trim((string) $this->input->post->getString('column_collation_issue', ''));
            $selectedTokens = $singleToken !== ''
                ? [$singleToken]
                : array_values(array_filter(
                    array_map(
                        static fn($value): string => trim((string) $value),
                        (array) $this->input->post->get('column_collation_issues', [], 'array')
                    ),
                    static fn(string $value): bool => $value !== ''
                ));

            if ($selectedTokens === []) {
                throw new \RuntimeException(Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_COLUMN_COLLATION_NO_SELECTION'));
            }

            $summary = $this->getRepairService()->repairColumnCollations($selectedTokens);

            if ((int) ($summary['selected_columns'] ?? 0) === 0) {
                throw new \RuntimeException(Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_COLUMN_COLLATION_NO_SELECTION'));
            }

            $this->app->setUserState('com_breezingformsng.about.audit', $this->getAuditService()->run());
            $failedColumns = (int) ($summary['failed_columns'] ?? 0);
            $this->setMessage(Text::sprintf(
                'COM_BREEZINGFORMSNG_ABOUT_AUDIT_COLUMN_COLLATION_REPAIR_SUMMARY',
                (int) ($summary['selected_columns'] ?? 0),
                (int) ($summary['repaired_columns'] ?? 0),
                $failedColumns
            ), $failedColumns > 0 ? 'warning' : 'message');
        } catch (\Throwable $exception) {
            $this->keepAuditWithError(
                Text::sprintf(
                    'COM_BREEZINGFORMSNG_ABOUT_AUDIT_COLUMN_COLLATION_REPAIR_FAILED',
                    htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8')
                )
            );
        }

        $this->setRedirect(Route::_('index.php?option=com_breezingformsng&task=about.display&view=about&layout=audit#bf-audit-section', false));
    }

    public function repairTableCollations(): void
    {
        $this->checkToken();

        try {
            $user = $this->getAuthorizedApplication()->getIdentity() ?? throw new \RuntimeException('The current user is not loaded.');
            AreaAccess::assertRepair($user);
            $singleToken = trim((string) $this->input->post->getString('table_collation_issue', ''));
            $selectedTokens = $singleToken !== ''
                ? [$singleToken]
                : array_values(array_filter(
                    array_map(
                        static fn($value): string => trim((string) $value),
                        (array) $this->input->post->get('table_collation_issues', [], 'array')
                    ),
                    static fn(string $value): bool => $value !== ''
                ));

            if ($selectedTokens === []) {
                throw new \RuntimeException(Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_TABLE_COLLATION_NO_SELECTION'));
            }

            $summary = $this->getRepairService()->repairTableCollations($selectedTokens);

            if ((int) ($summary['selected_tables'] ?? 0) === 0) {
                throw new \RuntimeException(Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_TABLE_COLLATION_NO_SELECTION'));
            }

            $this->app->setUserState('com_breezingformsng.about.audit', $this->getAuditService()->run());
            $failedTables = (int) ($summary['failed_tables'] ?? 0);
            $this->setMessage(Text::sprintf(
                'COM_BREEZINGFORMSNG_ABOUT_AUDIT_TABLE_COLLATION_REPAIR_SUMMARY',
                (int) ($summary['selected_tables'] ?? 0),
                (int) ($summary['repaired_tables'] ?? 0),
                $failedTables
            ), $failedTables > 0 ? 'warning' : 'message');
        } catch (\Throwable $exception) {
            $this->keepAuditWithError(
                Text::sprintf(
                    'COM_BREEZINGFORMSNG_ABOUT_AUDIT_TABLE_COLLATION_REPAIR_FAILED',
                    htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8')
                )
            );
        }

        $this->setRedirect(Route::_('index.php?option=com_breezingformsng&task=about.display&view=about&layout=audit#bf-audit-section', false));
    }

    public function deleteStaleInstallerTemp(): void
    {
        $this->checkToken();

        try {
            $user = $this->getAuthorizedApplication()->getIdentity() ?? throw new \RuntimeException('The current user is not loaded.');
            AreaAccess::assertRepair($user);
            $singlePath = trim((string) $this->input->post->getString('stale_installer_temp_dir', ''));
            $selectedPaths = $singlePath !== ''
                ? [$singlePath]
                : array_values(array_filter(
                    array_map(
                        static fn($value): string => trim((string) $value),
                        (array) $this->input->post->get('stale_installer_temp_dirs', [], 'array')
                    ),
                    static fn(string $value): bool => $value !== ''
                ));

            if ($selectedPaths === []) {
                throw new \RuntimeException(Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_STALE_INSTALLER_TEMP_NO_SELECTION'));
            }

            $summary = $this->getRepairService()->deleteStaleInstallerTemp($selectedPaths);

            if ((int) ($summary['selected_dirs'] ?? 0) === 0) {
                throw new \RuntimeException(Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_STALE_INSTALLER_TEMP_NO_SELECTION'));
            }

            $this->app->setUserState('com_breezingformsng.about.audit', $this->getAuditService()->run());
            $failedDirs = (int) ($summary['failed_dirs'] ?? 0);
            $this->setMessage(Text::sprintf(
                'COM_BREEZINGFORMSNG_ABOUT_AUDIT_STALE_INSTALLER_TEMP_REPAIR_SUMMARY',
                (int) ($summary['selected_dirs'] ?? 0),
                (int) ($summary['deleted_dirs'] ?? 0),
                $failedDirs
            ), $failedDirs > 0 ? 'warning' : 'message');
        } catch (\Throwable $exception) {
            $this->setMessage(
                Text::sprintf('COM_BREEZINGFORMSNG_ABOUT_AUDIT_STALE_INSTALLER_TEMP_REPAIR_FAILED', $exception->getMessage()),
                'error'
            );
        }

        $this->setRedirect(Route::_('index.php?option=com_breezingformsng&task=about.display&view=about&layout=audit#bf-audit-section', false));
    }

    public function removeLegacyExtensions(): void
    {
        $this->checkToken();

        try {
            $user = $this->getAuthorizedApplication()->getIdentity() ?? throw new \RuntimeException('The current user is not loaded.');
            AreaAccess::assertRepair($user);

            // Uninstalling extensions is a Joomla Installer right, like in its own model.
            if (!$user->authorise('core.delete', 'com_installer')) {
                throw new NotAllowed(Text::_('JERROR_CORE_DELETE_NOT_PERMITTED'), 403);
            }

            $single = $this->input->post->getInt('legacy_extension', 0);
            $selectedIds = $single > 0
                ? [$single]
                : array_map('intval', (array) $this->input->post->get('legacy_extensions', [], 'array'));

            if (array_filter($selectedIds) === []) {
                throw new \RuntimeException(Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_EXTENSION_LEGACY_NO_SELECTION'));
            }

            $summary = $this->getRepairService()->removeLegacyExtensions($selectedIds);

            if ((int) ($summary['selected'] ?? 0) === 0) {
                throw new \RuntimeException(Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_EXTENSION_LEGACY_NO_SELECTION'));
            }

            $this->app->setUserState('com_breezingformsng.about.audit', $this->getAuditService()->run());
            $failed = (int) ($summary['failed'] ?? 0);
            $this->setMessage(Text::sprintf(
                'COM_BREEZINGFORMSNG_ABOUT_AUDIT_EXTENSION_LEGACY_REPAIR_SUMMARY',
                (int) ($summary['selected'] ?? 0),
                (int) ($summary['removed'] ?? 0),
                $failed
            ), $failed > 0 ? 'warning' : 'message');

            foreach ((array) ($summary['errors'] ?? []) as $error) {
                $this->app->enqueueMessage(htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8'), 'warning');
            }
        } catch (\Throwable $exception) {
            $this->setMessage(
                Text::sprintf('COM_BREEZINGFORMSNG_ABOUT_AUDIT_EXTENSION_LEGACY_REPAIR_FAILED', $exception->getMessage()),
                'error'
            );
        }

        $this->setRedirect(Route::_('index.php?option=com_breezingformsng&task=about.display&view=about&layout=audit#bf-audit-section', false));
    }

    public function deleteDuplicateForm(): void
    {
        $this->checkToken();

        $formId = $this->input->getInt('duplicate_form_id', 0);

        try {
            $application = $this->getAuthorizedApplication();
            $user = $application->getIdentity() ?? throw new \RuntimeException('The current user is not loaded.');
            AreaAccess::assertRepair($user);

            // Revalidate against a fresh audit: only a currently detected
            // "drop" candidate may be deleted, and never one still holding
            // submission records.
            $auditService = $this->getAuditService();
            $report = $auditService->run();
            $candidate = null;

            foreach ((array) ($report['duplicate_forms'] ?? []) as $group) {
                foreach ((array) ($group['drop'] ?? []) as $entry) {
                    if ((int) ($entry['id'] ?? 0) === $formId) {
                        $candidate = $entry;
                        break 2;
                    }
                }
            }

            if ($formId < 1 || $candidate === null) {
                throw new \RuntimeException(Text::sprintf('COM_BREEZINGFORMSNG_ABOUT_AUDIT_DUPLICATE_FORM_NOT_CANDIDATE', $formId));
            }

            if ((int) ($candidate['record_count'] ?? 0) > 0) {
                throw new \RuntimeException(Text::plural(
                    'COM_BREEZINGFORMSNG_ABOUT_AUDIT_DUPLICATE_FORM_RECORDS_BLOCK',
                    (int) $candidate['record_count'],
                    $formId
                ));
            }

            $formModel = $this->getMVCFactoryOf($application)
                ->createModel('Form', 'Administrator', ['ignore_request' => true]);

            if (!$formModel instanceof FormModel) {
                throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
            }

            $pks = [$formId];

            if (!$formModel->delete($pks)) {
                throw new \RuntimeException((string) $formModel->getError());
            }

            $application->setUserState('com_breezingformsng.about.audit', $auditService->run());
            $this->setMessage(Text::sprintf('COM_BREEZINGFORMSNG_ABOUT_AUDIT_DUPLICATE_FORM_DELETED', $formId), 'message');
        } catch (\Throwable $exception) {
            $this->setMessage(Text::sprintf('COM_BREEZINGFORMSNG_ABOUT_AUDIT_DUPLICATE_FORM_DELETE_FAILED', $exception->getMessage()), 'error');
        }

        $this->setRedirect(Route::_('index.php?option=com_breezingformsng&task=about.display&view=about&layout=audit', false));
    }

    /**
     * Sends a site menu item reported by the audit to the trash, from the About
     * screen, under the Joomla rights of the user. It is not deleted for good.
     */
    public function deleteMenuItem(): void
    {
        $this->checkToken();

        $menuId = $this->input->getInt('menu_item_id', 0);

        try {
            $application = $this->getAuthorizedApplication();
            $user = $application->getIdentity() ?? throw new \RuntimeException('The current user is not loaded.');
            AreaAccess::assertRepair($user);

            if (!$user->authorise('core.edit.state', 'com_menus')) {
                throw new \RuntimeException(Text::_('JLIB_APPLICATION_ERROR_EDITSTATE_NOT_PERMITTED'));
            }

            $auditService = $this->getAuditService();
            $candidate = false;

            foreach ((array) ($auditService->run()['menu_issues'] ?? []) as $issue) {
                if ((int) ($issue['menu_id'] ?? 0) === $menuId) {
                    $candidate = true;
                    break;
                }
            }

            if ($menuId < 1 || !$candidate) {
                throw new \RuntimeException(Text::sprintf('COM_BREEZINGFORMSNG_ABOUT_AUDIT_MENU_NOT_CANDIDATE', $menuId));
            }

            $menus = $application->bootComponent('com_menus');

            if (!$menus instanceof MVCFactoryServiceInterface) {
                throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
            }

            $model = $menus->getMVCFactory()->createModel('Item', 'Administrator', ['ignore_request' => true]);

            if (!$model instanceof AdminModel) {
                throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
            }

            // Like the trash button of the Joomla menu screen: the item goes to the
            // trash, where it can be restored or emptied.
            $pks = [$menuId];

            if (!$model->publish($pks, -2)) {
                throw new \RuntimeException($this->menuError($model, 'JLIB_APPLICATION_ERROR_UNPUBLISH_FAILED'));
            }

            $application->setUserState('com_breezingformsng.about.audit', $auditService->run());
            $this->setMessage(Text::sprintf('COM_BREEZINGFORMSNG_ABOUT_AUDIT_MENU_DELETED', $menuId), 'message');
        } catch (\Throwable $exception) {
            $this->setMessage(Text::sprintf('COM_BREEZINGFORMSNG_ABOUT_AUDIT_MENU_DELETE_FAILED', $exception->getMessage()), 'error');
        }

        $this->setRedirect(Route::_('index.php?option=com_breezingformsng&task=about.display&view=about&layout=audit#bf-audit-section', false));
    }

    private function menuError(AdminModel $model, string $fallbackKey): string
    {
        // getError() is the only way an AdminModel reports why a menu item could not be trashed.
        /** @psalm-suppress DeprecatedMethod */
        $error = trim($model->getError());

        return $error !== '' ? $error : Text::_($fallbackKey);
    }

    public function emptyRecordsTrash(): void
    {
        $this->checkToken();

        try {
            $application = $this->getAuthorizedApplication();
            $user = $application->getIdentity() ?? throw new \RuntimeException('The current user is not loaded.');
            AreaAccess::assertRepair($user);

            $model = $this->getMVCFactoryOf($application)
                ->createModel('Record', 'Administrator', ['ignore_request' => true]);

            if (!$model instanceof RecordModel) {
                throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
            }

            $deleted = $model->emptyTrash();

            if ($deleted !== []) {
                (new ActionLogger($application))->log(ActionLogger::DELETED, ActionLogger::RECORD, $deleted);
            }

            $this->setMessage(Text::plural('COM_BREEZINGFORMSNG_RECORDS_N_ITEMS_DELETED', count($deleted)), 'message');
        } catch (\Throwable $exception) {
            $this->setMessage(Text::sprintf('COM_BREEZINGFORMSNG_ABOUT_AUDIT_TRASH_EMPTY_FAILED', $exception->getMessage()), 'error');
        }

        $this->setRedirect(Route::_('index.php?option=com_breezingformsng&task=about.display&view=about&layout=trash', false));
    }

    /**
     * Permanently deletes every trashed item of one type (forms, scripts, pieces or
     * Integrator rules) from the Tools / Trash screen: only what is already in the
     * trash, through the same models and permissions as the list of the type.
     */
    public function emptyItemsTrash(): void
    {
        $this->checkToken();
        $type = $this->input->post->getCmd('trash_type', '');
        $failed = null;
        $deleted = 0;

        try {
            $application = $this->getAuthorizedApplication();
            $user = $application->getIdentity() ?? throw new \RuntimeException('The current user is not loaded.');
            $service = new TrashInventoryService($this->getDatabase());
            $ids = $service->getTrashedIds($type);

            if (!in_array($type, ['forms', 'scripts', 'pieces', 'integrator'], true)) {
                throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
            }

            if (!$user->authorise('core.delete', 'com_breezingformsng')) {
                throw new NotAllowed(Text::_('JLIB_APPLICATION_ERROR_DELETE_NOT_PERMITTED'));
            }

            if ($type !== 'forms') {
                // Scripts, pieces and rules hold code.
                AreaAccess::assert($user, AreaAccess::CODE);
            }

            if ($ids !== []) {
                $factory = $this->getMVCFactoryOf($application);

                if ($type === 'integrator') {
                    $model = $factory->createModel('Integrator', 'Administrator', ['ignore_request' => true]);

                    if (!$model instanceof IntegratorModel) {
                        throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
                    }

                    $deleted = count($model->deleteRules($ids)['deleted']);
                } else {
                    $model = $factory->createModel(['forms' => 'Form', 'scripts' => 'Script', 'pieces' => 'Piece'][$type], 'Administrator', ['ignore_request' => true]);

                    if (!$model instanceof AdminModel) {
                        throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
                    }

                    $pks = $ids;

                    if (!$model->delete($pks)) {
                        throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
                    }

                    $deleted = count($ids) - count($service->getTrashedIds($type));
                    (new ActionLogger($application))->log(
                        ActionLogger::DELETED,
                        ['forms' => ActionLogger::FORM, 'scripts' => ActionLogger::SCRIPT, 'pieces' => ActionLogger::PIECE][$type],
                        $ids
                    );
                }
            }
        } catch (\Throwable $exception) {
            $failed = $exception->getMessage();
        }

        if ($failed !== null) {
            $this->setMessage(Text::sprintf('COM_BREEZINGFORMSNG_ABOUT_AUDIT_TRASH_EMPTY_FAILED', $failed), 'error');
        } else {
            $this->setMessage(Text::plural('COM_BREEZINGFORMSNG_N_ITEMS_DELETED', $deleted), 'message');
        }

        $this->setRedirect(Route::_('index.php?option=com_breezingformsng&task=about.display&view=about&layout=trash', false));
    }

    public function deleteOrphanElements(): void
    {
        $this->repairOrphans(
            fn (DatabaseRepairService $repair): int => $repair->deleteOrphanElements(),
            'elements_without_form',
            'COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_ELEMENTS_DELETED',
            'COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_ELEMENTS_DELETE_FAILED'
        );
    }

    public function reassignOrphanValues(): void
    {
        $all = $this->input->post->getBool('orphan_all', false);
        $rawId = $this->input->post->get('orphan_element_id', null, 'raw');
        $elementId = $all ? null : (is_string($rawId) && preg_match('/\A[0-9]+\z/', $rawId) ? (int) $rawId : -1);

        $this->repairOrphans(
            fn (DatabaseRepairService $repair): int => $repair->reassignOrphanValues($elementId),
            'subrecords_without_element',
            'COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_VALUES_REASSIGNED',
            'COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_VALUES_REASSIGN_FAILED',
            'COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_VALUES_REASSIGN_NONE'
        );
    }

    public function reassignOrphanValuesToElement(): void
    {
        $rawOld = $this->input->post->get('orphan_element_id', null, 'raw');
        $rawTarget = $this->input->post->get('orphan_target_id', null, 'raw');
        $old = is_string($rawOld) && preg_match('/\A[0-9]+\z/', $rawOld) ? (int) $rawOld : -1;
        $target = is_string($rawTarget) && preg_match('/\A[0-9]+\z/', $rawTarget) ? (int) $rawTarget : -1;
        // The values checked in the preview; absent (no preview, e.g. no script): all of them.
        $rawSelection = $this->input->post->get('orphan_values', null, 'array');
        $selection = is_array($rawSelection)
            ? array_values(array_filter(array_map('intval', $rawSelection), static fn (int $id): bool => $id > 0))
            : null;

        // Filled by the repair, read by the message: a shared object, not references.
        $outcome = new class {
            public int $moved = 0;
            public int $identical = 0;
        };

        $this->repairOrphans(
            function (DatabaseRepairService $repair) use ($old, $target, $selection, $outcome): int {
                if ($selection === []) {
                    throw new \InvalidArgumentException(Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_REASSIGN_NO_SELECTION'));
                }

                $identical = 0;
                $outcome->moved = $repair->reassignOrphanValuesToElement($old, $target, $identical, $selection);
                $outcome->identical = $identical;

                return $outcome->moved + $identical;
            },
            'subrecords_without_element',
            'COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_VALUES_REASSIGNED',
            'COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_VALUES_REASSIGN_FAILED',
            'COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_VALUES_REASSIGN_NONE',
            static fn (): ?string => $outcome->identical > 0
                ? Text::plural('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_VALUES_REASSIGNED', $outcome->moved)
                    . ' ' . Text::plural('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_VALUES_IDENTICAL_REMOVED', $outcome->identical)
                : null
        );
    }

    public function previewOrphanReassignment(): void
    {
        if (!$this->checkToken('post', false)) {
            $this->sendJson(403, Text::_('JINVALID_TOKEN'));

            return;
        }

        $rawOld = $this->input->post->get('orphan_element_id', null, 'raw');
        $rawTarget = $this->input->post->get('orphan_target_id', null, 'raw');
        $old = is_string($rawOld) && preg_match('/\A[0-9]+\z/', $rawOld) ? (int) $rawOld : -1;
        $target = is_string($rawTarget) && preg_match('/\A[0-9]+\z/', $rawTarget) ? (int) $rawTarget : -1;

        try {
            $user = $this->getAuthorizedApplication()->getIdentity() ?? throw new \RuntimeException('The current user is not loaded.');
            AreaAccess::assertDeleteOrphans($user);
            $preview = $this->getRepairService()->previewOrphanReassignment($old, $target);
        } catch (\Throwable $exception) {
            $this->sendJson(
                match (true) {
                    $exception instanceof NotAllowed => 403,
                    $exception instanceof \InvalidArgumentException => 400,
                    default => 500,
                },
                Text::sprintf('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_VALUES_REASSIGN_FAILED', $exception->getMessage())
            );

            return;
        }

        $this->sendJson(200, '', $preview);
    }

    public function deleteOrphanValues(): void
    {
        $all = $this->input->post->getBool('orphan_all', false);
        $rawId = $this->input->post->get('orphan_element_id', null, 'raw');
        $elementId = $all ? null : (is_string($rawId) && preg_match('/\A[0-9]+\z/', $rawId) ? (int) $rawId : -1);

        $this->repairOrphans(
            fn (DatabaseRepairService $repair): int => $repair->deleteOrphanValues($elementId),
            'subrecords_without_element',
            'COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_VALUES_DELETED',
            'COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_VALUES_DELETE_FAILED'
        );
    }

    public function deleteOrphanValuesWithRecords(): void
    {
        $rawId = $this->input->post->get('orphan_element_id', null, 'raw');
        $elementId = is_string($rawId) && preg_match('/\A[0-9]+\z/', $rawId) ? (int) $rawId : -1;
        $acknowledged = $this->input->post->getBool('orphan_acknowledged', false);

        $this->repairOrphans(
            function (DatabaseRepairService $repair) use ($elementId, $acknowledged): int {
                if (!$acknowledged) {
                    throw new \InvalidArgumentException(Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_VALUES_DELETE_LIVE_ACK_REQUIRED'));
                }

                return $repair->deleteOrphanValuesOfRecords($elementId);
            },
            'subrecords_without_element',
            'COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_VALUES_DELETED',
            'COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_VALUES_DELETE_FAILED'
        );
    }

    /**
     * Repairs orphan rows. With format=json (the About screen, from its script) the
     * answer is JSON and the page is updated in place; without, the page is reloaded
     * with a message.
     *
     * @param \Closure(DatabaseRepairService): int $repair
     * @param ?\Closure(): ?string $describe Optional message overriding the default one.
     */
    private function repairOrphans(\Closure $repair, string $checkId, string $doneKey, string $failedKey, ?string $noneKey = null, ?\Closure $describe = null): void
    {
        $json = $this->input->getCmd('format') === 'json';

        if ($json && !$this->checkToken('post', false)) {
            $this->sendJson(403, Text::_('JINVALID_TOKEN'));

            return;
        }

        if (!$json) {
            $this->checkToken();
        }

        try {
            $user = $this->getAuthorizedApplication()->getIdentity() ?? throw new \RuntimeException('The current user is not loaded.');
            AreaAccess::assertDeleteOrphans($user);

            $deleted = $repair($this->getRepairService());
            $report = $this->getAuditService()->run();
            $this->app->setUserState('com_breezingformsng.about.audit', $report);
            $message = ($describe !== null ? $describe() : null)
                ?? ($deleted === 0 && $noneKey !== null ? Text::_($noneKey) : Text::plural($doneKey, $deleted));
        } catch (\Throwable $exception) {
            $message = Text::sprintf($failedKey, $exception->getMessage());

            if ($json) {
                $this->sendJson(
                    match (true) {
                        $exception instanceof NotAllowed => 403,
                        $exception instanceof \InvalidArgumentException => 400,
                        default => 500,
                    },
                    $message
                );

                return;
            }

            $this->setMessage($message, 'error');
            $this->setRedirect(Route::_('index.php?option=com_breezingformsng&task=about.display&view=about&layout=audit#bf-audit-section', false));

            return;
        }

        if ($json) {
            $remaining = 0;

            foreach ((array) ($report['orphan_checks'] ?? []) as $check) {
                if (($check['id'] ?? '') === $checkId) {
                    $remaining = (int) ($check['count'] ?? 0);
                }
            }

            $this->sendJson(200, $message, ['deleted' => $deleted, 'remaining' => $remaining]);

            return;
        }

        $this->setMessage($message, 'message');
        $this->setRedirect(Route::_('index.php?option=com_breezingformsng&task=about.display&view=about&layout=audit#bf-audit-section', false));
    }

    /**
     * @param array<string, mixed>|null $data
     */
    private function sendJson(int $status, string $message, ?array $data = null): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $this->app->mimeType = 'application/json';
        $this->app->setHeader('Content-Type', 'application/json; charset=utf-8', true);

        if ($status !== 200) {
            $this->app->setHeader('status', (string) $status, true);
        }

        $this->app->sendHeaders();
        echo $status === 200 ? new JsonResponse($data, $message) : new JsonResponse(null, $message, true);
        $this->app->close();
    }

    /**
     * Shows a failed repair inside the audit section, next to the report it
     * applies to: the report is run again, as the view reads it only once.
     *
     * @param string $message HTML, as a Joomla message: the translations hold
     *                        entities such as &#8239;, untrusted parts are escaped.
     */
    private function keepAuditWithError(string $message): void
    {
        try {
            $this->app->setUserState('com_breezingformsng.about.audit', $this->getAuditService()->run());
        } catch (\Throwable $exception) {
            $message .= ' ' . htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8');
        }

        $this->app->setUserState('com_breezingformsng.about.audit_error', $message);
    }

    private function getAuthorizedApplication(): CMSApplication
    {
        $application = $this->app;

        $user = $application->getIdentity() ?? throw new \RuntimeException('The current user is not loaded.');

        if (!$user->authorise('core.manage', 'com_breezingformsng')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        return $application;
    }

    private function getMVCFactoryOf(CMSApplication $application): MVCFactoryInterface
    {
        $component = $application->bootComponent('com_breezingformsng');

        if (!$component instanceof MVCFactoryServiceInterface) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }

        return $component->getMVCFactory();
    }

    private function getDatabase(): DatabaseInterface
    {
        return Factory::getContainer()->get(DatabaseInterface::class);
    }

    private function getAuditService(?DatabaseInterface $database = null): DatabaseAuditService
    {
        return new DatabaseAuditService(
            $database ?? $this->getDatabase(),
            (string) $this->app->get('tmp_path', JPATH_ROOT . '/tmp')
        );
    }

    private function getRepairService(): DatabaseRepairService
    {
        return new DatabaseRepairService(
            $this->getDatabase(),
            (string) $this->app->get('tmp_path', JPATH_ROOT . '/tmp')
        );
    }
}
