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

namespace Vcmb\Component\BreezingformsNG\Site\Service\Runtime;

defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Uri\Uri;
use Vcmb\Component\BreezingformsNG\Site\Configuration\FormConfiguration;

final class RuntimeContextInitializer
{
    public function __construct(
        private readonly CMSApplication $application,
        private readonly FormConfiguration $configuration,
    ) {
    }

    public function initialize(?string $siteUrl, ?string $componentUrl, ?array $otherParameters): array
    {
        $siteUrl ??= $this->resolveSiteUrl();
        $siteUrl = rtrim($siteUrl, '/');
        $componentUrl ??= $siteUrl . '/components/com_breezingformsng';
        $otherParameters ??= $this->collectOtherParameters();

        return [
            'siteUrl' => $siteUrl,
            'componentUrl' => $componentUrl,
            'otherParameters' => $otherParameters,
        ];
    }

    private function resolveSiteUrl(): string
    {
        if ((int) $this->configuration->livesite === 0) {
            return Uri::root();
        }

        $server = $this->application->getInput()->server;
        $https = $server->getString('HTTPS', '') === 'on'
            || $server->getString('HTTP_X_FORWARDED_PROTO', '') === 'https';
        $protocol = $https ? 'https' : 'http';
        $host = preg_replace('/:\d+$/', '', $server->getString('HTTP_HOST', ''));
        $port = $server->getInt('SERVER_PORT', $https ? 443 : 80);
        $portSuffix = ($https && $port === 443) || (!$https && $port === 80) ? '' : ':' . $port;
        $path = dirname($server->getString('PHP_SELF', '/'));

        if (basename($path) === 'administrator') {
            $path = dirname($path);
        }

        return str_replace('\\', '/', $protocol . '://' . $host . $portSuffix . $path);
    }

    private function collectOtherParameters(): array
    {
        $input = $this->application->getInput();
        $result = [];
        $option = $this->saveParameter($result, 'option');

        $names = match ($option) {
            'com_content' => ['Itemid', 'task', 'sectionid', 'id'],
            'com_contact', 'com_contacts' => ['id', 'Itemid', 'task', 'catid', 'view', 'contact_id'],
            'com_weblinks' => ['Itemid', 'catid'],
            default => ['Itemid'],
        };

        foreach ($names as $name) {
            $this->saveParameter($result, $name);
        }

        return $result;
    }

    private function saveParameter(array &$parameters, string $name): ?string
    {
        $value = $this->application->getInput()->get($name, null, 'string');

        if ($value === null || is_array($value)) {
            return null;
        }

        $parameters[$name] = $value;

        return $value;
    }
}
