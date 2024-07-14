<?php
if(!defined('_JEXEC')){
    define('_JEXEC', 1);
}

defined('_JEXEC') or die('Direct Access to this location is not allowed.');

/**
 * BreezingForms - A Joomla Forms Application
 * @version 1.4.4
 * @package BreezingForms
 * @copyright (C) 2004-2005 by Peter Koch
 * @license Released under the terms of the GNU General Public License
 **/
$path = '';
if (is_string($_POST['filename'])) {
    $_path = trim($_POST['filename']);
    if (function_exists('get_magic_quotes_gpc') && @get_magic_quotes_gpc()) {
        $_path = str_replace(basename($_path),'',$_path);
        $_path = $_path . stripslashes(basename(trim($_POST['filename'])));
    }

    $admpath = dirname(dirname(__FILE__));
    $compath = str_replace('\\','/',dirname(dirname(dirname($admpath))));
    if ($compath[strlen($compath)-1]!='/') $compath .= '/';
    $compath .= 'components/com_breezingforms/packages';
    $admpath = str_replace('\\','/',$admpath);
    if ($admpath[strlen($admpath)-1]!='/') $admpath .= '/';
    $admpath .= 'packages/';
    if (preg_match("#^($admpath|$compath)#i", str_replace('\\','/',realpath($_path)))) {
        @ob_end_clean();
        $_size = filesize($_path);
        $_name = basename($_path);
        @ini_set("zlib.output_compression", "Off");
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control: private");
        header("Content-Type: application/octet-stream");
        header("Content-Disposition: attachment; filename=\"$_name\"");
        header("Accept-Ranges: bytes");
        header("Content-Length: $_size");
        readfile($_path);
        exit;
    } // if
} // if
echo
    "<html>\n".
    "<head><title>Abuse Warning</title></head>\n".
    '<body text="#000000" bgcolor="#FFFFFF" link="#FF0000" alink="#FF0000" vlink="#FF0000">'."\n".
    "<h1>*** ABUSE WARNING ***</h1>\n".
    "<b><p>Your attempt to hack BreezingForms has been registrated.</p>\n".
    "<p>Here are the logged details of your attack:</p></b>\n".
    '<table border="1" cellpadding="2" cellspacing="3">'."\n".
    "<tr><td><b>Your IP</b></td><td>".htmlentities(strip_tags($_SERVER['REMOTE_ADDR']),ENT_QUOTES, 'UTF-8')."</td></tr>\n".
    "<tr><td><b>Your browser</b></td><td>".htmlentities(strip_tags($_SERVER['HTTP_USER_AGENT']),ENT_QUOTES, 'UTF-8')."</td></tr>\n".
    "<tr><td><b>You came from</b></td><td>".htmlentities(strip_tags($_SERVER['HTTP_REFERER']),ENT_QUOTES, 'UTF-8')."</td></tr>\n".
    "<tr><td><b>You tried to download</b></td><td>".htmlentities(basename($_path),ENT_QUOTES, 'UTF-8')."</td></tr>\n".
    "</table>\n".
    "</body>\n".
    "</html>";
?>