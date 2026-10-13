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

\defined('_JEXEC') or die;

final class ScriptSignatureParser
{
    /**
     * @return array{0: string, 1: list<string>, 2: list<string>}
     */
    public function parse(string $code, string $fallbackName = ''): array
    {
        $functionName = '';
        $parameters = [];
        $defaults = [];

        $patterns = [
            '/function\s+([a-zA-Z_$][a-zA-Z0-9_$]*)\s*\(([^)]*)\)/m',
            '/(?:const|let|var)?\s*([a-zA-Z_$][a-zA-Z0-9_$]*)\s*=\s*function\s*\(([^)]*)\)/m',
            '/(?:const|let|var)?\s*([a-zA-Z_$][a-zA-Z0-9_$]*)\s*=\s*\(([^)]*)\)\s*=>/m',
        ];

        foreach ($patterns as $pattern) {
            if (!preg_match($pattern, $code, $matches)) {
                continue;
            }

            $functionName = $matches[1];
            $parameterList = trim($matches[2]);
            foreach ($parameterList === '' ? [] : explode(',', $parameterList) as $parameter) {
                $segments = explode('=', trim($parameter), 2);
                $name = preg_replace('/^\.\.\./', '', trim($segments[0]));

                if ($name === null || !preg_match('/^[a-zA-Z_$][a-zA-Z0-9_$]*$/', $name)) {
                    continue;
                }

                $parameters[] = $name;
                $defaults[] = isset($segments[1]) ? trim($segments[1]) : '';
            }

            break;
        }

        return [$functionName !== '' ? $functionName : trim($fallbackName), $parameters, $defaults];
    }
}
