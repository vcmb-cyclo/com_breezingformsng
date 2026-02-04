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

require_once($ff_admpath.'/admin/script.class.php');

$pkg = getScriptPackage();
switch ($task) {
	case 'save' :
		facileFormsScript::save($option, $pkg);
		break;
	case 'cancel':
		facileFormsScript::cancel($option, $pkg);
		break;
	case 'edit' :
		facileFormsScript::edit($option, $pkg, $ids);
		break;
	case 'new' :
		$ids = array();
		facileFormsScript::edit($option, $pkg, $ids);
		break;
	case 'copy' :
		facileFormsScript::copy($option, $pkg, $ids);
		break;
	case 'remove' :
		facileFormsScript::del($option, $pkg, $ids);
		break;
	case 'publish' :
		facileFormsScript::publish($option, $pkg, $ids, '1');
		break;
	case 'unpublish' :
		facileFormsScript::publish($option, $pkg, $ids, '0');
		break;
	case 'prev':
		facileFormsScript::prev($option, $pkg, $ids);
		break;
	case 'next':
		facileFormsScript::next($option, $pkg, $ids);
		break;
	case 'test':
		facileFormsScript::test($option, $pkg, $ids);
		break;
	case 'config' :
		$ff_config->edit(
			$option,
			"index.php?option=$option&act=managescripts",
			$pkg
		);
		break;
	default:
		facileFormsScript::listitems($option, $pkg);
		break;
} // switch

function getScriptPackage()
{
	global $ff_config;
	$pkg = BFRequest::getVar( 'pkg', null);
	if (is_null($pkg))
		$pkg = $pkg = $ff_config->scriptpkg;
	else
	if ($pkg == '- blank -')
		$pkg = '';
	else {
		$ok = _ff_selectValue(
			"select count(*) from `#__facileforms_scripts` ".
			"where package =  ".Factory::getContainer()->get(DatabaseInterface::class)->Quote($pkg)
		);
		if (!$ok) $pkg = $ff_config->scriptpkg;
	} // if
	if ($pkg != $ff_config->scriptpkg) {
		$ff_config->scriptpkg = $pkg;
		$ff_config->store();
	} // if
	return $pkg;
} // getScriptPackage

?>
