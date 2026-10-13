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

namespace Vcmb\Component\BreezingformsNG\Site\Service\Notification;

/** Resolves a form mail template before its default template. */
final class MailTemplateResolver
{
    /** @return array{file: string, isHtml: bool} */
    public function resolve(string $directory, string $formName, string $suffix, string $defaultName): array
    {
        $candidates = [
            [$formName . $suffix . '.html.php', true],
            [$formName . $suffix . '.txt.php', false],
            [$defaultName . '.html.php', true],
            [$defaultName . '.txt.php', false],
        ];

        foreach ($candidates as [$filename, $isHtml]) {
            $path = rtrim($directory, '/\\') . '/' . $filename;
            if (is_file($path) && is_readable($path)) {
                return ['file' => $path, 'isHtml' => $isHtml];
            }
        }

        return ['file' => '', 'isHtml' => false];
    }
}
