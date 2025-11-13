<?php
defined('_JEXEC') or die('Direct Access to this location is not allowed.');
/**
* BreezingForms - A Joomla Forms Application
* @version 1.9
* @package BreezingForms
* @copyright (C) 2008-2020 by Markus Bopp
* @license Released under the terms of the GNU General Public License
**/

/**
 * Zend_Exception
 */
require_once JPATH_SITE . '/administrator/components/com_breezingforms/libraries/Zend/Exception.php';


/**
 * @category   Zend
 * @package    Zend_Json
 * @copyright  Copyright (c) 2005-2008 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
if(!class_exists('Zend_Json_Exception')){
class Zend_Json_Exception extends Zend_Exception
{}
}

