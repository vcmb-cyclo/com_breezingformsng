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

class JFormFieldForms extends JFormField {

	protected $type = 'Forms';

	protected function getInput() {
		$class = $this->element['class'] ? $this->element['class'] : "text_area";
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$db->setQuery("Select id,`name` From #__contentbuilder_forms Where published = 1 Order By `ordering`");
		$status = $db->loadObjectList();
		return JHTML::_('select.genericlist',  $status, $this->name, '" onchange="if(typeof contentbuilder_setFormId != \'undefined\') { contentbuilder_setFormId(this.options[this.selectedIndex].value); }" class="' . $this->element['class'] . '"', 'id', 'name', $this->value );
	}
}