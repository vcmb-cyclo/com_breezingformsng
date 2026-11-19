<?php

/**
 * BreezingForms NG - A Joomla Forms Application
 *
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Site\Event;

\defined('_JEXEC') or die;

use Joomla\CMS\Event\AbstractImmutableEvent;

/**
 * Announces the files produced by a successful submission before external
 * submission notifications are sent.
 */
final class SubmissionArtifactsEvent extends AbstractImmutableEvent
{
    public const NAME = 'onBreezingformsngSubmissionArtifacts';

    /**
     * @param list<array{path: string, name: string}> $artifacts
     */
    public function __construct(
        int $formId,
        string $formName,
        int $recordId,
        array $artifacts
    ) {
        parent::__construct(self::NAME, [
            'formId' => $formId,
            'formName' => $formName,
            'recordId' => $recordId,
            'artifacts' => $artifacts,
        ]);
    }

    public function getFormId(): int
    {
        return $this->getArgument('formId');
    }

    public function getFormName(): string
    {
        return $this->getArgument('formName');
    }

    public function getRecordId(): int
    {
        return $this->getArgument('recordId');
    }

    /**
     * @return list<array{path: string, name: string}>
     */
    public function getArtifacts(): array
    {
        return $this->getArgument('artifacts');
    }
}
