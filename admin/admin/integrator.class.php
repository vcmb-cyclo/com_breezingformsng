<?php
/**
 * BreezingForms - A Joomla Forms Application
 * @version 1.9
 * @package BreezingForms
 * @copyright (C) 2008-2020 by Markus Bopp
 * @license Released under the terms of the GNU General Public License
 **/
defined('_JEXEC') or die('Direct Access to this location is not allowed.');

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

class BFIntegrator
{

	/**
	 *
	 * @var JDatabase
	 */
	private $db = null;

	function __construct()
	{
		$this->db = Factory::getContainer()->get(DatabaseInterface::class);
	}

	function getRules()
	{

		$this->db->setQuery("
			Select
				rules.*,
				rules.id As id,
				concat('" . $this->db->getPrefix() . "', rules.reference_table) As reference_table,
				forms.name As form_name,
				forms.id As form_id
			From
				#__facileforms_integrator_rules As rules,
				#__facileforms_forms As forms
			Where
				rules.form_id = forms.id
			Group By
				rules.id
			Order By
				rules.id
			");

		return $this->db->loadObjectList();
	}

	public function getRule($id)
	{

		$this->db->setQuery("
			Select
				rules.*,
				rules.id As id,
				concat('" . $this->db->getPrefix() . "', rules.reference_table) As reference_table,
				forms.name As form_name,
				forms.id As form_id
			From
				#__facileforms_integrator_rules As rules,
				#__facileforms_forms As forms
			Where
				rules.form_id = forms.id
			And
				rules.id = " . $this->db->Quote($id) . "
			Group By
				rules.id
			Order By
				rules.id
			");

		$res = $this->db->loadObjectList();

		return count($res) == 1 ? $res[0] : null;
	}

	public function getItems($ruleId)
	{

		$this->db->setQuery("

			Select
				items.*,
				elements.name As element_name,
				elements.type As element_type
			From
				#__facileforms_integrator_items As items,
				#__facileforms_elements As elements
			Where
				items.rule_id = " . $this->db->Quote($ruleId) . "
			And
				elements.id = items.element_id
			Group By items.id
			Order By items.id Desc
		");

		$ret = $this->db->loadObjectList();

		return $ret;
	}

	public function getTableFields($tables, $typeOnly = true)
	{
		$results = array();

		settype($tables, 'array');

		foreach ($tables as $table) {
			try {
				$results[$table] = Factory::getContainer()->get(DatabaseInterface::class)->getTableColumns($table, $typeOnly);
			} catch (Exception $e) {
			}
		}

		return $results;
	}

	public function getTables()
	{
		return $this->getTableFields($this->db->getTableList());
	}

	public function getForms()
	{

		$this->db->setQuery("

		Select
		id, name, published
		From
		#__facileforms_forms

		");

		return $this->db->loadObjectList();
	}
	// CUSTOM
	public function getPublishedForms()
	{

		$this->db->setQuery("

		Select
		id, name
		From
		#__facileforms_forms as forms
		Where
		forms.published = 1

		");

		return $this->db->loadObjectList();
	}

	public function getUnpublishedForms()
	{

		$this->db->setQuery("

		Select
		id, name
		From
		#__facileforms_forms as forms
		Where
		forms.published = 0

		");

		return $this->db->loadObjectList();
	}
	// END

	public function getFormElements($formId)
	{

		$this->db->setQuery("

			Select
				id, name, type
			From
				#__facileforms_elements
			Where
				form = " . $this->db->Quote($formId) . "

		");

		return $this->db->loadObjectList();
	}

	public function saveRule()
	{
		$refTab = BFRequest::getVar('reference_table', '');
		$pfx = $this->db->getPrefix();
		$tab = $this->str_replace_first($refTab, $pfx, '');

		$this->db->setQuery("

			Insert
			Into
				#__facileforms_integrator_rules
			(
				name,
				form_id,
				reference_table,
				type
			)
			Values
			(
				" . $this->db->Quote(BFRequest::getVar('rule_name')) . ",
				" . $this->db->Quote(BFRequest::getVar('form_id')) . ",
				" . $this->db->Quote($tab) . ",
				" . $this->db->Quote(BFRequest::getVar('type')) . "
			)

		");
		$this->db->execute();

		$ruleId = $this->db->insertid();


		return $ruleId;
	}

	public function getCriteria($ruleId)
	{

		$this->db->setQuery("

			Select
				crit.*,
				elements.name As element_name,
				elements.type As element_type
			From
				#__facileforms_integrator_criteria_form As crit,
				#__facileforms_elements As elements
			Where
				crit.rule_id = " . $this->db->Quote($ruleId) . "
			And
				elements.id = crit.element_id
			Group By crit.id
			Order By crit.id Desc
		");

		try {
			// If it fails, it will throw a RuntimeException
			$ret = $this->db->loadObjectList();
		} catch (RuntimeException $e) {
			Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
			return false;
		}
		return $ret;
	}

	public function addCriteria()
	{

		$this->db->setQuery("

			Insert
			Into
				#__facileforms_integrator_criteria_form
			(
				rule_id,
				operator,
				reference_column,
				element_id,
				andor
			)
			Values
			(
				" . $this->db->Quote(BFRequest::getInt('id', -1)) . ",
				" . $this->db->Quote($_REQUEST['operator']) . ",
				" . $this->db->Quote(BFRequest::getVar('reference_column', '')) . ",
				" . $this->db->Quote(BFRequest::getInt('element_id', '')) . ",
				" . $this->db->Quote(BFRequest::getVar('andor', '')) . "
			)

		");
		try {
			// If it fails, it will throw a RuntimeException
			$this->db->execute();
		} catch (RuntimeException $e) {
			Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
		}
	}

	public function removeCriteria()
	{

		$this->db->setQuery("Delete From #__facileforms_integrator_criteria_form Where id = " . BFRequest::getInt('criteriaId', -1) . "");
		$this->db->execute();

	}

	public function getCriteriaJoomla($ruleId)
	{

		$this->db->setQuery("

			Select
				crit.*
			From
				#__facileforms_integrator_criteria_joomla As crit
			Where
				crit.rule_id = " . $this->db->Quote($ruleId) . "
			Group By crit.id
			Order By crit.id Desc
		");

		$ret = $this->db->loadObjectList();
		return $ret;
	}

	public function addCriteriaJoomla()
	{

		$this->db->setQuery("

			Insert
			Into
				#__facileforms_integrator_criteria_joomla
			(
				rule_id,
				operator,
				reference_column,
				joomla_object,
				andor
			)
			Values
			(
				" . $this->db->Quote(BFRequest::getInt('id', -1)) . ",
				" . $this->db->Quote($_REQUEST['operator']) . ",
				" . $this->db->Quote(BFRequest::getVar('reference_column', '')) . ",
				" . $this->db->Quote(BFRequest::getVar('joomla_object', '')) . ",
				" . $this->db->Quote(BFRequest::getVar('andor', '')) . "
			)

		");
		try {
			$this->db->execute();
		} catch (RuntimeException $e) {
			Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
		}
	}

	public function removeCriteriaJoomla()
	{

		$this->db->setQuery("Delete From #__facileforms_integrator_criteria_joomla Where id = " . BFRequest::getInt('criteriaId', -1) . "");
		$this->db->execute();

	}

	public function getCriteriaFixed($ruleId)
	{

		$this->db->setQuery("

			Select
				crit.*
			From
				#__facileforms_integrator_criteria_fixed As crit
			Where
				crit.rule_id = " . $this->db->Quote($ruleId) . "
			Group By crit.id
			Order By crit.id Desc
		");

		$ret = $this->db->loadObjectList();
		return $ret;
	}

	public function addCriteriaFixed()
	{

		$this->db->setQuery("

			Insert
			Into
				#__facileforms_integrator_criteria_fixed
			(
				rule_id,
				operator,
				reference_column,
				fixed_value,
				andor
			)
			Values
			(
				" . $this->db->Quote(BFRequest::getInt('id', -1)) . ",
				" . $this->db->Quote($_REQUEST['operator']) . ",
				" . $this->db->Quote(BFRequest::getVar('reference_column', '')) . ",
				" . $this->db->Quote(BFRequest::getVar('fixed_value', '')) . ",
				" . $this->db->Quote(BFRequest::getVar('andor', '')) . "
			)

		");
		try {
			$this->db->execute();
		} catch (RuntimeException $e) {
			Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
		}
	}

	public function removeCriteriaFixed()
	{

		$this->db->setQuery("Delete From #__facileforms_integrator_criteria_fixed Where id = " . BFRequest::getInt('criteriaId', -1) . "");
		$this->db->execute();

	}

	public function addItem()
	{

		$this->db->setQuery("

			Insert
			Into
				#__facileforms_integrator_items
			(
				rule_id,
				element_id,
				reference_column
			)
			Values
			(
				" . $this->db->Quote(BFRequest::getInt('id', -1)) . ",
				" . $this->db->Quote(BFRequest::getInt('element_id', -1)) . ",
				" . $this->db->Quote(BFRequest::getVar('reference_column', '')) . "
			)

		");
		try {
			$this->db->execute();
		} catch (RuntimeException $e) {
			Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
		}
	}

	public function removeItem()
	{

		$this->db->setQuery("Delete From #__facileforms_integrator_items Where id = " . BFRequest::getInt('itemId', -1) . "");
		$this->db->execute();

	}

	public function saveCode()
	{
		$this->db->setQuery("Update #__facileforms_integrator_items Set code = " . $this->db->Quote($_REQUEST['code']) . " Where id = " . BFRequest::getInt('itemId', -1) . " And rule_id = " . BFRequest::getInt('id', -1) . "");
		$this->db->execute();
	}

	public function saveFinalizeCode()
	{
		$this->db->setQuery("Update #__facileforms_integrator_rules Set finalize_code = " . $this->db->Quote($_REQUEST['finalizeCode']) . " Where id = " . BFRequest::getInt('id', -1) . "");
		$this->db->execute();
	}

	public function publishRule()
	{
		$this->db->setQuery("Update #__facileforms_integrator_rules Set published = 1 Where id = " . BFRequest::getInt('publish_id', -1) . "");
		$this->db->execute();
	}

	public function unpublishRule()
	{
		$this->db->setQuery("Update #__facileforms_integrator_rules Set published = 0 Where id = " . BFRequest::getInt('publish_id', -1) . "");
		$this->db->execute();
	}

	public function removeRules()
	{
		foreach (BFRequest::getVar('cid', array()) as $id) {

			$this->db->setQuery("Delete From #__facileforms_integrator_rules Where id = " . $id . "");
			$this->db->execute();
			$this->db->setQuery("Delete From #__facileforms_integrator_items Where rule_id = " . $id . "");
			$this->db->execute();
			$this->db->setQuery("Delete From #__facileforms_integrator_criteria_form Where rule_id = " . $id . "");
			$this->db->execute();
			$this->db->setQuery("Delete From #__facileforms_integrator_criteria_joomla Where rule_id = " . $id . "");
			$this->db->execute();
			$this->db->setQuery("Delete From #__facileforms_integrator_criteria_fixed Where rule_id = " . $id . "");
			$this->db->execute();
		}
	}

	public function publishItem()
	{
		$this->db->setQuery("Update #__facileforms_integrator_items Set published = 1 Where id = " . BFRequest::getInt('publish_id', -1) . "");
		$this->db->execute();
	}

	public function unpublishItem()
	{
		$this->db->setQuery("Update #__facileforms_integrator_items Set published = 0 Where id = " . BFRequest::getInt('publish_id', -1) . "");
		$this->db->execute();
	}

	private function str_replace_first($or_string, $string_to_rep, $rep_with)
	{
		$mylen = strlen($string_to_rep);
		$pos = strpos($or_string, $string_to_rep);
		if ($pos === FALSE) {
			return FALSE;
		} else {
			$mystr = substr_replace($or_string, $rep_with, $pos, $mylen);
			return $mystr;
		}
	}

}
