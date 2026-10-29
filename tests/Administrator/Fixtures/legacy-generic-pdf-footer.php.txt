<?php
/**
 * BreezingForms - A Joomla Forms Application
 * @version 1.8
 * @package BreezingForms
 * @copyright (C) 2008-2012 by Markus Bopp
 * @license Released under the terms of the GNU General Public License
 * */
defined('_JEXEC') or die('Direct Access to this location is not allowed.');

$this->SetY(-25);
echo '<div style="font-size: 8pt;"><table width="100%" border="0"><tr><td style="width: 80%;height:150mm;">&nbsp;</td><td style="width: 20%; height:150mm; text-align: right;padding-left: 25%;">' . str_repeat('&nbsp;<br/>', 4) . $this->getAliasNumPage() . ' / ' . $this->getAliasNbPages() . '</td></tr></table></div>';