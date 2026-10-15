<?php
/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Site\Service\Callback;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Vcmb\Component\BreezingformsNG\Site\Service\Support\RedirectHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\ParameterType;
use Joomla\Database\DatabaseInterface;
use Joomla\Filesystem\File;
use Joomla\CMS\Mail\Mail;
use Joomla\CMS\Mail\MailerFactoryInterface;

/**
 * Sofortueberweisung payment callbacks: success page, server-side
 * confirmation and paid-file download.
 */
final class SofortCallback
{
    public function __construct(
        private readonly CMSApplication $application,
        private readonly DatabaseInterface $database,
        private readonly PaymentFormLoader $paymentFormLoader,
        private readonly PaymentRecordService $paymentRecordService,
        private readonly RedirectHelper $redirectHelper,
        private readonly MailerFactoryInterface $mailerFactory,
        private readonly PaymentDownloadService $paymentDownloadService,
    ) {
    }

    public function success(): void
    {
        $db = $this->database;



    $input = $this->application->getInput();
    $tx_token = $input->getString('tx', '');
    if ($tx_token == '') {
        $msg = Text::_('COM_BREEZINGFORMSNG_PAYMENT_TRANSACTION_ID_EMPTY');
        require_once (JPATH_SITE . '/components/com_breezingformsng/downloadtpl/error.php');
    } else {

        $formId = $input->getInt('user_variable_0', '');
        $recordId = $input->getInt('user_variable_1', '');

        if ($formId != '' && $recordId != '') {


            $formIdInt = (int) $formId;
            $form = $this->paymentFormLoader->load($formIdInt);
            if ($form === null) {
                $this->redirectHelper->to(Uri::root(), Text::_('COM_BREEZINGFORMSNG_FORM_DOES_NOT_EXIST'));
                $this->application->close();

                return;
            }

            $areas = $this->paymentFormLoader->decodeAreas($form);
            if ($areas === null) {
                $this->redirectHelper->to(Uri::root(), Text::_('COM_BREEZINGFORMSNG_COULD_NOT_FIND_SU_DATA'));

                return;
            }

            foreach ($areas as $area) {
                foreach ($area['elements'] as $element) {
                    if ($element['internalType'] == 'bfSofortueberweisung') {
                        $options = $element['options'];
                        if ($options['downloadableFile']) {
                            $tx_token = $input->getString('tx', '');
                            $tries = $options['downloadTries'];

                            $recordIdInt = (int) $recordId;
                            $sofortTx = 'Sofortüberweisung: ' . $input->getString('tx', '');
                            $downloadsQuery = $db->createQuery()
                                ->select($db->quoteName('paypal_download_tries'))
                                ->from($db->quoteName('#__facileforms_records'))
                                ->where($db->quoteName('id') . ' = :recordIdInt')
                                ->where($db->quoteName('paypal_tx_id') . ' = :sofortTx')
                                ->bind(':recordIdInt', $recordIdInt, ParameterType::INTEGER)
                                ->bind(':sofortTx', $sofortTx, ParameterType::STRING);
                            $db->setQuery($downloadsQuery);

                            $downloads = $db->loadObjectList();

                            $confirmed = false;
                            if (count($downloads) == 1) {
                                $confirmed = true;
                            }

                            require_once (JPATH_SITE . '/components/com_breezingformsng/downloadtpl/sofort_download.php');
                        } else {
                            if ($options['thankYouPage'] != '') {
                                $this->redirectHelper->to($options['thankYouPage']);
                            } else {
                                $this->redirectHelper->to(Uri::root(), Text::_('COM_BREEZINGFORMSNG_THANK_YOU_FOR_PAYING_WITH_SU'));
                            }
                        }

                        break;
                    }
                }
            }
        } else {
            $msg = Text::_("COM_BREEZINGFORMSNG_MISSING_PAYMENT_INFORMATION");
            $tx_token = Text::_("COM_BREEZINGFORMSNG_NOT_AVAILABLE");
            if ($input->getString('tx', '') != '') {
                $tx_token = $input->getString('tx', '');
            }
            require_once (JPATH_SITE . '/components/com_breezingformsng/downloadtpl/error.php');
        }
    }
    }

    public function confirm(): void
    {
        $db = $this->database;



    $input = $this->application->getInput();
    $formId = $input->getInt('user_variable_0', -1);
    $recordId = $input->getInt('user_variable_1', -1);


    $formIdInt = (int) $formId;
    $form = $this->paymentFormLoader->load($formIdInt);
    if ($form === null) {
        $this->application->close();

        return;
    }

    $areas = $this->paymentFormLoader->decodeAreas($form);
    if ($areas === null) {
        $this->application->close();

        return;
    }

    foreach ($areas as $area) {
        foreach ($area['elements'] as $element) {
            if ($element['internalType'] == 'bfSofortueberweisung') {

                $options = $element['options'];

                $data = array(
                    'transaction' => $input->getString('transaction', ''),
                    'user_id' => $input->getString('user_id', ''),
                    'project_id' => $input->getString('project_id', ''),
                    'sender_holder' => $input->getString('sender_holder', ''),
                    'sender_account_number' => $input->getString('sender_account_number', ''),
                    'sender_bank_code' => $input->getString('sender_bank_code', ''),
                    'sender_bank_name' => $input->getString('sender_bank_name', ''),
                    'sender_bank_bic' => $input->getString('sender_bank_bic', ''),
                    'sender_iban' => $input->getString('sender_iban', ''),
                    'sender_country_id' => $input->getString('sender_country_id', ''),
                    'recipient_holder' => $input->getString('recipient_holder', ''),
                    'recipient_account_number' => $input->getString('recipient_account_number', ''),
                    'recipient_bank_code' => $input->getString('recipient_bank_code', ''),
                    'recipient_bank_name' => $input->getString('recipient_bank_name', ''),
                    'recipient_bank_bic' => $input->getString('recipient_bank_bic', ''),
                    'recipient_iban' => $input->getString('recipient_iban', ''),
                    'recipient_country_id' => $input->getString('recipient_country_id', ''),
                    'international_transaction' => $input->getString('international_transaction', ''),
                    'amount' => $input->getString('amount', ''),
                    'currency_id' => $input->getString('currency_id', ''),
                    'reason_1' => $input->getString('reason_1', ''),
                    'reason_2' => $input->getString('reason_2', ''),
                    'security_criteria' => $input->getString('security_criteria', ''),
                    'user_variable_0' => $input->getString('user_variable_0', ''),
                    'user_variable_1' => $input->getString('user_variable_1', ''),
                    'user_variable_2' => $input->getString('user_variable_2', ''),
                    'user_variable_3' => $input->getString('user_variable_3', ''),
                    'user_variable_4' => $input->getString('user_variable_4', ''),
                    'user_variable_5' => $input->getString('user_variable_5', ''),
                    'created' => $input->getString('created', ''),
                    'project_password' => $options['project_password']
                );

                $data_implode = implode('|', $data);
                $hash = sha1($data_implode);

                $recordIdInt = (int) $recordId;
                $record = $this->paymentRecordService->findUnpaid($recordIdInt);

                if ($hash == $input->getString('hash', '')) {

                    if ($record !== null) {

                            $sofortRecordId = (int) $recordId;
                            $sofortTxId = 'Sofortüberweisung: ' . $input->getString('transaction', '');
                            $sofortPaymentDate = date('Y-m-d H:i:s', strtotime($input->getString('created', '')));
                            $this->paymentRecordService->storeTransaction(
                                $sofortRecordId,
                                $sofortTxId,
                                $sofortPaymentDate,
                                0
                            );

                            $recipients = explode('###', $input->getString('user_variable_2', ''));
                            $recipientsSize = count($recipients);
                            /** @var Mail $mailer */
                            $mailer = $this->mailerFactory->createMailer();
                            $mailer->Subject = Text::_('COM_BREEZINGFORMSNG_YOUR_PAYMENT_AT_SU');
                            $mailer->Body = Text::_('COM_BREEZINGFORMSNG_HALLO') . "\n\n";
                            $mailer->Body .= Text::_('COM_BREEZINGFORMSNG_YOUR_PAYMENT_SUCCEEDED') . "\n\n";
                            $mailer->Body .= '--------------------------------------' . "\n\n";
                            $mailer->Body .= Text::_('COM_BREEZINGFORMSNG_REASON1') . ': ' . $input->getString('reason_1', '') . "\n";
                            $mailer->Body .= Text::_('COM_BREEZINGFORMSNG_REASON2') . ': ' . $input->getString('reason_2', '') . "\n";
                            $mailer->Body .= Text::_('COM_BREEZINGFORMSNG_AMOUNT') . ': ' . str_replace('.', ',', $input->getString('amount', '')) . ' ' . $input->getString('currency_id', '') . "\n";
                            $mailer->Body .= Text::_('COM_BREEZINGFORMSNG_TRANSACTION') . ': ' . $input->getString('transaction', '') . "\n";
                            $mailer->Body .= Text::_('COM_BREEZINGFORMSNG_ACCOUNT_HOLDER') . ': ' . $input->getString('sender_holder', '') . "\n";
                            $mailer->Body .= Text::_('COM_BREEZINGFORMSNG_ACCOUNT_NUMBER') . ': ' . $input->getString('sender_account_number', '') . "\n";
                            $mailer->Body .= Text::_('COM_BREEZINGFORMSNG_BANK_CODE') . ': ' . $input->getString('recipient_bank_code', '') . "\n";
                            $mailer->Body .= Text::_('COM_BREEZINGFORMSNG_BANK_NAME') . ': ' . $input->getString('sender_bank_name', '') . "\n";
                            $mailer->Body .= Text::_('COM_BREEZINGFORMSNG_BIC') . ': ' . $input->getString('sender_bank_bic', '') . "\n";
                            $mailer->Body .= Text::_('COM_BREEZINGFORMSNG_IBAN') . ': ' . $input->getString('sender_iban', '') . "\n";
                            $mailer->Body .= Text::_('COM_BREEZINGFORMSNG_PAYMENT_DATE') . ': ' . $input->getString('created', '') . "\n\n";

                            $mailer->Body .= '--------------------------------------' . "\n\n";
                            $mailer->Body .= Text::_('COM_BREEZINGFORMSNG_RECEIPT_FOR_YOUR_PAYMENT') . "\n\n";
                            $mailer->Body .= '--------------------------------------' . "\n\n";

                            $mailer->Body .= Text::_('COM_BREEZINGFORMSNG_ACCOUNT_HOLDER') . ': ' . $input->getString('recipient_holder', '') . "\n";
                            $mailer->Body .= Text::_('COM_BREEZINGFORMSNG_ACCOUNT_NUMBER') . ': ' . $input->getString('recipient_account_number', '') . "\n";
                            $mailer->Body .= Text::_('COM_BREEZINGFORMSNG_BANK_CODE') . ': ' . $input->getString('recipient_bank_code', '') . "\n";
                            $mailer->Body .= Text::_('COM_BREEZINGFORMSNG_BANK_NAME') . ': ' . $input->getString('recipient_bank_name', '') . "\n";
                            $mailer->Body .= Text::_('COM_BREEZINGFORMSNG_BIC') . ': ' . $input->getString('recipient_bank_bic', '') . "\n";
                            $mailer->Body .= Text::_('COM_BREEZINGFORMSNG_IBAN') . ': ' . $input->getString('recipient_iban', '') . "\n\n";

                            $mailer->Body .= '--------------------------------------' . "\n\n";

                            $mailer->Body .= Text::_('COM_BREEZINGFORMSNG_PAYMENT_GATEWAY_SU');

                            for ($i = 0; $i < $recipientsSize; $i++) {
                                if (bf_is_email($recipients[$i])) {
                                    $mailer->addRecipient($recipients[$i]);
                                    $mailer->send();
                                }
                            }

                            // trigger a script after succeeded payment?
                            if (file_exists(JPATH_SITE . '/bf_sofortueberweisung_success.php')) {
                                require_once (JPATH_SITE . '/bf_sofortueberweisung_success.php');
                            }

                            // send mail after succeeded payment?
                            if (isset($options['sendNotificationAfterPayment']) && $options['sendNotificationAfterPayment']) {
                                bf_sendNotificationByPaymentCache($formId, $recordId, 'admin');
                                bf_sendNotificationByPaymentCache($formId, $recordId, 'mailback');
                            }
                    }
                }

                break;
            }
        }
    }
    }

    public function download(): void
    {
        $this->paymentDownloadService->download(
            'bfSofortueberweisung',
            'COM_BREEZINGFORMSNG_COULD_NOT_FIND_PAYMENT_DATA',
            'tx',
            'Sofortüberweisung'
        );
    }
}
