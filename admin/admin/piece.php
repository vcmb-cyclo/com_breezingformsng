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

require_once ($ff_admpath . '/admin/piece.class.php');

$pkg = getPiecePackage();
switch ($task) {
	case 'save':
		facileFormsPiece::save($option, $pkg);
		break;
	case 'cancel':
		facileFormsPiece::cancel($option, $pkg);
		break;
	case 'edit':
		facileFormsPiece::edit($option, $pkg, $ids);
		break;
	case 'new':
		$ids = array();
		facileFormsPiece::edit($option, $pkg, $ids);
		break;
	case 'copy':
		facileFormsPiece::copy($option, $pkg, $ids);
		break;
	case 'remove':
		facileFormsPiece::del($option, $pkg, $ids);
		break;
	case 'publish':
		facileFormsPiece::publish($option, $pkg, $ids, '1');
		break;
	case 'unpublish':
		facileFormsPiece::publish($option, $pkg, $ids, '0');
		break;
	case 'config':
		$ff_config->edit(
			$option,
			"index.php?option=$option&act=managepieces",
			$pkg
		);
		break;
	default:
		facileFormsPiece::listitems($option, $pkg);
		break;
} // switch

function getPiecePackage()
{
	global $ff_config;
	$pkg = BFRequest::getVar('pkg', null);
	if (is_null($pkg))
		$pkg = $pkg = $ff_config->piecepkg;
	else
		if ($pkg == '- blank -')
			$pkg = '';
		else {
			$ok = _ff_selectValue(
				"select count(*) from `#__facileforms_pieces` " .
				"where package =  " . Factory::getContainer()->get(DatabaseInterface::class)->Quote($pkg)
			);
			if (!$ok)
				$pkg = $ff_config->piecepkg;
		} // if
	if ($pkg != $ff_config->piecepkg) {
		$ff_config->piecepkg = $pkg;
		$ff_config->store();
	} // if
	return $pkg;
} // getPiecePackage

?>