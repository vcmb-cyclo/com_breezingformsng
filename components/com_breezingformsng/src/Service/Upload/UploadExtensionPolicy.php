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
 * field is checked, not only the first.
 *
 * Rules, whatever list of extensions the form allows:
 * 1. A name with a NUL, a control character, an invisible or bidirectional
 *    format character (U+202E...) or a colon (Windows alternate data stream,
 *    "a.php::$DATA", "a.php:.jpg") is refused.
 * 2. The name is normalised before it is judged: NFKC (full-width "ｐｈｐ"
 *    becomes "php"), lower case, leading and trailing spaces and dots removed,
 *    and every dot-separated segment trimmed ("shell.php ", "shell.php.", "a. php").
 * 3. A segment anywhere in the name that a server runs is refused
 *    ("shell.php.jpg", "x.cgi.png"): PHP variants (php, php5, phtml, phar...),
 *    includes (inc), server side includes, CGI, scripts and server configuration.
 * 4. A server configuration name (.htaccess, .htpasswd, .user.ini, php.ini,
 *    web.config, .env) is refused whole.
 * 5. A last extension of a program or script (exe, bat, jar, ps1...) is refused.
 * 6. Active content (html, svg, xml, js...) is refused unless the form lists
 *    that extension explicitly: an empty list does not allow it.
 * 7. When the form lists extensions, the last one must be in the list.
 */
final class UploadExtensionPolicy
{
    /** Segments a web server may run or that change its configuration: refused anywhere in the name. */
    private const SERVER_SEGMENTS = [
        'phps', 'phar', 'pht', 'phtml', 'phtm', 'phpt', 'inc', 'pgif',
        'shtml', 'shtm', 'stm', 'htaccess', 'htpasswd', 'cgi', 'fcgi', 'pl', 'py', 'rb',
        'asp', 'aspx', 'asa', 'asax', 'ascx', 'ashx', 'asmx', 'axd', 'cer', 'cshtml',
        'jsp', 'jspx', 'jspf', 'sh', 'bash', 'cfm', 'cfc', 'lua',
    ];

    /** Names refused whole: configuration of the server, of PHP or of the site. */
    private const SERVER_FILES = ['.htaccess', '.htpasswd', '.user.ini', 'php.ini', 'web.config', '.env'];

    /** Programs and scripts a user may run: refused as last extension. */
    private const PROGRAM_EXTENSIONS = [
        'exe', 'dll', 'bat', 'cmd', 'com', 'msi', 'scr', 'jar', 'war', 'vbs', 'vbe', 'wsf', 'ps1', 'reg', 'lnk',
    ];

    /** Active content: served as a page, it runs script in the site origin; refused unless listed. */
    private const ACTIVE_CONTENT = ['html', 'htm', 'xhtml', 'svg', 'svgz', 'xml', 'xsl', 'js', 'mjs', 'swf'];

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
     * @param list<string> $allowed Lower case extensions, empty allows any non dangerous file
     */
    public function isAccepted(string $fileName, array $allowed): bool
    {
        // NUL, controls, zero width / bidi format characters, spaces of any script and streams.
        if (
            preg_match('/[\x00-\x1F\x7F:]|[\p{Cc}\p{Cf}]/u', $fileName) === 1
            || !mb_check_encoding($fileName, 'UTF-8')
        ) {
            return false;
        }

        $base       = basename(str_replace('\\', '/', $fileName));
        $normalized = \Normalizer::normalize($base, \Normalizer::FORM_KC);

        if ($normalized === false) {
            return false;
        }

        $normalized = $this->trim(mb_strtolower($normalized));

        if ($normalized === '' || in_array($normalized, self::SERVER_FILES, true)) {
            return false;
        }

        $segments = array_map($this->trim(...), explode('.', $normalized));
        $last     = (string) array_pop($segments);

        foreach ($segments as $segment) {
            if ($this->isServerSegment($segment)) {
                return false;
            }
        }

        if ($this->isServerSegment($last) || in_array($last, self::PROGRAM_EXTENSIONS, true)) {
            return false;
        }

        $allowed = array_map(strtolower(...), $allowed);

        if (in_array($last, self::ACTIVE_CONTENT, true) && !in_array($last, $allowed, true)) {
            return false;
        }

        // A name without a dot has no extension to allow.
        if ($allowed === []) {
            return true;
        }

        return $segments !== [] && in_array($last, $allowed, true);
    }

    private function isServerSegment(string $segment): bool
    {
        // php, php3, php7, php8... and any "php" followed by digits.
        return preg_match('/^php\d*$/', $segment) === 1 || in_array($segment, self::SERVER_SEGMENTS, true);
    }

    /** Removes the spaces of any script around a name and the dots Windows strips from its end (a leading dot stays: ".htaccess"). */
    private function trim(string $value): string
    {
        return (string) preg_replace('/^[\s\p{Z}]+|[\s\p{Z}.]+$/u', '', $value);
    }
}
