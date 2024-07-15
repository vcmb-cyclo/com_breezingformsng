<?php
/**
* BreezingForms - A Joomla Forms Application
* @version 1.9
* @package BreezingForms
* @copyright (C) 2008-2020 by Markus Bopp
* @license Released under the terms of the GNU General Public License
**/

/** ensure this file is being included by a parent file */
defined( '_JEXEC' ) or die( 'Direct Access to this location is not allowed.' );

if(!defined('DS')){
    define('DS', DIRECTORY_SEPARATOR);
}

$bfplg = '';

jimport( 'joomla.plugin.plugin' );

if(file_exists(JPATH_SITE . '/administrator/components/com_breezingforms/libraries/crosstec/classes/BFRequest.php')){

    require_once(JPATH_SITE . '/administrator/components/com_breezingforms/libraries/crosstec/classes/BFRequest.php');
}

if(file_exists(JPATH_SITE . '/administrator/components/com_breezingforms/libraries/crosstec/classes/BFFactory.php')){

    require_once(JPATH_SITE . '/administrator/components/com_breezingforms/libraries/crosstec/classes/BFFactory.php');
}

class plgContentBreezingforms extends JPlugin {

    function __construct( &$subject, $params )
    {
        parent::__construct($subject, $params);
    }

    /**
     * Joomla 1.5 compatibility
     */
    function onPrepareContent(&$article, &$params, $limitstart = 0 )
    {
        $this->onContentPrepare('', $article, $params, $limitstart);
    }

    function onContentPrepare($context, &$article, &$params, $limitstart = 0) {

        global $botFacileFormsPublished, $botFacileFormsContentid, $bfplg;

        if($context == 'com_content.category' || !file_exists(JPATH_SITE . '/administrator/components/com_breezingforms/libraries/crosstec/classes/BFRequest.php')){

            return;
        }

        $bfplg = $this;
        
        $contentObj = $article;
        
	if(is_object($contentObj)){

		$botFacileFormsPublished = true;
		$botFacileFormsContentid = isset($contentObj->id) ? $contentObj->id : 0;

		// define the regular expression for the plugin
		// the syntax is: { BreezingForms : formname, page, border, urlparams, suffix }
		$regex =
			"/".                    // delimiter
			"\\{".                  // opening {
			"[\\s]*".               // skip whitespace
			"BreezingForms".          // required tag identifier
			"[\\s]*".               // skip whitespace
			":".                    // colon
			"[\\s]*".               // skip whitespace
			"([A-Za-z0-9_\\-]+)".   // required form name
			"(".                        // start of page/border/params scan
				"[\\s]*".               // skip whitespace
				",".                    // require a comma
				"[\\s]*".               // skip whitespace
				"(\\d*)".               // find integer pagenumber
				"(".                        // start border scan
					"[\\s]*".               // skip whitespace
					",".                    // require a comma
					"[\\s]*".              // skip whitespace
					"(0|1)?".               // find border as 0 or 1
					"(".                        // start params scan
						"[\\s]*".               // skip whitespace
						",".                    // require a comma
						"[\\s]*".               // skip whitespace
						"([^\\},]*)".           // find any chars but comma and }
						"(".                        // start suffix scan
							"[\\s]*".               // skip whitespace
							",".                    // require a comma
							"[\\s]*".               // skip whitespace
							"([^\\s\\},]*)".        // find any chars but whitespace, comma and }


							"(".                        // start editable scan
								"[\\s]*".               // skip whitespace
								",".                    // require a comma
								"[\\s]*".               // skip whitespace
								"(0|1)?".        		// find editable as 0 or 1

								"(".                        // start editable override scan
									"[\\s]*".               // skip whitespace
									",".                    // require a comma
									"[\\s]*".               // skip whitespace
									"(0|1)?".        		// find editable as 0 or 1
								")?".                       // 0 or 1 times editable override


							")?".                       // 0 or 1 times editable


						")?".                       // 0 or 1 times suffix
					")?".                       // 0 or 1 times params
				")?".                       // 0 or 1 times a border
			")?".                       // 0 or 1 times page/border/params
			"[\\s]*".               // skip whitespace
			"\\}".                  // closing }
			"/s";                    // delimiter

		// perform the replacement

		$contentObj->text = preg_replace_callback( $regex, 'botBreezingForms_replacer', $contentObj->text );

		// clean up globals
		unset( $GLOBALS['botFacileFormsPublished'] );
	}

	return true;
    }
}

function botBreezingForms_replacer( &$matches )
{
	global $database, $ff_mossite, $ff_version, $ff_config, $ff_target, $ff_request, $ff_mospath, $ff_compath;
	global $botFacileFormsPublished, $botFacileFormsContentid;
	global $params, $bfplg;
	
        $isContentBuilder = BFRequest::getCmd('option','') == 'com_contentbuilder' ? true : false;
        $cbFormId = 0;
        $cbRecordId = 0;
        $cbReturn = '';
        if($isContentBuilder){
            $cbFormId = BFRequest::getInt('id', 0);
            $cbRecordId = BFRequest::getInt('record_id', 0);
            $cbReturn = urlencode(BFRequest::getVar('return', ''));
        }
        
	// return nothing in case the mambot is disabled
	if (!$botFacileFormsPublished) return '';

	// get paths
	$ff_mospath = str_replace('\\','/',dirname(dirname(dirname(__FILE__))));
	$ff_compath = JPATH_SITE.'/components/com_breezingforms';

	// load config
	require_once($ff_compath.'/facileforms.class.php');
	$ff_config = new facileFormsConf();
	initFacileForms();

	// get the parameters from the regex scan
	$formid   = '';
	$formname = '';
	$page     = 1;
	$border   = 1;
	$suffix   = '';
	$editable = 0;
	$editable_override = 0;
	
	$ff_request = array();
	$cnt = count($matches);
	if ($cnt > 1) {
		$formname = $matches[1];
		if ($cnt > 3) {
			if ($matches[3]!='') $page = intval($matches[3]);
			if ($cnt > 5) {
				if ($matches[5]!='') $border = intval($matches[5]);
				if ($cnt > 7) {
					addRequestParams($matches[7]);
					if ($cnt > 9)  $suffix   = $matches[9];
					if ($cnt > 11) {
						$editable = $matches[11];
						//JFactory::getSession()->set('ff_editable', intval($editable));
						$_SESSION['ff_editablePlg'.$botFacileFormsContentid.$formname] = intval($editable);
					}
					if ($cnt > 13) {
						$editable_override = $matches[13];
						//JFactory::getSession()->set('ff_editable_override', intval($editable_override));
						$_SESSION['ff_editable_overridePlg'.$botFacileFormsContentid.$formname] = intval($editable_override);
					}
				} // if
			} // if
		} // if
	} // if

	if (!$ff_target) $ff_target = 1; else $ff_target++;
	$target = BFRequest::getVar( 'ff_target', '');
	$myTarget = $target==$ff_target || ($target=='' && $ff_target==1);

	if ($myTarget) {
		// yes, all ff_ parameters are meant for me
		$formid   = BFRequest::getInt( 'ff_form',  $formid);
		$formname = BFRequest::getVar( 'ff_name',  $formname);
		$page     = BFRequest::getInt( 'ff_page',  $page);
		$border   = BFRequest::getInt( 'ff_border',$border);
		reset($_REQUEST);
		foreach($_REQUEST as $prop => $val) {
			if (!is_array($val) && substr($prop,0,9)=='ff_param_')
				$ff_request[$prop] = $val;
		}
		// while (list($prop, $val) = each($_REQUEST))
		// 	if (!is_array($val) && substr($prop,0,9)=='ff_param_')
		// 		$ff_request[$prop] = $val;
	} // if

	// load form
	if (intval($formid) > 0) {
		$database->setQuery(
			"select * from #__facileforms_forms ".
			"where id=".intval($formid)." and published=1 and runmode<2"
		);
		$forms = $database->loadObjectList();
		if (count($forms) < 1) return '[Form '.htmlentities($formid, ENT_QUOTES, 'UTF-8').' not found!]';
	} else {
		$database->setQuery(
			"select * from #__facileforms_forms ".
			"where name=".$database->Quote($formname)." and published=1 and runmode<2 ".
			"order by ordering, id"
		);

		$forms = $database->loadObjectList();
		
		if (count($forms) < 1) return '[Form '.htmlentities($formname, ENT_QUOTES, 'UTF-8').' not found!]';
	} // if
	$form = $forms[0];

    // get Itemid
    $iid = BFRequest::getInt( 'Itemid', 0);
    if (!is_numeric($iid)) $iid = 1;

	// prepare width and height parameters
	$framewidth = 'width="'.$form->width;
	if ($form->widthmode) $framewidth .= '%" '; else $framewidth .= '" ';
	$frameheight = '';
	if (!$form->heightmode) $frameheight = 'height="'.$form->height.'" ';

	// build the url
	$url = $ff_mossite.'/index.php'
				.'?option=com_breezingforms'
                                .'&amp;tmpl=component'
				.'&amp;Itemid='.htmlentities($iid, ENT_QUOTES, 'UTF-8')
				.'&amp;ff_contentid='.htmlentities($botFacileFormsContentid, ENT_QUOTES, 'UTF-8')
				.'&amp;ff_form='.htmlentities($form->id, ENT_QUOTES, 'UTF-8')
				.'&amp;ff_applic=plg_facileforms'
				.'&amp;format=html'
				.'&amp;ff_frame=1'.($isContentBuilder ? '&amp;return='.htmlentities($cbReturn, ENT_QUOTES, 'UTF-8').'&cb_form_id='.htmlentities($cbFormId, ENT_QUOTES, 'UTF-8').'&amp;cb_record_id='.htmlentities($cbRecordId, ENT_QUOTES, 'UTF-8') : '');
	if ($page>1) $url .= '&amp;ff_page='.htmlentities($page, ENT_QUOTES, 'UTF-8');
	if ($suffix!='') $url .= '&amp;ff_suffix='.htmlentities(urlencode($suffix), ENT_QUOTES, 'UTF-8');
	reset($ff_request);
	// while (list($prop, $val) = each($ff_request))
    foreach($ff_request As $prop => $val) $url .= '&amp;'.htmlentities($prop, ENT_QUOTES, 'UTF-8').'='.htmlentities(urlencode($val), ENT_QUOTES, 'UTF-8');
	$params =   'id="ff_frame'.$form->id.'" '.
				'src="'.$url.'" '.
				$framewidth.
				$frameheight.
				'frameborder="'.htmlentities($border, ENT_QUOTES, 'UTF-8').'" '.
				'allowtransparency="true" '.
				'scrolling="no" ';

	$plugin = JPluginHelper::getPlugin('content', 'breezingforms');

	// Load plugin params info
        jimport('joomla.version');
        $version = new JVersion();
        
        if(version_compare($version->getShortVersion(), '3.0', '<')){
            jimport( 'joomla.html.parameter' );
            $pluginParams = new JParameter($plugin->params);
        }else{
            $pluginParams = $bfplg->params;
	}
        
        $mode = $pluginParams->def('load_in_iframe', 1);
        
	if( ( $isContentBuilder || $mode == '0' ) && BFRequest::getVar('option') != 'com_tags'){
	
		// NON-IFRAME		
		$tmpParams = $params;
	
                if($isContentBuilder){
                  BFRequest::setVar('cb_form_id', $cbFormId);
                  BFRequest::setVar('cb_record_id', $cbRecordId);
                }
                
		BFRequest::setVar('Itemid',$iid);
		BFRequest::setVar('option',$isContentBuilder ? 'com_contentbuilder' : 'com_breezingforms');
		BFRequest::setVar('ff_contentid',$botFacileFormsContentid);
		BFRequest::setVar('ff_form',$form->id);
		BFRequest::setVar('ff_frame',0);
		BFRequest::setVar('ff_page',$page);
		BFRequest::setVar('ff_suffix',$suffix);
                reset($ff_request);
		//while (list($prop, $val) = each($ff_request))
        foreach($ff_request As $prop => $val) BFRequest::setVar($prop,$val);
		BFRequest::setVar( 'ff_target', 2);
		
		$ff_modpath = str_replace('\\','/',dirname(__FILE__ ));
		$ff_compath = JPATH_SITE . '/components/com_breezingforms';
		$option = BFRequest::getVar('option','');
		$ff_applic = 'plg_facileforms';
                BFRequest::setVar('ff_applic', $ff_applic);
		$ff_runningAsModule = true;
		
		$plg_editable = $editable;
		$plg_editable_override = $editable_override;
		
		ob_start();
		require($ff_compath.'/breezingforms.php');
		$ff_contents = ob_get_contents();
		ob_end_clean();
                
                BFRequest::setVar('ff_applic', '');
		
		$params = $tmpParams;
	
		return $ff_contents;
		// NON_IFRAME-END
		
	} else {
                if($form->autoheight == 1 || BFRequest::getVar('option') == 'com_tags'){
                    JFactory::getDocument()->addScript(JURI::root(true) . '/components/com_breezingforms/libraries/jquery/jq.min.js');
                    JFactory::getDocument()->addScript(JURI::root(true).'/components/com_breezingforms/libraries/jquery/jq.iframeautoheight.js');
                    JFactory::getDocument()->addScriptDeclaration("<!--
                    JQuery(document).ready(function() {
                        //JQuery(\".breezingforms_iframe_plg\").css(\"width\",\"100%\");
                        JQuery(\".breezingforms_iframe_plg\").iframeAutoHeight({heightOffset: 15, debug: false, diagnostics: false});
                    });
                    //-->");
                }
                
                return
                
	        // DO NOT REMOVE OR CHANGE OR OTHERWISE MAKE INVISIBLE THE FOLLOWING COPYRIGHT MESSAGE
	        // FAILURE TO COMPLY IS A DIRECT VIOLATION OF THE GNU GENERAL PUBLIC LICENSE
	        // http://www.gnu.org/copyleft/gpl.html
	        "\n<!-- BreezingForms ".$ff_version." Copyright(c) 2008-2013 by Markus Bopp. All rights reserved. Original (FacileForms) Code until Version 1.4.7: Peter Koch -->\n".
	        // END OF COPYRIGHT
	        "<iframe class='breezingforms_iframe_plg' ".$params." sandbox=\"allow-same-origin allow-scripts allow-forms allow-pointer-lock allow-popups allow-top-navigation\">\n".
	        "<p>Sorry, your browser cannot display frames!</p>\n".
	        "</iframe>\n";
	}
} // botFacileForms_replacer