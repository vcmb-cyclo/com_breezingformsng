<?php
/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Administrator\Helper;

\defined('_JEXEC') or die;

/**
 * Idempotent loader for the component's Composer autoloader.
 * Ported from ContentBuilderNG.
 */
final class VendorHelper
{
    private static bool $loaded = false;

    public static function load(): void
    {
        if (self::$loaded) {
            return;
        }

        if (!defined('K_PATH_FONTS')) {
            define(
                'K_PATH_FONTS',
                JPATH_ADMINISTRATOR . '/components/com_breezingformsng/vendor/tecnickcom/tc-lib-pdf-font/target/fonts/core/'
            );
        }

        $autoload = JPATH_ADMINISTRATOR . '/components/com_breezingformsng/vendor/autoload.php';

        if (!is_file($autoload)) {
            throw new \RuntimeException('Composer autoload not found: ' . $autoload);
        }

        require_once $autoload;
        self::$loaded = true;
    }
}
