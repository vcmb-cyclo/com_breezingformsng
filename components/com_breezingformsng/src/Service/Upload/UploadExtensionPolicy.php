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

namespace Vcmb\Component\BreezingformsNG\Site\Service\Upload;

\defined('_JEXEC') or die;

/**
 * Decides which files of an upload field are accepted. Every file of the
 * field is checked, not only the first, and a file the web server may run as
 * a script is never accepted, whatever list of extensions the form allows.
 */
final class UploadExtensionPolicy
{
    /** Extensions a web server may run or that change its configuration. */
    private const EXECUTABLE = [
        'php', 'php2', 'php3', 'php4', 'php5', 'php6', 'php7', 'php8', 'phtml', 'pht', 'phps', 'phar',
        'pgif', 'shtml', 'htaccess', 'htpasswd', 'cgi', 'pl', 'py', 'asp', 'aspx', 'jsp', 'sh',
    ];

    /**
     * @param list<string> $fileNames    The names the browser sent, empty ones are skipped
     * @param string       $allowedCsv   The extensions the form allows, comma separated; empty allows any
     *
     * @return string|null The name of the first refused file, null when all are accepted
     */
    public function firstRefused(array $fileNames, string $allowedCsv): ?string
    {
        $allowed = array_values(array_filter(array_map(
            'trim',
            explode(',', strtolower(str_replace(' ', '', $allowedCsv)))
        ), static fn(string $extension): bool => $extension !== ''));

        foreach ($fileNames as $fileName) {
            if ($fileName === '') {
                continue;
            }

            if (!$this->isAccepted($fileName, $allowed)) {
                return $fileName;
            }
        }

        return null;
    }

    /**
     * @param list<string> $allowed Lower case extensions, empty allows any non executable file
     */
    public function isAccepted(string $fileName, array $allowed): bool
    {
        $base     = basename(str_replace('\\', '/', $fileName));
        $segments = explode('.', strtolower($base));
        $last     = (string) array_pop($segments);

        // "shell.php.jpg" is run by servers that read every extension of the name.
        foreach ($segments as $segment) {
            if (in_array(trim($segment), self::EXECUTABLE, true)) {
                return false;
            }
        }

        if (in_array($last, self::EXECUTABLE, true)) {
            return false;
        }

        // A name without a dot has no extension to allow; ".php" style hidden names end up here too.
        if ($allowed === []) {
            return true;
        }

        return $segments !== [] && in_array($last, $allowed, true);
    }
}
