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

namespace Vcmb\Component\BreezingformsNG\Administrator\Service;

/** Returns readable regular files from a PDF font directory. */
final class PdfFontDirectoryScanner
{
    /** @return list<string> */
    public function scan(string $directory): array
    {
        if (!is_dir($directory) || !is_readable($directory)) {
            return [];
        }

        $files = [];
        foreach (new \DirectoryIterator($directory) as $file) {
            if ($file->isFile() && is_readable($file->getPathname())) {
                $files[] = $file->getFilename();
            }
        }

        return $files;
    }
}
