<?php
defined('_JEXEC') or die('Direct Access to this location is not allowed.');
/**
* BreezingForms - A Joomla Forms Application
* @version 1.9
* @package BreezingForms
* @copyright (C) 2008-2020 by Markus Bopp
* @copyright Copyright (C) 2024 by XDA+GIL
* @license Released under the terms of the GNU General Public License
**/

use Joomla\CMS\Table\Table;

class BFTableElements extends Table {

	function __construct($db)
	{
		parent::__construct('#__facileforms_elements', 'id', $db);
	}

}