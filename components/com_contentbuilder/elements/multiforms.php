<?php

/**
 * @package     BreezingCommerce
 * @author      Markus Bopp
 * @link        https://www.crosstec.org
 * @license     GNU/GPL
 */
defined('_JEXEC') or die('Restricted access');

jimport('joomla.html.html');
jimport('joomla.form.formfield');

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

class JFormFieldMultiforms extends JFormField {

	protected $type = 'Multiforms';

	protected function getInput() {
		$class = $this->element['class'] ? $this->element['class'] : "text_area";
		$multiple = 'multiple="multiple" ';
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$db->setQuery("Select id,`name` From #__contentbuilder_forms Where published = 1 Order By `ordering`");
		$status = $db->loadObjectList();
		return JHTML::_('select.genericlist',  $status, $this->name, $multiple.'style="width: 100%;" onchange="if(typeof contentbuilder_setFormId != \'undefined\') { contentbuilder_setFormId(this.options[this.selectedIndex].value); }" class="' . $this->element['class'] . '"', 'id', 'name', $this->value );
	}
}