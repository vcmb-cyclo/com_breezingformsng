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

global $_ff_xmlPackage;

$_ff_xmlPackage = array();

class ff_xmlPackage
{
	var $parser     = NULL;     // xml parser id
	var $doc        = NULL;     // document structure
	var $error      = NULL;     // error message
	var $element    = NULL;     // element stack
	var $params     = NULL;     // parameters

	function __construct()
	{
		// constructor
		global $_ff_xmlPackage;
		$_ff_xmlPackage[] = &$this;
		$this->doc      = array();
	} // ff_xmlPackage

	function import($filename)
	{
		$this->error    = null;
		$this->element  = array();
		$this->params   = array();

		if (!$filename) {
			$this->setError(BFText::_('COM_BREEZINGFORMS_XML_MISSFNAME'));
			return false;
		} // if
		$this->filename = $filename;
		if (!($fp = fopen($this->filename, "r")))   {
			$this->setError(BFText::_('COM_BREEZINGFORMS_XML_ERROPENF').' '.$this->filename);
			return false;
		} // if
		$this->parser = xml_parser_create();
		xml_set_element_handler(
			$this->parser,
			'_ff_xmlStartElement',
			'_ff_xmlEndElement'
		);
		xml_set_character_data_handler(
			$this->parser,
			'_ff_xmlCharData'
		);
		xml_parser_set_option(
			$this->parser,
			XML_OPTION_CASE_FOLDING,
			false
		);
		while ($data = fread($fp, 4096)) {
			if (!xml_parse($this->parser, $data, feof($fp)))
				$this->setError(xml_error_string(xml_get_error_code($this->parser)));
			if ($this->hasError()) break;
		} // while
		xml_parser_free($this->parser);
		$this->parser = null;
		return !$this->hasError();
	} // install

	function saveParams($level, $key, $value)
	{
		if (array_key_exists($key, $this->params[$level]))
			$this->params[$level][$key] .= $value;
		else
			$this->params[$level][$key] = $value;
	} // saveParams

	function setError($message, $inparse = false)
	{
		$this->error = '';
		$level = count($this->element);
		if ($level>0) $this->error .= BFText::_('COM_BREEZINGFORMS_XML_ELEMENT')." '".$this->element[$level-1]."' ";
		if ($inparse) $this->error .= BFText::_('COM_BREEZINGFORMS_XML_ATLINE').' '.xml_get_current_line_number($this->parser).' ';
		if ($this->error != '') $this->error .= ': ';
		$this->error .= $message;
	} // setError

	function hasError()
	{
		return $this->error;
	} // hasError

	function getText($p, $tag, $def = '')
	{
		if (array_key_exists($tag, $this->params[$p])) return impstring($this->params[$p][$tag]);
		return $def;
	} // getText

	function getSqlText($p, $tag, $def = '')
	{
            $db = \Factory::getContainer()->get(DatabaseInterface::class);
            return $db->quote($this->getText($p, $tag, $def));
	} // getSqlText

	function getInt($p, $tag, $def = 0)
	{
		return intval($this->getText($p, $tag, $def));
	} // getInt

} // class ff_xmlPackage

function &_ff_xmlGetPackage($parser)
{
	global $_ff_xmlPackage;
	$n = count($_ff_xmlPackage);
	for ($x = 0; $x < $n; $x++)
		if ($_ff_xmlPackage[$x]->parser == $parser)
			return $_ff_xmlPackage[$x];
	die(BFText::_('COM_BREEZINGFORMS_XML_REFMISSED')." (".__FUNCTION__.")");
} // _ff_xmlGetPackage

function _ff_xmlStartElement($parser, $key, $attr)
{
	$pkg =& _ff_xmlGetPackage($parser);

	// stop processing if error detected
	if ($pkg->hasError()) return;

	// follow elem path in doc down to current element
	$curr =& $pkg->doc;
	foreach ($pkg->element as $tag) $curr =& $curr['elem'][$tag];
	if (!array_key_exists('elem', $curr) ||
		!array_key_exists($key, $curr['elem'])) {
		$pkg->setError(BFText::_('COM_BREEZINGFORMS_XML_UNEXPELEM')." '$key'", true);
		return;
	} // if

	array_push($pkg->element, $key);

	$curr =& $curr['elem'][$key];
	if (is_array($curr)) { // is not leaf
		if (array_key_exists('begin', $curr)) eval($curr['begin']);
		if (array_key_exists('attr', $curr)) $a = $curr['attr']; else $a = array();
		foreach ($attr as $key => $value) {
			if (!array_key_exists($key, $a)) {
				$pkg->setError(BFText::_('COM_BREEZINGFORMS_XML_UNEXPATTR')." '$key'", true);
				return;
			} // if
			$value = trim($value);
			eval($a[$key]);
			if ($pkg->hasError()) return;
		} // foreach
	} // if
} // _ff_xmlStartElement

function _ff_xmlCharData($parser, $value)
{
	$pkg =& _ff_xmlGetPackage($parser);

	// stop processing if error detected
	if ($pkg->hasError()) return;

	// follow elem path in doc down to current element
	$curr =& $pkg->doc;
	foreach ($pkg->element as $key) $curr =& $curr['elem'][$key];

	// if it is a leaf, execute code
	if (!is_array($curr))
		eval($curr);
	else {
		if (array_key_exists('data', $curr))
			eval($curr['data']);
		else {
			// dont complain about whitespace
			$value = trim($value);
			if ($value != '') $pkg->setError(BFText::_('COM_BREEZINGFORMS_XML_UNEXPDATA').": ".$value, true);
		} // if
	} // if
} // _ff_xmlCharData

function _ff_xmlEndElement($parser, $key)
{
	$pkg =& _ff_xmlGetPackage($parser);

	// stop processing if error detected
	if ($pkg->hasError()) return;

	// follow elem path in doc down to current element
	$curr =& $pkg->doc;
	foreach ($pkg->element as $tag) $curr =& $curr['elem'][$tag];

	// check if expected
	if ($tag != $key) {
		$pkg->setError(BFText::_('COM_BREEZINGFORMS_XML_UNEXPCLOS')." '$key'", true);
		return;
	} // if

	if (is_array($curr)) // not a leaf
		if (array_key_exists('end', $curr)) // end code exists
			eval($curr['end']);

	array_pop($pkg->element);
} // _ff_xmlEndElement