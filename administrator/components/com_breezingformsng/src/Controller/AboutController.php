<?php
/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\Database\DatabaseInterface;
use Vcmb\Component\BreezingformsNG\Administrator\Service\DatabaseAuditService;
use Vcmb\Component\BreezingformsNG\Administrator\Service\DatabaseRepairService;

/** @property CMSApplication $app */
class AboutController extends BaseController
{
    private const ABOUT_LOG_FILES = ['breezingforms_install2.log', 'breezingforms_install.log'];
    private const ABOUT_LOG_TAIL_BYTES = 65536;
    private const CONFIGURATION_TABLES = [
        'facileforms_packages',
        'facileforms_compmenus',
        'facileforms_forms',
        'facileforms_elements',
        'facileforms_scripts',
        'facileforms_pieces',
        'facileforms_integrator_rules',
        'facileforms_integrator_items',
        'facileforms_integrator_criteria_fixed',
        'facileforms_integrator_criteria_form',
        'facileforms_integrator_criteria_joomla',
    ];

    /**
     * Tables the collation repair should also cover but that exportConfiguration()
     * deliberately excludes from the config-only backup (user-submitted data,
     * not component configuration).
     */
    private const ADDITIONAL_REPAIR_TABLES = [
        'facileforms_config',
        'facileforms_records',
        'facileforms_subrecords',
    ];

    public function display($cachable = false, $urlparams = [])
    {
        $application = $this->app;

        if (!$application->getIdentity()->authorise('core.manage', 'com_breezingformsng')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        $application->getInput()->set('view', 'about');

        return parent::display($cachable, $urlparams);
    }

    public function migratePackedData(): void
    {
        $this->startRepairWorkflow();
    }

    public function startRepairWorkflow(): void
    {
        $this->checkToken();

        try {
            $this->getAuthorizedApplication();
            $db = $this->getDatabase();

            $report = $this->getAuditService($db)->run();
            $targetCollation = (string) ($report['target_collation'] ?? 'utf8mb4_unicode_ci');
            $currentCollations = array_column($report['tables'] ?? [], 'collation', 'table');

            $converted = 0;
            $skipped = 0;
            $missing = 0;

            foreach ([...self::CONFIGURATION_TABLES, ...self::ADDITIONAL_REPAIR_TABLES] as $table) {
                if (!$this->tableExists($table)) {
                    $missing++;
                    continue;
                }

                $currentCollation = (string) ($currentCollations['#__' . $table] ?? '');
                if ($currentCollation !== '' && strcasecmp($currentCollation, $targetCollation) === 0) {
                    $skipped++;
                    continue;
                }

                // $targetCollation only ever comes from DatabaseAuditService's own
                // hardcoded candidate list, never from user input.
                $db->setQuery(
                    'ALTER TABLE ' . $db->quoteName('#__' . $table)
                    . ' CONVERT TO CHARACTER SET utf8mb4 COLLATE ' . $targetCollation
                );
                $db->execute();
                $converted++;
            }

            $this->setMessage(
                Text::sprintf('COM_BREEZINGFORMSNG_ABOUT_DB_REPAIR_DONE', $converted, $missing)
                . ' ' . Text::sprintf('COM_BREEZINGFORMSNG_ABOUT_DB_REPAIR_SKIPPED', $skipped, $targetCollation),
                'message'
            );
        } catch (\Throwable $exception) {
            $this->setMessage(Text::sprintf('COM_BREEZINGFORMSNG_ABOUT_DB_REPAIR_FAILED', $exception->getMessage()), 'error');
        }

        $this->setRedirect(Route::_('index.php?option=com_breezingformsng&task=about.display&view=about', false));
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

        $this->setRedirect(Route::_('index.php?option=com_breezingformsng&task=about.display&view=about', false));
    }

    public function repairDuplicateIndexes(): void
    {
        $this->checkToken();

        try {
            $this->getAuthorizedApplication();
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

        $this->setRedirect(Route::_('index.php?option=com_breezingformsng&task=about.display&view=about#bf-audit-section', false));
    }

    public function repairColumnCollations(): void
    {
        $this->checkToken();

        try {
            $this->getAuthorizedApplication();
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
            $this->setMessage(
                Text::sprintf('COM_BREEZINGFORMSNG_ABOUT_AUDIT_COLUMN_COLLATION_REPAIR_FAILED', $exception->getMessage()),
                'error'
            );
        }

        $this->setRedirect(Route::_('index.php?option=com_breezingformsng&task=about.display&view=about#bf-audit-section', false));
    }

    public function repairTableCollations(): void
    {
        $this->checkToken();

        try {
            $this->getAuthorizedApplication();
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
            $this->setMessage(
                Text::sprintf('COM_BREEZINGFORMSNG_ABOUT_AUDIT_TABLE_COLLATION_REPAIR_FAILED', $exception->getMessage()),
                'error'
            );
        }

        $this->setRedirect(Route::_('index.php?option=com_breezingformsng&task=about.display&view=about#bf-audit-section', false));
    }

    public function deleteStaleInstallerTemp(): void
    {
        $this->checkToken();

        try {
            $this->getAuthorizedApplication();
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

        $this->setRedirect(Route::_('index.php?option=com_breezingformsng&task=about.display&view=about#bf-audit-section', false));
    }

    public function deleteDuplicateForm(): void
    {
        $this->checkToken();

        $formId = $this->input->getInt('duplicate_form_id', 0);

        try {
            $application = $this->getAuthorizedApplication();

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

            $application
                ->bootComponent('com_breezingformsng')
                ->getMVCFactory()
                ->createModel('Form', 'Administrator', ['ignore_request' => true])
                ->deleteItems([$formId]);

            $application->setUserState('com_breezingformsng.about.audit', $auditService->run());
            $this->setMessage(Text::sprintf('COM_BREEZINGFORMSNG_ABOUT_AUDIT_DUPLICATE_FORM_DELETED', $formId), 'message');
        } catch (\Throwable $exception) {
            $this->setMessage(Text::sprintf('COM_BREEZINGFORMSNG_ABOUT_AUDIT_DUPLICATE_FORM_DELETE_FAILED', $exception->getMessage()), 'error');
        }

        $this->setRedirect(Route::_('index.php?option=com_breezingformsng&task=about.display&view=about', false));
    }

    public function showLog(): void
    {
        $this->checkToken();

        $application = $this->getAuthorizedApplication();

        try {
            $logReport = $this->readAboutLogReport();
            $application->setUserState('com_breezingformsng.about.log', $logReport);
            $this->setMessage(Text::sprintf('COM_BREEZINGFORMSNG_ABOUT_LOG_LOADED', (string) ($logReport['file'] ?? '')), 'message');
        } catch (\Throwable $exception) {
            $application->setUserState('com_breezingformsng.about.log', []);
            $this->setMessage(Text::sprintf('COM_BREEZINGFORMSNG_ABOUT_LOG_LOAD_FAILED', $exception->getMessage()), 'error');
        }

        $this->setRedirect(Route::_('index.php?option=com_breezingformsng&task=about.display&view=about#bf-about-log', false));
    }

    public function exportConfiguration(): void
    {
        $this->checkToken();

        try {
            $app = $this->getAuthorizedApplication();
            $db = $this->getDatabase();
            $tables = [];

            foreach (self::CONFIGURATION_TABLES as $table) {
                if (!$this->tableExists($table)) {
                    continue;
                }

                $query = $db->getQuery(true)->select('*')->from($db->quoteName('#__' . $table));
                $db->setQuery($query);
                $tables[$table] = $db->loadAssocList() ?: [];
            }

            $payload = [
                'component' => 'com_breezingformsng',
                'format' => 'breezingformsng-configuration',
                'version' => 1,
                'exported_at' => (new \Joomla\CMS\Date\Date())->toSql(),
                'tables' => $tables,
            ];
            $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            if (!is_string($json)) {
                throw new \RuntimeException(Text::_('COM_BREEZINGFORMSNG_ABOUT_EXPORT_CONFIGURATION_INVALID'));
            }

            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            $fileName = 'breezingformsng-config-' . (new \Joomla\CMS\Date\Date())->format('Ymd-His') . '.json';
            $app->setHeader('Pragma', 'public', true);
            $app->setHeader('Expires', '0', true);
            $app->setHeader('Cache-Control', 'private', true);
            $app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
            $app->setHeader('Content-Disposition', 'attachment; filename="' . $fileName . '"', true);
            $app->sendHeaders();
            echo $json;
            $app->close();
        } catch (\Throwable $exception) {
            $this->setMessage(Text::sprintf('COM_BREEZINGFORMSNG_ABOUT_EXPORT_CONFIGURATION_FAILED', $exception->getMessage()), 'error');
            $this->setRedirect(Route::_('index.php?option=com_breezingformsng&task=about.display&view=about', false));
        }
    }

    public function importConfiguration(): void
    {
        $this->checkToken();
        $this->getAuthorizedApplication();
        $this->setMessage(Text::_('COM_BREEZINGFORMSNG_ABOUT_IMPORT_CONFIGURATION_PREPARE'), 'warning');
        $this->setRedirect(Route::_('index.php?option=com_breezingformsng&task=about.display&view=about', false));
    }

    private function getAuthorizedApplication()
    {
        $application = $this->app;

        if (!$application->getIdentity()->authorise('core.manage', 'com_breezingformsng')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        return $application;
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

    private function tableExists(string $table): bool
    {
        $db = $this->getDatabase();

        return in_array($db->getPrefix() . $table, $db->getTableList(), true);
    }

    private function readAboutLogReport(): array
    {
        $latestPath = '';
        $latestMtime = 0;

        foreach (self::ABOUT_LOG_FILES as $fileName) {
            $path = JPATH_ADMINISTRATOR . '/logs/' . $fileName;

            if (!is_file($path)) {
                continue;
            }

            $mtime = filemtime($path);

            if ($mtime === false) {
                continue;
            }

            if ($latestPath === '' || $mtime > $latestMtime) {
                $latestPath = $path;
                $latestMtime = (int) $mtime;
            }
        }

        if ($latestPath === '') {
            throw new \RuntimeException(Text::sprintf('COM_BREEZINGFORMSNG_ABOUT_LOG_NOT_FOUND', JPATH_ADMINISTRATOR . '/logs'));
        }

        if (!is_readable($latestPath)) {
            throw new \RuntimeException(basename($latestPath));
        }

        $size = filesize($latestPath);

        if ($size === false) {
            throw new \RuntimeException(basename($latestPath));
        }

        $content = '';
        $truncated = false;

        if ($size > 0) {
            $handle = fopen($latestPath, 'rb');

            if (!is_resource($handle)) {
                throw new \RuntimeException(basename($latestPath));
            }

            if ($size > self::ABOUT_LOG_TAIL_BYTES) {
                $truncated = true;
                fseek($handle, -self::ABOUT_LOG_TAIL_BYTES, SEEK_END);
            }

            $content = (string) stream_get_contents($handle);
            fclose($handle);
        }

        $loadedAt = '';

        if ($latestMtime > 0) {
            $timezone = new \DateTimeZone((string) $this->app->get('offset', 'UTC'));
            $loadedAt = (new \Joomla\CMS\Date\Date('@' . $latestMtime))
                ->setTimezone($timezone)
                ->format('Y-m-d H:i:s', true);
        }

        return [
            'file' => basename($latestPath),
            'size' => $size,
            'loaded_at' => $loadedAt,
            'content' => $content,
            'truncated' => $truncated ? 1 : 0,
            'tail_bytes' => self::ABOUT_LOG_TAIL_BYTES,
        ];
    }
}
