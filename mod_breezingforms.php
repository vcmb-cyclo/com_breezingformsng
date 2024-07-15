<?php
/**
* BreezingForms - A Joomla Forms Application
* @version 1.9
* @package BreezingForms
* @copyright (C) 2008-2020 by Markus Bopp
* @license Released under the terms of the GNU General Public License
**/
defined( '_JEXEC' ) or die( 'Direct Access to this location is not allowed.' );

if(file_exists(JPATH_SITE . '/administrator/components/com_breezingforms/libraries/crosstec/classes/BFRequest.php')){

    require_once(JPATH_SITE . '/administrator/components/com_breezingforms/libraries/crosstec/classes/BFRequest.php');

    // original breezing author overrides the global $param in sourcecode
    // to make the modules' parameter work again, we have to temp. save and re-assign after the module is rendered
    $tmpParams = $params;

    $ff_modpath = str_replace('\\','/',dirname(__FILE__ ));
    $ff_compath = JPATH_SITE . '/components/com_breezingforms';
    $option = BFRequest::getVar('option','');
    $ff_applic = 'mod_facileforms';
    $ff_runningAsModule = true;
    $xModuleId = $module->id;
    require($ff_compath.'/breezingforms.php');

    $params = $tmpParams;
}