<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Site\Service\Callback;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Vcmb\Component\BreezingformsNG\Site\Service\Support\DownloadHelper;
use Vcmb\Component\BreezingformsNG\Site\Service\Support\RedirectHelper;

/** Handles the shared paid-file download flow for payment callbacks. */
final class PaymentDownloadService
{
    public function __construct(
        private readonly CMSApplication $application,
        private readonly DatabaseInterface $database,
        private readonly PaymentFormLoader $paymentFormLoader,
        private readonly RedirectHelper $redirectHelper,
        private readonly PaymentDownloadPolicy $downloadPolicy,
        private readonly PaymentDownloadFilePolicy $filePolicy
    ) {
    }

    public function download(
        string $internalType,
        string $paymentDataMessageKey,
        string $transactionInput,
        string $transactionPrefix,
        bool $acceptValidatedTransaction = false
    ): void {
        $input = $this->application->getInput();
        $formId = $input->getInt('form', -1);
        $form = $this->paymentFormLoader->load($formId);
        if ($form === null) {
            $this->redirectHelper->to(Uri::root(), Text::_('COM_BREEZINGFORMSNG_FORM_DOES_NOT_EXIST'));
            $this->application->close();

            return;
        }

        $areas = $this->paymentFormLoader->decodeAreas($form);
        if ($areas === null) {
            $this->redirectHelper->to(Uri::root(), Text::_($paymentDataMessageKey));

            return;
        }

        foreach ($areas as $area) {
            if (!is_array($area) || !is_array($area['elements'] ?? null)) {
                continue;
            }

            foreach ($area['elements'] as $element) {
                if (!is_array($element) || ($element['internalType'] ?? null) !== $internalType) {
                    continue;
                }

                $options = $element['options'] ?? [];
                if (!is_array($options)) {
                    break;
                }

                if (empty($options['downloadableFile'])) {
                    $this->redirectHelper->to(
                        Uri::root(),
                        Text::_('COM_BREEZINGFORMSNG_NO_DOWNLOADABLE_PRODUCT')
                    );

                    break;
                }

                $downloadRecordId = $input->getInt('record_id', -1);
                $transaction = $transactionPrefix . ': ' . $input->getString($transactionInput, '');
                $transactionBindings = [':paymentTransaction0' => $transaction];
                if ($acceptValidatedTransaction) {
                    $transactionBindings[':paymentTransaction1'] = $transaction . ' (VALID)';
                }

                $selectQuery = $this->database->createQuery()
                    ->select($this->database->quoteName('paypal_download_tries'))
                    ->from($this->database->quoteName('#__facileforms_records'))
                    ->where($this->database->quoteName('id') . ' = :downloadRecordId')
                    ->where($this->database->quoteName('form') . ' = :downloadFormId');
                $transactionConditions = [];
                foreach (array_keys($transactionBindings) as $parameter) {
                    $transactionConditions[] = $this->database->quoteName('paypal_tx_id') . ' = ' . $parameter;
                }
                if (count($transactionConditions) === 1) {
                    $selectQuery->where($transactionConditions[0]);
                } else {
                    $selectQuery->extendWhere('AND', $transactionConditions, 'OR');
                }
                $selectQuery->bind(':downloadRecordId', $downloadRecordId, ParameterType::INTEGER);
                $selectQuery->bind(':downloadFormId', $formId, ParameterType::INTEGER);
                foreach ($transactionBindings as $parameter => $value) {
                    $selectQuery->bind($parameter, $value, ParameterType::STRING);
                }
                $this->database->setQuery($selectQuery);

                $downloads = $this->database->loadObjectList();
                if (count($downloads) !== 1) {
                    $this->redirectHelper->to(
                        Uri::root(),
                        Text::_('COM_BREEZINGFORMSNG_DOWNLOAD_NOT_POSSIBLE')
                    );

                    break;
                }

                $maximumTries = (int) ($options['downloadTries'] ?? 0);
                if (
                    !$this->downloadPolicy->canDownload(
                        (int) $downloads[0]->paypal_download_tries,
                        $maximumTries
                    )
                ) {
                    $this->redirectHelper->to(
                        Uri::root(),
                        Text::_('COM_BREEZINGFORMSNG_MAX_DOWNLOAD_TRIES_REACHED')
                    );

                    break;
                }

                $updateQuery = $this->database->createQuery()
                    ->update($this->database->quoteName('#__facileforms_records'))
                    ->set(
                        $this->database->quoteName('paypal_download_tries')
                        . ' = '
                        . $this->database->quoteName('paypal_download_tries')
                        . ' + 1'
                    )
                    ->where($this->database->quoteName('id') . ' = :downloadRecordId')
                    ->where($this->database->quoteName('form') . ' = :downloadFormId')
                    ->where($this->database->quoteName('paypal_download_tries') . ' < :maximumTries');
                if (count($transactionConditions) === 1) {
                    $updateQuery->where($transactionConditions[0]);
                } else {
                    $updateQuery->extendWhere('AND', $transactionConditions, 'OR');
                }
                $updateQuery->bind(':downloadRecordId', $downloadRecordId, ParameterType::INTEGER);
                $updateQuery->bind(':downloadFormId', $formId, ParameterType::INTEGER);
                $updateQuery->bind(':maximumTries', $maximumTries, ParameterType::INTEGER);
                foreach ($transactionBindings as $parameter => $value) {
                    $updateQuery->bind($parameter, $value, ParameterType::STRING);
                }
                $this->database->setQuery($updateQuery);
                $this->database->execute();

                // The conditional UPDATE is the authoritative quota check: concurrent
                // requests cannot both claim the last remaining download.
                if ($this->database->getAffectedRows() !== 1) {
                    $this->redirectHelper->to(
                        Uri::root(),
                        Text::_('COM_BREEZINGFORMSNG_MAX_DOWNLOAD_TRIES_REACHED')
                    );

                    break;
                }

                $file = (string) ($options['filepath'] ?? '');
                $resolved = realpath($file);
                if ($resolved === false || !$this->filePolicy->isServable($resolved)) {
                    $this->redirectHelper->to(
                        Uri::root(),
                        Text::_('COM_BREEZINGFORMSNG_COULD_NOT_FIND_DOWNLOAD_FILE')
                    );

                    break;
                }

                DownloadHelper::stream($this->application, $resolved);
            }
        }
    }
}
