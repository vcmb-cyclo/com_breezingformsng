<?php
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

// Public runtime helpers used by stored form scripts and the processor facade.

/**
 * @return string
 */
function nl()
{
	return "\r\n";
} // nl

/**
 * @return string|void
 */
function nlc()
{
	global $ff_config;
	if (!$ff_config->compress)
		return "\r\n";
} // nlc

/**
 * @param string $text
 * @return string
 */
function adjustNewlines($text)
{
	$text = str_replace("\r\n", "\n", $text); // unix mode
	return str_replace("\n", nl(), $text); // ff mode
} // adjustNewlines

/**
 * @param int $level
 * @return string
 */
function indent($level)
{
	$ind = '';
	for ($i = 0; $i < $level; $i++)
		$ind .= "\t";
	return $ind;
} // indent

/**
 * @param int $level
 * @return string
 */
function indentc($level)
{
	global $ff_config;
	$ind = '';
	if (!$ff_config->compress)
		for ($i = 0; $i < $level; $i++)
			$ind .= "\t";
	return $ind;
} // indentc

/**
 * @param string $text
 * @return string
 */
function expstring($text)
{
	$o = '';
	$text = trim($text);
	$l = strlen($text);
	for ($i = 0; $i < $l; $i++) {
		$c = $text[$i];
		switch ($c) {
			case ';':
				$o .= '\\x3B';
				break;
			case ',':
				$o .= '\\x2C';
				break;
			case '&':
				$o .= '\\x26';
				break;
			case '<':
				$o .= '\\x3C';
				break;
			case '>':
				$o .= '\\x3E';
				break;
			case '\'':
				$o .= '\\x27';
				break;
			case '\\':
				$o .= '\\x5C';
				break;
			case '"':
				$o .= '\\x22';
				break;
			case "\n":
				$o .= '\\n';
				break;
			case "\r":
				$o .= '\\r';
				break;
			default:
				$o .= $c;
		} // switch
	} // for
	return $o;
} // expstring

/**
 * @param string $text
 * @return string
 */
function impstring($text)
{
	return stripcslashes($text);
} // impstring

/**
 * @param string $p
 * @param bool $ff_param
 * @return bool
 */
function ff_reserved($p, $ff_param = true)
{
	global $ff_resnames;

	$p = strtolower($p);
	if (substr($p, 0, 3) != 'ff_')
		return false;

	if ($ff_param && substr($p, 0, 9) == 'ff_param_')
		return true;

	if (count($ff_resnames))
		foreach ($ff_resnames as $n)
			if ($p == $n)
				return true;
	return false;
} // ff_reserved
