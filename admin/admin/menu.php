<?php
/**
* BreezingForms - A Joomla Forms Application
* @version 1.4.4
* @package BreezingForms
* @copyright (C) 2004-2005 by Peter Koch
* @license Released under the terms of the GNU General Public License
**/
defined('_JEXEC') or die('Direct Access to this location is not allowed.');

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

require_once($ff_admpath.'/admin/menu.class.php');

$pkg = getMenuPackage();
switch ($task) {
	case 'save' :
		facileFormsMenu::save($option, $pkg);
		break;
	case 'cancel':
		facileFormsMenu::cancel($option, $pkg);
		break;
	case 'new' :
		facileFormsMenu::create($option, $pkg);
		break;
	case 'newedit' :
		$parent = BFRequest::getVar( 'parent', '');
		$formid = BFRequest::getVar( 'formid', '');
		facileFormsMenu::edit($option, $pkg, $ids, $formid, $parent);
		break;
	case 'edit' :
		facileFormsMenu::edit($option, $pkg, $ids, '', '');
		break;
	case 'copy' :
		facileFormsMenu::copy($option, $pkg, $ids);
		break;
	case 'remove' :
		facileFormsMenu::del($option, $pkg, $ids);
		break;
	case 'publish' :
		facileFormsMenu::publish($option, $pkg, $ids, '1');
		break;
	case 'unpublish' :
		facileFormsMenu::publish($option, $pkg, $ids, '0');
		break;
	case 'orderup':
		facileFormsMenu::order($option, $pkg, $ids, -1);
		break;
	case 'orderdown':
		facileFormsMenu::order($option, $pkg, $ids, 1);
		break;
	case 'config' :
		$ff_config->edit(
			$option,
			"index.php?option=$option&act=managemenus",
			$pkg
		);
		break;
	default:
		facileFormsMenu::listitems($option, $pkg);
		break;
} // switch

function getMenuPackage()
{
	global $ff_config;
	$pkg = BFRequest::getVar( 'pkg', null);
        
	if (is_null($pkg))
		$pkg = $pkg = $ff_config->menupkg;
	else
	if ($pkg == '- blank -')
		$pkg = '';
	else {
		$ok = _ff_selectValue(
			"select count(*) from `#__facileforms_compmenus` ".
			"where package =  ".Factory::getContainer()->get(DatabaseInterface::class)->Quote($pkg)
		);
		if (!$ok) $pkg = $ff_config->menupkg;
	} // if
	if ($pkg != $ff_config->menupkg) {
		$ff_config->menupkg = $pkg;
		$ff_config->store();
	} // if
        
	return $pkg;
} // getMenuPackage

?>