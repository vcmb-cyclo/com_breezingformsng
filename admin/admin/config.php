<?php
/**
* BreezingForms - A Joomla Forms Application
* @version 1.4.4
* @package BreezingForms
* @copyright (C) 2004 by Peter Koch
* @license Released under the terms of the GNU General Public License
**/
defined('_JEXEC') or die('Direct Access to this location is not allowed.');

$caller =  BFRequest::getVar( 'caller_url', '');
$pkg = BFRequest::getVar( 'pkg', '');
switch($task) {
	case 'edit':
		$ff_config->edit($option, $caller, $pkg);
		break;
	case 'save':
		$ff_config->save($option, $caller, $pkg);
		break;
	case 'cancel':
		$ff_config->cancel($option, $caller, $pkg);
		break;
	case 'instpackage':
		$ff_config->instPackage($option, $caller, $pkg);
		break;
	case 'delpkgs':
		$ff_config->uninstPackages($option, $caller, $pkg, $ids);
		break;
	case 'makepackage':
		$ff_config->makePackage($option, $caller, $pkg);
		break;
	case 'mkpackage':
		$ff_config->mkPackage($option, $caller, $pkg);
		break;
	case 'localpackage':
		$ff_config->instLocalPackage($option, $caller, $pkg);
		break;
	case 'uploadpackage':
		$ff_config->instUploadPackage($option, $caller, $pkg);
		break;
	default:
		$ff_config->edit($option, "index.php", $pkg);
		break;
} // switch
?>