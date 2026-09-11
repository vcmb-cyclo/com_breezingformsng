<?php

/**
 * @package BreezingFormsNG
 * @version 6.0.0
 * @author      Markus Bopp
 * @link        http://breezings.vcmb.fr
 *  * @copyright Copyright (C) 2008-2020 Markus Bopp
 * @copyright Copyright (C) 2025 XDA+GIL
 * @license     GNU/GPL
 */
defined('_JEXEC') or die('Direct Access to this location is not allowed.');

use Joomla\Filesystem\File;
use Joomla\CMS\Factory;
use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Language\Text;
use Joomla\Filesystem\Folder;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Table\Menu;

Log::addLogger(
    [
        'text_file' => 'breezingforms_install.log',
        'text_entry_format' => '{DATETIME}\t{PRIORITY}\t{MESSAGE}',
        'text_file_path'     => JPATH_ADMINISTRATOR . '/logs'
    ],
    Log::ALL,
    ['com_breezingformsng.install']
);


// Logs de démarrage
Log::add('[OK] BreezingformsNG installation/update started.', Log::INFO, 'com_breezingformsng.install');
Log::add('PHP Version: ' . PHP_VERSION . '.', Log::INFO, 'com_breezingformsng.install');
Log::add('Joomla Version : ' . JVERSION . '.', Log::INFO, 'com_breezingformsng.install');
Log::add('User Agent: ' . ($_SERVER['HTTP_USER_AGENT'] ?? 'CLI') . '.', Log::INFO, 'com_breezingformsng.install');

class com_breezingformsngInstallerScript
{
    private const TARGET_COMPONENT = 'com_breezingformsng';
    private const MINIMUM_PHP = '8.3';
    private const MINIMUM_JOOMLA = '6.0';
    private const LEGACY_COMPONENT_ALIASES = [
        'breezingforms',
        'com_breezingforms',
        'com_breezingformsng',
    ];

    private $installStartedAt = 0.0;
    private $incomingPackageVersion = '';
    private $currentInstalledVersion = '0.0.0';
    private $utf8mb4SupportChecked = false;
    private $utf8mb4Supported = false;
    private $utf8mb4Collation = 'utf8mb4_general_ci';
    private $utf8mb4CheckPerformed = false;
    private $utf8mb4CapabilityAnnounced = false;
    private $criticalFailureDetected = false;
    private $criticalFailureMessages = [];
    private $logRotationChecked = false;

    public function __construct()
    {
        $this->installStartedAt = microtime(true);
        $this->applyJoomlaTimezoneForLogging();
        $this->currentInstalledVersion = $this->getCurrentInstalledVersion();
    }

    /**
     * Sets PHP's default timezone to the site's configured one, once, so
     * that Joomla's own Log::add() calls (including the bootstrap ones at
     * the top of this file, executed before this class even exists) and
     * any other date()-based code in this request use the correct offset,
     * not just this script's own custom log line.
     */
    private function applyJoomlaTimezoneForLogging(): void
    {
        try {
            $offset = (string) Factory::getApplication()->get('offset', 'UTC');
            new \DateTimeZone($offset !== '' ? $offset : 'UTC');
            date_default_timezone_set($offset !== '' ? $offset : 'UTC');
        } catch (\Throwable) {
            // Leave PHP's default timezone untouched if it can't be resolved.
        }
    }

    /**
     * Translated installer message with an untranslated-fallback sprintf,
     * matching this project's rule of routing user-facing strings through
     * translation keys even in the installer script.
     */
    private function installerText(string $key, string $fallback, ...$args): string
    {
        try {
            $translated = $args === [] ? Text::_($key) : Text::sprintf($key, ...$args);
        } catch (\Throwable) {
            $translated = $key;
        }

        return $translated === $key ? sprintf($fallback, ...$args) : $translated;
    }

    private function checkRequirements(): bool
    {
        if (!version_compare(PHP_VERSION, self::MINIMUM_PHP, '>=')) {
            $this->log(
                $this->installerText(
                    'COM_BREEZINGFORMSNG_INSTALLER_ERROR_PHP_REQUIRES',
                    'PHP %s is too old. Requires PHP %s or later.',
                    PHP_VERSION,
                    self::MINIMUM_PHP
                ),
                Log::ERROR
            );

            return false;
        }

        if (defined('JVERSION') && !version_compare(JVERSION, self::MINIMUM_JOOMLA, '>=')) {
            $this->log(
                $this->installerText(
                    'COM_BREEZINGFORMSNG_INSTALLER_ERROR_JOOMLA_REQUIRES',
                    'Joomla %s is too old. Requires Joomla %s or later.',
                    JVERSION,
                    self::MINIMUM_JOOMLA
                ),
                Log::ERROR
            );

            return false;
        }

        return true;
    }

    private function checkInstalledFilePermissionsBeforeCopy(): bool
    {
        $paths = $this->getInstalledWritableCheckPaths();
        $blocked = [];

        foreach ($paths as $path) {
            if (!file_exists($path)) {
                continue;
            }

            $blocked = array_merge($blocked, $this->collectNonWritablePaths($path, 25 - count($blocked)));

            if (count($blocked) >= 25) {
                break;
            }
        }

        if ($blocked === []) {
            $this->log('Existing BreezingForms NG files are writable for this update.');

            return true;
        }

        $sample = array_slice($blocked, 0, 10);
        $lines = array_map(
            static fn(string $path): string => '<li><code>' . htmlspecialchars($path, ENT_QUOTES, 'UTF-8') . '</code></li>',
            $sample
        );
        $remaining = max(0, count($blocked) - count($sample));

        if ($remaining > 0) {
            $lines[] = '<li>... +' . $remaining . ' more</li>';
        }

        $this->log(
            'BreezingForms NG update cannot copy files because some installed files or directories are not writable by Joomla.'
            . '<br>Fix ownership/permissions before reinstalling.'
            . '<br>Examples of blocked paths:<ul>' . implode('', $lines) . '</ul>',
            Log::ERROR
        );

        return false;
    }

    private function getInstalledWritableCheckPaths(): array
    {
        return [
            JPATH_ADMINISTRATOR . '/components/com_breezingformsng',
            JPATH_ROOT . '/components/com_breezingformsng',
            JPATH_ROOT . '/media/com_breezingformsng',
        ];
    }

    private function collectNonWritablePaths(string $path, int $limit): array
    {
        if ($limit <= 0) {
            return [];
        }

        if (!is_writable($path)) {
            return [$path];
        }

        if (!is_dir($path)) {
            return [];
        }

        $blocked = [];

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $item) {
                $itemPath = $item instanceof \SplFileInfo ? $item->getPathname() : (string) $item;

                if ($itemPath !== '' && !is_writable($itemPath)) {
                    $blocked[] = $itemPath;

                    if (count($blocked) >= $limit) {
                        break;
                    }
                }
            }
        } catch (\Throwable) {
            if (!is_readable($path)) {
                $blocked[] = $path;
            }
        }

        return $blocked;
    }

    private function detectUtf8mb4Support(): void
    {
        if ($this->utf8mb4SupportChecked) {
            return;
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);

        try {
            $db->setQuery("SHOW CHARACTER SET LIKE 'utf8mb4'");
            $charset = $db->loadAssoc();
            $this->utf8mb4Supported = is_array($charset) && !empty($charset);

            if ($this->utf8mb4Supported) {
                $db->setQuery("SHOW COLLATION WHERE Charset = 'utf8mb4'");
                $collations = (array) $db->loadAssocList();
                $available = [];

                foreach ($collations as $collation) {
                    if (!empty($collation['Collation'])) {
                        $available[] = $collation['Collation'];
                    }
                }

                foreach (['utf8mb4_unicode_ci', 'utf8mb4_general_ci'] as $preferredCollation) {
                    if (in_array($preferredCollation, $available, true)) {
                        $this->utf8mb4Collation = $preferredCollation;
                        break;
                    }
                }

                if (!in_array($this->utf8mb4Collation, $available, true) && !empty($available)) {
                    $this->utf8mb4Collation = $available[0];
                }
            }
        } catch (\Throwable $e) {
            $this->utf8mb4Supported = false;
            $this->log('Unable to detect utf8mb4 support on this database server: ' . $e->getMessage(), Log::WARNING);
        }

        $this->utf8mb4SupportChecked = true;
    }

    private function announceUtf8mb4Capability(): void
    {
        if ($this->utf8mb4CapabilityAnnounced) {
            return;
        }

        $this->detectUtf8mb4Support();

        if ($this->utf8mb4Supported) {
            $message = 'utf8mb4 support detected on the database server. BreezingForms tables will be checked and converted when needed.';
            $this->announce($message . ' Target collation: ' . $this->utf8mb4Collation . '.', 'message', Log::INFO);
        } else {
            $message = 'utf8mb4 is not available on the database server. BreezingForms cannot auto-convert its text columns to utf8mb4 on this installation.';
            $this->announce($message, 'warning', Log::WARNING);
        }

        $this->utf8mb4CapabilityAnnounced = true;
    }

    private function getBreezingFormsTables(DatabaseInterface $db): array
    {
        $prefix = $db->getPrefix();
        $tables = [];

        foreach ($db->getTableList() as $tableName) {
            if (strpos($tableName, $prefix . 'facileforms_') === 0) {
                $tables[] = $tableName;
            }
        }

        sort($tables);

        return $tables;
    }

    private function getUtf8mb4NonCompliantTables(DatabaseInterface $db, array $tables): array
    {
        if (empty($tables)) {
            return [];
        }

        $quotedTables = array_map([$db, 'quote'], $tables);

        $tableQuery = $db->getQuery(true)
            ->select([
                $db->quoteName('TABLE_NAME'),
                $db->quoteName('TABLE_COLLATION'),
            ])
            ->from($db->quoteName('information_schema.TABLES'))
            ->where($db->quoteName('TABLE_SCHEMA') . ' = DATABASE()')
            ->where($db->quoteName('TABLE_NAME') . ' IN (' . implode(',', $quotedTables) . ')');

        $db->setQuery($tableQuery);
        $tableRows = (array) $db->loadAssocList();

        $columnQuery = $db->getQuery(true)
            ->select([
                $db->quoteName('TABLE_NAME'),
                $db->quoteName('COLUMN_NAME'),
                $db->quoteName('CHARACTER_SET_NAME'),
                $db->quoteName('COLLATION_NAME'),
            ])
            ->from($db->quoteName('information_schema.COLUMNS'))
            ->where($db->quoteName('TABLE_SCHEMA') . ' = DATABASE()')
            ->where($db->quoteName('TABLE_NAME') . ' IN (' . implode(',', $quotedTables) . ')')
            ->where($db->quoteName('CHARACTER_SET_NAME') . ' IS NOT NULL');

        $db->setQuery($columnQuery);
        $columnRows = (array) $db->loadAssocList();

        $issues = [];

        foreach ($tableRows as $row) {
            $tableName = $row['TABLE_NAME'] ?? '';
            $tableCollation = (string) ($row['TABLE_COLLATION'] ?? '');

            if ($tableName !== '' && strpos($tableCollation, 'utf8mb4_') !== 0) {
                $issues[$tableName][] = 'table collation=' . ($tableCollation !== '' ? $tableCollation : 'none');
            }
        }

        foreach ($columnRows as $row) {
            $tableName = $row['TABLE_NAME'] ?? '';
            $columnName = $row['COLUMN_NAME'] ?? '';
            $charset = (string) ($row['CHARACTER_SET_NAME'] ?? '');
            $collation = (string) ($row['COLLATION_NAME'] ?? '');

            if ($tableName === '' || $columnName === '') {
                continue;
            }

            if ($charset !== 'utf8mb4' || strpos($collation, 'utf8mb4_') !== 0) {
                $issues[$tableName][] = $columnName . '=' . ($charset !== '' ? $charset : 'none') . '/' . ($collation !== '' ? $collation : 'none');
            }
        }

        return $issues;
    }

    private function ensureUtf8mb4Columns(): void
    {
        if ($this->utf8mb4CheckPerformed) {
            return;
        }

        $this->detectUtf8mb4Support();
        $this->utf8mb4CheckPerformed = true;

        if (!$this->utf8mb4Supported) {
            return;
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $tables = $this->getBreezingFormsTables($db);

        if (empty($tables)) {
            $this->log('No BreezingForms NG tables found yet for utf8mb4 verification.', Log::INFO);
            return;
        }

        $issues = $this->getUtf8mb4NonCompliantTables($db, $tables);

        if (empty($issues)) {
            $message = 'BreezingForms NG utf8mb4 check: all existing component tables already use utf8mb4.';
            $this->announce($message, 'message', Log::INFO);
            return;
        }

        $converted = [];
        $failed = [];

        foreach ($issues as $tableName => $tableIssues) {
            try {
                $db->setQuery(
                    'ALTER TABLE ' . $db->quoteName($tableName) .
                    ' CONVERT TO CHARACTER SET utf8mb4 COLLATE ' . $this->utf8mb4Collation
                )->execute();

                $converted[] = $tableName;
                $this->log(
                    'Converted table ' . $tableName . ' to utf8mb4/' . $this->utf8mb4Collation .
                    '. Previous issues: ' . implode(', ', $tableIssues)
                );
            } catch (\Throwable $e) {
                $failed[] = $tableName;
                $message = 'Failed to convert table ' . $tableName . ' to utf8mb4: ' . $e->getMessage();
                $this->announce($message, 'error', Log::ERROR);
            }
        }

        if (!empty($converted)) {
            $message = 'BreezingForms utf8mb4 correction applied on ' . count($converted) . ' table(s): ' . implode(', ', $converted) . '.';
            $this->announce($message, 'warning', Log::INFO);
        }

        if (empty($failed)) {
            $message = 'BreezingForms utf8mb4 verification completed successfully.';
            $this->announce($message, 'message', Log::INFO);
        }
    }

    private function getTextCollationClause(): string
    {
        $this->detectUtf8mb4Support();

        if (!$this->utf8mb4Supported) {
            return '';
        }

        return ' COLLATE ' . $this->utf8mb4Collation;
    }

    private function migrateLegacyConfig(): void
    {
        $admPath = JPATH_ADMINISTRATOR . '/components/com_breezingformsng';

        require_once $admPath . '/src/Model/ConfigModel.php';

        try {
            $migrated = (new \Vcmb\Component\BreezingformsNG\Administrator\Model\ConfigModel())->migrateFromLegacy();
        } catch (\Throwable $e) {
            $this->log('Legacy config migration failed: ' . $e->getMessage(), Log::WARNING);
            return;
        }

        if ($migrated !== []) {
            $this->log('Legacy config migrated to component params: ' . implode(', ', $migrated));
        }
    }

    private function importStandardLibrary(): void
    {
        $admPath = JPATH_ADMINISTRATOR . '/components/com_breezingformsng';
        $xmlFile = $admPath . '/packages/stdlib.english.xml';

        if (!File::exists($xmlFile)) {
            $this->log("Standard library package not found: {$xmlFile}", Log::WARNING);
            return;
        }

        require_once $admPath . '/src/Model/ImportModel.php';

        $importer = new \Vcmb\Component\BreezingformsNG\Administrator\Model\ImportModel();
        $importer->reinstallOnlyIfChanged = true;

        try {
            $importer->import($xmlFile);
        } catch (\Throwable $e) {
            $this->log('Standard library import failed. ' . $e->getMessage(), Log::ERROR);
            $this->announce('BreezingForms standard pieces import failed: ' . $e->getMessage(), 'error', Log::ERROR);
            return;
        }

        $this->log(
            'Standard library imported successfully: ' .
            count($importer->scripts) . ' script(s), ' .
            count($importer->pieces) . ' piece(s).'
        );

        $this->announceImportedLibraryChanges($importer);
    }

    private function announceImportedLibraryChanges(object $importer): void
    {
        $createdScripts = $this->normalizeImportedItemNames($importer->createdScripts ?? []);
        $createdPieces = $this->normalizeImportedItemNames($importer->createdPieces ?? []);
        $updatedScripts = $this->normalizeImportedItemNames($importer->updatedScripts ?? []);
        $updatedPieces = $this->normalizeImportedItemNames($importer->updatedPieces ?? []);

        if (empty($createdScripts) && empty($createdPieces) && empty($updatedScripts) && empty($updatedPieces)) {
            return;
        }

        $parts = [];

        if (!empty($createdScripts)) {
            $parts[] = Text::sprintf(
                'COM_BREEZINGFORMSNG_INSTALL_STANDARD_LIBRARY_CREATED_SCRIPTS',
                count($createdScripts),
                implode(', ', $createdScripts)
            );
        }

        if (!empty($createdPieces)) {
            $parts[] = Text::sprintf(
                'COM_BREEZINGFORMSNG_INSTALL_STANDARD_LIBRARY_CREATED_PIECES',
                count($createdPieces),
                implode(', ', $createdPieces)
            );
        }

        if (!empty($updatedScripts)) {
            $parts[] = Text::sprintf(
                'COM_BREEZINGFORMSNG_INSTALL_STANDARD_LIBRARY_UPDATED_SCRIPTS',
                count($updatedScripts),
                implode(', ', $updatedScripts)
            );
        }

        if (!empty($updatedPieces)) {
            $parts[] = Text::sprintf(
                'COM_BREEZINGFORMSNG_INSTALL_STANDARD_LIBRARY_UPDATED_PIECES',
                count($updatedPieces),
                implode(', ', $updatedPieces)
            );
        }

        $this->announce(
            Text::sprintf(
                'COM_BREEZINGFORMSNG_INSTALL_STANDARD_LIBRARY_CHANGES',
                implode(' | ', $parts)
            ),
            'warning',
            Log::INFO
        );
    }

    private function normalizeImportedItemNames($items): array
    {
        if (!is_array($items)) {
            return [];
        }

        $items = array_map(
            static fn($item): string => trim((string) $item),
            $items
        );
        $items = array_values(array_filter($items, static fn(string $item): bool => $item !== ''));
        $items = array_values(array_unique($items));
        sort($items, SORT_NATURAL | SORT_FLAG_CASE);

        return $items;
    }

    private function getIncomingVersion($parent): string
    {
        if (is_object($parent) && method_exists($parent, 'getManifest')) {
            $manifest = $parent->getManifest();

            if ($manifest instanceof \SimpleXMLElement) {
                $version = trim((string) ($manifest->version ?? ''));

                if ($version !== '') {
                    return $version;
                }
            }
        }

        $installer = is_object($parent) && method_exists($parent, 'getParent')
            ? $parent->getParent()
            : null;
        $manifest = $installer && method_exists($installer, 'getManifest')
            ? $installer->getManifest()
            : null;

        return $manifest && isset($manifest->version) ? trim((string) $manifest->version) : '';
    }

    private function getLogTimestamp(): string
    {
        try {
            $offset = (string) Factory::getApplication()->get('offset', 'UTC');
            $date   = new \DateTime('now', new \DateTimeZone($offset !== '' ? $offset : 'UTC'));

            return $date->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return date('Y-m-d H:i:s');
        }
    }

    private function rotateLogIfNeeded(string $logPath): void
    {
        // Checked once per installer run (mirrors cbng's boot-time check),
        // rather than re-stat'ing the log file on every single log() call.
        if ($this->logRotationChecked) {
            return;
        }
        $this->logRotationChecked = true;

        $maxBytes = 1024 * 1024;

        try {
            if (!is_file($logPath) || filesize($logPath) < $maxBytes) {
                return;
            }

            $rotatedPath = $logPath . '.' . date('Y-m-d_His') . '.bak';
            @rename($logPath, $rotatedPath);

            $keepFiles = 10;
            $rotatedFiles = glob($logPath . '.*.bak') ?: [];
            sort($rotatedFiles);

            $excess = count($rotatedFiles) - $keepFiles;

            for ($i = 0; $i < $excess; $i++) {
                @unlink($rotatedFiles[$i]);
            }
        } catch (\Throwable) {
            // Best-effort log rotation only.
        }
    }

    private function log(string $message, int $priority = Log::INFO): void
    {
        if ($priority === Log::ERROR) {
            $this->criticalFailureDetected = true;
            $normalized = trim(strip_tags($message));

            if ($normalized !== '' && !in_array($normalized, $this->criticalFailureMessages, true)) {
                $this->criticalFailureMessages[] = $normalized;
            }
        }

        $message = $this->prefixMessage($message, $priority);
        Log::add($message, $priority, 'com_breezingformsng.install');

        $logPath = JPATH_ADMINISTRATOR . '/logs/breezingforms_install2.log';

        if (!Folder::exists(dirname($logPath))) {
            Folder::create(dirname($logPath));
        }

        $this->rotateLogIfNeeded($logPath);

        $timestamp = $this->getLogTimestamp();
        $line = "[{$timestamp}] [] {$message}" . PHP_EOL;

        file_put_contents($logPath, $line, FILE_APPEND | LOCK_EX);

        try {
            $type = match ($priority) {
                Log::ERROR => 'error',
                Log::WARNING => 'warning',
                default => 'message',
            };

            Factory::getApplication()->enqueueMessage($this->formatInstallMessageForDisplay($message), $type);
        } catch (\Throwable) {
        }
    }

    private function announce(string $message, string $type = 'message', int $priority = Log::INFO): void
    {
        $this->log($message, $priority);
    }

    private function resetCriticalFailures(): void
    {
        $this->criticalFailureDetected = false;
        $this->criticalFailureMessages = [];
    }

    private function hasCriticalFailure(): bool
    {
        return $this->criticalFailureDetected;
    }

    private function getCriticalFailureSummary(int $max = 3): string
    {
        if ($this->criticalFailureMessages === []) {
            return '';
        }

        $messages = array_slice($this->criticalFailureMessages, 0, $max);
        $summary = implode(' | ', $messages);
        $remaining = count($this->criticalFailureMessages) - count($messages);

        if ($remaining > 0) {
            $summary .= ' | +' . $remaining . ' more';
        }

        return $summary;
    }

    private function prefixMessage(string $message, int $priority = Log::INFO): string
    {
        if (preg_match('/^\[(OK|INFO|WARNING|ERROR)\]\s/u', $message)) {
            return $message;
        }

        return match ($priority) {
            Log::ERROR => '[ERROR] ' . $message,
            Log::WARNING => '[WARNING] ' . $message,
            default => '[INFO] ' . $message,
        };
    }

    private function formatInstallMessageForDisplay(string $message): string
    {
        return str_replace(
            ['[OK]', '[INFO]', '[WARNING]', '[ERROR]'],
            [
                '<span style="color:#198754;font-weight:700;" aria-hidden="true">&#10003;</span>',
                '<span style="color:#0d6efd;font-weight:700;" aria-hidden="true">&#9432;</span>',
                '<span style="color:#fd7e14;font-weight:700;" aria-hidden="true">&#9888;</span>',
                '<span style="color:#dc3545;font-weight:700;" aria-hidden="true">&#10060;</span>',
            ],
            $message
        );
    }

    private function getDatabaseRuntimeLabel(): string
    {
        try {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $serverType = method_exists($db, 'getServerType') ? (string) $db->getServerType() : 'database';
            $version = method_exists($db, 'getVersion') ? trim((string) $db->getVersion()) : '';
            $prefix = method_exists($db, 'getPrefix') ? (string) $db->getPrefix() : '';

            $this->checkDatabaseSessionCharset($db);

            return trim($serverType . ' ' . $version) . ($prefix !== '' ? ' (prefix: ' . $prefix . ')' : '');
        } catch (\Throwable $e) {
            $this->log('Unable to detect database runtime information: ' . $e->getMessage(), Log::WARNING);
            return 'unknown';
        }
    }

    /**
     * Complements the existing table/column utf8mb4 conversion logic: even
     * with every table and column correctly converted, data can still be
     * mangled on write/read if the DB *connection itself* isn't negotiating
     * utf8mb4 (e.g. a driver/config defaulting the session to latin1).
     */
    private function checkDatabaseSessionCharset(DatabaseInterface $db): void
    {
        try {
            $db->setQuery('SELECT @@character_set_connection, @@collation_connection');
            $row = $db->loadAssoc() ?: [];
            $charset = strtolower((string) ($row['@@character_set_connection'] ?? ''));
            $collation = (string) ($row['@@collation_connection'] ?? '');

            if ($charset !== '' && $charset !== 'utf8mb4') {
                $this->log(
                    "Database session charset is '{$charset}' (collation '{$collation}'), not utf8mb4 - "
                    . 'the connection may not be negotiating utf8mb4 even if tables/columns are converted.',
                    Log::WARNING
                );
            }
        } catch (\Throwable) {
            // Best-effort diagnostic only; not every driver exposes these session variables.
        }
    }

    private function purgeCaches(string $context = 'install'): void
    {
        $autoloadFiles = [
            JPATH_ADMINISTRATOR . '/cache/autoload_psr4.php',
            JPATH_ADMINISTRATOR . '/cache/autoload_classmap.php',
            JPATH_ADMINISTRATOR . '/cache/autoload_namespaces.php',
        ];

        $deleted = 0;

        foreach ($autoloadFiles as $file) {
            try {
                if (is_file($file) && @unlink($file)) {
                    $deleted++;
                }
            } catch (\Throwable) {
                // Best-effort cache cleanup.
            }
        }

        if ($deleted > 0) {
            $this->log("Autoload cache cleared ({$deleted} file(s)) during {$context}.");
        }

        try {
            $app = Factory::getApplication();
            if (method_exists($app, 'createExtensionNamespaceMap')) {
                $app->createExtensionNamespaceMap();
                $this->log("Extension namespace map rebuilt during {$context}.");
            }
        } catch (\Throwable) {
            // Without this, a stale in-memory namespace map from earlier in
            // the same installer request could fail to autoload classes
            // that were just added/renamed by this same install/update.
        }

        $cacheGroups = ['_system', 'com_installer', 'com_plugins', 'com_breezingformsng'];

        try {
            $cacheFactory = Factory::getContainer()->get(CacheControllerFactoryInterface::class);

            if (is_object($cacheFactory)) {
                foreach ($cacheGroups as $group) {
                    try {
                        $cacheFactory->createCacheController('callback', [
                            'defaultgroup' => $group,
                            'cachebase' => JPATH_ROOT . '/cache',
                        ])->clean();
                    } catch (\Throwable) {
                        // Continue with the remaining cache groups.
                    }
                }
            }

            $this->log("Joomla cache cleaned (best-effort) during {$context}.");
        } catch (\Throwable $e) {
            $this->log("Joomla cache cleanup failed during {$context}: " . $e->getMessage(), Log::WARNING);
        }

        try {
            if (function_exists('opcache_reset')) {
                @opcache_reset();
                $this->log("OPcache reset attempted during {$context}.");
            }
        } catch (\Throwable) {
            // Best-effort OPcache reset.
        }
    }

    private function verifyInstalledExtensionConsistency($parent): void
    {
        $requiredFiles = [
            JPATH_ADMINISTRATOR . '/components/com_breezingformsng/services/provider.php',
            JPATH_ADMINISTRATOR . '/components/com_breezingformsng/src/Extension/BreezingFormsNGComponent.php',
            JPATH_SITE . '/components/com_breezingformsng/breezingformsng.php',
        ];

        foreach ($requiredFiles as $requiredFile) {
            if (!is_file($requiredFile)) {
                $this->log(
                    'Installed files inconsistent: required file missing after copy: ' . $requiredFile
                    . '. The package files were not deployed correctly - reinstall the package.',
                    Log::ERROR
                );

                return;
            }
        }

        $expectedVersion = $this->getIncomingVersion($parent);

        if ($expectedVersion === '') {
            $this->log('Installed files consistency: package version unknown, version check skipped.');

            return;
        }

        $installedVersion = $this->getCurrentInstalledVersion();

        if ($installedVersion !== '0.0.0' && $installedVersion !== $expectedVersion) {
            $this->log(
                'Installed files inconsistent: extension manifest cache reports version "' . $installedVersion
                . '" but the installed package is "' . $expectedVersion
                . '". The package files may not have been deployed correctly.',
                Log::ERROR
            );

            return;
        }

        $this->log('Installed files consistency verified.');
    }

    private function getCurrentInstalledVersion()
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select($db->quoteName('manifest_cache'))
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('element') . ' = ' . $db->quote('com_breezingformsng'));

        $db->setQuery($query);
        $manifest = $db->loadResult();

        if ($manifest) {
            $manifest = json_decode($manifest, true);
            $version = $manifest['version'] ?? '0.0.0';
        } else {
            $version = '0.0.0';
        }

        $this->log('Detected current version : ' . $version . '.');
        return $version;
    }

    private function installPlugins(): void
    {
        $basePath = JPATH_ADMINISTRATOR . '/components/com_breezingformsng/plugins';

        if (!Folder::exists($basePath)) {
            $this->log('Plugins directory not found – skipping plugin installation.', Log::WARNING);
            return;
        }

        $folders = Folder::folders($basePath);
        if (empty($folders)) {
            return;
        }

        foreach ($folders as $folder) {
            $this->log("Installing plugin from folder: {$folder}");

            // Installer keeps manifest state; use a fresh instance for every
            // bundled extension so each plugin receives its own database row.
            $installer = new Installer();
            $installer->setDatabase(Factory::getContainer()->get(DatabaseInterface::class));

            if ($installer->install($basePath . '/' . $folder)) {
                $this->log("Plugin {$folder} installed successfully.", Log::INFO);
            } else {
                $this->announce("Failed to install BreezingForms plugin: {$folder}", 'error', Log::ERROR);
            }
        }

        // Activation avec driver standard (safe)
        $standardDb = Factory::getContainer()->get(DatabaseInterface::class);

        foreach ($this->getPlugins() as $folder => $plugins) {
            foreach ($plugins as $plugin) {
                $query = $standardDb->getQuery(true)
                    ->update('#__extensions')
                    ->set('enabled = 1')
                    ->where('type = ' . $standardDb->quote('plugin'))
                    ->where('element = ' . $standardDb->quote($plugin))
                    ->where('folder = ' . $standardDb->quote($folder));

                $standardDb->setQuery($query)->execute();
                $this->log("Plugin {$plugin} enabled.");
            }
        }
    }

    private function removeOldUpdateSite(): void
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $query = $db->getQuery(true)
            ->select('update_site_id')
            ->from('#__update_sites')
            ->where('name = ' . $db->quote('BreezingForms Free'))
            ->where('type = ' . $db->quote('extension'));

        $db->setQuery($query);
        $siteId = $db->loadResult();

        if ($siteId) {
            $db->setQuery('DELETE FROM #__update_sites WHERE update_site_id = ' . (int)$siteId)->execute();
            $db->setQuery('DELETE FROM #__update_sites_extensions WHERE update_site_id = ' . (int)$siteId)->execute();
            $db->setQuery('DELETE FROM #__updates WHERE update_site_id = ' . (int)$siteId)->execute();

            $this->log('Old BreezingForms Free update site removed.');
        }
    }

    private function cleanupOldConfig(): void
    {
        $oldConfig = JPATH_SITE . '/media/breezingforms/facileforms.config.php';
        if (File::exists($oldConfig) && File::delete($oldConfig)) {
            $this->log('Old config file removed: facileforms.config.php');
        }
    }

    private function cleanupComponentLogFile(): void
    {
        $logFile = JPATH_ADMINISTRATOR . '/logs/com_breezingformsng.log';

        if (!is_file($logFile)) {
            return;
        }

        try {
            if (File::delete($logFile)) {
                $this->log('Component log file removed: com_breezingformsng.log');
            }
        } catch (\Throwable $e) {
            $this->log('Unable to remove component log file during uninstall: ' . $e->getMessage(), Log::WARNING);
        }
    }

    private function copyComponentImageAssets(): void
    {
        $sourceImages = JPATH_SITE . '/media/com_breezingformsng/images/site';
        $targetRoot = JPATH_SITE . '/images';
        $directories = ['icons', 'galerie/breezingforms'];

        if (!Folder::exists($sourceImages)) {
            $this->log("Component image directory not found: {$sourceImages}. Skipping copy.");
            return;
        }

        foreach ($directories as $directory) {
            $sourceDir = $sourceImages . '/' . $directory;
            if (!Folder::exists($sourceDir)) {
                continue;
            }

            $relativeCounter = 0;
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($sourceDir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($iterator as $file) {
                if (!$file->isFile()) {
                    continue;
                }

                $relativePath = ltrim(str_replace('\\', '/', substr($file->getPathname(), strlen($sourceImages))), '/');
                if ($relativePath === '') {
                    continue;
                }

                $targetPath = $targetRoot . '/' . $relativePath;
                $targetDir = dirname($targetPath);

                if (!Folder::exists($targetDir)) {
                    Folder::create($targetDir);
                }

                if (!@copy($file->getPathname(), $targetPath)) {
                    $this->log("Failed to copy component image '{$relativePath}' into /images.", Log::WARNING);
                    continue;
                }

                $relativeCounter++;
            }

            if ($relativeCounter > 0) {
                $this->log("Copied {$relativeCounter} file(s) from '{$directory}' into /images.");
            }
        }
    }

    private function buildMenuLinkOptionWhereClauses(DatabaseInterface $db, string $option): array
    {
        $quotedOption = $db->quote('%option=' . $option . '%');

        return [
            $db->quoteName('link') . ' LIKE ' . $quotedOption,
            $db->quoteName('params') . ' LIKE ' . $db->quote('%"option":"' . $option . '"%'),
        ];
    }

    private function removeBreezingFormsMenuEntries(): void
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $conditions = [];

        foreach (self::LEGACY_COMPONENT_ALIASES as $alias) {
            $conditions = array_merge($conditions, $this->buildMenuLinkOptionWhereClauses($db, $alias));
        }

        if ($conditions === []) {
            return;
        }

        try {
            $db->setQuery(
                $db->getQuery(true)
                    ->delete($db->quoteName('#__menu'))
                    ->where($db->quoteName('client_id') . ' = 1')
                    ->where('(' . implode(' OR ', $conditions) . ')')
            );
            $db->execute();
            $this->log('BreezingForms admin menu entries removed (best-effort).');
        } catch (\Throwable $e) {
            $this->log('Unable to remove BreezingForms admin menu entries: ' . $e->getMessage(), Log::WARNING);
        }
    }

    private function cleanupExtensionReferences(array $extensionIds): void
    {
        $extensionIds = array_values(array_filter(array_map('intval', $extensionIds), static fn(int $id): bool => $id > 0));

        if ($extensionIds === []) {
            return;
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $ids = implode(',', $extensionIds);

        foreach (['#__schemas', '#__update_sites_extensions'] as $table) {
            try {
                $db->setQuery(
                    $db->getQuery(true)
                        ->delete($db->quoteName($table))
                        ->where($db->quoteName('extension_id') . ' IN (' . $ids . ')')
                );
                $db->execute();
            } catch (\Throwable $e) {
                $this->log("Unable to clean {$table} for BreezingForms legacy extension rows: " . $e->getMessage(), Log::WARNING);
            }
        }
    }

    private function getComponentExtensionId(string $element): int
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        try {
            $db->setQuery(
                $db->getQuery(true)
                    ->select($db->quoteName('extension_id'))
                    ->from($db->quoteName('#__extensions'))
                    ->where($db->quoteName('type') . ' = ' . $db->quote('component'))
                    ->where($db->quoteName('element') . ' = ' . $db->quote($element))
                    ->where($db->quoteName('client_id') . ' = 1')
            );

            return (int) $db->loadResult();
        } catch (\Throwable $e) {
            $this->log("Unable to inspect component extension row {$element}: " . $e->getMessage(), Log::WARNING);
            return 0;
        }
    }

    private function normalizeJoomlaComponentReferences(): void
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $targetElement = self::TARGET_COMPONENT;
        $targetId = $this->getComponentExtensionId($targetElement);

        if ($targetId <= 0) {
            $this->log('BFNG component row not found; Joomla component reference normalization skipped.', Log::WARNING);
            return;
        }

        foreach (['breezingforms', 'com_breezingforms'] as $legacyElement) {
            $legacyId = $this->getComponentExtensionId($legacyElement);

            try {
                $query = $db->getQuery(true)
                    ->update($db->quoteName('#__menu'))
                    ->set($db->quoteName('component_id') . ' = ' . (int) $targetId)
                    ->set($db->quoteName('link') . ' = REPLACE(' . $db->quoteName('link') . ', ' . $db->quote('option=' . $legacyElement) . ', ' . $db->quote('option=' . $targetElement) . ')')
                    ->set($db->quoteName('params') . ' = REPLACE(' . $db->quoteName('params') . ', ' . $db->quote('"option":"' . $legacyElement . '"') . ', ' . $db->quote('"option":"' . $targetElement . '"') . ')')
                    ->where(
                        '('
                        . $db->quoteName('link') . ' LIKE ' . $db->quote('%option=' . $legacyElement . '%')
                        . ' OR ' . $db->quoteName('params') . ' LIKE ' . $db->quote('%"option":"' . $legacyElement . '"%')
                        . ')'
                    );

                if ($legacyElement === 'com_breezingforms') {
                    $query->where($db->quoteName('link') . ' NOT LIKE ' . $db->quote('%option=' . $targetElement . '%'));
                    $query->where($db->quoteName('params') . ' NOT LIKE ' . $db->quote('%"option":"' . $targetElement . '"%'));
                }

                $db->setQuery($query);
                $db->execute();

                $updated = (int) $db->getAffectedRows();
                if ($updated > 0) {
                    $this->log("Normalized {$updated} Joomla menu row(s) from {$legacyElement} to {$targetElement}.");
                }
            } catch (\Throwable $e) {
                $this->log("Unable to normalize Joomla menu links for {$legacyElement}: " . $e->getMessage(), Log::WARNING);
            }

            if ($legacyId > 0) {
                try {
                    $db->setQuery(
                        $db->getQuery(true)
                            ->update($db->quoteName('#__menu'))
                            ->set($db->quoteName('component_id') . ' = ' . (int) $targetId)
                            ->where($db->quoteName('component_id') . ' = ' . (int) $legacyId)
                    );
                    $db->execute();
                } catch (\Throwable $e) {
                    $this->log("Unable to remap Joomla menu component_id for {$legacyElement}: " . $e->getMessage(), Log::WARNING);
                }
            }

            $this->normalizeComponentAssetName($legacyElement, $targetElement);
        }

        $this->normalizeJoomlaMenuIdentity($targetId);
    }

    private function normalizeJoomlaMenuIdentity(int $targetId): void
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $oldTitleBase = 'COM_' . 'BREEZINGFORMS';
        $newTitleBase = 'COM_BREEZINGFORMSNG';
        $oldAliasBase = 'com-breezingforms';
        $newAliasBase = 'com-breezingformsng';

        try {
            $db->setQuery(
                $db->getQuery(true)
                    ->select($db->quoteName(['id', 'title', 'alias', 'path', 'link', 'params']))
                    ->from($db->quoteName('#__menu'))
                    ->where($db->quoteName('client_id') . ' = 1')
                    ->where(
                        '('
                        . $db->quoteName('title') . ' LIKE ' . $db->quote($oldTitleBase . '%')
                        . ' OR ' . $db->quoteName('alias') . ' LIKE ' . $db->quote('%' . $oldAliasBase . '%')
                        . ' OR ' . $db->quoteName('path') . ' LIKE ' . $db->quote('%' . $oldAliasBase . '%')
                        . ' OR ' . $db->quoteName('link') . ' LIKE ' . $db->quote('%option=breezingforms%')
                        . ' OR ' . $db->quoteName('link') . ' LIKE ' . $db->quote('%option=com_breezingforms%')
                        . ' OR ' . $db->quoteName('link') . ' LIKE ' . $db->quote('%option=' . self::TARGET_COMPONENT . '%')
                        . ')'
                    )
            );

            $rows = $db->loadObjectList() ?: [];
            $updated = 0;

            foreach ($rows as $row) {
                $title = (string) ($row->title ?? '');
                $alias = (string) ($row->alias ?? '');
                $path = (string) ($row->path ?? '');
                $link = (string) ($row->link ?? '');
                $params = (string) ($row->params ?? '');

                if ($title === $oldTitleBase) {
                    $title = $newTitleBase;
                } elseif (str_starts_with($title, $oldTitleBase . '_')) {
                    $title = $newTitleBase . substr($title, strlen($oldTitleBase));
                }

                $alias = preg_replace('/com-breezingforms(?:ng)*/', $newAliasBase, $alias) ?? $alias;
                $path = preg_replace('/com-breezingforms(?:ng)*/', $newAliasBase, $path) ?? $path;
                $link = preg_replace('/option=com_breezingforms(?:ng)*/', 'option=' . self::TARGET_COMPONENT, $link) ?? $link;
                $link = str_replace('option=breezingforms', 'option=' . self::TARGET_COMPONENT, $link);
                $params = preg_replace('/"option":"com_breezingforms(?:ng)*"/', '"option":"' . self::TARGET_COMPONENT . '"', $params) ?? $params;
                $params = str_replace('"option":"breezingforms"', '"option":"' . self::TARGET_COMPONENT . '"', $params);

                $db->setQuery(
                    $db->getQuery(true)
                        ->update($db->quoteName('#__menu'))
                        ->set($db->quoteName('component_id') . ' = ' . (int) $targetId)
                        ->set($db->quoteName('title') . ' = ' . $db->quote($title))
                        ->set($db->quoteName('alias') . ' = ' . $db->quote($alias))
                        ->set($db->quoteName('path') . ' = ' . $db->quote($path))
                        ->set($db->quoteName('link') . ' = ' . $db->quote($link))
                        ->set($db->quoteName('params') . ' = ' . $db->quote($params))
                        ->where($db->quoteName('id') . ' = ' . (int) $row->id)
                );
                $db->execute();
                $updated += (int) $db->getAffectedRows();
            }

            if ($updated > 0) {
                $this->log("Normalized {$updated} Joomla administration menu identity row(s) for " . self::TARGET_COMPONENT . '.');
            }
        } catch (\Throwable $e) {
            $this->log('Unable to normalize Joomla administration menu identity: ' . $e->getMessage(), Log::WARNING);
        }
    }

    private function normalizeComponentAssetName(string $legacyElement, string $targetElement): void
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        try {
            $db->setQuery(
                $db->getQuery(true)
                    ->select('COUNT(*)')
                    ->from($db->quoteName('#__assets'))
                    ->where($db->quoteName('name') . ' = ' . $db->quote($targetElement))
            );
            $targetExists = (int) $db->loadResult() > 0;

            if ($targetExists) {
                return;
            }

            $db->setQuery(
                $db->getQuery(true)
                    ->update($db->quoteName('#__assets'))
                    ->set($db->quoteName('name') . ' = ' . $db->quote($targetElement))
                    ->set($db->quoteName('title') . ' = ' . $db->quote('COM_BREEZINGFORMSNG'))
                    ->where($db->quoteName('name') . ' = ' . $db->quote($legacyElement))
            );
            $db->execute();

            if ((int) $db->getAffectedRows() > 0) {
                $this->log("Normalized Joomla asset from {$legacyElement} to {$targetElement}.");
            }
        } catch (\Throwable $e) {
            $this->log("Unable to normalize Joomla asset {$legacyElement}: " . $e->getMessage(), Log::WARNING);
        }
    }

    private function resolveMenuAlias(int $parentId, string $preferredAlias): string
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $alias = $preferredAlias;
        $suffix = 2;

        while (true) {
            $db->setQuery(
                $db->getQuery(true)
                    ->select('COUNT(*)')
                    ->from($db->quoteName('#__menu'))
                    ->where($db->quoteName('client_id') . ' = 1')
                    ->where($db->quoteName('parent_id') . ' = ' . (int) $parentId)
                    ->where($db->quoteName('alias') . ' = ' . $db->quote($alias))
            );

            if ((int) $db->loadResult() === 0) {
                return $alias;
            }

            $alias = $preferredAlias . '-' . $suffix;
            $suffix++;
        }
    }

    /**
     * The #__menu root row (id=1) is the anchor every nested-set menu
     * computation below relies on (reading its rgt to compute new lft/rgt
     * values). Rather than guessing at a repair insert for a core Joomla
     * table, just detect a missing/corrupt root and skip the menu-repair
     * routines entirely when it can't be trusted.
     */
    private function ensureAdminMenuRootNodeExists(): bool
    {
        try {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $db->setQuery(
                $db->getQuery(true)
                    ->select($db->quoteName(['id', 'lft', 'rgt']))
                    ->from($db->quoteName('#__menu'))
                    ->where($db->quoteName('id') . ' = 1')
            );
            $root = $db->loadAssoc();

            if ($root === null) {
                $this->log('#__menu root node (id=1) is missing; skipping admin menu repair to avoid computing invalid nested-set values.', Log::WARNING);
                return false;
            }

            if ((int) $root['rgt'] <= (int) $root['lft']) {
                $this->log('#__menu root node (id=1) has invalid lft/rgt values; skipping admin menu repair.', Log::WARNING);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            $this->log('Unable to verify #__menu root node: ' . $e->getMessage(), Log::WARNING);
            return false;
        }
    }

    private function ensureAdministrationMainMenuEntry(): void
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $componentId = $this->getComponentExtensionId(self::TARGET_COMPONENT);

        if ($componentId <= 0) {
            $this->log('Cannot ensure BFNG admin menu entry: component row is missing.', Log::WARNING);
            return;
        }

        try {
            $db->setQuery(
                $db->getQuery(true)
                    ->select($db->quoteName(['id', 'alias', 'path']))
                    ->from($db->quoteName('#__menu'))
                    ->where($db->quoteName('client_id') . ' = 1')
                    ->where($db->quoteName('parent_id') . ' = 1')
                    ->where($db->quoteName('type') . ' = ' . $db->quote('component'))
                    ->where(
                        '('
                        . $db->quoteName('component_id') . ' = ' . (int) $componentId
                        . ' OR ' . $db->quoteName('link') . ' LIKE ' . $db->quote('index.php?option=' . self::TARGET_COMPONENT . '%')
                        . ')'
                    )
                    ->order($db->quoteName('id') . ' ASC')
            );
            $mainRows = $db->loadAssocList() ?: [];

            if ($mainRows !== []) {
                $mainId = (int) $mainRows[0]['id'];
                $alias = trim((string) ($mainRows[0]['alias'] ?? ''));
                $path = trim((string) ($mainRows[0]['path'] ?? ''));

                if ($alias === '') {
                    $alias = $this->resolveMenuAlias(1, 'breezingformsng');
                }

                if ($path === '') {
                    $path = $alias;
                }

                $db->setQuery(
                    $db->getQuery(true)
                        ->update($db->quoteName('#__menu'))
                        ->set($db->quoteName('title') . ' = ' . $db->quote('COM_BREEZINGFORMSNG'))
                        ->set($db->quoteName('alias') . ' = ' . $db->quote($alias))
                        ->set($db->quoteName('path') . ' = ' . $db->quote($path))
                        ->set($db->quoteName('link') . ' = ' . $db->quote('index.php?option=' . self::TARGET_COMPONENT))
                        ->set($db->quoteName('type') . ' = ' . $db->quote('component'))
                        ->set($db->quoteName('published') . ' = 1')
                        ->set($db->quoteName('component_id') . ' = ' . (int) $componentId)
                        ->set($db->quoteName('client_id') . ' = 1')
                        ->where($db->quoteName('id') . ' = ' . (int) $mainId)
                );
                $db->execute();
                $this->log('BFNG administration component menu entry checked and updated.');
                return;
            }

            $db->setQuery(
                $db->getQuery(true)
                    ->select($db->quoteName(['id', 'rgt']))
                    ->from($db->quoteName('#__menu'))
                    ->where($db->quoteName('id') . ' = 1')
            );
            $root = $db->loadAssoc() ?: [];
            $rootId = (int) ($root['id'] ?? 1);
            $rootRgt = (int) ($root['rgt'] ?? 0);

            if ($rootRgt <= 0) {
                $this->log('Cannot recreate BFNG admin menu entry: invalid root menu boundary.', Log::WARNING);
                return;
            }

            $alias = $this->resolveMenuAlias($rootId, 'breezingformsng');

            $db->setQuery(
                $db->getQuery(true)
                    ->update($db->quoteName('#__menu'))
                    ->set($db->quoteName('rgt') . ' = ' . $db->quoteName('rgt') . ' + 2')
                    ->where($db->quoteName('rgt') . ' >= ' . (int) $rootRgt)
            );
            $db->execute();

            $db->setQuery(
                $db->getQuery(true)
                    ->update($db->quoteName('#__menu'))
                    ->set($db->quoteName('lft') . ' = ' . $db->quoteName('lft') . ' + 2')
                    ->where($db->quoteName('lft') . ' > ' . (int) $rootRgt)
            );
            $db->execute();

            $columns = [
                'menutype',
                'title',
                'alias',
                'note',
                'path',
                'link',
                'type',
                'published',
                'parent_id',
                'level',
                'component_id',
                'checked_out',
                'checked_out_time',
                'browserNav',
                'access',
                'img',
                'template_style_id',
                'params',
                'lft',
                'rgt',
                'home',
                'language',
                'client_id',
            ];

            $values = [
                $db->quote('main'),
                $db->quote('COM_BREEZINGFORMSNG'),
                $db->quote($alias),
                $db->quote(''),
                $db->quote($alias),
                $db->quote('index.php?option=' . self::TARGET_COMPONENT),
                $db->quote('component'),
                1,
                $rootId,
                1,
                $componentId,
                0,
                'NULL',
                0,
                1,
                $db->quote('class:component'),
                0,
                $db->quote(''),
                $rootRgt,
                $rootRgt + 1,
                0,
                $db->quote('*'),
                1,
            ];

            $db->setQuery(
                $db->getQuery(true)
                    ->insert($db->quoteName('#__menu'))
                    ->columns($db->quoteName($columns))
                    ->values(implode(', ', $values))
            );
            $db->execute();

            $this->log('BFNG administration component menu entry recreated.');
        } catch (\Throwable $e) {
            $this->log('Unable to ensure BFNG administration menu entry: ' . $e->getMessage(), Log::WARNING);
        }
    }

    private function ensureAdministrationSubmenuEntries(): void
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $componentId = $this->getComponentExtensionId(self::TARGET_COMPONENT);

        if ($componentId <= 0) {
            $this->log('Cannot ensure BFNG admin submenu entries: component row is missing.', Log::WARNING);
            return;
        }

        try {
            $db->setQuery(
                $db->getQuery(true)
                    ->select($db->quoteName(['id', 'alias', 'path']))
                    ->from($db->quoteName('#__menu'))
                    ->where($db->quoteName('client_id') . ' = 1')
                    ->where($db->quoteName('parent_id') . ' = 1')
                    ->where($db->quoteName('link') . ' = ' . $db->quote('index.php?option=' . self::TARGET_COMPONENT))
                    ->where($db->quoteName('type') . ' = ' . $db->quote('component'))
                    ->order($db->quoteName('id') . ' ASC')
            );
            $parent = $db->loadAssoc() ?: [];
            $parentId = (int) ($parent['id'] ?? 0);

            if ($parentId <= 0) {
                $this->log('Cannot ensure BFNG admin submenu entries: parent menu entry is missing.', Log::WARNING);
                return;
            }

            $parentPath = trim((string) ($parent['path'] ?? ''));

            if ($parentPath === '') {
                $parentPath = trim((string) ($parent['alias'] ?? 'breezingformsng'));
            }

            $items = [
                ['COM_BREEZINGFORMSNG_MANAGE_RECORDS', 'breezingformsng-records', 'view=records', []],
                [
                    'COM_BREEZINGFORMSNG_MANAGE_FORMS',
                    'breezingformsng-forms',
                    'view=forms',
                    [
                        'menu-quicktask' => 'index.php?option=' . self::TARGET_COMPONENT . '&task=forms.edit',
                        'menu-quicktask-title' => 'COM_BREEZINGFORMSNG_MENUS_NEW_FORM',
                        'menu-quicktask-icon' => 'plus',
                    ],
                ],
                [
                    'COM_BREEZINGFORMSNG_MANAGE_SCRIPTS',
                    'breezingformsng-scripts',
                    'view=scripts',
                    [
                        'menu-quicktask' => 'index.php?option=' . self::TARGET_COMPONENT . '&view=scripts&task=scripts.add',
                        'menu-quicktask-title' => 'COM_BREEZINGFORMSNG_MENUS_NEW_SCRIPT',
                        'menu-quicktask-icon' => 'plus',
                    ],
                ],
                [
                    'COM_BREEZINGFORMSNG_MANAGE_PIECES',
                    'breezingformsng-pieces',
                    'view=pieces',
                    [
                        'menu-quicktask' => 'index.php?option=' . self::TARGET_COMPONENT . '&view=pieces&task=pieces.add',
                        'menu-quicktask-title' => 'COM_BREEZINGFORMSNG_MENUS_NEW_PIECE',
                        'menu-quicktask-icon' => 'plus',
                    ],
                ],
                [
                    'COM_BREEZINGFORMSNG_INTEGRATOR',
                    'breezingformsng-integrator',
                    'view=integrator',
                    [
                        'menu-quicktask' => 'index.php?option=' . self::TARGET_COMPONENT . '&task=integrator.edit',
                        'menu-quicktask-title' => 'COM_BREEZINGFORMSNG_MENUS_NEW_INTEGRATION',
                        'menu-quicktask-icon' => 'plus',
                    ],
                ],
                [
                    'COM_BREEZINGFORMSNG_CONFIGURATION',
                    'breezingformsng-configuration',
                    'index.php?option=com_config&view=component&component=' . self::TARGET_COMPONENT,
                    [],
                ],
                ['COM_BREEZINGFORMSNG_ABOUT', 'breezingformsng-about', 'task=about.display&view=about', []],
            ];

            $checked = 0;

            foreach ($items as [$title, $alias, $route, $params]) {
                $link = str_starts_with($route, 'index.php?')
                    ? $route
                    : 'index.php?option=' . self::TARGET_COMPONENT . '&' . $route;

                $db->setQuery(
                    $db->getQuery(true)
                        ->select($db->quoteName('id'))
                        ->from($db->quoteName('#__menu'))
                        ->where($db->quoteName('client_id') . ' = 1')
                        ->where(
                            '('
                            . $db->quoteName('link') . ' = ' . $db->quote($link)
                            . ' OR ' . $db->quoteName('title') . ' = ' . $db->quote($title)
                            . ')'
                        )
                        ->order($db->quoteName('id') . ' ASC')
                );
                $menuId = (int) $db->loadResult();

                $menu = new Menu($db);

                if ($menuId > 0) {
                    $menu->load($menuId);
                }

                $menu->setLocation($parentId, 'last-child');

                $data = [
                    'menutype' => 'main',
                    'title' => $title,
                    'alias' => $alias,
                    'note' => '',
                    'path' => $parentPath . '/' . $alias,
                    'link' => $link,
                    'type' => 'component',
                    'published' => 1,
                    'parent_id' => $parentId,
                    'level' => 2,
                    'component_id' => $componentId,
                    'checked_out' => 0,
                    'checked_out_time' => null,
                    'browserNav' => 0,
                    'access' => 1,
                    'img' => 'class:component',
                    'template_style_id' => 0,
                    'params' => $params,
                    'home' => 0,
                    'language' => '*',
                    'client_id' => 1,
                ];

                if (!$menu->bind($data) || !$menu->check() || !$menu->store()) {
                    $this->log('Unable to ensure BFNG admin submenu entry ' . $title . '.', Log::WARNING);
                    continue;
                }

                $checked++;
            }

            $query = $db->getQuery(true)
                ->delete($db->quoteName('#__menu'))
                ->where($db->quoteName('client_id') . ' = 1')
                ->where($db->quoteName('alias') . ' = ' . $db->quote('breezingformsng-import-export'));
            $db->setQuery($query)->execute();

            $this->log('BFNG administration submenu entries checked: ' . $checked . ' item(s).');
        } catch (\Throwable $e) {
            $this->log('Unable to ensure BFNG administration submenu entries: ' . $e->getMessage(), Log::WARNING);
        }
    }

    private function migrateLegacyBreezingFormsName(): void
    {
        if ($this->getComponentExtensionId(self::TARGET_COMPONENT) > 0) {
            $this->normalizeJoomlaComponentReferences();
            return;
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);

        foreach (['com_breezingforms', 'breezingforms'] as $legacyElement) {
            $legacyId = $this->getComponentExtensionId($legacyElement);

            if ($legacyId <= 0) {
                continue;
            }

            try {
                $db->setQuery(
                    $db->getQuery(true)
                        ->update($db->quoteName('#__extensions'))
                        ->set($db->quoteName('element') . ' = ' . $db->quote(self::TARGET_COMPONENT))
                        ->set($db->quoteName('name') . ' = ' . $db->quote(self::TARGET_COMPONENT))
                        ->where($db->quoteName('extension_id') . ' = ' . (int) $legacyId)
                );
                $db->execute();
                $this->log("Migrated legacy BreezingForms extension row from {$legacyElement} to " . self::TARGET_COMPONENT . '.');
            } catch (\Throwable $e) {
                $this->log("Unable to migrate legacy BreezingForms extension row {$legacyElement}: " . $e->getMessage(), Log::WARNING);
                continue;
            }

            $this->normalizeComponentAssetName($legacyElement, self::TARGET_COMPONENT);
            $this->normalizeJoomlaComponentReferences();
            return;
        }
    }

    private function deduplicateBreezingFormsComponentRows(): void
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        try {
            $db->setQuery(
                $db->getQuery(true)
                    ->select($db->quoteName(['extension_id', 'enabled', 'manifest_cache']))
                    ->from($db->quoteName('#__extensions'))
                    ->where($db->quoteName('type') . ' = ' . $db->quote('component'))
                    ->where($db->quoteName('element') . ' = ' . $db->quote(self::TARGET_COMPONENT))
                    ->where($db->quoteName('client_id') . ' = 1')
                    ->order($db->quoteName('extension_id') . ' DESC')
            );
            $rows = $db->loadAssocList() ?: [];
        } catch (\Throwable $e) {
            $this->log('Unable to inspect BreezingForms component extension rows: ' . $e->getMessage(), Log::WARNING);
            return;
        }

        if (count($rows) <= 1) {
            return;
        }

        $ids = array_values(array_filter(
            array_map(static fn(array $row): int => (int) ($row['extension_id'] ?? 0), $rows),
            static fn(int $id): bool => $id > 0
        ));

        if (count($ids) <= 1) {
            return;
        }

        $menuRefs = array_fill_keys($ids, 0);

        try {
            $db->setQuery(
                $db->getQuery(true)
                    ->select([$db->quoteName('component_id'), 'COUNT(1) AS refs'])
                    ->from($db->quoteName('#__menu'))
                    ->where($db->quoteName('client_id') . ' = 1')
                    ->where($db->quoteName('component_id') . ' IN (' . implode(',', $ids) . ')')
                    ->group($db->quoteName('component_id'))
            );
            foreach (($db->loadAssocList() ?: []) as $row) {
                $componentId = (int) ($row['component_id'] ?? 0);
                if ($componentId > 0) {
                    $menuRefs[$componentId] = (int) ($row['refs'] ?? 0);
                }
            }
        } catch (\Throwable $e) {
            $this->log('Unable to inspect BreezingForms menu references: ' . $e->getMessage(), Log::WARNING);
        }

        $keepId = 0;
        $bestScore = [-1, -1, -1, -1];

        foreach ($rows as $row) {
            $id = (int) ($row['extension_id'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            $manifest = json_decode((string) ($row['manifest_cache'] ?? ''), true);
            $isNg = is_array($manifest) && stripos((string) ($manifest['authorUrl'] ?? '') . ' ' . (string) ($manifest['name'] ?? ''), 'breezingforms-ng') !== false ? 1 : 0;
            $enabled = (int) ($row['enabled'] ?? 0);
            $refs = (int) ($menuRefs[$id] ?? 0);
            $score = [$isNg, $enabled, $refs, $id];

            if ($keepId === 0 || $score > $bestScore) {
                $keepId = $id;
                $bestScore = $score;
            }
        }

        if ($keepId <= 0) {
            return;
        }

        $removeIds = array_values(array_filter($ids, static fn(int $id): bool => $id !== $keepId));
        if ($removeIds === []) {
            return;
        }

        try {
            $db->setQuery(
                $db->getQuery(true)
                    ->update($db->quoteName('#__menu'))
                    ->set($db->quoteName('component_id') . ' = ' . (int) $keepId)
                    ->where($db->quoteName('component_id') . ' IN (' . implode(',', $removeIds) . ')')
            );
            $db->execute();
        } catch (\Throwable $e) {
            $this->log('Unable to remap BreezingForms menu entries to BFNG component row: ' . $e->getMessage(), Log::WARNING);
        }

        $this->cleanupExtensionReferences($removeIds);

        try {
            $db->setQuery(
                $db->getQuery(true)
                    ->delete($db->quoteName('#__extensions'))
                    ->where($db->quoteName('extension_id') . ' IN (' . implode(',', $removeIds) . ')')
            );
            $db->execute();
            $this->log('Deduplicated BreezingForms component rows: kept extension_id ' . $keepId . ', removed ' . count($removeIds) . ' stale row(s).');
        } catch (\Throwable $e) {
            $this->log('Unable to remove stale BreezingForms component extension rows: ' . $e->getMessage(), Log::WARNING);
        }
    }

    private function removeLegacyBreezingFormsComponentRows(): void
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $targetId = $this->getComponentExtensionId(self::TARGET_COMPONENT);

        if ($targetId <= 0) {
            $this->log('BFNG component row not found; legacy BreezingForms cleanup skipped.', Log::WARNING);
            return;
        }

        foreach (['breezingforms', 'com_breezingforms'] as $legacyElement) {
            $legacyId = $this->getComponentExtensionId($legacyElement);

            if ($legacyId <= 0) {
                continue;
            }

            try {
                $db->setQuery(
                    $db->getQuery(true)
                        ->update($db->quoteName('#__menu'))
                        ->set($db->quoteName('component_id') . ' = ' . (int) $targetId)
                        ->set($db->quoteName('link') . ' = REPLACE(' . $db->quoteName('link') . ', ' . $db->quote('option=' . $legacyElement) . ', ' . $db->quote('option=' . self::TARGET_COMPONENT) . ')')
                        ->where($db->quoteName('component_id') . ' = ' . (int) $legacyId)
                );
                $db->execute();
            } catch (\Throwable $e) {
                $this->log("Unable to remap legacy BreezingForms menus for {$legacyElement}: " . $e->getMessage(), Log::WARNING);
            }

            $this->cleanupExtensionReferences([$legacyId]);

            try {
                $db->setQuery(
                    $db->getQuery(true)
                        ->delete($db->quoteName('#__extensions'))
                        ->where($db->quoteName('extension_id') . ' = ' . (int) $legacyId)
                );
                $db->execute();
                $this->log("Legacy BreezingForms component row removed safely: {$legacyElement} (id {$legacyId}).");
            } catch (\Throwable $e) {
                $this->log("Unable to remove legacy BreezingForms component row {$legacyElement}: " . $e->getMessage(), Log::WARNING);
            }
        }
    }

    private function removeLegacyBreezingFormsComponentDirectories(): void
    {
        $directories = [
            JPATH_ADMINISTRATOR . '/components/com_breezingforms',
            JPATH_SITE . '/components/com_breezingforms',
            JPATH_SITE . '/media/com_breezingforms',
            JPATH_ROOT . '/api/components/com_breezingforms',
        ];

        foreach ($directories as $directory) {
            if (!Folder::exists($directory)) {
                continue;
            }

            try {
                if (Folder::delete($directory)) {
                    $this->log('Removed legacy BreezingForms directory: ' . $directory);
                }
            } catch (\Throwable $e) {
                $this->log('Unable to remove legacy BreezingForms directory ' . $directory . ': ' . $e->getMessage(), Log::WARNING);
            }
        }
    }

    private function migrateStaleMenuLinks(): void
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $replacements = [
            'index.php?option=' . self::TARGET_COMPONENT . '&act=about'
                => 'index.php?option=' . self::TARGET_COMPONENT . '&task=about.display&view=about',
            'index.php?option=' . self::TARGET_COMPONENT . '&task=integrate.display&view=integrate'
                => 'index.php?option=' . self::TARGET_COMPONENT . '&view=integrator',
            'index.php?option=' . self::TARGET_COMPONENT . '&act=integrate'
                => 'index.php?option=' . self::TARGET_COMPONENT . '&view=integrator',
            'index.php?option=' . self::TARGET_COMPONENT . '&act=managerecs'
                => 'index.php?option=' . self::TARGET_COMPONENT . '&view=records',
            'index.php?option=' . self::TARGET_COMPONENT . '&act=manageforms'
                => 'index.php?option=' . self::TARGET_COMPONENT . '&view=forms',
        ];

        foreach ($replacements as $oldLink => $newLink) {
            try {
                $db->setQuery(
                    $db->getQuery(true)
                        ->update($db->quoteName('#__menu'))
                        ->set($db->quoteName('link') . ' = ' . $db->quote($newLink))
                        ->where($db->quoteName('client_id') . ' = 1')
                        ->where($db->quoteName('link') . ' = ' . $db->quote($oldLink))
                );
                $db->execute();
                $updated = (int) $db->getAffectedRows();

                if ($updated > 0) {
                    $this->log("[OK] Migrated {$updated} menu link(s): {$oldLink} → {$newLink}");
                }
            } catch (\Throwable $e) {
                $this->log("Unable to migrate menu link {$oldLink}: " . $e->getMessage(), Log::WARNING);
            }
        }
    }

    private function cleanupLegacyBreezingFormsAfterInstall(): void
    {
        $this->normalizeJoomlaComponentReferences();

        if ($this->ensureAdminMenuRootNodeExists()) {
            $this->ensureAdministrationMainMenuEntry();
            $this->migrateStaleMenuLinks();
            $this->ensureAdministrationSubmenuEntries();
        }
        $this->deduplicateBreezingFormsComponentRows();
        $this->removeLegacyBreezingFormsComponentRows();
        $this->removeLegacyBreezingFormsComponentDirectories();
        $this->removeObsoleteSystemPlugin();
        $this->removeObsoleteComponentFiles();
        $this->log('Legacy BreezingForms component cleanup completed in safe mode; uninstall hooks intentionally skipped.');
    }

    private function removeObsoleteSystemPlugin(): void
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select($db->quoteName('extension_id'))
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
            ->where($db->quoteName('folder') . ' = ' . $db->quote('system'))
            ->where($db->quoteName('element') . ' = ' . $db->quote('sysbreezingforms'));
        $extensionId = (int) $db->setQuery($query)->loadResult();

        if ($extensionId > 0) {
            $installer = new Installer();
            $installer->setDatabase($db);
            $installer->uninstall('plugin', $extensionId);
            $this->log('Obsolete sysbreezingforms plugin removed.');
        }
    }

    /**
     * Files shipped by earlier NG releases and replaced since; Joomla does
     * not delete removed package files on update.
     */
    private function removeObsoleteComponentFiles(): void
    {
        $obsolete = [
            JPATH_SITE . '/components/com_breezingformsng/router.php',
            JPATH_SITE . '/components/com_breezingformsng/facileforms.xml.php',
            JPATH_SITE . '/components/com_breezingformsng/legacy/processor/bfProcessorUploads.php',
            JPATH_SITE . '/components/com_breezingformsng/legacy/processor/bfProcessorCodeTools.php',
            JPATH_SITE . '/components/com_breezingformsng/legacy/processor/bfProcessorScripting.php',
            JPATH_SITE . '/components/com_breezingformsng/legacy/processor/bfProcessorExports.php',
            JPATH_SITE . '/components/com_breezingformsng/legacy/processor/bfProcessorNotifications.php',
            JPATH_SITE . '/components/com_breezingformsng/legacy/processor/bfProcessorSubmission.php',
            JPATH_SITE . '/components/com_breezingformsng/legacy/processor/bfProcessorRendering.php',
            JPATH_SITE . '/components/com_breezingformsng/legacy/Conf.php',
            JPATH_SITE . '/components/com_breezingformsng/legacy/tables.php',
            JPATH_SITE . '/components/com_breezingformsng/legacy/functions.php',
            JPATH_SITE . '/components/com_breezingformsng/src/Table/RuntimeTables.php',
            JPATH_ADMINISTRATOR . '/components/com_breezingformsng/libraries/crosstec/classes/BFFactory.php',
            JPATH_ADMINISTRATOR . '/components/com_breezingformsng/libraries/crosstec/classes/BFIntegrate.php',
            JPATH_ADMINISTRATOR . '/components/com_breezingformsng/libraries/crosstec/classes/BFJoomlaConfig.php',
            JPATH_ADMINISTRATOR . '/components/com_breezingformsng/libraries/crosstec/classes/BFPDF.php',
            JPATH_ADMINISTRATOR . '/components/com_breezingformsng/libraries/crosstec/classes/BFRequest.php',
            JPATH_ADMINISTRATOR . '/components/com_breezingformsng/libraries/crosstec/classes/BFText.php',
            JPATH_SITE . '/media/com_breezingformsng/images/site/captcha/securimage_show.php',
            JPATH_ADMINISTRATOR . '/components/com_breezingformsng/images/captcha/securimage_show.php',
            // This generic Joomla 3 PDF template is superseded by the
            // packaged administrator template. Keep user-specific templates.
            JPATH_SITE . '/media/breezingforms/pdftpl/export_pdf.php',
            JPATH_SITE . '/media/breezingforms/pdftpl/pdf_attachment.php',
            JPATH_SITE . '/media/breezingforms/downloadtpl/download.php',
            JPATH_SITE . '/media/breezingforms/downloadtpl/sofort_download.php',
            JPATH_SITE . '/media/breezingforms/downloadtpl/sofort_success.php',
            JPATH_SITE . '/media/breezingforms/downloadtpl/stripe_download.php',
            JPATH_SITE . '/media/breezingforms/downloadtpl/error.php',
            JPATH_SITE . '/language/fr-FR/fr-FR.com_breezingforms.ini',
            JPATH_SITE . '/language/en-GB/en-GB.com_breezingforms.ini',
            JPATH_ADMINISTRATOR . '/language/fr-FR/fr-FR.com_breezingforms.ini',
            JPATH_ADMINISTRATOR . '/language/fr-FR/fr-FR.com_breezingforms.sys.ini',
            JPATH_ADMINISTRATOR . '/language/en-GB/en-GB.com_breezingforms.sys.ini',
            JPATH_ADMINISTRATOR . '/language/en-GB/en-GB.com_breezingforms.ini',
            JPATH_SITE . '/components/com_breezingformsng/facileforms.class.php',
            JPATH_SITE . '/components/com_breezingformsng/facileforms.process.php',
            JPATH_ADMINISTRATOR . '/components/com_breezingformsng/sql/create_sql.php',
            JPATH_ADMINISTRATOR . '/components/com_breezingformsng/src/Helper/LegacyClassLoader.php',
            JPATH_ADMINISTRATOR . '/components/com_breezingformsng/libraries/jquery/jq.js',
            JPATH_SITE . '/components/com_breezingformsng/libraries/jquery/jq.min.legacy.js',
            JPATH_SITE . '/components/com_breezingformsng/libraries/jquery/jq.min.js',
            JPATH_SITE . '/components/com_breezingformsng/src/Helper/legacy/route.php',
            JPATH_ADMINISTRATOR . '/components/com_breezingformsng/src/Model/LegacyPackageModel.php',
        ];

        foreach ($obsolete as $file) {
            if (File::exists($file) && File::delete($file)) {
                $this->log('Obsolete file removed: ' . basename($file));
            }
        }

        $obsoleteDirectories = [
            JPATH_ADMINISTRATOR . '/components/com_breezingformsng/libraries/dropbox',
            JPATH_ADMINISTRATOR . '/components/com_breezingformsng/libraries/mailchimp',
            JPATH_ADMINISTRATOR . '/components/com_breezingformsng/libraries/recaptcha',
            JPATH_ADMINISTRATOR . '/components/com_breezingformsng/libraries/salesforce',
        ];

        foreach ($obsoleteDirectories as $directory) {
            if (Folder::exists($directory) && Folder::delete($directory)) {
                $this->log('Obsolete library directory removed: ' . basename($directory));
            }
        }
    }


    /**
     * method to install the component
     *
     * @return void
     */
    public function install($parent): void
    {
        $this->log('Fresh installation of BreezingForms.');
    }
    /**
     * method to update the component
     *
     * @return void
     */
    public function update($parent): void
    {
        $this->log('Updating BreezingForms from version ' . $this->getCurrentInstalledVersion());
        $this->ensureUtf8mb4Columns();

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $textCollationClause = $this->getTextCollationClause();
        $tables = self::getTableFields($db->getTableList());
        $prefix = $db->getPrefix();

        $recordsTable = $prefix . 'facileforms_records';
        $formsTable   = $prefix . 'facileforms_forms';
        $scriptsTable = $prefix . 'facileforms_scripts';
        $piecesTable  = $prefix . 'facileforms_pieces';

        if (isset($tables[$recordsTable])) {
            $columns = $tables[$recordsTable];

            $recordAuditColumns = [
                'modified'        => "DATETIME NULL DEFAULT NULL AFTER `submitted`",
                'modified_by'     => "VARCHAR(255){$textCollationClause} NOT NULL DEFAULT '' AFTER `modified`",
                'modified_user_id' => "INT(11) NOT NULL DEFAULT '0' AFTER `modified_by`",
            ];

            foreach ($recordAuditColumns as $col => $def) {
                if (!isset($columns[$col])) {
                    $db->setQuery("ALTER TABLE `{$recordsTable}` ADD `{$col}` {$def}")->execute();
                    $this->log("Added column {$col} to facileforms_records.");
                }
            }

            $newColumns = [
                'opted'     => "TINYINT(1) NOT NULL DEFAULT '0' AFTER `paypal_download_tries`",
                'opt_ip'    => "VARCHAR(255) NOT NULL DEFAULT '' AFTER `opted`",
                'opt_date'  => "DATETIME NULL DEFAULT NULL AFTER `opt_ip`",
                'opt_token' => "VARCHAR(255) NOT NULL DEFAULT '' AFTER `opt_date`",
            ];

            foreach ($newColumns as $col => $def) {
                if (!isset($columns[$col])) {
                    $db->setQuery("ALTER TABLE `{$recordsTable}` ADD `{$col}` {$def}, ADD INDEX (`{$col}`)")->execute();
                    $this->log("Added column {$col} to facileforms_records.");
                }
            }

            if (isset($columns['opt_date'])) {
                $db->setQuery("ALTER TABLE `{$recordsTable}` MODIFY `opt_date` DATETIME NULL DEFAULT NULL")->execute();
                $this->log('Updated opt_date column definition in facileforms_records.');
            }

            if (isset($columns['ip'])) {
                $ipType = strtolower((string) $columns['ip']);
                if ($ipType !== 'varchar(45)') {
                    $db->setQuery("ALTER TABLE `{$recordsTable}` MODIFY `ip` VARCHAR(45) NOT NULL DEFAULT ''")->execute();
                    $this->log('Updated ip column definition in facileforms_records.');
                }
            }

            if (isset($columns['browser'])) {
                $browserType = strtolower((string) $columns['browser']);
                if (strpos($browserType, 'text') === false) {
                    $db->setQuery("ALTER TABLE `{$recordsTable}` MODIFY `browser` TEXT NOT NULL")->execute();
                    $this->log('Updated browser column definition in facileforms_records.');
                }
            }
        }

        if (isset($tables[$formsTable])) {
            $columns = $tables[$formsTable];

            if (!isset($columns['debug_mode'])) {
                $db->setQuery(
                    "ALTER TABLE `{$formsTable}` ADD `debug_mode` TINYINT(1) NOT NULL DEFAULT '0' AFTER `published`"
                )->execute();
                $this->log('Added column debug_mode to facileforms_forms.');
            }

            $newFormColumns = [
                'double_opt' => "TINYINT(1) NOT NULL DEFAULT '0' AFTER `filter_state`",
                'opt_mail'   => "VARCHAR(128) NOT NULL DEFAULT '' AFTER `double_opt`",
            ];

            foreach ($newFormColumns as $col => $def) {
                if (!isset($columns[$col])) {
                    $db->setQuery("ALTER TABLE `{$formsTable}` ADD `{$col}` {$def}, ADD INDEX (`{$col}`)")->execute();
                    $this->log("Added column {$col} to facileforms_forms.");
                }
            }

            $auditFormColumns = [
                'created'     => "DATETIME NULL DEFAULT CURRENT_TIMESTAMP AFTER `filter_state`",
                'modified'    => "DATETIME NULL DEFAULT NULL AFTER `created`",
                'created_by'  => "VARCHAR(255){$textCollationClause} NOT NULL DEFAULT '' AFTER `modified`",
                'modified_by' => "VARCHAR(255){$textCollationClause} NOT NULL DEFAULT '' AFTER `created_by`"
            ];

            foreach ($auditFormColumns as $col => $def) {
                if (!isset($columns[$col])) {
                    $db->setQuery("ALTER TABLE `{$formsTable}` ADD `{$col}` {$def}")->execute();
                    $this->log("Added column {$col} to facileforms_forms.");
                }
            }

            if (isset($columns['created_by'])) {
                $db->setQuery("ALTER TABLE `{$formsTable}` MODIFY `created_by` VARCHAR(255){$textCollationClause} NOT NULL DEFAULT ''")->execute();
                $this->log("Updated created_by column definition in facileforms_forms.");
            }
            if (isset($columns['modified_by'])) {
                $db->setQuery("ALTER TABLE `{$formsTable}` MODIFY `modified_by` VARCHAR(255){$textCollationClause} NOT NULL DEFAULT ''")->execute();
                $this->log("Updated modified_by column definition in facileforms_forms.");
            }
        }

        $auditColumns = [
            'created'     => "DATETIME NULL DEFAULT NULL AFTER `code`",
            'created_by'  => "VARCHAR(255){$textCollationClause} NOT NULL DEFAULT '' AFTER `created`",
            'modified'    => "DATETIME NULL DEFAULT NULL AFTER `created_by`",
            'modified_by' => "VARCHAR(255){$textCollationClause} NOT NULL DEFAULT '' AFTER `modified`",
        ];

        if (isset($tables[$scriptsTable])) {
            $columns = $tables[$scriptsTable];

            if (!isset($columns['unit_tests'])) {
                $db->setQuery("ALTER TABLE `{$scriptsTable}` ADD `unit_tests` LONGTEXT NULL AFTER `code`")->execute();
                $this->log('Added column unit_tests to facileforms_scripts.');
            }
        }

        if (isset($tables[$piecesTable])) {
            $columns = $tables[$piecesTable];

            if (!isset($columns['unit_tests'])) {
                $db->setQuery("ALTER TABLE `{$piecesTable}` ADD `unit_tests` LONGTEXT NULL AFTER `code`")->execute();
                $this->log('Added column unit_tests to facileforms_pieces.');
            }
        }

        foreach ([$scriptsTable, $piecesTable] as $tableName) {
            if (!isset($tables[$tableName])) {
                continue;
            }

            $columns = $tables[$tableName];
            foreach ($auditColumns as $col => $def) {
                if (!isset($columns[$col])) {
                    $db->setQuery("ALTER TABLE `{$tableName}` ADD `{$col}` {$def}")->execute();
                    $this->log("Added column {$col} to {$tableName}.");
                }
            }

            if (isset($columns['created_by'])) {
                $db->setQuery("ALTER TABLE `{$tableName}` MODIFY `created_by` VARCHAR(255){$textCollationClause} NOT NULL DEFAULT ''")->execute();
                $this->log("Updated created_by column definition in {$tableName}.");
            }
            if (isset($columns['modified_by'])) {
                $db->setQuery("ALTER TABLE `{$tableName}` MODIFY `modified_by` VARCHAR(255){$textCollationClause} NOT NULL DEFAULT ''")->execute();
                $this->log("Updated modified_by column definition in {$tableName}.");
            }
        }

        $this->migrateElementData1FunctionNames();
        $this->migrateQuickmodeIconPaths();

        $this->log('BreezingForms database update completed.');
    }

    private function migrateQuickmodeIconPaths(): void
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $formsTable = $db->getPrefix() . 'facileforms_forms';

        if (!in_array($formsTable, $db->getTableList(), true)) {
            return;
        }

        $db->setQuery(
            "SELECT `id`, `template_code` FROM `{$formsTable}`" .
            " WHERE `template_code` IS NOT NULL AND `template_code` != ''"
        );
        $rows = $db->loadObjectList();

        if (empty($rows)) {
            return;
        }

        $updated = 0;

        foreach ($rows as $row) {
            $json = base64_decode((string) $row->template_code, true);

            if ($json === false || $json === '') {
                continue;
            }

            // Replace any full path ending with /icon_*.png by just the filename.
            // Works regardless of component name (com_breezingforms, com_facileforms, etc.)
            // or URL format (absolute, relative, with or without protocol/host).
            $fixed = preg_replace(
                '/"icon":"[^"]*\/(icon_[^"\/]+\.png)"/',
                '"icon":"$1"',
                $json
            );

            if ($fixed === null || $fixed === $json) {
                continue;
            }

            try {
                $db->setQuery(
                    "UPDATE `{$formsTable}` SET `template_code` = " .
                    $db->quote(base64_encode($fixed)) .
                    " WHERE `id` = " . (int) $row->id
                )->execute();
                $updated++;
            } catch (\Throwable $e) {
                $this->log("Unable to migrate quickmode icon paths for form {$row->id}: " . $e->getMessage(), Log::WARNING);
            }
        }

        if ($updated > 0) {
            $this->announce("[OK] Migrated quickmode icon paths in {$updated} form(s).", 'message', Log::INFO);
        } else {
            $this->log('Quickmode icon paths: no migration needed.');
        }
    }

    private function migrateElementData1FunctionNames(): void
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $elementsTable = $db->getPrefix() . 'facileforms_elements';

        if (!in_array($elementsTable, $db->getTableList(), true)) {
            return;
        }

        $existingColumns = array_keys($db->getTableColumns($elementsTable, true));
        $candidates = ['data1', 'data2', 'data3', 'data4', 'data5',
                       'data6', 'data7', 'data8', 'data9', 'data10'];
        $columns = array_intersect($candidates, $existingColumns);

        if (empty($columns)) {
            return;
        }

        $totalUpdated = 0;

        foreach ($columns as $col) {
            try {
                $db->setQuery(
                    "UPDATE `{$elementsTable}`" .
                    " SET `{$col}` = REPLACE(`{$col}`, 'gn_vcmb_', 'gn_get_vcmb_')" .
                    " WHERE `{$col}` LIKE '%gn\\_vcmb\\_%'"
                )->execute();

                $updated = (int) $db->getAffectedRows();
                $totalUpdated += $updated;

                if ($updated > 0) {
                    $this->log("Migrated gn_vcmb_ → gn_get_vcmb_ in {$col}: {$updated} element(s) updated.");
                }
            } catch (\Throwable $e) {
                $this->log("Unable to migrate gn_vcmb_ function names in {$col}: " . $e->getMessage(), Log::WARNING);
            }
        }

        if ($totalUpdated > 0) {
            $this->announce(
                "[OK] Migrated {$totalUpdated} element field(s): gn_vcmb_* → gn_get_vcmb_*.",
                'message',
                Log::INFO
            );
        } else {
            $this->log('No gn_vcmb_ function references found in element fields – migration skipped.');
        }
    }

    /**
     * method to uninstall the component
     *
     * @return void
     */
    function uninstall($parent)
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $plugins = $this->getPlugins();

        $installer = new Installer();
        $installer->setDatabase($db);

        foreach ($plugins as $folder => $subplugs) {
            if (is_array($subplugs)) {
                foreach ($subplugs as $plugin) {
                    $db->setQuery('SELECT `extension_id` FROM #__extensions WHERE `type` = "plugin" AND `element` = "' . $plugin . '" AND `folder` = "' . $folder . '"');
                    $id = $db->loadResult();
                    if ($id) {
                        $installer->uninstall('plugin', $id);
                    }
                }
            }
        }

        $this->cleanupOldConfig();
        $this->cleanupComponentLogFile();
        $this->removeBreezingFormsMenuEntries();
        $this->log('BreezingForms uninstallation completed.');
    }

    /**
     * method to run before an install/update/uninstall method
     *
     * @return bool
     */

    public function preflight(string $type, $parent): bool
    {
        $this->resetCriticalFailures();

        $type = strtolower((string) $type);
        $this->incomingPackageVersion = $this->getIncomingVersion($parent);

        if (!$this->checkRequirements()) {
            return false;
        }

        if (in_array($type, ['install', 'update', 'discover_install'], true)) {
            if (!$this->checkInstalledFilePermissionsBeforeCopy()) {
                return false;
            }

            $this->purgeCaches('preflight:' . $type);
            $this->migrateLegacyBreezingFormsName();
        }

        $this->currentInstalledVersion = $this->getCurrentInstalledVersion();

        $this->announce(
            '[INFO] BreezingForms ' . strtoupper($type) .
            ' | package version: <strong>' . htmlspecialchars($this->incomingPackageVersion !== '' ? $this->incomingPackageVersion : 'unknown', ENT_QUOTES, 'UTF-8') . '</strong>' .
            ' | installed version: <strong>' . htmlspecialchars($this->currentInstalledVersion, ENT_QUOTES, 'UTF-8') . '</strong>.',
            'message',
            Log::INFO
        );
        $this->announce(
            '[INFO] Environment | Joomla <strong>' . htmlspecialchars(defined('JVERSION') ? JVERSION : 'unknown', ENT_QUOTES, 'UTF-8') . '</strong>' .
            ' | PHP <strong>' . htmlspecialchars(PHP_VERSION, ENT_QUOTES, 'UTF-8') . '</strong>' .
            ' | DB <strong>' . htmlspecialchars($this->getDatabaseRuntimeLabel(), ENT_QUOTES, 'UTF-8') . '</strong>.',
            'message',
            Log::INFO
        );
        $this->log("Preflight executed for action: {$type}");

        if (in_array($type, ['install', 'update', 'discover_install'], true)) {
            $this->announceUtf8mb4Capability();
        }

        return !$this->hasCriticalFailure();
    }
    /**
     * method to run after an install/update/uninstall method
     *
     * @return void
     */
    function postflight($type, $parent)
    {
        $type = strtolower((string) $type);

        // === LOG POUR DÉBOGAGE ===
        $this->log('Postflight installation method call, parameter : ' . $type . '.');
        $this->log('Current version in manifest_cache : ' . $this->getCurrentInstalledVersion() . '.');

        if (in_array($type, ['install', 'update', 'discover_install'], true)) {
            $this->ensureUtf8mb4Columns();
            if ($type === 'update') {
                $this->migrateLegacyConfig();
            }
            $this->importStandardLibrary();
            $this->copyComponentImageAssets();
            $this->installPlugins();
            $this->cleanupLegacyBreezingFormsAfterInstall();
            $this->removeOldUpdateSite();
            $this->cleanupOldConfig();
            $this->purgeCaches('postflight:' . $type);
            $this->verifyInstalledExtensionConsistency($parent);
        } elseif ($type === 'uninstall') {
            $this->removeBreezingFormsMenuEntries();
            $this->cleanupOldConfig();
            $this->cleanupComponentLogFile();
            $this->purgeCaches('postflight:' . $type);
        }

        $durationSeconds = max(0.0, microtime(true) - $this->installStartedAt);
        $actionLabel = strtoupper((string) $type);
        $packageVersion = $this->incomingPackageVersion !== '' ? $this->incomingPackageVersion : $this->getIncomingVersion($parent);
        $installedVersion = $this->getCurrentInstalledVersion();

        $finishedWithFailure = $this->hasCriticalFailure();
        $finishMessage = ($finishedWithFailure ? '[ERROR] BreezingForms ' : '[OK] BreezingForms ')
            . $actionLabel
            . ($finishedWithFailure ? ' finished with critical issue(s).' : ' finished successfully.')
            . ' Package version: <strong>' . htmlspecialchars($packageVersion !== '' ? $packageVersion : 'unknown', ENT_QUOTES, 'UTF-8') . '</strong>'
            . ' | installed version: <strong>' . htmlspecialchars($installedVersion, ENT_QUOTES, 'UTF-8') . '</strong>'
            . ' | duration: <strong>' . number_format($durationSeconds, 2, '.', '') . 's</strong>.';

        $this->announce(
            $finishMessage,
            $finishedWithFailure ? 'error' : 'message',
            $finishedWithFailure ? Log::ERROR : Log::INFO
        );

        if ($finishedWithFailure) {
            $summary = $this->getCriticalFailureSummary();
            $this->log('BreezingForms installation/update process finished with critical issue(s)' . ($summary !== '' ? ': ' . $summary : '.'), Log::ERROR);

            // Actually fail the installer instead of silently reporting success -
            // logging alone left Joomla showing "Installation Successful" even
            // when a critical step failed.
            throw new \RuntimeException(
                'BreezingForms NG postflight failed' . ($summary !== '' ? ': ' . $summary : '.')
            );
        }

        $this->log('BreezingForms installation/update process finished successfully.');
    }

    function getPlugins()
    {
        $plugins = array();
        $plugins['system'] = array();
        $plugins['system'][] = 'bfcompat';
        return $plugins;
    }

    public static function getTableFields($tables, $typeOnly = true)
    {
        $results = array();
        settype($tables, 'array');

        $db = Factory::getContainer()->get(DatabaseInterface::class);

        foreach ($tables as $table) {
            $results[$table] = $db->getTableColumns($table, $typeOnly);
        }

        return $results;
    }
}
