<?php
/**
 * BreezingForms - A Joomla Forms Application
 * @version 1.9
 * @package BreezingForms
 * @copyright   Copyright (C) 2024 by XDA+GIL | Until 1.9 2008-2020 by Markus Bopp
 * @license Released under the terms of the GNU General Public License
 **/
defined('_JEXEC') or die('Direct Access to this location is not allowed.');

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

function com_install()
{
        if (!version_compare(PHP_VERSION, '5.1.2', '>=')) {
                echo '<b style="color:red">WARNING: YOU ARE RUNNING PHP VERSION "' . PHP_VERSION . '". BREEZINGFORMS WON\'T WORK WITH THIS VERSION. PLEASE UPGRADE TO AT LEAST PHP 5.1.2, SORRY BUT YOU BETTER UNINSTALL THIS COMPONENT NOW!</b>';
        }

        // Adjust component menu
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $db->setQuery(
                "update #__menu set `alias` = 'BreezingForms' " .
                "where `link`='index.php?option=com_breezingforms'"
        );
        $db->execute();
        $db->setQuery(
                "update #__menu set `alias` = 'Manage Records', img='components/com_breezingforms/images/js/ThemeOffice/checkin.png' " .
                "where `link`='index.php?option=com_breezingforms&act=managerecs'"
        );
        $db->execute();
        $db->setQuery(
                "update #__menu set `alias` = 'Manage Backend Menus', img='components/com_breezingforms/images/js/ThemeOffice/mainmenu.png' " .
                "where `link`='index.php?option=com_breezingforms&act=managemenus'"
        );
        $db->execute();
        $db->setQuery(
                "update #__menu set `alias` = 'Manage Forms', img='components/com_breezingforms/images/js/ThemeOffice/content.png' " .
                "where `link`='index.php?option=com_breezingforms&act=manageforms'"
        );
        $db->execute();
        $db->setQuery(
                "update #__menu set `alias` = 'Manage Scripts', img='components/com_breezingforms/images/js/ThemeOffice/controlpanel.png' " .
                "where `link`='index.php?option=com_breezingforms&act=managescripts'"
        );
        $db->execute();
        $db->setQuery(
                "update #__menu set `alias` = 'Manage Pieces', img='components/com_breezingforms/images/js/ThemeOffice/controlpanel.png' " .
                "where `link`='index.php?option=com_breezingforms&act=managepieces'"
        );
        $db->execute();
        $db->setQuery(
                "update #__menu set `alias` = 'Integrator', img='components/com_breezingforms/images/js/ThemeOffice/controlpanel.png' " .
                "where `link`='index.php?option=com_breezingforms&act=integrate'"
        );
        $db->execute();
        $db->setQuery(
                "update #__menu set `alias` = 'Configuration', img='components/com_breezingforms/images/js/ThemeOffice/config.png' " .
                "where `link`='index.php?option=com_breezingforms&act=configuration'"
        );
        $db->execute();
}
