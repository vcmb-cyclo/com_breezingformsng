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

use Vcmb\Component\BreezingformsNG\Site\Service\Upload\UploadExtensionPolicy;

/**
 * Decides whether the file set on a payment element may be sent to the payer.
 * The path is typed by a form editor: it must never serve the code or the
 * configuration of the site, such as configuration.php.
 */
final class PaymentDownloadFilePolicy
{
    /** @param string $resolvedPath The path after realpath(): symbolic links and "../" are already resolved */
    public function isServable(string $resolvedPath): bool
    {
        if ($resolvedPath === '' || !is_file($resolvedPath)) {
            return false;
        }

        $name = basename($resolvedPath);

        // Hidden files (.env, .htaccess, .git...) and anything a server runs are not downloads.
        if (str_starts_with($name, '.')) {
            return false;
        }

        return (new UploadExtensionPolicy())->isAccepted($name, []);
    }
}
