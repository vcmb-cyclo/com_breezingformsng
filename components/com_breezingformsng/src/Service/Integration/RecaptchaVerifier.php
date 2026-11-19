<?php
/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Site\Service\Integration;

\defined('_JEXEC') or die;

use JsonException;
use Joomla\Http\Http;
use Joomla\Http\HttpFactory;

final class RecaptchaVerifier
{
    private Http $http;

    public function __construct(?Http $http = null)
    {
        $this->http = $http ?? (new HttpFactory())->getHttp();
    }

    public function verify(string $secret, string $response, string $remoteIp): bool
    {
        if ($secret === '' || $response === '') {
            return false;
        }

        $result = $this->http->post(
            'https://www.google.com/recaptcha/api/siteverify',
            ['secret' => $secret, 'response' => $response, 'remoteip' => $remoteIp]
        );

        if ($result->getStatusCode() !== 200) {
            return false;
        }

        try {
            $payload = json_decode((string) $result->getBody(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return false;
        }

        if (!is_array($payload)) {
            return false;
        }

        return ($payload['success'] ?? false) === true;
    }
}
