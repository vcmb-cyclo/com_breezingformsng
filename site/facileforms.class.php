<?php
/**
 * BreezingForms - A Joomla Forms Application
 * @version 5.0.0
 * @package BreezingForms
 * @copyright   Copyright (C) 2024 by XDA+GIL | 2008-2020 by Markus Bopp
 * @license Released under the terms of the GNU General Public License
 **/
defined('_JEXEC') or die('Direct Access to this location is not allowed.');

use Joomla\CMS\Uri\Uri;
use Joomla\Filesystem\File;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

global $ff_version, $ff_resnames, $ff_request, $ff_target;

$ff_version = '5.0.0 Beta (build 1)';
$ff_target = 0;

$ff_resnames = array(
	'ff_name',
	'ff_form',
	'ff_border',
	'ff_align',
	'ff_runmode',
	'ff_page',
	'ff_task',
	'ff_target',
	'ff_frame',
	'ff_suffix',
	'ff_top'
);

DEFINE('_FF_RUNMODE_FRONTEND', 0);
DEFINE('_FF_RUNMODE_BACKEND', 1);
DEFINE('_FF_RUNMODE_PREVIEW', 2);

function nl()
{
	return "\r\n";
} // nl

function nlc()
{
	global $ff_config;
	if (!$ff_config->compress)
		return "\r\n";
} // nlc

function adjustNewlines($text)
{
	$text = str_replace("\r\n", "\n", $text); // unix mode
	return str_replace("\n", nl(), $text); // ff mode
} // adjustNewlines

function indent($level)
{
	$ind = '';
	for ($i = 0; $i < $level; $i++)
		$ind .= "\t";
	return $ind;
} // indent

function indentc($level)
{
	global $ff_config;
	$ind = '';
	if (!$ff_config->compress)
		for ($i = 0; $i < $level; $i++)
			$ind .= "\t";
	return $ind;
} // indentc

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

function impstring($text)
{
	return stripcslashes($text);
} // impstring

function addRequestParams($params)
{
	global $ff_request;

	$is_quoted = false;

	$px = explode('&amp;', $params);
	if (count($px) == 0) {
		$px = explode('&', $params);
	}
	if (count($px))
		foreach ($px as $p) {
			$x = explode('=', $p);
			$c = count($x);
			$n = '';
			if ($c > 0)
				$n = trim($x[0]);
			$v = '';
			if ($c > 1)
				$v = trim($x[1]);
			if ($n != '')
				$ff_request[$n] = $v;
		} // foreach
} // addRequestParams

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

function saveOtherParam($name)
{
	global $ff_otherparams;
	if (BFRequest::getVar($name, null) != null && !is_array(BFRequest::getVar($name, null))) {
		$value = BFRequest::getVar($name);
		$ff_otherparams[$name] = $value;
		return $value;
	} // if
	return NULL;
} // saveOtherParam

function initFacileForms()
{
	global $ff_mossite, $ff_comsite, $ff_config, $ff_otherparams, $mosConfig_live_site;
	$mainframe = Factory::getApplication();


	if (!isset($ff_mossite)) {
		if ($ff_config->livesite == 0) {
			//$ff_mossite = str_replace('\\','/', Uri::root());
			$ff_mossite = Uri::root();
		} else {
			$s = empty($_SERVER["HTTPS"]) ? '' : (($_SERVER["HTTPS"] == "on") ? "s" : "");
			$s = !empty($_SERVER['HTTP_X_FORWARDED_PROTO']) ? 's' : $s;

			$protocol = strtolower($_SERVER["SERVER_PROTOCOL"]);
			$protocol = substr($protocol, 0, strpos($protocol, '/')) . $s;
			$port = ":" . $_SERVER["SERVER_PORT"];
			if (($protocol == 'http' && $port == ':80') || ($protocol == 'https' && $port == ':443'))
				$port = '';
			$path = dirname($_SERVER['PHP_SELF']);
			if (basename($path) == 'administrator')
				$path = dirname($path);
			$domain = $_SERVER['HTTP_HOST'];
			$p = strrpos($domain, ':');
			if ($p)
				$domain = substr($domain, 0, $p);
			$ff_mossite = str_replace('\\', '/', $protocol . "://" . $domain . $port . $path);
		} // if
		$len = strlen($ff_mossite);
		if ($len > 0 && $ff_mossite[$len - 1] == '/')
			$ff_mossite = substr($ff_mossite, 0, $len - 1);
	} // if

	if (!isset($ff_comsite))
		$ff_comsite = $ff_mossite . '/components/com_breezingforms';

	if (!isset($ff_otherparams)) {
		$ff_otherparams = array();

		switch (saveOtherParam('option')) {
			case 'com_content':
				saveOtherParam('Itemid');
				saveOtherParam('task');
				saveOtherParam('sectionid');
				saveOtherParam('id');
				break;
			case 'com_contact':
			case 'com_contacts':
				saveOtherParam('id');
				saveOtherParam('Itemid');
				saveOtherParam('task');
				saveOtherParam('catid');
				saveOtherParam('view');
				saveOtherParam('contact_id');
				break;
			case 'com_weblinks':
				saveOtherParam('Itemid');
				saveOtherParam('catid');
				break;
			default:
				saveOtherParam('Itemid');
		} // switch
	} // if
} // initFacileForms

class facileFormsConf
{
	public $stylesheet = 1;        	// backend frame preview no/yes
	public $wysiwyg = 0;        	// use wysiwyg editor for static text
	public $areasmall = 4;        	// small textarea lines
	public $areamedium = 12;       	// medium textarea lines
	public $arealarge = 20;       	// large textarea lines
	public $limitdesc = 100;      	// listview description limit
	public $emailadr = 'Enter your email address here';                  // default email notify address
	public $images = '{mossite}/components/com_breezingforms/images';    // {ff_images} path
	public $uploads = '{mospath}/media/breezingforms/uploads';   // {ff_uploads} path
	public $movepixels = 10;       	// pixelmover stepping
	public $compress = 1;        	// compress output
	public $livesite = 0;        	// use $mosConfig_live_site as site url
	public $getprovider = 0;        // get provider with gethostbyaddr
	public $gridshow = 1;        	// show grid in preview
	public $gridsize = 10;       	// grid size
	public $gridcolor1 = '#e0e0ff';	// grid color even lines
	public $gridcolor2 = '#ffe0e0';	// grid color odd lines

	// record manager settings
	public $viewed = 0;        		// default viewed filter setting
	public $exported = 0;        	// default exported filter setting
	public $archived = 0;        	// default archived filter setting
	public $formname = '';       	// default formname filter setting

	public $menupkg = '';       	// last selected menu package
	public $formpkg = '';       	// last selected form package
	public $scriptpkg = '';       	// last selected script package
	public $piecepkg = '';       	// last selected piece package

	public $csvdelimiter = ";";
	public $csvquote = '"';
	public $cellnewline = 1;

	public $enable_classic = 0;

	public $disable_ip = 0;

	function __construct()
	{
		$this->load();
	} // constructor

	function load()
	{
		global $ff_compath, $database;

		$database = Factory::getContainer()->get(DatabaseInterface::class);

		$configfile = JPATH_SITE . '/media/breezingforms/facileforms.config.php';
		if (file_exists($configfile)) {
			include ($configfile);
		} else {
			$arr = get_object_vars($this);
			$ids = array();

			foreach ($arr as $prop => $val)
				$ids[] = "'" . $prop . "'";
			Joomla\Utilities\ArrayHelper::toInteger($ids);

			$rows = array();

			try {
				$database->setQuery(
					"select id, value from #__facileforms_config " .
					"where id in (" . implode(',', $ids) . ")"
				);
				$rows = $database->loadObjectList();
			} catch (Exception $e) {
			}

			if (count($rows))
				foreach ($rows as $row) {
					$prop = $row->id;
					$this->$prop = stripcslashes($row->value);
				} // foreach
		} // if
	} // load

	function store()
	{
		global $ff_compath, $database, $mosConfig_fileperms;

		$database = Factory::getContainer()->get(DatabaseInterface::class);
		$configfile = JPATH_SITE . '/media/breezingforms/facileforms.config.php';

		// prepare output
		$config = "<?php\n";
		$arr = get_object_vars($this);

		foreach ($arr as $prop => $val) {
			$config .= "\$this->" . $prop . " = \"" . addslashes($val) . "\";\n";

			$database->setQuery(
				"update #__facileforms_config " .
				"set value=" . $database->Quote($val) . " " .
				"where id = " . $database->Quote($prop) . ""
			);

			try {
				$database->execute();
			} catch (RuntimeException $e) {
				echo "<br/>" . $e->getMessage();
				exit;
			}

			$database->setQuery(
				"select count(*) from #__facileforms_config " .
				"where id = " . $database->Quote($prop)
			);
			$saved = $database->loadResult();
			if (!$saved) {
				$database->setQuery(
					"insert into #__facileforms_config (id, value) " .
					"values (" . $database->Quote($prop) . ", " . $database->Quote($val) . ")"
				);
				try {
					$database->execute();
				} catch (RuntimeException $e) {
					echo "<br/>" . $e->getMessage();
					exit;
				}

			} // if
		} // while
		$config .= "?>\n";

		// save to file

		if (!File::write($configfile, $config)) {
			die('Could not write config file, please check the permissions! <a href="javascript:history.go(-1)">back</a>');
		}

		/**
			$existed = file_exists($configfile);
			if ($fp = fopen($configfile, "w")) {
				fputs($fp, $config, strlen($config));
				fclose($fp);
				if (!$existed) {
					$filemode = NULL;
					if (isset($mosConfig_fileperms)) {
						if ($mosConfig_fileperms!='')
							$filemode = octdec($mosConfig_fileperms);
					} else
						$filemode = 0644;
					if (isset($filemode)) @chmod($configfile, $filemode);
				} // if
			} // if
					*/
	} // store

	function bindRequest($request)
	{
		$arr = get_object_vars($this);
		foreach ($arr as $prop => $val)
			$this->$prop = @BFRequest::getVar($prop, $val);
	} // bindRequest
} // class facileFormsConf

class facileFormsMenus extends Table
{
	public $id = null;     // identifier
	public $package = null;     // package name
	public $parent = 0;        // parent id
	public $ordering = 0;        // ordering
	public $published = 1;        // is published
	public $img = '';       // menu icon image
	public $title = '';       // displayed menu name
	public $name = '';       // form name (identifier)
	public $page = 1;        // starting page
	public $frame = 0;        // run in iframe
	public $border = 0;        // show a border
	public $params = null;     // additional parameters

	function __construct(&$db)
	{
		parent::__construct('#__facileforms_compmenus', 'id', Factory::getContainer()->get(DatabaseInterface::class));
	} // constructor

	public function load($id = null, $reset = true)
	{
		global $database;
		$database = Factory::getContainer()->get(DatabaseInterface::class);
		$database->setQuery("select * from #__facileforms_compmenus where id = $id");
		$rows = $database->loadObjectList();
		if ($rows) {
			$row = $rows[0];
			$arr = get_object_vars($this);
			foreach ($arr as $prop => $val)
				if ($prop[0] != '_')
					$this->$prop = $row->$prop;
			return true;
		} // if
		return false;
	} // load

} // class facileFormsMenus

class facileFormsForms extends Table
{
	public $id = null;     // identifier
	public $package = null;     // package name
	public $ordering = null;     // ordering
	public $published = null;     // no/yes
	public $runmode = null;     // 0-any/1-foreground/2-background
	public $name = null;     // form name (identifier)
	public $title = null;     // fancy name
	public $description = null;     // form description
	public $class1 = null;     // css class for <div>
	public $class2 = null;     // css class for <form>
	public $width = null;     // form width in px
	public $widthmode = null;     // 0=px 1=%
	public $height = null;     // form height in px
	public $heightmode = null;     // 0=px 1=auto
	public $pages = null;     // # of pages
	public $emailntf = null;     // none/default/custom
	public $mb_emailntf = null;     // none/default/custom
	public $emaillog = null;     // header only/nonempty values/all
	public $mb_emaillog = null;     // header only/nonempty values/all
	public $emailxml = null;     // xml attachment no/nonempty values/all
	public $mb_emailxml = null;     // xml attachment no/nonempty values/all
	public $emailadr = null;     // custom email address
	public $dblog = null;     // no/nonempty values/all
	public $script1cond = null;     // init: none/library/custom
	public $script1id = null;     // library function id
	public $script1code = null;     // custom code ff_{form}_init()
	public $script2cond = null;     // submitted: none/library/custom
	public $script2id = null;     // library function id
	public $script2code = null;     // custom code ff_{form}_submitted(status='success','failed')
	public $piece1cond = null;     // Before form: none/library/custom
	public $piece1id = null;     // library function id
	public $piece1code = null;     // custom code
	public $piece2cond = null;     // After form: none/library/custom
	public $piece2id = null;     // library function id
	public $piece2code = null;     // custom code
	public $piece3cond = null;     // Begin submit: none/library/custom
	public $piece3id = null;     // library function id
	public $piece3code = null;     // custom code
	public $piece4cond = null;     // End submit: none/library/custom
	public $piece4id = null;     // library function id
	public $piece4code = null;     // custom code
	public $prevmode = null;     // preview mode 0-none 1-below 2-overlay
	public $prevwidth = null;     // preview width px in case of widthmode=1
	public $template_code_processed = null; // the processed templated easymode form html code
	public $template_code = null;
	public $template_areas = null;
	public $custom_mail_subject = null;
	public $mb_custom_mail_subject = null;
	public $alt_mailfrom = null;
	public $mb_alt_mailfrom = null;
	public $alt_fromname = null;
	public $mb_alt_fromname = null;
	public $mailchimp_email_field = null;
	public $mailchimp_api_key = null;
	public $mailchimp_list_id = null;
	public $mailchimp_double_optin = null;
	public $mailchimp_mergevars = null;
	public $mailchimp_checkbox_field = null;
	public $mailchimp_text_html_mobile_field = null;
	public $mailchimp_send_errors = null;
	public $mailchimp_update_existing = null;
	public $mailchimp_replace_interests = null;
	public $mailchimp_send_welcome = null;
	public $mailchimp_default_type = null;
	public $mailchimp_unsubscribe_field = null;
	public $mailchimp_send_notify = null;
	public $mailchimp_send_goodbye = null;
	public $mailchimp_delete_member = null;
	public $salesforce_token = null;
	public $salesforce_username = null;
	public $salesforce_password = null;
	public $salesforce_type = null;
	public $salesforce_fields = null;
	public $salesforce_enabled = null;
	public $email_type = null;
	public $mb_email_type = null;
	public $email_custom_template = null;
	public $mb_email_custom_template = null;
	public $email_custom_html = null;
	public $mb_email_custom_html = null;
	public $dropbox_email = '';
	public $dropbox_password = '';
	public $dropbox_folder = '';
	public $dropbox_submission_enabled = 0;
	public $dropbox_submission_types = 'pdf';
	public $double_opt = '';
	public $opt_mail = '';

	function __construct(&$db)
	{
		parent::__construct('#__facileforms_forms', 'id', Factory::getContainer()->get(DatabaseInterface::class));
	} // constructor

	public function load($id = null, $reset = true)
	{
		global $database;

		$database->setQuery("select * from #__facileforms_forms where id = $id");
		$rows = $database->loadObjectList();

		if ($rows) {
			$row = $rows[0];
			$arr = get_object_vars($this);
			foreach ($arr as $prop => $val) {
				if ($prop[0] != '_') {
					@$this->$prop = $row->$prop;
				}
			}
			// Deprecated in PHP 7.2 version so code above is used

			// while (list($prop, $val) = each($arr))
			// 	if ($prop[0] != '_'){
			// 		@$this->$prop = $row->$prop;
			// 	}
			return true;
		} // if
		return false;
	} // load

} // class facileFormsForms

class facileFormsElements extends Table
{
	public $id = null;     // general parameters
	public $form = null;     // form id
	public $page = null;     // page number
	public $ordering = null;     // ordering index
	public $published = null;     // publish status
	public $name = null;     // identifier
	public $title = null;     // fancy name
	public $type = null;     // element type
/*
-----------------------------------------Element Parameter Cross Reference-------------------------------------------
Element             logging posx posy width height flag1    flag2    data1   data2     data3  script1 script2 script3
---------------------------------------------------------------------------------------------------------------------
Static Text/HTML    -       px%  px%  px%   px%    -        -        value   -         -      -       -       -
Rectangle           -       px%  px%  px%   px%    -        -        border  bckg.col. -      -       -       -
Image               -       px%  px%  px%   px%    -        -        img.url alt.text  -      -       -       -
Tooltip             -       px%  px%  -     -      type     -        img.url text      -      -       -       -
Regular Button      -       px%  px%  -     -      -        disabled -       caption   -      -       action  -
Graphic Button      -       px%  px%  -     -      capt.pos disabled img.url caption   -      -       action  -
Icon                -       px%  px%  -     -      capt.pos border   img.url caption   img.f2 -       action  -
Checkbox            yes     px%  px%  -     -      checked  disabled value   label     -      init    action  valid.
Radio Button        yes     px%  px%  -     -      checked  disabled value   label     -      init    action  valid.
Select List         yes     px%  px%  px    px     multiple disabled size    options   -      init    action  valid.
Query List          yes     px%  px%  px%   m.rows dsp.hdr  dsp.ckbx setting query     cols   -       -       -
Text                yes     px%  px%  szpx  maxlen password dis/rdo  value   -         -      init    action  valid.
Textarea            yes     px%  px%  szpx  colpx  -        dis/rdo  value   -         -      init    action  valid.
File Upload         yes     px%  px%  size  limit  -        disabled dir     types     -      init    action  valid.
Hidden Input        yes     -    -     -     -     -        -        value   -         -      init    -       valid.
---------------------------------------------------------------------------------------------------------------------

Query List Settings: border / cellspacing / cellpadding / <tr(h)>class / <tr(1)>class / <tr(2)>class
*/
	public $class1 = null;     // css class for <div>
	public $class2 = null;     // css class for <element>

	public $logging = null;     // element is logged in email/database no/yes

	public $posx = null;     // horizontal position in px or %
	public $posxmode = null;     // 0-px 1-%
	public $posy = null;     // vertical position in px or %
	public $posymode = null;     // 0-px 1-%
	public $width = null;     // width in % or px
	public $widthmode = null;     // 0-px 1-%
	public $height = null;     // height in px
	public $heightmode = null;     // 0-fixed px 1-auto 2-automax

	public $flag1 = null;     // element specific data, see xref
	public $flag2 = null;
	public $data1 = null;
	public $data2 = null;
	public $data3 = null;

	public $script1cond = null;     // init script
	public $script1flag1 = null;     // condition 1 = on form entry no/yes
	public $script1flag2 = null;     // condition 2 = on page entry
	public $script1id = null;     // script id
	public $script1code = null;     // custom code

	public $script2cond = null;     // action script
	public $script2flag1 = null;     // action 1 = Click
	public $script2flag2 = null;     // action 2 = Blur
	public $script2flag3 = null;     // action 3 = Change
	public $script2flag4 = null;     // action 4 = Focus
	public $script2flag5 = null;     // action 5 = Select
	public $script2id = null;     // script id
	public $script2code = null;     // custom code

	public $script3cond = null;     // validation script
	public $script3id = null;     // script id
	public $script3msg = null;     // message
	public $script3code = null;     // custom code

	public $mailback = null;
	public $mailbackfile = null;

	function __construct(&$db)
	{
		parent::__construct('#__facileforms_elements', 'id', Factory::getContainer()->get(DatabaseInterface::class));
	} // constructor

	public function load($id = null, $reset = null)
	{
		global $database;

		$database->setQuery("select * from #__facileforms_elements where id = $id");
		$rows = $database->loadObjectList();
		if ($rows) {
			$row = $rows[0];
			$arr = get_object_vars($this);
			foreach ($arr as $prop => $val)
				if ($prop[0] != '_')
					@$this->$prop = $row->$prop;
			return true;
		} // if
		return false;
	} // load

} // class facileFormsElements

class facileFormsScripts extends Table
{
	public $id = null;     		// identifier
	public $published = null;   // is published
	public $package = null;     // package name
	public $name = null;     	// function name
	public $title = null;     	// fancy name
	public $description = null; // description
	public $type = null;     	// type name
	public $code = null;     	// the code

	function __construct(&$db)
	{
		parent::__construct('#__facileforms_scripts', 'id', Factory::getContainer()->get(DatabaseInterface::class));
	} // constructor

	public function load($id = null, $reset = true)
	{
		global $database;

		$database->setQuery("select * from #__facileforms_scripts where id = $id");
		$rows = $database->loadObjectList();
		if ($rows) {
			$row = $rows[0];
			$arr = get_object_vars($this);
			foreach ($arr as $prop => $val) {
				if ($prop[0] != '_') {
					@$this->$prop = $row->$prop;
				}
			}
			// Deprecated in PHP 7.2 version so code above is used

			// while (list($prop, $val) = each($arr))
			// 	if ($prop[0] != '_')
			// 		$this->$prop = $row->$prop;
			return true;
		} // if
		return false;
	} // load

} // class facileFormsScripts

class facileFormsPieces extends Table
{
	public $id = null;     			// identifier
	public $published = null;   	// is published
	public $package = null;     	// package name
	public $name = null;     		// function name
	public $title = null;     		// fancy name
	public $description = null; 	// description
	public $type = null;     		// type name
	public $code = null;     		// the code

	function __construct(&$db)
	{
		parent::__construct('#__facileforms_pieces', 'id', Factory::getContainer()->get(DatabaseInterface::class));
	} // constructor

	public function load($id = null, $reset = true)
	{
		global $database;

		$database->setQuery("select * from #__facileforms_pieces where id = $id");
		$rows = $database->loadObjectList();
		if ($rows) {
			$row = $rows[0];
			$arr = get_object_vars($this);
			foreach ($arr as $prop => $val) {
				if ($prop[0] != '_') {
					@$this->$prop = $row->$prop;
				}
			}
			// Deprecated in PHP 7.2 version so code above is used

			// while (list($prop, $val) = each($arr))
			// 	if ($prop[0] != '_')
			// 		$this->$prop = $row->$prop;
			return true;
		} // if
		return false;
	} // load

} // class facileFormsPieces

class facileFormsRecords extends Table
{
	public $id = null;     		// identifier
	public $submitted = null;   // date and time
	public $form = null;     	// form id
	public $title = null;     	// form title
	public $name = null;     	// form name
	public $ip = null;     		// submitters ip
	public $browser = null;     // browser
	public $opsys = null;     	// operating system
	public $provider = null;    // provider
	public $viewed = null;     	// view status
	public $exported = null;    // export status
	public $archived = null;    // archive status
	public $paypal_tx_id = null;
	public $paypal_payment_date = null;
	public $paypal_testaccount = null;
	public $paypal_download_tries = null;

	function __construct(&$db)
	{
		parent::__construct('#__facileforms_records', 'id', Factory::getContainer()->get(DatabaseInterface::class));
	} // constructor

	public function load($id = null, $reset = true)
	{
		global $database;

		$database->setQuery("select * from #__facileforms_records where id = $id");
		$rows = $database->loadObjectList();
		if ($rows) {
			$row = $rows[0];
			$arr = get_object_vars($this);
			foreach ($arr as $prop => $val)
				if ($prop[0] != '_')
					$this->$prop = $row->$prop;
			return true;
		} // if
		return false;
	} // load

} // class facileFormsRecords

class facileFormsSubrecords extends Table
{
	public $id = null;     	// identifier
	public $record = null;  // record id
	public $element = null; // element id
	public $name = null;    // element name
	public $type = null;    // data type
	public $value = null;   // data value

	function __construct(&$db)
	{
		parent::__construct('#__facileforms_subrecords', 'id', Factory::getContainer()->get(DatabaseInterface::class));
	} // constructor

	public function load($id = null, $reset = true)
	{
		global $database;

		$database->setQuery("select * from #__facileforms_subrecords where id = $id");
		$rows = $database->loadObjectList();
		if ($rows) {
			$row = $rows[0];
			$arr = get_object_vars($this);
			foreach ($arr as $prop => $val)
				if ($prop[0] != '_')
					$this->$prop = $row->$prop;
			return true;
		} // if
		return false;
	} // load

} // class facileFormsSubrecords

class facileFormsQuerycols
{
	public $title = null;    // column title
	public $name = null;     // column name
	public $class1 = null;   // class for th
	public $class2 = null;   // class for td(1)
	public $class3 = null;   // class for td(2)
	public $thspan = null;   // th span
	public $align = null;    // 0-left 1-center 2-right
	public $valign = null;   // 0-top 1-middle 2-bottom 3-baseline
	public $wrap = null;     // 0-nowrap 1-wrap
	public $value = null;    // value field (php allowed)
	public $comp = null;     // complied value: array of array(type, value/code)

	public $width = null;
	public $widthmd = null;
	public $thalign = null;
	public $thvalign = null;
	public $thwrap = null;

	function __construct()
	{
		$this->title = '';
		$this->name = '';
		$this->class1 = '';
		$this->class2 = '';
		$this->class3 = '';
		$this->width = '';
		$this->widthmd = 0;
		$this->thspan = 1;
		$this->thalign = 0;
		$this->thvalign = 0;
		$this->thwrap = 0;
		$this->align = 0;
		$this->valign = 0;
		$this->wrap = 0;
		$this->value = '';
	} // constructor

	function unpack($line)
	{
		$vals = explode('&', $line);
		$cnt = count($vals);
		if ($cnt > 0)
			$this->title = impstring($vals[0]);
		if ($cnt > 1)
			$this->name = impstring($vals[1]);
		if ($cnt > 2)
			$this->class1 = impstring($vals[2]);
		if ($cnt > 3)
			$this->class2 = impstring($vals[3]);
		if ($cnt > 4)
			$this->class3 = impstring($vals[4]);
		if ($cnt > 5)
			$this->width = impstring($vals[5]);
		if ($cnt > 6)
			$this->widthmd = impstring($vals[6]);
		if ($cnt > 7)
			$this->thspan = impstring($vals[7]);
		if ($cnt > 8)
			$this->thalign = impstring($vals[8]);
		if ($cnt > 9)
			$this->thvalign = impstring($vals[9]);
		if ($cnt > 10)
			$this->thwrap = impstring($vals[10]);
		if ($cnt > 11)
			$this->align = impstring($vals[11]);
		if ($cnt > 12)
			$this->valign = impstring($vals[12]);
		if ($cnt > 13)
			$this->wrap = impstring($vals[13]);
		if ($cnt > 14)
			$this->value = impstring($vals[14]);
	} // unpack

	function pack()
	{
		return
			expstring($this->title) . '&' .
			expstring($this->name) . '&' .
			expstring($this->class1) . '&' .
			expstring($this->class2) . '&' .
			expstring($this->class3) . '&' .
			expstring($this->width) . '&' .
			expstring($this->widthmd) . '&' .
			expstring($this->thspan) . '&' .
			expstring($this->thalign) . '&' .
			expstring($this->thvalign) . '&' .
			expstring($this->thwrap) . '&' .
			expstring($this->align) . '&' .
			expstring($this->valign) . '&' .
			expstring($this->wrap) . '&' .
			expstring($this->value);
	} // pack

} // class facileFormsQuerycols

?>