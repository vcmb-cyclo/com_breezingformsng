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

namespace Vcmb\Component\BreezingformsNG\Site\Service\Rendering;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/** Loads the associated and selected ContentBuilder form metadata. */
final class ContentBuilderFormMetadataLoader
{
    public function __construct(private readonly DatabaseInterface $database)
    {
    }

    /** @return array<int, mixed> */
    public function loadAssociatedFormIds(int $breezingFormsFormId): array
    {
        $query = $this->formsQuery($this->database->quoteName('id'))
            ->where($this->database->quoteName('type') . ' = ' . $this->database->quote('com_breezingformsng'))
            ->where($this->database->quoteName('reference_id') . ' = :referenceId')
            ->where($this->publishedCondition())
            ->bind(':referenceId', $breezingFormsFormId, ParameterType::INTEGER);
        $this->database->setQuery($query);

        return (array) $this->database->loadColumn();
    }

    /** @return array<string, mixed>|null */
    public function loadForm(int $contentBuilderFormId): ?array
    {
        $query = $this->formsQuery('*')
            ->where($this->database->quoteName('id') . ' = :cbFormId')
            ->where($this->publishedCondition())
            ->bind(':cbFormId', $contentBuilderFormId, ParameterType::INTEGER);
        $this->database->setQuery($query);
        $data = $this->database->loadAssoc();

        return is_array($data) ? $data : null;
    }

    private function formsQuery(string|array $select): object
    {
        return $this->database->createQuery()
            ->select($select)
            ->from($this->database->quoteName('#__contentbuilderng_forms'));
    }

    private function publishedCondition(): string
    {
        return $this->database->quoteName('published') . ' = 1';
    }
}
