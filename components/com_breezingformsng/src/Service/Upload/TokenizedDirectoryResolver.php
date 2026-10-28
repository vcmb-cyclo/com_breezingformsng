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
use Joomla\Filesystem\Folder;
use Joomla\Input\Input;
use Vcmb\Component\BreezingformsNG\Site\Service\Runtime\SubmissionTimestampAdjuster;

final class TokenizedDirectoryResolver
{
    public function __construct(
        private readonly Input $input,
        private readonly SubmissionTimestampAdjuster $timestampAdjuster = new SubmissionTimestampAdjuster()
    ) {
    }

    /**
     * @param array<int, \stdClass> $rows
     * @param array<int, string> $findTags
     * @param array<int, string> $replaceTags
     * @param array{username?: mixed, id?: mixed, name?: mixed} $identity
     */
    public function resolve(
        string $path,
        array $rows,
        string $fieldName,
        array $findTags,
        array $replaceTags,
        array $identity,
        string $submittedAt,
        string $timezone
    ): string {
        if (str_starts_with(strtolower($path), '{cbsite}')) {
            $path = str_replace(['{cbsite}', '{CBSite}'], [JPATH_SITE, JPATH_SITE], $path);
        }

        $path = str_replace($findTags, $replaceTags, $path);

        $separator = strpos($path, '|');

        if ($separator === false) {
            return $path;
        }

        $after = str_replace('|', '', substr($path, $separator));
        $path = str_replace('|', '/', substr($path, 0, $separator + 1));
        $filter = InputFilter::getInstance([], [], 1, 1);

        foreach ($rows as $row) {
            $rawValue = $this->input->post->get('ff_nm_' . $row->name, [], 'raw');
            $values = \is_array($rawValue)
                ? array_map(static fn ($value) => $filter->clean((string) $value, 'html'), $rawValue)
                : [];
            $value = implode('/', $values);
            $path = str_replace(
                '{' . strtolower($row->name) . ':value}',
                trim($value) === '' ? '_empty_' : trim($value),
                $path
            );
            $path = str_replace(
                '{field:' . strtolower($row->name) . '}',
                strtolower($row->name),
                $path
            );
        }

        $userId = (string) ($identity['id'] ?? 0);
        $path = str_replace('{userid}', $userId, $path);
        $path = str_replace('{username}', (string) ($identity['username'] ?? 'anonymous') . '_' . $userId, $path);
        $path = str_replace('{name}', (string) ($identity['name'] ?? 'Anonymous') . '_' . $userId, $path);
        $path = str_replace('{field}', File::makeSafe(strtolower(trim($fieldName))), $path);

        [$date, $time, $dateTime] = $this->formatSubmittedAt($submittedAt, $timezone);
        $path = str_replace(['{date}', '{time}', '{datetime}'], [$date, $time, $dateTime], $path);
        $directory = $this->makeSafeFolder($path);

        Folder::create($directory);

        return $directory . $after;
    }

    public function makeSafeFolder(string $path): string
    {
        return (string) preg_replace('#[^A-Za-z0-9{}\.:_\\/-]#', '_', $path);
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function formatSubmittedAt(string $submittedAt, string $timezone): array
    {
        $date = $this->timestampAdjuster->adjust($submittedAt, $timezone);

        return [
            $date->format('Y_m_d', true),
            $date->format('H_i_s', true),
            $date->format('Y_m_d_H_i_s', true),
        ];
    }
}
