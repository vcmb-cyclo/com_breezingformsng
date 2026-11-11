<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Administrator\Mcp;

\defined('_JEXEC') or die;

/** Validates the browser Origin header against the endpoint's public URL. */
final class OriginValidator
{
    /** An absent Origin is valid for non-browser clients. */
    public static function isAllowed(string $origin, string $requestUrl): bool
    {
        if ($origin === '') {
            return true;
        }

        // An Origin is exactly one serialized origin: it has no path, query,
        // fragment, credentials, whitespace, or empty port.
        if (!preg_match('~\Ahttps?://(?:\[[0-9a-f:.]+\]|[a-z0-9.-]+)(?::[0-9]{1,5})?\z~iD', $origin)) {
            return false;
        }

        $supplied = self::authority($origin);
        $expected = self::authority($requestUrl);

        return $supplied !== null && $expected !== null && $supplied === $expected;
    }

    /** @return array{string, string, int}|null */
    private static function authority(string $url): ?array
    {
        $parts = parse_url($url);
        if (!is_array($parts) || !isset($parts['scheme'], $parts['host']) || isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }

        $scheme = strtolower($parts['scheme']);
        if ($scheme !== 'http' && $scheme !== 'https') {
            return null;
        }

        $host = strtolower($parts['host']);
        if (str_starts_with($host, '[') && str_ends_with($host, ']')) {
            $ip = substr($host, 1, -1);
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false) {
                return null;
            }
            $packed = inet_pton($ip);
            if ($packed === false) {
                return null;
            }
            $host = '[' . bin2hex($packed) . ']';
        } elseif (filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false) {
            return null;
        }

        $port = $parts['port'] ?? ($scheme === 'https' ? 443 : 80);
        if ($port < 1 || $port > 65535) {
            return null;
        }

        return [$scheme, $host, $port];
    }
}
