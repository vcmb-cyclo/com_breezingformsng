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

class EasyMode{
	
	/**
	 * @var JDatabase
	 */
	private $db = null;
	
	function __construct(){
		$this->db = Factory::getContainer()->get(DatabaseInterface::class);
	}
	
	public function save($form, $formName, $formTitle, array $formOptions, $templateCode, array $areas, $pages = 1){
		
		$templateCodeProcessed = $templateCode;
		
		$this->db->setQuery("Select id From #__facileforms_forms Where id = ".$this->db->Quote($form)."");
		
		if(count($this->db->loadObjectList()) == 0){
			
			$this->db->setQuery(
						"Insert Into #__facileforms_forms 
						(
							package,
							template_code,
							template_areas,
							published,
							name,
							title,
							class1,
							width,
							height,
							pages
						) 
						Values 
						(
							'EasyModeForms',
							".trim($this->db->Quote($templateCode), "\t, ,\n,\r").",
							".$this->db->Quote(Zend_Json::encode($areas)).",
							'1',
							".trim($this->db->Quote($formName), "\t, ,\n,\r").",
							".trim($this->db->Quote($formTitle), "\t, ,\n,\r").",
							'',
							'400',
							'500',
							".$this->db->Quote($pages)."
						)"
			);
			
			$this->db->execute();
			$form = $this->db->insertid();
			
		} else {
			
			$this->db->setQuery(
						"Update 
							#__facileforms_forms
						 Set 
							template_code = ".$this->db->Quote(trim($templateCode), "\t, ,\n,\r").",
							template_areas = ".$this->db->Quote(Zend_Json::encode($areas)).",
							name = ".trim($this->db->Quote($formName), "\t, ,\n,\r").",
							title = ".trim($this->db->Quote($formTitle), "\t, ,\n,\r").",
							pages = ".$this->db->Quote($pages)."
						 Where
							id = ".$this->db->Quote($form)."
						"
			);
			
			$this->db->execute();
		}

		$notRemoveIds = '';
		
		$i = 0;
		foreach($areas As $area){
			
			$elementCount = 0;
			foreach ($area['elements'] As $element){
				
				$elementId = -1;
				
				if($element['dbId'] == 0){
				
					$this->db->setQuery(
							"Insert Into #__facileforms_elements
							(
								mailback,
								mailbackfile,
								form,
								page,
								published,
								ordering,
								name,
								title,
								type,
								class1,
								class2,
								logging,
								posx,
								posxmode,
								posy,
								posymode,
								width,
								widthmode,
								height,
								heightmode,
								flag1,
								flag2,
								data1,
								data2,
								data3,
								script1cond,
								script1id,
								script1code,
								script1flag1,
								script1flag2,
								script2cond,
								script2id,
								script2code,
								script2flag1,
								script2flag2,
								script2flag3,
								script2flag4,
								script2flag5,
								script3cond,
								script3id,
								script3code,
								script3msg
							) 
							Values 
							(
								".$this->db->Quote($element['mailback']).",
								".$this->db->Quote($element['mailbackfile']).",
								".$this->db->Quote($form).",
								".$this->db->Quote(isset($element['page']) ? $element['page'] : 1).",
								'1',
								".$this->db->Quote($element['orderNumber'] > -1 ? $element['orderNumber'] : $element['appElementOrderId']).",
								".$this->db->Quote($element['name']).",
								".$this->db->Quote($element['title']).",
								".$this->db->Quote($element['bfType']).",
								'',
								'',
								'1',
								'0',
								'0',
								'".(40*$elementCount)."',
								'0',
								'20',
								'0',
								'20',
								'0',
								".$this->db->Quote($element['flag1']).",
								".$this->db->Quote($element['flag2']).",
								".$this->db->Quote($element['data1']).",
								".$this->db->Quote($element['data2']).",
								".$this->db->Quote($element['data3']).",
								".$this->db->Quote($element['script1cond']).",
								".$this->db->Quote($element['script1id']).",
								".$this->db->Quote($element['script1code']).",
								".$this->db->Quote($element['script1flag1']).",
								".$this->db->Quote($element['script1flag2']).",
								".$this->db->Quote($element['script2cond']).",
								".$this->db->Quote($element['script2id']).",
								".$this->db->Quote($element['script2code']).",
								".$this->db->Quote($element['script2flag1']).",
								".$this->db->Quote($element['script2flag2']).",
								".$this->db->Quote($element['script2flag3']).",
								".$this->db->Quote($element['script2flag4']).",
								".$this->db->Quote($element['script2flag5']).",
								".$this->db->Quote($element['script3cond']).",
								".$this->db->Quote($element['script3id']).",
								".$this->db->Quote($element['script3code']).",
								".$this->db->Quote($element['script3msg'])."
							)"
					);
					
					$this->db->execute();
					$elementId = $this->db->insertid();
					$areas[$i]['elements'][$elementCount]['dbId'] = $elementId;
					
				} else {
					
					// fix ids of copied elements
                                        
					$this->db->setQuery("Select id From #__facileforms_elements Where name = ".$this->db->Quote($element['name'])." And form = ".$this->db->Quote($form)." ");
					$elementCheck = $this->db->loadObjectList();
					foreach($elementCheck as $check){
						if($check->id != intval($element['dbId'])){
							$element['dbId'] = $check->id; 
							$areas[$i]['elements'][$elementCount]['dbId'] = $check->id;
						}
						break;
					}
					
					$this->db->setQuery(
							"Update #__facileforms_elements Set
								mailback=".$this->db->Quote($element['mailback']).",
								mailbackfile=".$this->db->Quote($element['mailbackfile']).",
								form=".$this->db->Quote($form).",
								page=".$this->db->Quote(isset($element['page']) ? $element['page'] : 1).",
								published='1',
								ordering=".$this->db->Quote($element['orderNumber'] > -1 ? $element['orderNumber'] : $element['appElementOrderId']).",
								name=".$this->db->Quote($element['name']).",
								title=".$this->db->Quote($element['title']).",
								type=".$this->db->Quote($element['bfType']).",
								class1='',
								class2='',
								logging='1',
								posx='0',
								posxmode='0',
								posy='".(40*$elementCount)."',
								posymode='0',
								width='20',
								widthmode='0',
								height='20',
								heightmode='0',
								flag1=".$this->db->Quote($element['flag1']).",
								flag2=".$this->db->Quote($element['flag2']).",
								data1=".$this->db->Quote($element['data1']).",
								data2=".$this->db->Quote($element['data2']).",
								data3=".$this->db->Quote($element['data3']).",
								script1cond=".$this->db->Quote($element['script1cond']).",
								script1id=".$this->db->Quote($element['script1id']).",
								script1code=".$this->db->Quote($element['script1code']).",
								script1flag1=".$this->db->Quote($element['script1flag1']).",
								script1flag2=".$this->db->Quote($element['script1flag2']).",
								script2cond=".$this->db->Quote($element['script2cond']).",
								script2id=".$this->db->Quote($element['script2id']).",
								script2code=".$this->db->Quote($element['script2code']).",
								script2flag1=".$this->db->Quote($element['script2flag1']).",
								script2flag2=".$this->db->Quote($element['script2flag2']).",
								script2flag3=".$this->db->Quote($element['script2flag3']).",
								script2flag4=".$this->db->Quote($element['script2flag4']).",
								script2flag5=".$this->db->Quote($element['script2flag5']).",
								script3cond=".$this->db->Quote($element['script3cond']).",
								script3id=".$this->db->Quote($element['script3id']).",
								script3code=".$this->db->Quote($element['script3code']).",
								script3msg=".$this->db->Quote($element['script3msg'])."
							Where
								id = ".$this->db->Quote($element['dbId'])."
							"
					);
					$this->db->execute();
					$elementId = $element['dbId'];
				}
				
				$notRemoveIds .= ' id<>' . $this->db->Quote($elementId) . ' And ';
				
				$templateCodeProcessed = str_replace('ff_listItem'    . $element['rndId'],        'ff_listItem'.$elementId,       $templateCodeProcessed);
				$templateCodeProcessed = str_replace('ff_iconCaption' . $element['rndId'],        'ff_iconCaption'.$elementId,    $templateCodeProcessed);
				$templateCodeProcessed = str_replace('ff_dragBox'     . $element['rndId'],        'ff_dragBox'.$elementId,        $templateCodeProcessed);
				$templateCodeProcessed = str_replace('ff_label'       . $element['rndId'],        'ff_label'.$elementId,          $templateCodeProcessed);
				$templateCodeProcessed = str_replace('ff_elem'        . $element['rndId'],        'ff_elem'.$elementId,           $templateCodeProcessed);
				$templateCodeProcessed = str_replace('ff_nm_'         . $element['rndId'] . '[]', 'ff_nm_'.$element['name'].'[]', $templateCodeProcessed);
				$templateCodeProcessed = str_replace('ff_static'      . $element['rndId'],        'ff_static'.$elementId,         $templateCodeProcessed);
				$templateCodeProcessed = str_replace('ff_div'         . $element['rndId'],        'ff_div'.$elementId,            $templateCodeProcessed);
				$templateCodeProcessed = str_replace('ff_captcha'     . $element['rndId'],        'ff_captcha'.$elementId,        $templateCodeProcessed);
				$templateCodeProcessed = str_replace('ff_capimg'      . $element['rndId'],        'ff_capimg'.$elementId,         $templateCodeProcessed);
				$templateCodeProcessed = str_replace('ff_break'      . $element['rndId'],         'ff_break'.$elementId,         $templateCodeProcessed);
				$templateCodeProcessed = str_replace('readonly="readonly"', 'disabled="disabled"', $templateCodeProcessed);
				
				$elementCount++;
			}
			
			$i++;
		}
		
		if(strlen($notRemoveIds) != 0){
			$this->db->setQuery("Delete From #__facileforms_elements Where " . $notRemoveIds . " form = ".$this->db->Quote($form)." ");
			$this->db->execute();
		} else {
			$this->db->setQuery("Delete From #__facileforms_elements Where form = ".$this->db->Quote($form)." ");
			$this->db->execute();
		}
		
		$this->db->setQuery(
						"Update 
							#__facileforms_forms
						 Set 
							template_code_processed = ".$this->db->Quote(trim($templateCodeProcessed)).",
							template_areas          = ".$this->db->Quote(Zend_Json::encode($areas))."
						 Where
							id = ".$this->db->Quote($form)."
						"
			);
			
		$this->db->execute();
		
		return $form;
	}
	
	public function getTemplateCode($form){
		$this->db->setQuery("Select template_code From #__facileforms_forms Where id = ".$this->db->Quote($form)."");
		$objList = $this->db->loadObjectList();
		$objListCount = count($objList);
		
		if($objListCount == 1){
			return $objList[0]->template_code;
		}
		
		return '';
	}
	
	public function getFormNameTitle($form){
		$this->db->setQuery("Select name, title From #__facileforms_forms Where id = ".$this->db->Quote($form)."");
		$objList = $this->db->loadObjectList();
		$objListCount = count($objList);
		if($objListCount == 1){
			return $objList[0];
		}
		
		return null;
	}
	
	public function getNumFormPages($form){
		$this->db->setQuery("Select pages From #__facileforms_forms Where id = ".$this->db->Quote($form)."");
		$objList = $this->db->loadObjectList();
		$objListCount = count($objList);
		if($objListCount == 1){
			return $objList[0]->pages;
		}
		
		return 1;
	}
	
	public function getTemplateCodeProcessed($form){
		$this->db->setQuery("Select template_code_processed From #__facileforms_forms Where id = ".$this->db->Quote($form)."");
		$objList = $this->db->loadObjectList();
		$objListCount = count($objList);
		if($objListCount == 1){
			return $objList[0]->template_code_processed;
		}
		
		return '';
	}
	
	public function getCallbackParams($form){
		$retArray = array();
		$this->db->setQuery("Select template_areas From #__facileforms_forms Where id = ".$this->db->Quote($form)."");
		$objList = $this->db->loadObjectList();
		$objListCount = count($objList);
		if($objListCount == 1){
			$retArray['areas'] = $objList[0]->template_areas;
		}
		return $retArray;
	}
	
	public static function strip_selected_tags($text, $tags = array())
	{
	    $args = func_get_args();
	    $text = array_shift($args);
	    $tags = func_num_args() > 2 ? array_diff($args,array($text))  : (array)$tags;
	    foreach ($tags as $tag){
	        while(preg_match('/<'.$tag.'(|\W[^>]*)>(.*)<\/'. $tag .'>/iusU', $text, $found)){
	            $text = str_replace($found[0],$found[2],$text);
	        }
	    }
	
	    return preg_replace('/(<('.join('|',$tags).')(|\W.*)\/>)/iusU', '', $text);
	}
	
	public function getElementScripts(){
		$retArray = array();
		$this->db->setQuery("Select id, package, name, title, description, type From #__facileforms_scripts Where published = 1 And type = 'Element Validation'");
		$retArray['validation'] = $this->db->loadObjectList();
		$this->db->setQuery("Select id, package, name, title, description, type From #__facileforms_scripts Where published = 1 And type = 'Element Action'");
		$retArray['action'] = $this->db->loadObjectList();
		$this->db->setQuery("Select id, package, name, title, description, type From #__facileforms_scripts Where published = 1 And type = 'Element Init'");
		$retArray['init'] = $this->db->loadObjectList();;
		return $retArray;
	}
	
	public function getUserBrowser()
	{
		$u_agent = $_SERVER['HTTP_USER_AGENT'];
		$ub = '';
		if(preg_match('/MSIE/i',$u_agent))
		{
			$ub = "ie";
		}
		elseif(preg_match('/Firefox/i',$u_agent))
		{
			$ub = "firefox";
		}
		elseif(preg_match('/Safari/i',$u_agent) && !preg_match('/Chrome/i',$u_agent))
		{
			$ub = "safari";
		}
		elseif(preg_match('/Chrome/i',$u_agent))
		{
			$ub = "chrome";
		}
		elseif(preg_match('/Flock/i',$u_agent))
		{
			$ub = "flock";
		}
		elseif(preg_match('/Opera/i',$u_agent))
		{
			$ub = "opera";
		}

		return $ub;
	}
}