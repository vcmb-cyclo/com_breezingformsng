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
 *
 * The getters read the arguments directly: Joomla's getArgument() calls a
 * get<Name>() method of the event when one exists, so a getter that called
 * getArgument() for its own argument would call itself without end.
 */
final class AfterSubmitEvent extends AbstractImmutableEvent
{
    public const NAME = 'onBreezingformsngAfterSubmit';

    /**
     * @param list<array<int, mixed>> $submitData Submitted values, one row per element value.
     * @param list<array<int, mixed>> $mailData Values prepared for notifications, one row per element value.
     * @param list<array<int, mixed>> $salesforceData Values prepared for Salesforce, one row per element value.
     */
    public function __construct(
        int $formId,
        string $formName,
        int $recordId,
        array $submitData,
        array $mailData = [],
        array $salesforceData = []
    ) {
        parent::__construct(self::NAME, [
            'formId' => $formId,
            'formName' => $formName,
            'recordId' => $recordId,
            'submitData' => $submitData,
            'mailData' => $mailData,
            'salesforceData' => $salesforceData,
        ]);
    }

    public function getFormId(): int
    {
        return $this->arguments['formId'];
    }

    public function getFormName(): string
    {
        return $this->arguments['formName'];
    }

    /** Identifier of the stored record, 0 when the form does not log to the database. */
    public function getRecordId(): int
    {
        return $this->arguments['recordId'];
    }

    /**
     * @return list<array<int, mixed>>
     */
    public function getSubmitData(): array
    {
        return $this->arguments['submitData'];
    }

    /**
     * @return list<array<int, mixed>>
     */
    public function getMailData(): array
    {
        return $this->arguments['mailData'];
    }

    /**
     * @return list<array<int, mixed>>
     */
    public function getSalesforceData(): array
    {
        return $this->arguments['salesforceData'];
    }
}
