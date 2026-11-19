<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Vcmb\Plugin\BreezingformsAddons\Dropbox\Service;

\defined('_JEXEC') or die;

use Joomla\Http\Http;
use Joomla\Http\HttpFactory;
use RuntimeException;

final class DropboxUploader
{
    private Http $http;

    public function __construct(?Http $http = null)
    {
        $this->http = $http ?? (new HttpFactory())->getHttp();
    }

    public function upload(string $accessToken, string $remotePath, string $localFile): void
    {
        if (!is_file($localFile) || !is_readable($localFile)) {
            throw new RuntimeException('Unable to read the Dropbox upload source.');
        }

        $contents = file_get_contents($localFile);
        if ($contents === false) {
            throw new RuntimeException('Unable to read the Dropbox upload source.');
        }

        $result = $this->http->post(
            'https://content.dropboxapi.com/2/files/upload',
            $contents,
            [
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/octet-stream',
                'Dropbox-API-Arg' => json_encode([
                    'path' => $remotePath,
                    'mode' => 'add',
                    'autorename' => true,
                    'mute' => false,
                ], JSON_THROW_ON_ERROR),
            ]
        );

        if ($result->getStatusCode() < 200 || $result->getStatusCode() >= 300) {
            throw new RuntimeException('Dropbox API error: ' . (string) $result->getBody());
        }
    }
}
