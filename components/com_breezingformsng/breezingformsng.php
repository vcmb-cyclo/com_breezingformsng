<?php

/**
 * BreezingForms NG - A Joomla Forms Application
 *
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2008-2020 by Markus Bopp
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 *
 * Front controller of the native form engine. Boots the public stored-script runtime,
 * then dispatches either to the form renderer (view/submit) or to one of
 * the callback services (payments, captcha, uploads, opt-in/out).
 * */
defined('_JEXEC') or die('Direct Access to this location is not allowed.');

use Joomla\CMS\Factory;
use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\Database\DatabaseInterface;
use Vcmb\Component\BreezingformsNG\Administrator\Extension\BreezingFormsNGComponent;
use Vcmb\Component\BreezingformsNG\Site\Configuration\FormConfiguration;
use Vcmb\Component\BreezingformsNG\Site\Service\Runtime\RuntimeContextInitializer;

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

$mainframe = Factory::getApplication();

// the engine renders outside the MVC dispatch (modules, plugins, iframes):
// make sure the component language is available for Text::_()
$mainframe->getLanguage()->load('com_breezingformsng');

$container = Factory::getContainer();
$cacheControllerFactory = $container->get(CacheControllerFactoryInterface::class);
$cache = $cacheControllerFactory->createCacheController('callback');
$cache->setCaching(false);

require_once (JPATH_SITE . '/administrator/components/com_breezingformsng/libraries/crosstec/functions/helpers.php');
require_once (JPATH_SITE . '/administrator/components/com_breezingformsng/libraries/crosstec/classes/BFQuickMode.php');
require_once (JPATH_SITE . '/administrator/components/com_breezingformsng/libraries/crosstec/classes/BFQuickModeBootstrap.php');
require_once (JPATH_SITE . '/administrator/components/com_breezingformsng/libraries/crosstec/classes/BFQuickModeOnePage.php');

// declare global variables
global
$database, // joomla database object
$ff_version, // FacileForms version number
$ff_config, // FacileForms configuration object
$ff_mospath, // path to root of joomla
$ff_compath, // path to component frontend root
$ff_mossite, // url of the site root
$ff_comsite, // url of the component frontend root
$ff_otherparams, // request parameters propagated through the form
$ff_request, // array of request parameters ff_param_*
$ff_processor, // current form procesor object
$ff_target;    // index of form on current page

$database = $db = $container->get(DatabaseInterface::class);

if (!isset($xModuleId)) {
    $xModuleId = 0;
}

if (!isset($ff_applic)) {
    $ff_applic = '';
}

// get paths
$ff_mospath = JPATH_SITE;
$ff_compath = $ff_mospath . '/components/com_breezingformsng';

// load config and initialize globals
require_once $ff_compath . '/src/Support/runtime_bootstrap.php';
$ff_config = new FormConfiguration();
$runtimeContext = (new RuntimeContextInitializer($mainframe, $ff_config))->initialize(
    $ff_mossite ?? null,
    $ff_comsite ?? null,
    $ff_otherparams ?? null,
);
$ff_mossite = $runtimeContext['siteUrl'];
$ff_comsite = $runtimeContext['componentUrl'];
$ff_otherparams = $runtimeContext['otherParameters'];

// context handed over by the including application (module/plugin or MVC template)
$bfEngineContext = [
    'ff_applic' => $ff_applic,
    'module_id' => $xModuleId,
    'params' => $params ?? null,
    'plg_editable' => $plg_editable ?? 0,
    'plg_editable_override' => $plg_editable_override ?? 0,
];

$input = $mainframe->getInput();
$component = $mainframe->bootComponent('com_breezingformsng');
/** @var BreezingFormsNGComponent $component */
$component->getEngineDispatcher()
    ->dispatch($bfEngineContext, (string) $ff_applic);

if ($input->getBool('raw', false)) {
    session_write_close();
    exit;
}

$cache->setCaching(true);
