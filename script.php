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
    private function log(string $message, int $priority = Log::INFO): void
    {
        Log::add($message, $priority, 'com_breezingforms.install');

        $logPath = JPATH_ADMINISTRATOR . '/logs/breezingforms_install2.log';

        if (!Folder::exists(dirname($logPath))) {
            Folder::create(dirname($logPath));
        }

        $timestamp = date('Y-m-d H:i:s');
        $line = "[{$timestamp}] [] {$message}" . PHP_EOL;

        file_put_contents($logPath, $line, FILE_APPEND | LOCK_EX);
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
                $this->log("Failed to install plugin {$folder}.", Log::ERROR);
                Factory::getApplication()->enqueueMessage("Failed to install BreezingForms plugin: {$folder}", 'error');
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

        $db = Factory::getContainer()->get(DatabaseInterface::class);
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
        }

        $auditColumns = [
            'created'     => "DATETIME NULL DEFAULT NULL AFTER `code`",
            'created_by'  => "VARCHAR(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' AFTER `created`",
            'modified'    => "DATETIME NULL DEFAULT NULL AFTER `created_by`",
            'modified_by' => "VARCHAR(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' AFTER `modified`",
        ];

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
                $db->setQuery("ALTER TABLE `{$tableName}` MODIFY `created_by` VARCHAR(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT ''")->execute();
                $this->log("Updated created_by column definition in {$tableName}.");
            }
            if (isset($columns['modified_by'])) {
                $db->setQuery("ALTER TABLE `{$tableName}` MODIFY `modified_by` VARCHAR(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT ''")->execute();
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
        $this->log("Preflight executed for action: {$type}");
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

        $this->installPlugins();
        $this->removeOldUpdateSite();
        $this->cleanupOldConfig();

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
