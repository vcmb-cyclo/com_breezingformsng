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

namespace Vcmb\Component\BreezingformsNG\Site\Service\Notification;

\defined('_JEXEC') or die;

use Vcmb\Component\BreezingformsNG\Site\Service\Upload\UploadDirectoryProtection;

/**
 * Queue of notification e-mails waiting for a payment confirmation.
 *
 * Entries are JSON files named <form>_<record>_<type>_<random>.json in a folder under the
 * Joomla temporary path (outside the web root). Entries written by the previous release
 * (serialized arrays named *.txt under media/breezingforms/payment_cache) are still read,
 * without any class being rebuilt, so that pending payments survive the upgrade.
 */
final class PaymentNotificationCache
{
    public const FOLDER = 'bfng_payment_cache';

    private const MAX_AGE = 86400;

    private const STRING_KEYS = ['subject', 'body'];

    private const NULLABLE_STRING_KEYS = ['from', 'fromname', 'alt_sender'];

    public function __construct(
        private readonly string $directory,
        private readonly ?string $legacyDirectory = null
    ) {
    }

    public static function fromTemporaryPath(string $temporaryPath, ?string $legacyDirectory = null): self
    {
        return new self(rtrim($temporaryPath, '/\\') . '/' . self::FOLDER . '/', $legacyDirectory);
    }

    public function directory(): string
    {
        return rtrim($this->directory, '/\\') . '/';
    }

    /**
     * Queues a message. Returns the written path, or null when the entry could not be stored.
     *
     * @param array<string, mixed> $message
     */
    public function store(int $formId, int $recordId, string $type, array $message): ?string
    {
        if (!preg_match('/^[a-z]+$/', $type) || !$this->ensureDirectory()) {
            return null;
        }

        try {
            $json = json_encode($message, JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);

            for ($attempt = 0; $attempt <= 1000; $attempt++) {
                $path = $this->directory() . $formId . '_' . $recordId . '_' . $type . '_' . bin2hex(random_bytes(16)) . '.json';
                if (file_exists($path)) {
                    continue;
                }

                $handle = @fopen($path, 'xb');
                if ($handle === false) {
                    return null;
                }
                @chmod($path, 0640);
                $written = fwrite($handle, $json);
                fclose($handle);

                if ($written !== strlen($json)) {
                    @unlink($path);

                    return null;
                }

                return $path;
            }
        } catch (\JsonException | \Random\RandomException) {
            return null;
        }

        return null;
    }

    /** Returns the path of the first queued entry for this payment (current location first), or null. */
    public function find(int $formId, int $recordId, string $type): ?string
    {
        foreach ($this->directories() as $directory) {
            if (!is_dir($directory) || !is_readable($directory) || ($handle = @opendir($directory)) === false) {
                continue;
            }

            try {
                while (($file = readdir($handle)) !== false) {
                    if ($this->matches($file, $formId, $recordId, $type) && is_file($directory . $file)) {
                        return $directory . $file;
                    }
                }
            } finally {
                closedir($handle);
            }
        }

        return null;
    }

    /**
     * Decodes and validates the content of an entry. Returns null for anything malformed.
     *
     * @return array{from: string, fromname: string, recipients: string|list<string>, subject: string, body: string, attachment: string|list<string>|false|null, isHtml: bool, alt_sender: string}|null
     */
    public function decode(string $path, string $contents): ?array
    {
        $data = $this->isLegacy($path) ? $this->unserializeLegacy($contents) : $this->decodeJson($contents);

        return is_array($data) ? $this->validate($data) : null;
    }

    public function isLegacy(string $path): bool
    {
        return $this->legacyDirectory !== null
            && str_starts_with($path, rtrim($this->legacyDirectory, '/\\') . '/');
    }

    /** Removes entries older than one day from the current and the legacy folders. */
    public function purgeExpired(?int $now = null): void
    {
        $now ??= time();

        foreach ($this->directories() as $directory) {
            if (!is_dir($directory) || !is_readable($directory)) {
                continue;
            }

            foreach (new \DirectoryIterator($directory) as $file) {
                if (
                    $file->isFile()
                    && $this->isEntryName($file->getFilename())
                    && $now - $file->getCTime() >= self::MAX_AGE
                ) {
                    @unlink($file->getPathname());
                }
            }
        }
    }

    /** @return list<string> */
    private function directories(): array
    {
        $directories = [$this->directory()];
        if ($this->legacyDirectory !== null) {
            $directories[] = rtrim($this->legacyDirectory, '/\\') . '/';
        }

        return $directories;
    }

    private function matches(string $file, int $formId, int $recordId, string $type): bool
    {
        $parts = explode('_', $file);

        return count($parts) === 4
            && $parts[0] === (string) $formId
            && $parts[1] === (string) $recordId
            && $parts[2] === $type;
    }

    private function isEntryName(string $file): bool
    {
        return count(explode('_', $file)) === 4;
    }

    private function ensureDirectory(): bool
    {
        $directory = $this->directory();
        if (!is_dir($directory) && !@mkdir($directory, 0750, true) && !is_dir($directory)) {
            return false;
        }

        // Defence in depth: the folder is outside the web root, but must never be served if the tmp path moves.
        UploadDirectoryProtection::ensure($directory, true);

        return is_writable($directory);
    }

    /** @return array<mixed>|null */
    private function decodeJson(string $contents): ?array
    {
        try {
            $data = json_decode($contents, true, 8, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        return is_array($data) ? $data : null;
    }

    /** @return array<mixed>|null */
    private function unserializeLegacy(string $contents): ?array
    {
        $data = @unserialize($contents, ['allowed_classes' => false]);

        return is_array($data) ? $data : null;
    }

    /**
     * @param array<mixed> $data
     * @return array{from: string, fromname: string, recipients: string|list<string>, subject: string, body: string, attachment: string|list<string>|false|null, isHtml: bool, alt_sender: string}|null
     */
    private function validate(array $data): ?array
    {
        foreach ([...self::STRING_KEYS, ...self::NULLABLE_STRING_KEYS, 'recipients', 'attachment', 'isHtml'] as $key) {
            if (!array_key_exists($key, $data)) {
                return null;
            }
        }

        foreach (self::STRING_KEYS as $key) {
            if (!is_string($data[$key])) {
                return null;
            }
        }

        $clean = ['subject' => $data['subject'], 'body' => $data['body']];
        foreach (self::NULLABLE_STRING_KEYS as $key) {
            if ($data[$key] !== null && !is_string($data[$key])) {
                return null;
            }
            $clean[$key] = (string) $data[$key];
        }

        $recipients = $this->stringOrList($data['recipients']);
        if ($recipients === null) {
            return null;
        }

        $attachment = $data['attachment'];
        if ($attachment !== null && $attachment !== false) {
            $attachment = $this->stringOrList($attachment);
            if ($attachment === null) {
                return null;
            }
        }

        if (!is_bool($data['isHtml']) && $data['isHtml'] !== 0 && $data['isHtml'] !== 1) {
            return null;
        }

        return [
            'from' => $clean['from'],
            'fromname' => $clean['fromname'],
            'recipients' => $recipients,
            'subject' => $clean['subject'],
            'body' => $clean['body'],
            'attachment' => $attachment,
            'isHtml' => (bool) $data['isHtml'],
            'alt_sender' => $clean['alt_sender'],
        ];
    }

    /** @return string|list<string>|null null when invalid */
    private function stringOrList(mixed $value): string|array|null
    {
        if (is_string($value)) {
            return $value;
        }

        if (!is_array($value)) {
            return null;
        }

        foreach ($value as $item) {
            if (!is_string($item)) {
                return null;
            }
        }

        return array_values($value);
    }
}
