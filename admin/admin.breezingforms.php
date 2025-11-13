<?php
/**
 * BreezingForms - A Joomla Forms Application
 * @version     5.0.0
 * @package     BreezingForms
 * @copyright   Copyright (C) 2024 by XDA+GIL | Until 1.9, 2008-2020 by Markus Bopp
 * @license     Released under the terms of the GNU General Public License
 **/

defined('_JEXEC') or die('Direct Access to this location is not allowed.');

use Joomla\Filesystem\Folder;
use Joomla\Filesystem\File;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Log\Log;
//use Joomla\CMS\Cache\CacheControllerFactoryInterface;

require_once (JPATH_SITE . '/administrator/components/com_breezingforms/libraries/crosstec/classes/BFFactory.php');
require_once (JPATH_SITE . '/administrator/components/com_breezingforms/libraries/crosstec/classes/BFRequest.php');

if (BFRequest::getVar('mosmsg', '') != '') {
    Factory::getApplication()->enqueueMessage(BFRequest::getVar('mosmsg', ''));
}

$db = Factory::getContainer()->get(DatabaseInterface::class);

if (!function_exists('bf_b64enc')) {

    function bf_b64enc($str)
    {
        $base = 'base';
        $sixty_four = '64_encode';
        return call_user_func($base . $sixty_four, $str);
    }

}

if (!function_exists('bf_b64dec')) {
    function bf_b64dec($str)
    {
        $base = 'base';
        $sixty_four = '64_decode';
        return call_user_func($base . $sixty_four, $str);
    }
}

require_once (JPATH_SITE . '/administrator/components/com_breezingforms/libraries/crosstec/classes/BFJoomlaConfig.php');

function bf_getTableFields($tables, $typeOnly = true)
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

$option = BFRequest::getCmd('option');
$task = BFRequest::getCmd('task');

if (!Factory::getApplication()->getIdentity()->authorise('core.manage', 'com_breezingforms')) {
    Factory::getApplication()->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
    Factory::getApplication()->redirect('index.php', 403);
    return;
}

// purge ajax save
$sourcePath = JPATH_SITE . '/components/com_breezingforms/exports/';
if (@file_exists($sourcePath) && @is_readable($sourcePath) && @is_dir($sourcePath) && $handle = @opendir($sourcePath)) {
    while (false !== ($file = @readdir($handle))) {
        if ($file != "." && $file != ".." && $file != "index.html") {
            @File::delete($sourcePath . $file);
        }
    }
    @closedir($handle);
}

$sourcePath = JPATH_SITE . '/administrator/components/com_breezingforms/packages/';
if (@file_exists($sourcePath) && @is_readable($sourcePath) && @is_dir($sourcePath) && $handle = @opendir($sourcePath)) {
    while (false !== ($file = @readdir($handle))) {
        if ($file != "." && $file != ".." && $file != "index.html" && $file != "stdlib.english.xml") {
            @File::delete($sourcePath . $file);
        }
    }
    @closedir($handle);
}

if (!is_dir(JPATH_SITE . '/media/breezingforms')) {
    Folder::create(JPATH_SITE . '/media/breezingforms');
}

if (!file_exists(JPATH_SITE . '/media/breezingforms/index.html')) {
    File::copy(
        JPATH_SITE . '/components/com_breezingforms/index.html',
        JPATH_SITE . '/media/breezingforms/index.html'
    );
}

#### MAIL TEMPLATES

if (!is_dir(JPATH_SITE . '/media/breezingforms/mailtpl')) {
    Folder::copy(
        JPATH_ADMINISTRATOR . '/components/com_breezingforms/mailtpl/',
        JPATH_SITE . '/media/breezingforms/mailtpl/'
    );
}

#### PDF TEMPLATES

if (!is_dir(JPATH_SITE . '/media/breezingforms/pdftpl')) {
    Folder::copy(
        JPATH_ADMINISTRATOR . '/components/com_breezingforms/pdftpl/',
        JPATH_SITE . '/media/breezingforms/pdftpl/'
    );
}

Folder::create(JPATH_SITE . '/media/breezingforms/pdftpl/fonts');

#### DOWNLOAD TEMPLATES

if (!is_dir(JPATH_SITE . '/media/breezingforms/downloadtpl')) {
    Folder::copy(
        JPATH_SITE . '/components/com_breezingforms/downloadtpl/',
        JPATH_SITE . '/media/breezingforms/downloadtpl/'
    );
}

if (!file_exists(JPATH_SITE . '/media/breezingforms/downloadtpl/stripe_download.php')) {
    File::copy(
        JPATH_SITE . '/components/com_breezingforms/downloadtpl/stripe_download.php',
        JPATH_SITE . '/media/breezingforms/downloadtpl/stripe_download.php'
    );
}

#### UPLOADS

if (!is_dir(JPATH_SITE . '/media/breezingforms/uploads')) {
    Folder::create(JPATH_SITE . '/media/breezingforms/uploads');
    File::copy(
        JPATH_SITE . '/components/com_breezingforms/uploads/index.html',
        JPATH_SITE . '/media/breezingforms/uploads/index.html'
    );
}

// Default upload folder is now htaccess protected 2016-02-16

if (!file_exists(JPATH_SITE . '/media/breezingforms/uploads/.htaccess')) {
    $def = 'deny from all';
    File::write(JPATH_SITE . '/media/breezingforms/uploads/.htaccess', $def);
}

#### PAYMENT CACHE

if (!is_dir(JPATH_SITE . '/media/breezingforms/payment_cache')) {
    Folder::create(JPATH_SITE . '/media/breezingforms/payment_cache');

}

if (!file_exists(JPATH_SITE . '/media/breezingforms/payment_cache/.htaccess')) {
    $def = 'deny from all';
    File::write(JPATH_SITE . '/media/breezingforms/payment_cache/.htaccess', $def);
}

#### DROPBOX CUSTOM KEYS

if (!is_dir(JPATH_SITE . '/media/breezingforms/dropbox')) {
    Folder::create(JPATH_SITE . '/media/breezingforms/dropbox');
    $def = 'deny from all';
    File::write(JPATH_SITE . '/media/breezingforms/dropbox/.htaccess', $def);
    File::copy(
        JPATH_SITE . '/administrator/components/com_breezingforms/libraries/dropbox/config.json',
        JPATH_SITE . '/media/breezingforms/dropbox/config.json'
    );
}

#### THEMES

if (!is_dir(JPATH_SITE . '/media/breezingforms/themes')) {
    Folder::copy(
        JPATH_SITE . '/components/com_breezingforms/themes/quickmode/',
        JPATH_SITE . '/media/breezingforms/quickmode/'
    );
    Folder::move(
        JPATH_SITE . '/media/breezingforms/quickmode/',
        JPATH_SITE . '/media/breezingforms/themes/'
    );
}

if (!is_dir(JPATH_SITE . '/media/breezingforms/themes-bootstrap4')) {
    Folder::copy(
        JPATH_SITE . '/components/com_breezingforms/themes/quickmode-bootstrap4/',
        JPATH_SITE . '/media/breezingforms/quickmode-bootstrap4/'
    );
    Folder::move(
        JPATH_SITE . '/media/breezingforms/quickmode-bootstrap4/',
        JPATH_SITE . '/media/breezingforms/themes-bootstrap4/'
    );
}

if (!is_dir(JPATH_SITE . '/media/breezingforms/themes-bootstrap5')) {
    Folder::copy(
        JPATH_SITE . '/components/com_breezingforms/themes/quickmode-bootstrap5/',
        JPATH_SITE . '/media/breezingforms/quickmode-bootstrap5/'
    );
    Folder::move(
        JPATH_SITE . '/media/breezingforms/quickmode-bootstrap5/',
        JPATH_SITE . '/media/breezingforms/themes-bootstrap5/'
    );
}

if (!is_dir(JPATH_SITE . '/media/breezingforms/themes/images')) {
    Folder::copy(
        JPATH_SITE . '/components/com_breezingforms/themes/quickmode/images/',
        JPATH_SITE . '/media/breezingforms/themes/images/'
    );
}

if (!is_dir(JPATH_SITE . '/media/breezingforms/themes/images/icons-png/')) {
    Folder::copy(
        JPATH_SITE . '/components/com_breezingforms/themes/quickmode/images/icons-png/',
        JPATH_SITE . '/media/breezingforms/themes/images/icons-png/'
    );
}

if (!file_exists(JPATH_SITE . '/media/breezingforms/themes/jq.mobile.1.4.5.min.css')) {
    File::copy(
        JPATH_SITE . '/components/com_breezingforms/themes/quickmode/jq.mobile.1.4.5.min.css',
        JPATH_SITE . '/media/breezingforms/themes/jq.mobile.1.4.5.min.css'
    );
}

if (!file_exists(JPATH_SITE . '/media/breezingforms/themes/jq.mobile.1.4.5.icons.min.css')) {
    File::copy(
        JPATH_SITE . '/components/com_breezingforms/themes/quickmode/jq.mobile.1.4.5.icons.min.css',
        JPATH_SITE . '/media/breezingforms/themes/jq.mobile.1.4.5.icons.min.css'
    );
}

if (!file_exists(JPATH_SITE . '/media/breezingforms/themes/ajax-loader.gif')) {
    File::copy(
        JPATH_SITE . '/components/com_breezingforms/themes/quickmode/ajax-loader.gif',
        JPATH_SITE . '/media/breezingforms/themes/ajax-loader.gif'
    );
}

#### DELETE SYSTEM THEMES FILES FROM MEDIA FOLDER (the ones in the original themes path are being used)

if (file_exists(JPATH_SITE . '/media/breezingforms/themes/system.css')) {
    File::delete(JPATH_SITE . '/media/breezingforms/themes/system.css');
}

if (file_exists(JPATH_SITE . '/media/breezingforms/themes/system.ie7.css')) {
    File::delete(JPATH_SITE . '/media/breezingforms/themes/system.ie7.css');
}

if (file_exists(JPATH_SITE . '/media/breezingforms/themes/system.ie6.css')) {
    File::delete(JPATH_SITE . '/media/breezingforms/themes/system.ie6.css');
}

if (file_exists(JPATH_SITE . '/media/breezingforms/themes/system.ie.css')) {
    File::delete(JPATH_SITE . '/media/breezingforms/themes/system.ie.css');
}

if (file_exists(JPATH_SITE . '/media/breezingforms/themes-bootstrap/system.css')) {
    File::delete(JPATH_SITE . '/media/breezingforms/themes-bootstrap/system.css');
}

/**
 * 
 * SAME CHECKS FOR CAPTCHA AS IN FRONTEND, SINCE THEY DONT SHARE THE SAME SESSION
 * 
 */
if (BFRequest::getBool('checkCaptcha')) {

    ob_end_clean();

    require_once (JPATH_SITE . '/components/com_breezingforms/images/captcha/securimage.php');
    $securimage = new Securimage();
    if (!$securimage->check(str_replace('?', '', BFRequest::getVar('value', '')))) {
        echo 'capResult=>false';
    } else {
        echo 'capResult=>true';
    }
    exit;

}

$mainframe = Factory::getApplication();

# $cache = Factory::getContainer()->get(CacheControllerFactoryInterface::class)->createCacheController('callback', ['defaultgroup' => 'com_content']);
$cache = Factory::getCache('com_content');
$cache->clean();

// force jquery to be loaded after mootools but before any other js (since J! 3.4)
HTMLHelper::_('jquery.framework');

// purge ajax save
$sourcePath = JPATH_SITE . '/media/breezingforms/ajax_cache/';
if (@file_exists($sourcePath) && @is_readable($sourcePath) && @is_dir($sourcePath) && $handle = @opendir($sourcePath)) {
    while (false !== ($file = @readdir($handle))) {
        if ($file != "." && $file != "..") {
            $parts = explode('_', $file);
            if (count($parts) == 3 && $parts[0] == 'ajaxsave') {
                if (@file_exists($sourcePath . $file) && @is_readable($sourcePath . $file)) {
                    $fileCreationTime = @filectime($sourcePath . $file);
                    $fileAge = time() - $fileCreationTime;
                    if ($fileAge >= 86400) {
                        @File::delete($sourcePath . $file);
                    }
                }
            }
        }
    }
    @closedir($handle);
}

$tables = bf_getTableFields(Factory::getContainer()->get(DatabaseInterface::class)->getTableList());
if (isset($tables[BFJoomlaConfig::get('dbprefix') . 'facileforms_forms'])) {


}

require_once (JPATH_SITE . '/administrator/components/com_breezingforms/libraries/crosstec/classes/BFTabs.php');
require_once (JPATH_SITE . '/administrator/components/com_breezingforms/libraries/crosstec/classes/BFText.php');
require_once (JPATH_SITE . '/administrator/components/com_breezingforms/libraries/crosstec/classes/BFTableElements.php');
require_once (JPATH_SITE . '/administrator/components/com_breezingforms/libraries/crosstec/functions/helpers.php');
require_once (JPATH_SITE . '/administrator/components/com_breezingforms/libraries/crosstec/constants.php');


$_POST = bf_stripslashes_deep($_POST);
$_GET = bf_stripslashes_deep($_GET);
$_REQUEST = bf_stripslashes_deep($_REQUEST);

$db = Factory::getContainer()->get(DatabaseInterface::class);

/*
 * Temporary section end
 */

global $errors, $errmode;
global $ff_mospath, $ff_admpath, $ff_compath, $ff_request;
global $ff_mossite, $ff_admsite, $ff_admicon, $ff_comsite;
global $ff_config, $ff_compatible, $ff_install;

$my = Factory::getApplication()->getIdentity();

if (!isset($ff_compath)) { // joomla!
    // get paths
    $comppath = '/components/com_breezingforms';
    #    $ff_admpath = dirname(__FILE__);
    $ff_admpath = JPATH_ADMINISTRATOR . '/components/com_breezingforms';

    $ff_mospath = str_replace('\\', '/', dirname(dirname(dirname($ff_admpath))));
    $ff_admpath = str_replace('\\', '/', $ff_admpath);
    $ff_compath = $ff_mospath . $comppath;

    require_once ($ff_admpath . '/toolbar.facileforms.php');
} // if

$errors = array();
$errmode = 'die';   // die or log

// compatibility check
if (!$ff_compatible) {
    echo '<h1>' . BFText::_('COM_BREEZINGFORMS_INCOMPATIBLE') . '</h1>';
    exit;
} // if

// load ff parameters
$ff_request = array();
// reset($_REQUEST);
foreach ($_REQUEST as $prop => $val) {
    if (is_scalar($val) && substr($prop, 0, 9) == 'ff_param_')
        $ff_request[$prop] = $val;
}

if ($ff_install) {
    $act = 'installation';
    $task = 'step2';
} // if

$ids = BFRequest::getVar('ids', array());

echo '<div class="row" id="bf-content"><div class="col-md-12">';

switch ($act) {
    case 'installation':
        require_once ($ff_admpath . '/admin/install.php');
        break;
    case 'configuration':
        require_once ($ff_admpath . '/admin/config.php');
        break;
    case 'managemenus':
        require_once ($ff_admpath . '/admin/menu.php');
        break;
    case 'manageforms':
        switch ($task) {
            case 'manageforms':
                require_once ($ff_admpath . '/admin/quickmode.php');
                break;
            case 'quickmode_editor':
                require_once ($ff_admpath . '/admin/quickmode-editor.php');
                break;
            default:
                require_once ($ff_admpath . '/admin/form.php');
                break;
        }
        break;
    case 'editpage':
        require_once ($ff_admpath . '/admin/element.php');
        break;
    case 'managescripts':
        require_once ($ff_admpath . '/admin/script.php');
        break;
    case 'managepieces':
        require_once ($ff_admpath . '/admin/piece.php');
        break;
    case 'run':
        require_once ($ff_admpath . '/admin/run.php');
        break;
    //case 'easymode':
    //    require_once ($ff_admpath . '/admin/easymode.php');
    //    break;
    case 'quickmode':
        require_once ($ff_admpath . '/admin/quickmode.php');
        break;
    case 'quickmode_editor':
        require_once ($ff_admpath . '/admin/quickmode-editor.php');
        break;
    case 'integrate':
        require_once ($ff_admpath . '/admin/integrator.php');
        break;
    case 'recordmanagement':
        require_once ($ff_admpath . '/admin/recordmanagement.php');
        break;
    default:
        require_once ($ff_admpath . '/admin/recordmanagement.php');
        break;
} // switch

echo '</div></div>';

// some general purpose functions for admin

function isInputElement($type)
{
    switch ($type) {
        case 'Static Text/HTML':
        case 'Rectangle':
        case 'Image':
        case 'Tooltip':
        case 'Query List':
        case 'Regular Button':
        case 'Graphic Button':
        case 'Icon':
            return false;
        default:
            break;
    } // switch
    return true;
} // isInputElement

function isVisibleElement($type)
{
    switch ($type) {
        case 'Hidden Input':
            return false;
        default:
            break;
    } // switch
    return true;
} // isVisibleElement

function _ff_query($sql, $insert = 0)
{
    global $database, $errors;
    $database = Factory::getContainer()->get(DatabaseInterface::class);
    $id = null;

    try {
        $database->setQuery($sql);
        $database->execute();
    } catch (\Exception $e) {
        if (isset($errmode) && $errmode == 'log') {
            $errors[] = $e->getMessage();
            Log::add(Text::sprintf('JLIB_DATABASE_ERROR_FUNCTION_FAILED', $e->getCode(), $e->getMessage()), Log::WARNING, 'jerror');
        } else {
            die($e->getMessage());
        }
    }

    if ($insert)
        $id = $database->insertid();
    return $id;
} // _ff_query

function _ff_select($sql)
{
    global $database, $errors;
    $database = Factory::getContainer()->get(DatabaseInterface::class);
    try {
        $database->setQuery($sql);
        $rows = $database->loadObjectList();
    } catch (\Exception $e) {
        if (isset($errmode) && $errmode == 'log') {
            $errors[] = $e->getMessage();
            Log::add(Text::sprintf('JLIB_DATABASE_ERROR_FUNCTION_FAILED', $e->getCode(), $e->getMessage()), Log::WARNING, 'jerror');
        } else {
            die($e->getMessage());
        } // if
    }

    return $rows;
} // _ff_select

function _ff_selectValue($sql)
{
    global $database, $errors;
    $database = Factory::getContainer()->get(DatabaseInterface::class);

    try {
        $database->setQuery($sql);
        $value = $database->loadResult();
    } catch (\Exception $e) {
        if (isset($errmode) && $errmode == 'log') {
            $errors[] = $e->getMessage();
            Log::add(Text::sprintf('JLIB_DATABASE_ERROR_FUNCTION_FAILED', $e->getCode(), $e->getMessage()), Log::WARNING, 'jerror');
        } else {
            die($e->getMessage());
        }
    }

    return $value;
} // _ff_selectValue

function protectedComponentIds()
{
    $rows = _ff_select(
        "select id, parent_id As parent from #__menu " .
        "where " .
        " link in (" .
        "'index.php?option=com_breezingforms&act=managerecs'," .
        "'index.php?option=com_breezingforms&act=managemenus'," .
        "'index.php?option=com_breezingforms&act=manageforms'," .
        "'index.php?option=com_breezingforms&act=managescripts'," .
        "'index.php?option=com_breezingforms&act=managepieces'," .
        "'index.php?option=com_breezingforms&act=share'," .
        "'index.php?option=com_breezingforms&act=integrate'," .
        "'index.php?option=com_breezingforms&act=configuration'" .
        ") " .
        "order by id"
    );

    $parent = 0;
    $ids = array();
    if (count($rows))
        foreach ($rows as $row) {
            if ($parent == 0) {
                $parent = 1;
                if (isset($row->parent)) {
                    $ids[] = intval($row->parent);
                }
            } // if
            $ids[] = intval($row->id);
        } // foreach
    return implode(',', $ids);
} // protectedComponentIds

function addComponentMenu($row, $parent, $copy = false)
{
    $db = Factory::getContainer()->get(DatabaseInterface::class);
    $admin_menu_link = '';
    if ($row->name != '') {
        $admin_menu_link =
            'option=com_breezingforms' .
            '&act=run' .
            '&ff_name=' . htmlentities($row->name, ENT_QUOTES, 'UTF-8');
        if ($row->page != 1)
            $admin_menu_link .= '&ff_page=' . htmlentities($row->page, ENT_QUOTES, 'UTF-8');
        if ($row->frame == 1)
            $admin_menu_link .= '&ff_frame=1';
        if ($row->border == 1)
            $admin_menu_link .= '&ff_border=1';
        if ($row->params != '')
            $admin_menu_link .= $row->params;
    } // if
    if ($parent == 0)
        $ordering = 0;
    else
        $ordering = $row->ordering;


    $parent = $parent == 0 ? 1 : $parent;

    try {
        $db->setQuery("Select component_id From #__menu Where link = 'index.php?option=com_breezingforms' And parent_id = 1");
        $result = $db->loadResult();
    } catch (\Exception $e) {
        if (isset($errmode) && $errmode == 'log') {
            $errors[] = $e->getMessage();
            Log::add(Text::sprintf('JLIB_DATABASE_ERROR_FUNCTION_FAILED', $e->getCode(), $e->getMessage()), Log::WARNING, 'jerror');
        } else {
            die($e->getMessage());
        } // if
    }


    if ($result) {

        return _ff_query(
            "insert into #__menu (" .
            "`title`, alias, menutype, parent_id, " .
            "link," .
            "level, component_id, client_id, img, lft, rgt" .
            ") " .
            "values (" . $db->Quote(($copy ? 'Copy of ' : '') . $row->title . ($copy ? ' (' . md5(session_id() . microtime() . mt_rand(0, mt_getrandmax())) . ')' : '')) . ", " . $db->Quote(($copy ? 'Copy of ' : '') . $row->title . ($copy ? ' (' . md5(session_id() . microtime() . mt_rand(0, mt_getrandmax())) . ')' : '')) . ", 'menu', $parent, " .
            "'index.php?$admin_menu_link'," .
            "1, " . intval($result) . ", 1, 'components/com_breezingforms/images/$row->img',( Select mlftrgt From (Select max(mlft.rgt)+1 As mlftrgt From #__menu As mlft) As tbone ),( Select mrgtrgt From (Select max(mrgt.rgt)+2 As mrgtrgt From #__menu As mrgt) As filet )" .
            ")",
            true
        );
    } else {
        die("BreezingForms main menu item not found!");
    }
} // addComponentMenu

function updateComponentMenus($copy = false)
{
    // remove unprotected menu items
    $protids = protectedComponentIds();
    if (trim($protids) != '') {
        _ff_query(
            "delete from #__menu " .
            "where `link` Like 'index.php?option=com_breezingforms&act=run%' " .
            "and id not in ($protids)"
        );
    }

    // add published menu items
    $rows = _ff_select(
        "select " .
        "m.id as id, " .
        "m.parent as parent, " .
        "m.ordering as ordering, " .
        "m.title as title, " .
        "m.img as img, " .
        "m.name as name, " .
        "m.page as page, " .
        "m.frame as frame, " .
        "m.border as border, " .
        "m.params as params, " .
        "m.published as published " .
        "from #__facileforms_compmenus as m " .
        "left join #__facileforms_compmenus as p on m.parent=p.id " .
        "where m.published=1 " .
        "and (m.parent=0 or p.published=1) " .
        "order by " .
        "if(m.parent,p.ordering,m.ordering), " .
        "if(m.parent,m.ordering,-1)"
    );
    $parent = 0;
    if (count($rows))
        foreach ($rows as $row) {
            Factory::getContainer()->get(DatabaseInterface::class)->setQuery("Select id From #__menu Where `alias` = " . Factory::getContainer()->get(DatabaseInterface::class)->Quote($row->title));

            if (Factory::getContainer()->get(DatabaseInterface::class)->loadResult()) {
                return BFText::_('COM_BREEZINGFORMS_MENU_ITEM_EXISTS');
            }

            if ($row->parent == 0 || $row->parent == 1) {
                $parent = addComponentMenu($row, 1, $copy);
            } else {
                addComponentMenu($row, $parent, $copy);
            }
        } // foreach

    return '';
} // updateComponentMenus


function dropPackage($id)
{
    $db = Factory::getContainer()->get(DatabaseInterface::class);

    // Drop package settings
    _ff_query("delete from #__facileforms_packages where id = " . $db->Quote($id) . "");

    // Drop backend menus
    $rows = _ff_select("select id from #__facileforms_compmenus where package = " . $db->Quote($id) . "");
    if (count($rows))
        foreach ($rows as $row)
            _ff_query("delete from #__facileforms_compmenus where id=$row->id or parent=$row->id");
    updateComponentMenus();

    // Drop forms
    $rows = _ff_select("select id from #__facileforms_forms where package = " . $db->Quote($id) . "");
    if (count($rows))
        foreach ($rows as $row) {
            _ff_query("delete from #__facileforms_elements where form = $row->id");
            _ff_query("delete from #__facileforms_forms where id = $row->id");
        } // if

    // Drop scripts
    _ff_query("delete from #__facileforms_scripts where package =  " . $db->Quote($id) . "");

    // Drop pieces
    _ff_query("delete from #__facileforms_pieces where package =  " . $db->Quote($id) . "");
} // dropPackage


function savePackage($id, $name, $title, $version, $created, $author, $email, $url, $description, $copyright)
{
    $db = Factory::getContainer()->get(DatabaseInterface::class);
    $cnt = _ff_selectValue("select count(*) from #__facileforms_packages where id=" . $db->Quote($id) . "");

    if (!$cnt) {
        _ff_query(
            "insert into #__facileforms_packages " .
            "(id, name, title, version, created, author, " .
            "email, url, description, copyright) " .
            "values (" . $db->Quote($id) . ", " . $db->Quote($name) . ", " . $db->Quote($title) . ", " . $db->Quote($version) . ", " . $db->Quote($created) . ", " . $db->Quote($author) . ",
                    " . $db->Quote($email) . ", " . $db->Quote($url) . ", " . $db->Quote($description) . ", " . $db->Quote($copyright) . ")"
        );
    } else {
        _ff_query(
            "update #__facileforms_packages " .
            "set name=" . $db->Quote($name) . ", title=" . $db->Quote($title) . ", version=" . $db->Quote($version) . ", created=" . $db->Quote($created) . ", author=" . $db->Quote($author) . ", " .
            "email=" . $db->Quote($email) . ", url=" . $db->Quote($url) . ", description=" . $db->Quote($description) . ", copyright=" . $db->Quote($copyright) . " 
            where id =  " . $db->Quote($id)
        );
    } // if
} // savePackage


function relinkScripts(&$oldscripts)
{
    $db = Factory::getContainer()->get(DatabaseInterface::class);
    if ($oldscripts != null && @count($oldscripts))
        foreach ($oldscripts as $row) {
            $newid = _ff_selectValue("select max(id) from #__facileforms_scripts where name = " . $db->Quote($row->name) . "");
            if ($newid) {
                _ff_query("update #__facileforms_forms set script1id=$newid where script1id=$row->id");
                _ff_query("update #__facileforms_forms set script2id=$newid where script2id=$row->id");
                _ff_query("update #__facileforms_elements set script1id=$newid where script1id=$row->id");
                _ff_query("update #__facileforms_elements set script2id=$newid where script2id=$row->id");
                _ff_query("update #__facileforms_elements set script3id=$newid where script3id=$row->id");
            } // if
        } // foreach
} // relinkScripts

function relinkPieces(&$oldpieces)
{
    $db = Factory::getContainer()->get(DatabaseInterface::class);
    if ($oldpieces != null && @count($oldpieces))
        foreach ($oldpieces as $row) {
            $newid = _ff_selectValue("select max(id) from #__facileforms_pieces where name = " . $db->Quote($row->name) . "");
            if ($newid) {
                _ff_query("update #__facileforms_forms set piece1id=$newid where piece1id=$row->id");
                _ff_query("update #__facileforms_forms set piece2id=$newid where piece2id=$row->id");
                _ff_query("update #__facileforms_forms set piece3id=$newid where piece3id=$row->id");
                _ff_query("update #__facileforms_forms set piece4id=$newid where piece4id=$row->id");
            } // if
        } // foreach
} // relinkPieces
