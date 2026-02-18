<?php
/**
 * @package     BreezingForms
 * @author      Markus Bopp
 * @link        http://www.crosstec.de
 * @copyright   Copyright (C) 2024 by XDA+GIL
 * @license     GNU/GPL
 */
defined('_JEXEC') or die('Direct Access to this location is not allowed.');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\MVC\Controller\BaseController;

$controller = BaseController::getInstance('Breezingforms');
$controller->execute('');
$controller->redirect();

require_once(JPATH_SITE . '/administrator/components/com_breezingforms/admin.breezingforms.php');

Factory::getApplication()->getDocument()->addScript(Uri::root(true) . '/administrator/components/com_breezingforms/assets/js/custom.js');
Factory::getApplication()->getDocument()->addStyleSheet(Uri::root(true) . '/administrator/components/com_breezingforms/assets/css/custom.css');

Factory::getApplication()->getDocument()->addStyleSheet(Uri::root(true) . '/administrator/components/com_breezingforms/assets/font-awesome/css/font-awesome.css');
Factory::getApplication()->getDocument()->addStyleDeclaration(
    '.page-title .icon-logo_left{
        background-image:url(' . Uri::root(true) . '/administrator/components/com_breezingforms/assets/images/logo_left.png);
        background-size:contain;
        background-repeat:no-repeat;
        background-position:center;
        display:inline-block;
        width:48px;
        height:48px;
        vertical-align:middle;
        margin-right:.5rem;
    }'
);


$recs = BFRequest::getVar('act', '') == 'managerecs' || BFRequest::getVar('act', '') == 'recordmanagement' || BFRequest::getVar('act', '') == '';
$mgforms = BFRequest::getVar('act', '') == 'manageforms' || BFRequest::getVar('act', '') == 'quickmode';
$mgscripts = BFRequest::getVar('act', '') == 'managescripts';
$mgpieces = BFRequest::getVar('act', '') == 'managepieces';
$mgintegrate = BFRequest::getVar('act', '') == 'integrate';
$mgmenus = BFRequest::getVar('act', '') == 'managemenus';
$mgconfig = BFRequest::getVar('act', '') == 'configuration';
$mgabout = BFRequest::getVar('act', '') == 'about';

$add = '';
if ($recs)
    $add = ' :: ' . Text::_('COM_BREEZINGFORMS_MANAGERECS');
if ($mgforms)
    $add = ' :: ' . Text::_('COM_BREEZINGFORMS_MANAGEFORMS');
if ($mgscripts)
    $add = ' :: ' . Text::_('COM_BREEZINGFORMS_MANAGESCRIPTS');
if ($mgpieces)
    $add = ' :: ' . Text::_('COM_BREEZINGFORMS_MANAGEPIECES');
if ($mgintegrate)
    $add = ' :: ' . Text::_('COM_BREEZINGFORMS_INTEGRATOR');
if ($mgmenus)
    $add = ' :: ' . Text::_('COM_BREEZINGFORMS_MANAGEMENUS');
if ($mgconfig)
    $add = ' :: ' . Text::_('COM_BREEZINGFORMS_CONFIG');
if ($mgabout)
    $add = ' :: ' . Text::_('COM_BREEZINGFORMS_ABOUT');

if ($mgabout) {
    Factory::getApplication()->getDocument()->addStyleDeclaration(
        '.subhead{
            background-color:#e9ecef !important;
            box-shadow:0 1px 2px rgba(33,33,33,.12) !important;
        }
        .subhead .btn,
        .subhead .btn > span{
            color: var(--template-text-dark) !important;
        }
        .subhead .btn:not([disabled]):hover,
        .subhead .btn:not([disabled]):focus,
        .subhead .btn:not([disabled]):active{
            color: var(--template-text-dark) !important;
            background: transparent !important;
        }'
    );
}

$app = Factory::getApplication();
$app->JComponentTitle = "BreezingForms NG" . $add;
$app->JComponentTitle = '<h1 class="page-title"><span class="icon-logo_left" aria-hidden="true"></span>BreezingForms NG' . $add . '</h1>';
