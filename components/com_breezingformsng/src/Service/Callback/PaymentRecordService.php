<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Site\Service\Callback;

\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/** Reads and stores the common payment transaction fields. */
final class PaymentRecordService
{
    public function __construct(private readonly DatabaseInterface $database)
    {
    }

    /** @return \stdClass|null */
    public function find(int $recordId): ?object
    {
        return $this->findRecord($recordId, false);
    }

    /** @return \stdClass|null */
    public function findUnpaid(int $recordId): ?object
    {
        return $this->findRecord($recordId, true);
    }

    public function storeTransaction(
        int $recordId,
        string $transaction,
        string $paymentDate,
        int $testAccount
    ): void {
        $query = $this->database->createQuery()
            ->update($this->database->quoteName('#__facileforms_records'))
            ->set($this->database->quoteName('paypal_tx_id') . ' = :paymentTransaction')
            ->set($this->database->quoteName('paypal_payment_date') . ' = :paymentDate')
            ->set($this->database->quoteName('paypal_testaccount') . ' = :testAccount')
            ->set(
                $this->database->quoteName('paypal_download_tries')
                . ' = 0'
            )
            ->where($this->database->quoteName('id') . ' = :recordId')
            ->bind(':paymentTransaction', $transaction, ParameterType::STRING)
            ->bind(':paymentDate', $paymentDate, ParameterType::STRING)
            ->bind(':testAccount', $testAccount, ParameterType::INTEGER)
            ->bind(':recordId', $recordId, ParameterType::INTEGER);
        $this->database->setQuery($query);
        $this->database->execute();
    }

    /**
     * Stores the transaction only if the record has none yet, so that the
     * browser return and the Stripe webhook record a payment once.
     *
     * @return bool true when this call stored the transaction
     */
    public function storeTransactionOnce(
        int $recordId,
        string $transaction,
        string $paymentDate,
        int $testAccount
    ): bool {
        $query = $this->database->createQuery()
            ->update($this->database->quoteName('#__facileforms_records'))
            ->set($this->database->quoteName('paypal_tx_id') . ' = :paymentTransaction')
            ->set($this->database->quoteName('paypal_payment_date') . ' = :paymentDate')
            ->set($this->database->quoteName('paypal_testaccount') . ' = :testAccount')
            ->set($this->database->quoteName('paypal_download_tries') . ' = 0')
            ->where($this->database->quoteName('id') . ' = :recordId')
            ->where($this->database->quoteName('paypal_tx_id') . " = ''")
            ->bind(':paymentTransaction', $transaction, ParameterType::STRING)
            ->bind(':paymentDate', $paymentDate, ParameterType::STRING)
            ->bind(':testAccount', $testAccount, ParameterType::INTEGER)
            ->bind(':recordId', $recordId, ParameterType::INTEGER);
        $this->database->setQuery($query);
        $this->database->execute();

        return $this->database->getAffectedRows() === 1;
    }

    /** @return \stdClass|null */
    private function findRecord(int $recordId, bool $unpaidOnly): ?object
    {
        $query = $this->database->createQuery()
            ->select('*')
            ->from($this->database->quoteName('#__facileforms_records'))
            ->where($this->database->quoteName('id') . ' = :recordId')
            ->setLimit(1)
            ->bind(':recordId', $recordId, ParameterType::INTEGER);
        if ($unpaidOnly) {
            $query->where($this->database->quoteName('paypal_tx_id') . " = ''");
        }
        $this->database->setQuery($query);
        $records = $this->database->loadObjectList();

        return $records[0] ?? null;
    }
}
