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

namespace Vcmb\Component\BreezingformsNG\Administrator\Service;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

/** Reads the tail of the latest installation log of BreezingForms NG (read-only). */
final class InstallLogReader
{
    private const LOG_FILES = ['breezingforms_install2.log', 'breezingforms_install.log'];
    private const TAIL_BYTES = 65536;

    public function __construct(private readonly string $timezone = 'UTC')
    {
        if ($timezone === '') {
            throw new \InvalidArgumentException('The time zone cannot be empty.');
        }
    }

    /**
     * @return array{file: string, size: int, loaded_at: string, content: string, truncated: int, tail_bytes: int}
     */
    public function read(): array
    {
        $latestPath = '';
        $latestMtime = 0;

        foreach (self::LOG_FILES as $fileName) {
            $path = JPATH_ADMINISTRATOR . '/logs/' . $fileName;

            if (!is_file($path)) {
                continue;
            }

            $mtime = filemtime($path);

            if ($mtime === false) {
                continue;
            }

            if ($latestPath === '' || $mtime > $latestMtime) {
                $latestPath = $path;
                $latestMtime = $mtime;
            }
        }

        if ($latestPath === '') {
            throw new \RuntimeException(Text::sprintf('COM_BREEZINGFORMSNG_ABOUT_LOG_NOT_FOUND', JPATH_ADMINISTRATOR . '/logs'));
        }

        if (!is_readable($latestPath)) {
            throw new \RuntimeException(basename($latestPath));
        }

        $size = filesize($latestPath);

        if ($size === false) {
            throw new \RuntimeException(basename($latestPath));
        }

        $content = '';
        $truncated = false;

        if ($size > 0) {
            $handle = fopen($latestPath, 'rb');

            if (!is_resource($handle)) {
                throw new \RuntimeException(basename($latestPath));
            }

            if ($size > self::TAIL_BYTES) {
                $truncated = true;
                fseek($handle, -self::TAIL_BYTES, SEEK_END);
            }

            $content = (string) stream_get_contents($handle);
            fclose($handle);
        }

        $loadedAt = '';

        if ($latestMtime > 0) {
            $timezone = new \DateTimeZone($this->timezone);
            $loadedAt = (new \Joomla\CMS\Date\Date('@' . $latestMtime))
                ->setTimezone($timezone)
                ->format('Y-m-d H:i:s', true);
        }

        return [
            'file' => basename($latestPath),
            'size' => $size,
            'loaded_at' => $loadedAt,
            'content' => $content,
            'truncated' => $truncated ? 1 : 0,
            'tail_bytes' => self::TAIL_BYTES,
        ];
    }
}
