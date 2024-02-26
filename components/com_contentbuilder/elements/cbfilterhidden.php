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

use Joomla\Database\DatabaseInterface;
use Joomla\CMS\Factory;

class JFormFieldCbfilterhidden extends JFormField {

	protected $type = 'Forms';

	protected function getInput() {
		$class = $this->element['class'] ? $this->element['class'] : "text_area";
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$out = '<input type="hidden" name="'.$this->name.'" id="'.$this->id.'" value="'.$this->value.'"/>'."\n";
		$out .= '
                <script type="text/javascript">
                <!--
                var cb_value = {};
                var currval = "'.str_replace(array("\n","\r"),array("\\n",""),addslashes($this->value)).'";
                
                function contentbuilder_addValue(element_id, value){
                    cb_value[element_id] = value;
                    var contents = "";
                    for(var x in cb_value){
                        contents += x + "\t" + cb_value[x] + "\n";
                    }
                    document.getElementById("'.$this->id.'").value = contents;
                }
                //-->
                </script>';
		return $out;
	}
}