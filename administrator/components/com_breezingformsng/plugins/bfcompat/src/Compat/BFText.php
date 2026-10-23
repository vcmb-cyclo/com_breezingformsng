<?php
/**
 *
 * @copyright @copyright  Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die('Direct Access to this location is not allowed.');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Language;
use Joomla\CMS\Language\Text;

/**
 * BreezingForms NG - A Joomla Forms Application
 * 
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL | Until 1.9 2008-2020 by Markus Bopp
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 * 
 * SPDX-License-Identifier: GPL-2.0-or-later
 **/

/**
 * Not a Joomla-version compatibility shim: kept as the BreezingForms legacy
 * API surface so PHP stored in the database (facileforms_pieces.code,
 * forms.piece*code) can keep calling BFText::* directly.
 */
final class BFText
{

	const COMPONENT_NAME = 'com_breezingformsng';

	private ?Language $language = null;

	/**
	 * @var BFText
	 */
	static private $bftext = null;

	/**
	 * Constructor is private, only BFText::_(string) is allowed to be used from outside
	 */
	private function __construct()
	{
	}

	/**
	 * Returns a new BFText object containing the language object.
	 * 
	 * @return BFText
	 */
	private static function getInstance()
	{
		if (!(self::$bftext instanceof BFText)) {
			self::$bftext = new BFText();
			self::$bftext->language = Factory::getApplication()->getLanguage();
		}

		return self::$bftext;
	}

	/**
	 * BFText::_(string) does the same like Text::_(string), except that it reloads the
	 * language for com_breezingformsng if a key is not set. A key could be not set from within the HTML_* view functions because the language
	 * is not loaded there. So if one text request comes from one of these view functions, 
	 * it makes sure that the language is always been loaded (of course in a lazy way).
	 *
	 * @param string $name
	 * @return string
	 */
	public static function _(string $name): string
	{
		$bftext = BFText::getInstance();
		if (!$bftext->language->hasKey($name)) {
			$bftext->language->load(BFText::COMPONENT_NAME);
		}

		// ok, loaded and ready to go
		return Text::_($name);
	}
}
