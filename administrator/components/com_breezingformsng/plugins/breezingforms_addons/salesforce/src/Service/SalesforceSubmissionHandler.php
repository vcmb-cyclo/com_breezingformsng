<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Vcmb\Plugin\BreezingformsAddons\Salesforce\Service;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Vcmb\Component\BreezingformsNG\Site\Service\Integration\SalesforceClient;

final class SalesforceSubmissionHandler
{
    private const DATA_NAME = 1;
    private const DATA_VALUE = 4;

    /**
     * @param array<string, mixed> $form
     * @param list<array<int, mixed>> $salesforceData
     */
    public function handle(array $form, array $salesforceData): void
    {
        $fields = [];
        $salesforceFields = is_array($form['salesforce_fields'] ?? null)
            ? $form['salesforce_fields']
            : explode(',', (string) ($form['salesforce_fields'] ?? ''));

        foreach ($salesforceFields as $mapping) {
            if (!is_string($mapping)) {
                continue;
            }

            [$formField, $salesforceField] = array_pad(explode('::', $mapping, 2), 2, '');
            $formField = trim($formField);
            $salesforceField = trim($salesforceField);
            if ($formField === '' || $salesforceField === '') {
                continue;
            }

            foreach ($salesforceData as $data) {
                if (!is_array($data) || (string) ($data[self::DATA_NAME] ?? '') !== $formField) {
                    continue;
                }

                $fields[$salesforceField] = $data[self::DATA_VALUE] ?? '';
                break;
            }
        }

        try {
            (new SalesforceClient())->createRecord(
                trim((string) ($form['salesforce_username'] ?? '')),
                (string) ($form['salesforce_password'] ?? '') . (string) ($form['salesforce_token'] ?? ''),
                trim((string) ($form['salesforce_type'] ?? '')),
                $fields
            );
        } catch (\Throwable) {
            Log::add('Salesforce notification failed.', Log::ERROR, 'com_breezingformsng');
            echo Text::_('JERROR_AN_ERROR_HAS_OCCURRED');
            session_write_close();
            exit;
        }
    }
}
