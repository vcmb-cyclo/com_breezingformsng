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

/**
 * Stripe payment callbacks: checkout confirmation and paid-file download.
 */
final class StripeCallback
{
    public function __construct(
        private readonly CMSApplication $application,
        private readonly DatabaseInterface $database,
        private readonly PaymentFormLoader $paymentFormLoader,
        private readonly PaymentRecordService $paymentRecordService,
        private readonly RedirectHelper $redirectHelper,
        private readonly PaymentDownloadService $paymentDownloadService,
    ) {
    }

    public function confirm(): void
    {
        $db = $this->database;



    $input = $this->application->getInput();
    $form = $this->paymentFormLoader->load($input->getInt('form_id', -1));
    if ($form === null) {
        $this->redirectHelper->to(Uri::root(), Text::_('COM_BREEZINGFORMSNG_FORM_DOES_NOT_EXIST'));
        $this->application->close();

        return;
    }

    $areas = $this->paymentFormLoader->decodeAreas($form);
    if ($areas === null) {
        $this->redirectHelper->to(Uri::root(), Text::_('COM_BREEZINGFORMSNG_COULD_NOT_FIND_STRIPE_DATA'));
        $this->application->close();

        return;
    }

    $tx_token = $input->getString('token', '');
    $record_id = $input->getInt('record_id', 0);

    foreach ($areas as $area) {

        foreach ($area['elements'] as $element) {

            if ($element['internalType'] == 'bfStripe') {

                $options = $element['options'];

                \Vcmb\Component\BreezingformsNG\Administrator\Helper\VendorHelper::load();

                \Stripe\Stripe::setApiKey($options['secretKey']);
                $stripe = new \Stripe\StripeClient($options['secretKey']);

                // Create the charge on Stripe's servers - this will charge the user's card
                try {


                    $stripePrefix = 'Stripe:%';
                    $existsQuery = $db->createQuery()
                        ->select($db->quoteName('paypal_tx_id'))
                        ->from($db->quoteName('#__facileforms_records'))
                        ->where($db->quoteName('id') . ' = :recordId')
                        ->where($db->quoteName('paypal_tx_id') . ' LIKE :stripePrefix')
                        ->bind(':recordId', $record_id, ParameterType::INTEGER)
                        ->bind(':stripePrefix', $stripePrefix, ParameterType::STRING);
                    $db->setQuery($existsQuery);

                    $exists = $db->loadResult();

                    if (!$exists) {

                        /* XDA if( $this->application->getSession()->get('bf_stripe_last_payment_amount'.$record_id, null) == null ){

            $this->redirectHelper->to(Uri::root(), Text::_('COM_BREEZINGFORMSNG_COULD_NOT_FIND_STRIPE_AMOUNT'));
            $this->application->close();
                                } XDA */

                        $stripearray = array();
                        $stripearray = [
                            "amount" => $this->application->getSession()->get('bf_stripe_last_payment_amount' . $record_id, null),
                            // amount in cents, again
                            "currency" => strtolower($options['currencyCode']),
                            "source" => $tx_token,
                            "description" => $options['itemname'],
                            "metadata" => array()
                            //,"metadata" => array("Order ID" => $_session_cart['order_id'])
                        ];
                        if ($this->application->getSession()->get('emailfield', '') !== '') {
                            $stripearray += ['receipt_email' => $this->application->getSession()->get('emailfield', '')];
                            $this->application->getSession()->remove('emailfield');
                        }
                        //$charge = \Stripe\Charge::create( $stripearray );
                        /*
                        $charge = \Stripe\Checkout\Session::create([
                                'customer_email' => 'bff@gmail.com',
                                'billing_address_collection' => 'required',
                                'line_items' => [[
                                  'price' => 'price_1JYA3UDkYxK6vMJ2QF2S6fNh',
                                  'quantity' => 1,
                                ]],
                                'payment_method_types' => [
                                  'card',
                                ],
                                'mode' => 'payment',
                                'success_url' => 'https://firadeldibuixilapintura.cat/test/success',
                                'cancel_url' => 'https://firadeldibuixilapintura.cat/test/cancel',
                          ]);*/


                        $this->application->getSession()->remove('bf_stripe_last_payment_amount' . $record_id);
                    } else {

                        $exploded = explode(':', $exists);
                        //$charge = \Stripe\Charge::retrieve(trim($exploded[1]));
                    }

                    //                                      $tx_token = $charge->id;
                    $session_id = $input->getString('session_id');
                    $session = $stripe->checkout->sessions->retrieve(
                        "$session_id",
                        []
                    );

                    if ($session->payment_status != 'paid') {

                        $msg = Text::_("COM_BREEZINGFORMSNG_STRIPE_DECLINED");

                        require_once (JPATH_SITE . '/components/com_breezingformsng/downloadtpl/error.php');
                    } else {
                        /** get payment intend id and creation time */
                        $stripe_pi_id = $session->payment_intent; //payment intent id from last session also replacing tx_token/charge->id with paymentIntents->id
                        $stripe_pi = $stripe->paymentIntents->retrieve(
                            "$stripe_pi_id",
                            []
                        );
                        $stripe_pi_create = $stripe_pi->created; // replacing charge->created with paymentIntents->created
                        // XDA BEGIN
                        // Stripe Payment Intent updates to complete its description from pi_xxx to BF item Name - pi_xxx
                        $stripe->paymentIntents->update($stripe_pi_id, ['description' => $options['itemname']]);
                        // XDA END
                        $stripeRecordId = $input->getInt('record_id', -1);
                        $stripeTxId = 'Stripe: ' . strip_tags($stripe_pi_id);
                        $stripePaymentDate = date('Y-m-d H:i:s', $stripe_pi_create);
                        $stripeTestaccount = !$stripe_pi->livemode ? 1 : 0;
                        $this->paymentRecordService->storeTransaction(
                            $stripeRecordId,
                            $stripeTxId,
                            $stripePaymentDate,
                            $stripeTestaccount
                        );

                        // trigger a script after succeeded payment?
                        if (file_exists(JPATH_SITE . '/bf_stripe_success.php')) {
                            require_once (JPATH_SITE . '/bf_stripe_success.php');
                        }

                        // send mail after succeeded payment?
                        if (isset($options['sendNotificationAfterPayment']) && $options['sendNotificationAfterPayment']) {
                            bf_sendNotificationByPaymentCache($input->getInt('form_id', -1), $input->getInt('record_id', -1), 'admin');
                            bf_sendNotificationByPaymentCache($input->getInt('form_id', -1), $input->getInt('record_id', -1), 'mailback');
                        }

                        if ($options['downloadableFile']) {

                            $record_id = $input->getInt('record_id', -1);
                            $tries = $options['downloadTries'];
                            $form_id = $input->getInt('form_id', -1);
                            require_once (JPATH_SITE . '/components/com_breezingformsng/downloadtpl/stripe_download.php');
                        } else {

                            if ($options['thankYouPage'] != '') {
                                $this->redirectHelper->to($options['thankYouPage']);
                            } else {
                                $this->redirectHelper->to(Uri::root(), Text::_('COM_BREEZINGFORMSNG_THANK_YOU_FOR_PAYING_WITH_STRIPE'));
                            }
                        }
                    }
                } catch (\Stripe\Exception\CardException $e) {

                    $msg = Text::_("COM_BREEZINGFORMSNG_STRIPE_DECLINED");
                    require_once (JPATH_SITE . '/components/com_breezingformsng/downloadtpl/error.php');
                }

                break;
            }
        }
    }
    }

    public function download(): void
    {
        $this->paymentDownloadService->download(
            'bfStripe',
            'COM_BREEZINGFORMSNG_COULD_NOT_FIND_PAYMENT_DATA',
            'token',
            'Stripe'
        );
    }
}
