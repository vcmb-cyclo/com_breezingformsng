<?php
/**
 * BreezingForms NG - A Joomla Forms Application
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2008-2012 by Markus Bopp
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 * */
defined('_JEXEC') or die('Direct Access to this location is not allowed.');

$this->SetY(-25);
echo '<div style="font-size: 8pt;"><table width="100%" border="0"><tr><td style="width: 80%;height:150mm;">&nbsp;</td><td style="width: 20%; height:150mm; text-align: right;padding-left: 25%;">' . str_repeat('&nbsp;<br/>', 4) . $this->getAliasNumPage() . ' / ' . $this->getAliasNbPages() . '</td></tr></table></div>';