<?php

/**
 * @package     BreezingForms
 * @version     5.0.0
 * @author      Markus Bopp
 * @link        http://breezings.vcmb.fr
 * @copyright   Copyright (C) 2025 by XDA+GIL | Until 2020 - Markus Bopp
 * @license     GNU/GPL
 */
defined('_JEXEC') or die('Direct Access to this location is not allowed.');

use Joomla\Filesystem\File;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\Installer\Installer;
use Joomla\Filesystem\Folder;
use Joomla\CMS\Log\Log;

if (!function_exists('_ff_query')) {
    function _ff_query($sql, $insert = 0)
    {
        $database = Factory::getContainer()->get(DatabaseInterface::class);
        $database->setQuery($sql);
        $database->execute();

        return $insert ? $database->insertid() : null;
    }
}

if (!function_exists('_ff_select')) {
    function _ff_select($sql)
    {
        $database = Factory::getContainer()->get(DatabaseInterface::class);
        $database->setQuery($sql);

        return $database->loadObjectList();
    }
}

if (!function_exists('_ff_selectValue')) {
    function _ff_selectValue($sql)
    {
        $database = Factory::getContainer()->get(DatabaseInterface::class);
        $database->setQuery($sql);

        return $database->loadResult();
    }
}

if (!function_exists('savePackage')) {
    function savePackage($id, $name, $title, $version, $created, $author, $email, $url, $description, $copyright)
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $cnt = _ff_selectValue("select count(*) from #__facileforms_packages where id=" . $db->quote($id));

        if (!$cnt) {
            _ff_query(
                "insert into #__facileforms_packages " .
                "(id, name, title, version, created, author, email, url, description, copyright) " .
                "values (" . $db->quote($id) . ", " . $db->quote($name) . ", " . $db->quote($title) . ", " .
                $db->quote($version) . ", " . $db->quote($created) . ", " . $db->quote($author) . ", " .
                $db->quote($email) . ", " . $db->quote($url) . ", " . $db->quote($description) . ", " .
                $db->quote($copyright) . ")"
            );
        } else {
            _ff_query(
                "update #__facileforms_packages " .
                "set name=" . $db->quote($name) . ", title=" . $db->quote($title) . ", version=" . $db->quote($version) . ", " .
                "created=" . $db->quote($created) . ", author=" . $db->quote($author) . ", email=" . $db->quote($email) . ", " .
                "url=" . $db->quote($url) . ", description=" . $db->quote($description) . ", " .
                "copyright=" . $db->quote($copyright) . " where id = " . $db->quote($id)
            );
        }
    }
}

if (!function_exists('relinkScripts')) {
    function relinkScripts(&$oldscripts)
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        if ($oldscripts != null && count($oldscripts)) {
            foreach ($oldscripts as $row) {
                $newid = _ff_selectValue("select max(id) from #__facileforms_scripts where name = " . $db->quote($row->name));
                if ($newid) {
                    _ff_query("update #__facileforms_forms set script1id=$newid where script1id=$row->id");
                    _ff_query("update #__facileforms_forms set script2id=$newid where script2id=$row->id");
                    _ff_query("update #__facileforms_elements set script1id=$newid where script1id=$row->id");
                    _ff_query("update #__facileforms_elements set script2id=$newid where script2id=$row->id");
                    _ff_query("update #__facileforms_elements set script3id=$newid where script3id=$row->id");
                }
            }
        }
    }
}

if (!function_exists('relinkPieces')) {
    function relinkPieces(&$oldpieces)
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        if ($oldpieces != null && count($oldpieces)) {
            foreach ($oldpieces as $row) {
                $newid = _ff_selectValue("select max(id) from #__facileforms_pieces where name = " . $db->quote($row->name));
                if ($newid) {
                    _ff_query("update #__facileforms_forms set piece1id=$newid where piece1id=$row->id");
                    _ff_query("update #__facileforms_forms set piece2id=$newid where piece2id=$row->id");
                    _ff_query("update #__facileforms_forms set piece3id=$newid where piece3id=$row->id");
                    _ff_query("update #__facileforms_forms set piece4id=$newid where piece4id=$row->id");
                }
            }
        }
    }
}

if (!function_exists('updateComponentMenus')) {
    function updateComponentMenus($copy = false)
    {
        return '';
    }
}

Log::addLogger(
    [
        'text_file' => 'breezingforms_install.log',
        'text_entry_format' => '{DATETIME}\t{PRIORITY}\t{MESSAGE}',
        'text_file_path'     => JPATH_ADMINISTRATOR . '/logs'
    ],
    Log::ALL,
    ['com_breezingforms.install']
);


// Logs de démarrage
Log::add('[OK] Breezingforms installation/update started.', Log::INFO, 'com_breezingforms.install');
Log::add('PHP Version: ' . PHP_VERSION . '.', Log::INFO, 'com_breezingforms.install');
Log::add('Joomla Version : ' . JVERSION . '.', Log::INFO, 'com_breezingforms.install');
Log::add('User Agent: ' . ($_SERVER['HTTP_USER_AGENT'] ?? 'CLI') . '.', Log::INFO, 'com_breezingforms.install');

class com_breezingformsInstallerScript
{
    private $installStartedAt = 0.0;
    private $incomingPackageVersion = '';
    private $currentInstalledVersion = '0.0.0';
    private $utf8mb4SupportChecked = false;
    private $utf8mb4Supported = false;
    private $utf8mb4Collation = 'utf8mb4_general_ci';
    private $utf8mb4CheckPerformed = false;
    private $utf8mb4CapabilityAnnounced = false;

    public function __construct()
    {
        $this->installStartedAt = microtime(true);
        $this->currentInstalledVersion = $this->getCurrentInstalledVersion();
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

    private function importStandardLibrary(): void
    {
        global $ff_admpath, $ff_compath, $errors, $errmode;

        $ff_admpath = str_replace('\\', '/', JPATH_ADMINISTRATOR . '/components/com_breezingforms');
        $ff_compath = str_replace('\\', '/', JPATH_SITE . '/components/com_breezingforms');
        $xmlFile = $ff_admpath . '/packages/stdlib.english.xml';

        if (!File::exists($xmlFile)) {
            $this->log("Standard library package not found: {$xmlFile}", Log::WARNING);
            return;
        }

        require_once $ff_admpath . '/libraries/crosstec/classes/BFText.php';
        require_once $ff_compath . '/facileforms.class.php';
        require_once $ff_admpath . '/admin/import.class.php';

        $errors = [];
        $errmode = 'log';

        $importer = new ff_importPackage();
        $importer->reinstallOnlyIfChanged = true;

        if (!$importer->import($xmlFile)) {
            $details = [];

            if (!empty($errors)) {
                $details = $errors;
            } elseif (!empty($importer->error)) {
                $details[] = $importer->error;
            }

            $detailText = empty($details) ? 'Unknown import error.' : implode(' | ', $details);
            $this->log('Standard library import failed. ' . $detailText, Log::ERROR);
            $this->announce('BreezingForms standard pieces import failed: ' . $detailText, 'error', Log::ERROR);
            return;
        }

        $this->log(
            'Standard library imported successfully: ' .
            count($importer->scripts) . ' script(s), ' .
            count($importer->pieces) . ' piece(s), ' .
            count($importer->forms) . ' form(s).'
        );
    }

    private function getIncomingVersion($parent): string
    {
        $installer = is_object($parent) && method_exists($parent, 'getParent')
            ? $parent->getParent()
            : null;
        $manifest = $installer && method_exists($installer, 'getManifest')
            ? $installer->getManifest()
            : null;

        return $manifest && isset($manifest->version) ? (string) $manifest->version : '';
    }

    private function log(string $message, int $priority = Log::INFO): void
    {
        $message = $this->prefixMessage($message, $priority);
        Log::add($message, $priority, 'com_breezingforms.install');

        $logPath = JPATH_ADMINISTRATOR . '/logs/breezingforms_install2.log';

        if (!Folder::exists(dirname($logPath))) {
            Folder::create(dirname($logPath));
        }

        $timestamp = date('Y-m-d H:i:s');
        $line = "[{$timestamp}] [] {$message}" . PHP_EOL;

        file_put_contents($logPath, $line, FILE_APPEND | LOCK_EX);
    }

    private function announce(string $message, string $type = 'message', int $priority = Log::INFO): void
    {
        $this->log($message, $priority);
        Factory::getApplication()->enqueueMessage($this->formatInstallMessageForDisplay($this->prefixMessage($message, $priority)), $type);
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

            return trim($serverType . ' ' . $version) . ($prefix !== '' ? ' (prefix: ' . $prefix . ')' : '');
        } catch (\Throwable $e) {
            $this->log('Unable to detect database runtime information: ' . $e->getMessage(), Log::WARNING);
            return 'unknown';
        }
    }

    private function getCurrentInstalledVersion()
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select($db->quoteName('manifest_cache'))
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('element') . ' = ' . $db->quote('com_breezingforms'));

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
        $basePath = JPATH_ADMINISTRATOR . '/components/com_breezingforms/plugins';

        if (!Folder::exists($basePath)) {
            $this->log('Plugins directory not found – skipping plugin installation.', Log::WARNING);
            return;
        }

        $folders = Folder::folders($basePath);
        if (empty($folders)) {
            return;
        }

        $installer = new Installer();
        $installer->setDatabase(Factory::getContainer()->get(DatabaseInterface::class));

        foreach ($folders as $folder) {
            $this->log("Installing plugin from folder: {$folder}");

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

    private function copyComponentImageAssets(): void
    {
        $sourceImages = JPATH_SITE . '/components/com_breezingforms/images';
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

        $this->log('BreezingForms database update completed.');
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
                        $installer->uninstall('plugin', $id, 1);
                    }
                }
            }
        }

        $this->cleanupOldConfig();
        $this->log('BreezingForms uninstallation completed.');
    }

    /**
     * method to run before an install/update/uninstall method
     *
     * @return void
     */

    public function preflight(string $type, $parent): void
    {
        $this->incomingPackageVersion = $this->getIncomingVersion($parent);
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

    }
    /**
     * method to run after an install/update/uninstall method
     *
     * @return void
     */
    function postflight($type, $parent)
    {

        // === LOG POUR DÉBOGAGE ===
        $this->log('Postflight installation method call, parameter : ' . $type . '.');
        $this->log('Current version in manifest_cache : ' . $this->getCurrentInstalledVersion() . '.');

        if (in_array($type, ['install', 'update', 'discover_install'], true)) {
            $this->ensureUtf8mb4Columns();
            $this->importStandardLibrary();
            $this->copyComponentImageAssets();
        }

        $this->installPlugins();
        $this->removeOldUpdateSite();
        $this->cleanupOldConfig();

        $durationSeconds = max(0.0, microtime(true) - $this->installStartedAt);
        $actionLabel = strtoupper((string) $type);
        $packageVersion = $this->incomingPackageVersion !== '' ? $this->incomingPackageVersion : $this->getIncomingVersion($parent);
        $installedVersion = $this->getCurrentInstalledVersion();

        $this->announce(
            '[OK] BreezingForms ' . $actionLabel . ' finished successfully.' .
            ' Package version: <strong>' . htmlspecialchars($packageVersion !== '' ? $packageVersion : 'unknown', ENT_QUOTES, 'UTF-8') . '</strong>' .
            ' | installed version: <strong>' . htmlspecialchars($installedVersion, ENT_QUOTES, 'UTF-8') . '</strong>' .
            ' | duration: <strong>' . number_format($durationSeconds, 2, '.', '') . 's</strong>.',
            'message',
            Log::INFO
        );
        $this->log('BreezingForms installation/update process finished successfully.');
    }

    function getPlugins()
    {
        $plugins = array();
        $plugins['system'] = array();
        $plugins['system'][] = 'sysbreezingforms';
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
