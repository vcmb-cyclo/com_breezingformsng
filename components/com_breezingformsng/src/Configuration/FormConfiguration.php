<?php

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Site\Configuration;
/**
 * BreezingForms NG - A Joomla Forms Application
 *
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2008-2020 by Markus Bopp
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 **/

defined('_JEXEC') or die('Direct Access to this location is not allowed.');

use Joomla\CMS\Component\ComponentHelper;

/**
 * Runtime form-engine configuration backed by the component parameters.
 */
final class FormConfiguration
{
	/** @var int */
	public $stylesheet = 1;        	// backend frame preview no/yes
	/** @var int */
	public $wysiwyg = 0;        	// use wysiwyg editor for static text
	/** @var int */
	public $areasmall = 4;        	// small textarea lines
	/** @var int */
	public $areamedium = 12;       	// medium textarea lines
	/** @var int */
	public $arealarge = 20;       	// large textarea lines
	/** @var int */
	public $limitdesc = 100;      	// listview description limit
	/** @var string */
	public $emailadr = 'Enter your email address here';                  // default email notify address
	/** @var string */
	public $images = '{mossite}/media/com_breezingformsng/images/site';    // {ff_images} path
	/** @var string */
	public $uploads = '{mospath}/media/breezingforms/uploads';   // {ff_uploads} path
	/** @var int */
	public $movepixels = 10;       	// pixelmover stepping
	/** @var int */
	public $compress = 1;        	// compress output
	/** @var int */
	public $livesite = 0;        	// use $mosConfig_live_site as site url
	/** @var int */
	public $getprovider = 0;        // get provider with gethostbyaddr
	/** @var int */
	public $gridshow = 1;        	// show grid in preview
	/** @var int */
	public $gridsize = 10;       	// grid size
	/** @var string */
	public $gridcolor1 = '#e0e0ff';	// grid color even lines
	/** @var string */
	public $gridcolor2 = '#ffe0e0';	// grid color odd lines

	// record manager settings
	/** @var int */
	public $viewed = 0;        		// default viewed filter setting
	/** @var int */
	public $exported = 0;        	// default exported filter setting
	/** @var int */
	public $archived = 0;        	// default archived filter setting
	/** @var string */
	public $formname = '';       	// default formname filter setting

	/** @var string */
	public $menupkg = '';       	// last selected menu package
	/** @var string */
	public $formpkg = '';       	// last selected form package
	/** @var string */
	public $scriptpkg = '';       	// last selected script package
	/** @var string */
	public $piecepkg = '';       	// last selected piece package

	/** @var string */
	public $csvdelimiter = ";";
	/** @var string */
	public $csvquote = '"';
	/** @var int|string */
	public $cellnewline = 1;

		/** @var int|string */
		public $disable_ip = 0;

	public function __construct()
	{
		$this->load();
	} // constructor

	public function load(): void
	{
		$params = ComponentHelper::getParams('com_breezingformsng');

		foreach (['disable_ip', 'emailadr', 'uploads', 'csvdelimiter', 'csvquote', 'cellnewline'] as $key) {
			$value = $params->get($key);
			if ($value !== null) {
				$this->$key = $value;
			}
		}
	} // load
}
