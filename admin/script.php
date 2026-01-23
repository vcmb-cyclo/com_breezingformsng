<?php
/**
 * @package     BreezingForms
 * @author      Markus Bopp
 * @link        http://www.crosstec.de
 * @copyright   (C) 2025 by XDA+GIL
 * @license     GNU/GPL
 */
defined('_JEXEC') or die ('Direct Access to this location is not allowed.');

use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\Installer\Installer;

class com_breezingformsInstallerScript
{
    /**
     * method to install the component
     *
     * @return void
     */
    function install($parent)
    {
    }

    /**
     * method to update the component
     *
     * @return void
     */
    function update($parent)
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $tables = self::getTableFields(Factory::getContainer()->get(DatabaseInterface::class)->getTableList());

        if (isset ($tables[Factory::getContainer()->get(DatabaseInterface::class)->getPrefix() . 'facileforms_records'])) {
            if (!isset ($tables[Factory::getContainer()->get(DatabaseInterface::class)->getPrefix() . 'facileforms_records']['opted'])) {
                $db->setQuery("ALTER TABLE `#__facileforms_records` ADD `opted` TINYINT(1) NOT NULL DEFAULT '0' AFTER `paypal_download_tries`, ADD INDEX (`opted`)");
                $db->execute();
            }
        }

        if (isset ($tables[Factory::getContainer()->get(DatabaseInterface::class)->getPrefix() . 'facileforms_records'])) {
            if (!isset ($tables[Factory::getContainer()->get(DatabaseInterface::class)->getPrefix() . 'facileforms_records']['opt_ip'])) {
                $db->setQuery("ALTER TABLE `#__facileforms_records` ADD `opt_ip` VARCHAR(255) NOT NULL DEFAULT '' AFTER `opted`, ADD INDEX (`opt_ip`)");
                $db->execute();
            }
        }

        if (isset ($tables[Factory::getContainer()->get(DatabaseInterface::class)->getPrefix() . 'facileforms_records'])) {
            if (!isset ($tables[Factory::getContainer()->get(DatabaseInterface::class)->getPrefix() . 'facileforms_records']['opt_date'])) {
                $db->setQuery("ALTER TABLE `#__facileforms_records` ADD `opt_date` DATETIME NULL DEFAULT NULL AFTER `opt_ip`, ADD INDEX (`opt_date`)");
                $db->execute();
            }
        }

        if (isset ($tables[Factory::getContainer()->get(DatabaseInterface::class)->getPrefix() . 'facileforms_records'])) {
            if (!isset ($tables[Factory::getContainer()->get(DatabaseInterface::class)->getPrefix() . 'facileforms_records']['opt_token'])) {
                $db->setQuery("ALTER TABLE `#__facileforms_records` ADD `opt_token` VARCHAR(255) NOT NULL DEFAULT '' AFTER `opt_date`, ADD INDEX (`opt_token`)");
                $db->execute();
            }
        }
        if (isset ($tables[Factory::getContainer()->get(DatabaseInterface::class)->getPrefix() . 'facileforms_records'])) {
            if (isset ($tables[Factory::getContainer()->get(DatabaseInterface::class)->getPrefix() . 'facileforms_records']['opt_date'])) {
                $db->setQuery("ALTER TABLE `#__facileforms_records` MODIFY `opt_date` DATETIME NULL DEFAULT NULL");
                $db->execute();
            }
        }

        if (isset ($tables[Factory::getContainer()->get(DatabaseInterface::class)->getPrefix() . 'facileforms_forms'])) {
            if (!isset ($tables[Factory::getContainer()->get(DatabaseInterface::class)->getPrefix() . 'facileforms_forms']['double_opt'])) {
                $db->setQuery("ALTER TABLE `#__facileforms_forms` ADD `double_opt` TINYINT(1) NOT NULL DEFAULT '0' AFTER `filter_state`, ADD INDEX (`double_opt`)");
                $db->execute();
            }
        }

        if (isset ($tables[Factory::getContainer()->get(DatabaseInterface::class)->getPrefix() . 'facileforms_forms'])) {
            if (!isset ($tables[Factory::getContainer()->get(DatabaseInterface::class)->getPrefix() . 'facileforms_forms']['opt_mail'])) {
                $db->setQuery("ALTER TABLE `#__facileforms_forms` ADD `opt_mail` VARCHAR(128) NOT NULL DEFAULT '' AFTER `double_opt`, ADD INDEX (`opt_mail`)");
                $db->execute();
            }
        }

        if (isset ($tables[Factory::getContainer()->get(DatabaseInterface::class)->getPrefix() . 'facileforms_scripts'])) {
            if (!isset ($tables[Factory::getContainer()->get(DatabaseInterface::class)->getPrefix() . 'facileforms_scripts']['created'])) {
                $db->setQuery("ALTER TABLE `#__facileforms_scripts` ADD `created` DATETIME NULL DEFAULT NULL AFTER `code`");
                $db->execute();
            }
            if (!isset ($tables[Factory::getContainer()->get(DatabaseInterface::class)->getPrefix() . 'facileforms_scripts']['created_by'])) {
                $db->setQuery("ALTER TABLE `#__facileforms_scripts` ADD `created_by` VARCHAR(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' AFTER `created`");
                $db->execute();
            }
            if (!isset ($tables[Factory::getContainer()->get(DatabaseInterface::class)->getPrefix() . 'facileforms_scripts']['modified'])) {
                $db->setQuery("ALTER TABLE `#__facileforms_scripts` ADD `modified` DATETIME NULL DEFAULT NULL AFTER `created_by`");
                $db->execute();
            }
            if (!isset ($tables[Factory::getContainer()->get(DatabaseInterface::class)->getPrefix() . 'facileforms_scripts']['modified_by'])) {
                $db->setQuery("ALTER TABLE `#__facileforms_scripts` ADD `modified_by` VARCHAR(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' AFTER `modified`");
                $db->execute();
            }
            if (isset ($tables[Factory::getContainer()->get(DatabaseInterface::class)->getPrefix() . 'facileforms_scripts']['created_by'])) {
                $db->setQuery("ALTER TABLE `#__facileforms_scripts` MODIFY `created_by` VARCHAR(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT ''");
                $db->execute();
            }
            if (isset ($tables[Factory::getContainer()->get(DatabaseInterface::class)->getPrefix() . 'facileforms_scripts']['modified_by'])) {
                $db->setQuery("ALTER TABLE `#__facileforms_scripts` MODIFY `modified_by` VARCHAR(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT ''");
                $db->execute();
            }
        }

        if (isset ($tables[Factory::getContainer()->get(DatabaseInterface::class)->getPrefix() . 'facileforms_pieces'])) {
            if (!isset ($tables[Factory::getContainer()->get(DatabaseInterface::class)->getPrefix() . 'facileforms_pieces']['created'])) {
                $db->setQuery("ALTER TABLE `#__facileforms_pieces` ADD `created` DATETIME NULL DEFAULT NULL AFTER `code`");
                $db->execute();
            }
            if (!isset ($tables[Factory::getContainer()->get(DatabaseInterface::class)->getPrefix() . 'facileforms_pieces']['created_by'])) {
                $db->setQuery("ALTER TABLE `#__facileforms_pieces` ADD `created_by` VARCHAR(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' AFTER `created`");
                $db->execute();
            }
            if (!isset ($tables[Factory::getContainer()->get(DatabaseInterface::class)->getPrefix() . 'facileforms_pieces']['modified'])) {
                $db->setQuery("ALTER TABLE `#__facileforms_pieces` ADD `modified` DATETIME NULL DEFAULT NULL AFTER `created_by`");
                $db->execute();
            }
            if (!isset ($tables[Factory::getContainer()->get(DatabaseInterface::class)->getPrefix() . 'facileforms_pieces']['modified_by'])) {
                $db->setQuery("ALTER TABLE `#__facileforms_pieces` ADD `modified_by` VARCHAR(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' AFTER `modified`");
                $db->execute();
            }
            if (isset ($tables[Factory::getContainer()->get(DatabaseInterface::class)->getPrefix() . 'facileforms_pieces']['created_by'])) {
                $db->setQuery("ALTER TABLE `#__facileforms_pieces` MODIFY `created_by` VARCHAR(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT ''");
                $db->execute();
            }
            if (isset ($tables[Factory::getContainer()->get(DatabaseInterface::class)->getPrefix() . 'facileforms_pieces']['modified_by'])) {
                $db->setQuery("ALTER TABLE `#__facileforms_pieces` MODIFY `modified_by` VARCHAR(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT ''");
                $db->execute();
            }
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
                        $installer->uninstall('plugin', $id, 1);
                    }
                }
            }
        }

        if (File::exists(JPATH_SITE . '/media/breezingforms/facileforms.config.php')) {
            File::delete(JPATH_SITE . '/media/breezingforms/facileforms.config.php');
        }
    }

    /**
     * method to run before an install/update/uninstall method
     *
     * @return void
     */
    function preflight($type, $parent)
    {

    }

    /**
     * method to run after an install/update/uninstall method
     *
     * @return void
     */
    function postflight($type, $parent)
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $plugins = $this->getPlugins();

        $base_path = JPATH_SITE . '/administrator/components/com_breezingforms/plugins';

        if (file_exists($base_path)) {

            $folders = @Folder::folders($base_path);

            if (count($folders) != 0) {

                $installer = new Installer();
                $installer->setDatabase($db);
                
                foreach ($folders as $folder) {
                    $installer->install($base_path . '/' . $folder);
                }

                foreach ($plugins as $folder => $subplugs) {
                    foreach ($subplugs as $plugin) {
                        $db->setQuery('Update #__extensions Set `enabled` = 1 WHERE `type` = "plugin" AND `element` = "' . $plugin . '" AND `folder` = "' . $folder . '"');
                        $db->execute();
                    }
                }

            }
        }

        $db->setQuery("Select update_site_id From #__update_sites Where `name` = 'BreezingForms Free' And `type` = 'extension'");
        $site_id = $db->loadResult();

        if ($site_id) {

            $db->setQuery("Delete From #__update_sites Where update_site_id = " . $db->quote($site_id));
            $db->execute();
            $db->setQuery("Delete From #__update_sites_extensions Where update_site_id = " . $db->quote($site_id));
            $db->execute();
            $db->setQuery("Delete From #__updates Where update_site_id = " . $db->quote($site_id));
            $db->execute();
        }
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
