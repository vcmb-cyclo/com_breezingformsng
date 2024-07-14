<?php
/**
* BreezingForms - A Joomla Forms Application
* @version 1.9
* @package BreezingForms
* @copyright (C) 2008-2020 by Markus Bopp
* @license Released under the terms of the GNU General Public License
**/
defined('_JEXEC') or die('Direct Access to this location is not allowed.');

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

function com_install(){

    if(!version_compare(PHP_VERSION, '5.1.2', '>=')){
	echo '<b style="color:red">WARNING: YOU ARE RUNNING PHP VERSION "'.PHP_VERSION.'". BREEZINGFORMS WON\'T WORK WITH THIS VERSION. PLEASE UPGRADE TO AT LEAST PHP 5.1.2, SORRY BUT YOU BETTER UNINSTALL THIS COMPONENT NOW!</b>';
    }
    
    // adjust component menu

        Factory::getContainer()->get(DatabaseInterface::class)->setQuery(
                "update #__menu set `alias` = 'BreezingForms' " .
                "where `link`='index.php?option=com_breezingforms'"
        );
        Factory::getContainer()->get(DatabaseInterface::class)->execute();
        Factory::getContainer()->get(DatabaseInterface::class)->setQuery(
                "update #__menu set `alias` = 'Manage Records', img='components/com_breezingforms/images/js/ThemeOffice/checkin.png' " .
                "where `link`='index.php?option=com_breezingforms&act=managerecs'"
        );
        Factory::getContainer()->get(DatabaseInterface::class)->execute();
        Factory::getContainer()->get(DatabaseInterface::class)->setQuery(
                "update #__menu set `alias` = 'Manage Backend Menus', img='components/com_breezingforms/images/js/ThemeOffice/mainmenu.png' " .
                "where `link`='index.php?option=com_breezingforms&act=managemenus'"
        );
        Factory::getContainer()->get(DatabaseInterface::class)->execute();
        Factory::getContainer()->get(DatabaseInterface::class)->setQuery(
                "update #__menu set `alias` = 'Manage Forms', img='components/com_breezingforms/images/js/ThemeOffice/content.png' " .
                "where `link`='index.php?option=com_breezingforms&act=manageforms'"
        );
        Factory::getContainer()->get(DatabaseInterface::class)->execute();
        Factory::getContainer()->get(DatabaseInterface::class)->setQuery(
                "update #__menu set `alias` = 'Manage Scripts', img='components/com_breezingforms/images/js/ThemeOffice/controlpanel.png' " .
                "where `link`='index.php?option=com_breezingforms&act=managescripts'"
        );
        Factory::getContainer()->get(DatabaseInterface::class)->execute();
        Factory::getContainer()->get(DatabaseInterface::class)->setQuery(
                "update #__menu set `alias` = 'Manage Pieces', img='components/com_breezingforms/images/js/ThemeOffice/controlpanel.png' " .
                "where `link`='index.php?option=com_breezingforms&act=managepieces'"
        );
        Factory::getContainer()->get(DatabaseInterface::class)->execute();
        Factory::getContainer()->get(DatabaseInterface::class)->setQuery(
                "update #__menu set `alias` = 'Integrator', img='components/com_breezingforms/images/js/ThemeOffice/controlpanel.png' " .
                "where `link`='index.php?option=com_breezingforms&act=integrate'"
        );
        Factory::getContainer()->get(DatabaseInterface::class)->execute();
        Factory::getContainer()->get(DatabaseInterface::class)->setQuery(
                "update #__menu set `alias` = 'Configuration', img='components/com_breezingforms/images/js/ThemeOffice/config.png' " .
                "where `link`='index.php?option=com_breezingforms&act=configuration'"
        );
        Factory::getContainer()->get(DatabaseInterface::class)->execute();
    
}
