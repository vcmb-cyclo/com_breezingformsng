<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Site\Service\Upload;

\defined('_JEXEC') or die;

use Joomla\CMS\Filter\InputFilter;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Path;
use Joomla\Input\Input;
use Vcmb\Component\BreezingformsNG\Site\Service\Runtime\SubmissionTimestampAdjuster;

final class UploadPathResolver
{
    public function __construct(
        private readonly Input $input,
        private readonly SubmissionTimestampAdjuster $timestampAdjuster = new SubmissionTimestampAdjuster()
    ) {
    }

    /**
     * @param array<int, string> $findTags
     * @param array<int, string> $replaceTags
     * @param array<int, \stdClass> $rows
     * @param array{username?: mixed, id?: mixed, name?: mixed} $identity
     *
     * @return array{directory: string, filename: string, path: string}
     */
    public function resolve(
        string $destination,
        string $clientFilename,
        array $findTags,
        array $replaceTags,
        array $rows,
        string $submittedAt,
        string $timezone,
        array $identity
    ): array {
        $directory = Path::clean(str_replace($findTags, $replaceTags, $destination));
        $maskedDirectory = $directory;
        $basename = basename($directory);
        $containsFilemask = str_replace('{filemask:', '', $basename) !== $basename;

        if ($containsFilemask) {
            $directory = rtrim(rtrim(str_replace($basename, '', $directory), '/'), '\\');
            $clientFilename = $this->resolveFilemask(
                basename($maskedDirectory),
                $clientFilename,
                $rows,
                $submittedAt,
                $timezone,
                $identity
            );
        }

        return [
            'directory' => $directory,
            'filename' => $clientFilename,
            'path' => $directory . '/' . $clientFilename,
        ];
    }

    /**
     * @param array<int, \stdClass> $rows
     * @param array{username?: mixed, id?: mixed, name?: mixed} $identity
     */
    private function resolveFilemask(
        string $filemask,
        string $clientFilename,
        array $rows,
        string $submittedAt,
        string $timezone,
        array $identity
    ): string {
        $filter = InputFilter::getInstance([], [], 1, 1);

        foreach ($rows as $row) {
            $rawValues = $this->input->post->get('ff_nm_' . $row->name, [], 'raw');
            $values = \is_array($rawValues)
                ? array_map(static fn ($value) => $filter->clean((string) $value, 'html'), $rawValues)
                : [];

            foreach ($values as $value) {
                $safeValue = File::makeSafe(trim($value));
                $filemask = str_replace('{filemask:' . strtolower($row->name) . '}', $safeValue, $filemask);
                $filemask = str_replace('{' . strtolower($row->name) . ':value}', $safeValue, $filemask);
            }
        }

        [$dateTime, $date] = $this->formatSubmittedAt($submittedAt, $timezone);
        $extension = File::getExt($clientFilename);
        $replacements = [
            '{filemask:_separator}' => '_',
            '{filemask:_username}' => trim((string) ($identity['username'] ?? '')),
            '{filemask:_userid}' => trim((string) ($identity['id'] ?? '')),
            '{filemask:_name}' => trim((string) ($identity['name'] ?? '')),
            '{filemask:_datetime}' => $dateTime,
            '{filemask:_date}' => $date,
            '{filemask:_timestamp}' => (string) time(),
            '{filemask:_random}' => (string) mt_rand(0, mt_getrandmax()),
            '{filemask:_filename}' => trim(basename($clientFilename, '.' . $extension)),
        ];
        $filemask = str_replace(array_keys($replacements), array_values($replacements), $filemask);

        return ($filemask === '' ? '__empty__' : $filemask) . '.' . $extension;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function formatSubmittedAt(string $submittedAt, string $timezone): array
    {
        $date = $this->timestampAdjuster->adjust($submittedAt, $timezone);

        return [$date->format('Y_m_d_H_i_s', true), $date->format('Y_m_d', true)];
    }
}
