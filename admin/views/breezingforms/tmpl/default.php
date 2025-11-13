<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_cpanel
 *
 * @copyright   Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

if(BFRequest::getVar('act') != 'quickmode_editor' && BFRequest::getVar('task') != 'csvimport' && BFRequest::getVar('task') != 'setcsvimport'){

    echo $this->sidebar;
}