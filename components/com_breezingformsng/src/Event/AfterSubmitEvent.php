<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Site\Event;

\defined('_JEXEC') or die;

use Joomla\CMS\Event\AbstractImmutableEvent;

/**
 * Dispatched as "onBreezingformsngAfterSubmit" once a submission has been
 * accepted, stored and notified. Read-only: listeners react to the
 * submission, they cannot alter it.
 */
final class AfterSubmitEvent extends AbstractImmutableEvent
{
    public const NAME = 'onBreezingformsngAfterSubmit';

    /**
     * @param list<array<int, mixed>> $submitData Submitted values, one row per element value.
     */
    public function __construct(int $formId, string $formName, int $recordId, array $submitData)
    {
        parent::__construct(self::NAME, [
            'formId' => $formId,
            'formName' => $formName,
            'recordId' => $recordId,
            'submitData' => $submitData,
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

    /** Identifier of the stored record, 0 when the form does not log to the database. */
    public function getRecordId(): int
    {
        return $this->getArgument('recordId');
    }

    /**
     * @return list<array<int, mixed>>
     */
    public function getSubmitData(): array
    {
        return $this->getArgument('submitData');
    }
}
