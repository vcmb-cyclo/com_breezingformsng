<?php

/**
 * BreezingForms NG - A Joomla Forms Application
 *
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 **/

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Site\Service\Callback;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Vcmb\Component\BreezingformsNG\Site\Service\Upload\UploadExtensionPolicy;

/**
 * Decides whether the file set on a payment element may be sent to the payer.
 * The path is typed by a form editor: it must never serve the code, the
 * configuration, the logs or the system files of the server.
 *
 * Rules, applied to the path after realpath() (symbolic links and "../" are resolved):
 * 1. The path must be an existing regular file.
 * 2. A hidden file (name starting with a dot) is refused.
 * 3. A file without extension is refused (/etc/passwd, /etc/hostname, logs).
 * 4. A script, server configuration or program extension is refused (UploadExtensionPolicy).
 * 5. A file under a sensitive root is refused. A root matches on its real path
 *    followed by a separator, so "/var/www/html2" is not under "/var/www/html".
 *    The system roots (/etc, /proc, /sys, /root, /var/log) are always applied;
 *    the roots of the site (see forSite()) are given to the constructor.
 *    A root that does not exist is ignored.
 */
final class PaymentDownloadFilePolicy
{
    /** Roots of the system whose files are never a download. */
    private const SYSTEM_ROOTS = ['/etc', '/proc', '/sys', '/root', '/var/log'];

    /** @var list<string> Real paths of the sensitive roots, without trailing separator */
    private readonly array $roots;

    /** @param list<string> $sensitiveRoots Sensitive roots of the site, added to the system roots */
    public function __construct(array $sensitiveRoots = [])
    {
        $roots = [];

        foreach ([...self::SYSTEM_ROOTS, ...$sensitiveRoots] as $root) {
            $real = $root === '' ? false : realpath($root);

            if ($real !== false) {
                $roots[] = rtrim($real, '/');
            }
        }

        $this->roots = array_values(array_unique($roots));
    }

    /**
     * The policy of the running site: configuration folder, administrator,
     * libraries, plugins, API, cache, logs, tmp and the component libraries.
     * When the configuration folder is the site root, only configuration.php
     * is protected there, the site root itself stays a legitimate base.
     */
    public static function forSite(CMSApplication $application): self
    {
        $roots = [
            JPATH_ADMINISTRATOR,
            JPATH_LIBRARIES,
            JPATH_PLUGINS,
            JPATH_API,
            JPATH_CACHE,
            JPATH_SITE . '/components/com_breezingformsng/libraries',
            JPATH_SITE . '/components/com_breezingformsng/src',
            (string) $application->get('log_path'),
            (string) $application->get('tmp_path'),
        ];

        $configuration = realpath(JPATH_CONFIGURATION);
        $siteRoot      = realpath(JPATH_ROOT);

        $roots[] = $configuration !== false && $configuration === $siteRoot
            ? JPATH_CONFIGURATION . '/configuration.php'
            : JPATH_CONFIGURATION;

        return new self($roots);
    }

    /** @param string $resolvedPath The path after realpath(): symbolic links and "../" are already resolved */
    public function isServable(string $resolvedPath): bool
    {
        if ($resolvedPath === '' || !is_file($resolvedPath)) {
            return false;
        }

        $real = realpath($resolvedPath);

        if ($real === false) {
            return false;
        }

        $name = basename($real);

        // Hidden files (.env, .htaccess, .git...) and files without extension are not downloads.
        if (str_starts_with($name, '.') || pathinfo($name, PATHINFO_EXTENSION) === '') {
            return false;
        }

        foreach ($this->roots as $root) {
            if ($real === $root || str_starts_with($real, $root . '/')) {
                return false;
            }
        }

        return (new UploadExtensionPolicy())->isAccepted($name, []);
    }
}
