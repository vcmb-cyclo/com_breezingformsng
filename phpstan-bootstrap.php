<?php

/**
 * BreezingForms NG - A Joomla Forms Application
 *
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 **/

declare(strict_types=1);

$componentAutoload = __DIR__ . '/administrator/components/com_breezingformsng/vendor/autoload.php';

if (is_file($componentAutoload)) {
    require_once $componentAutoload;
}

\defined('_JEXEC') || \define('_JEXEC', 1);

foreach (
    [
        'JPATH_ROOT',
        'JPATH_SITE',
        'JPATH_BASE',
        'JPATH_ADMINISTRATOR',
        'JPATH_API',
        'JPATH_CLI',
        'JPATH_CONFIGURATION',
        'JPATH_CACHE',
        'JPATH_INSTALLATION',
        'JPATH_LIBRARIES',
        'JPATH_MANIFESTS',
        'JPATH_PLUGINS',
        'JPATH_PUBLIC',
        'JPATH_THEMES',
        'JPATH_COMPONENT',
        'JPATH_COMPONENT_SITE',
        'JPATH_COMPONENT_ADMINISTRATOR',
    ] as $bfPhpstanConstant
) {
    \defined($bfPhpstanConstant) || \define($bfPhpstanConstant, __DIR__);
}

\defined('JDEBUG') || \define('JDEBUG', false);

// Runmode constants defined at runtime by
// components/com_breezingformsng/src/Support/runtime_bootstrap.php - declared
// here too so PHPStan can resolve them in files that assume that bootstrap
// already ran (the legacy stored-script runtime), without adding the whole
// Support/ bootstrap chain (with its top-level dispatch side effects) to the
// analysed paths.
\defined('_FF_RUNMODE_FRONTEND') || \define('_FF_RUNMODE_FRONTEND', 0);
\defined('_FF_RUNMODE_BACKEND') || \define('_FF_RUNMODE_BACKEND', 1);
\defined('_FF_RUNMODE_PREVIEW') || \define('_FF_RUNMODE_PREVIEW', 2);
