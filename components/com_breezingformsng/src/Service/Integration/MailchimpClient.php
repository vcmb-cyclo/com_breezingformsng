<?php
/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Site\Service\Integration;

\defined('_JEXEC') or die;

use Joomla\Http\Http;
use Joomla\Http\HttpFactory;
use RuntimeException;

final class MailchimpClient
{
    private Http $http;

    public function __construct(?Http $http = null)
    {
        $this->http = $http ?? (new HttpFactory())->getHttp();
    }

    /**
     * @param array<mixed>|null $payload
     * @return array<mixed>
     */
    public function request(string $apiKey, string $method, string $resource, ?array $payload = null): array
    {
        $separator = strrpos($apiKey, '-');
        if ($separator === false || $separator === strlen($apiKey) - 1) {
            throw new RuntimeException('Invalid Mailchimp API key.');
        }

        $dataCenter = substr($apiKey, $separator + 1);
        $headers = [
            'Authorization' => 'Basic ' . base64_encode('breezingformsng:' . $apiKey),
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
        $body = $payload === null ? '' : json_encode($payload, JSON_THROW_ON_ERROR);
        $url = 'https://' . $dataCenter . '.api.mailchimp.com/3.0/' . ltrim($resource, '/');
        $result = match (strtoupper($method)) {
            'DELETE' => $this->http->delete($url, $headers),
            'PUT' => $this->http->put($url, $body, $headers),
            default => throw new RuntimeException('Unsupported Mailchimp HTTP method.'),
        };

        $body = (string) $result->getBody();
        $decoded = $body === '' ? [] : json_decode($body, true, 512, JSON_THROW_ON_ERROR);

        if ($result->getStatusCode() < 200 || $result->getStatusCode() >= 300) {
            throw new RuntimeException((string) ($decoded['detail'] ?? $body));
        }

        return $decoded;
    }
}
