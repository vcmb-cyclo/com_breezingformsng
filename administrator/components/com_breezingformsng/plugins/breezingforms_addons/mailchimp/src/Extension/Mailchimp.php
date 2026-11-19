<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Vcmb\Plugin\BreezingformsAddons\Mailchimp\Extension;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Application\CMSApplication;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\ParameterType;
use Joomla\Event\SubscriberInterface;
use Joomla\Input\Input;
use Vcmb\Component\BreezingformsNG\Site\Event\AfterSubmitEvent;
use Vcmb\Component\BreezingformsNG\Site\Service\Integration\MailchimpClient;
use Vcmb\Component\BreezingformsNG\Site\Service\Notification\MailSender;
use Joomla\CMS\Mail\MailerFactoryInterface;

/** Sends configured form submissions to Mailchimp after the component accepts them. */
final class Mailchimp extends CMSPlugin implements SubscriberInterface
{
    use DatabaseAwareTrait;

    private const DATA_NAME = 1;
    private const DATA_VALUE = 4;

    protected $autoloadLanguage = true;

    /** @return array<string, string> */
    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [AfterSubmitEvent::NAME => 'onAfterSubmit'];
    }

    public function onAfterSubmit(AfterSubmitEvent $event): void
    {
        $form = $this->loadForm($event->getFormId());
        if (
            $form === null
            || trim((string) ($form['mailchimp_email_field'] ?? '')) === ''
            || trim((string) ($form['mailchimp_api_key'] ?? '')) === ''
            || trim((string) ($form['mailchimp_list_id'] ?? '')) === ''
            || $event->getMailData() === []
        ) {
            return;
        }

        $application = $this->getApplication();
        if (!$application instanceof CMSApplication) {
            return;
        }
        $input = $application->getInput();
        $checkboxField = trim((string) ($form['mailchimp_checkbox_field'] ?? ''));
        $unsubscribeField = trim((string) ($form['mailchimp_unsubscribe_field'] ?? ''));
        $htmlTextMobileField = trim((string) ($form['mailchimp_text_html_mobile_field'] ?? ''));
        $checked = $checkboxField === '' || $this->inputHasValue($input, $checkboxField);
        $unsubscribe = $unsubscribeField !== '' && $this->inputHasValue($input, $unsubscribeField);
        $htmlTextMobile = (string) ($form['mailchimp_default_type'] ?? 'text');

        if ($htmlTextMobileField !== '') {
            $selection = $this->inputValue($input, $htmlTextMobileField);
            if ($selection !== '') {
                $htmlTextMobile = $selection;
            }
        }

        $emailField = trim((string) $form['mailchimp_email_field']);
        $mergeVarFields = array_values(array_filter(
            explode(',', str_replace(' ', '', (string) ($form['mailchimp_mergevars'] ?? ''))),
            static fn(string $field): bool => $field !== ''
        ));
        $email = '';
        $mergeVars = [];
        foreach ($event->getMailData() as $data) {
            if (!is_array($data) || !array_key_exists(self::DATA_NAME, $data)) {
                continue;
            }

            $name = (string) $data[self::DATA_NAME];
            $value = (string) ($data[self::DATA_VALUE] ?? '');
            if ($name === $emailField) {
                $candidate = trim($value);
                $email = filter_var($candidate, FILTER_VALIDATE_EMAIL) !== false ? $candidate : '';
                continue;
            }
            if (in_array($name, $mergeVarFields, true)) {
                $mergeVars[$name] = $value;
            }
        }

        if ($email === '') {
            return;
        }

        $api = new MailchimpClient();
        $apiKey = trim((string) $form['mailchimp_api_key']);
        // nosemgrep: php.lang.security.weak-crypto.weak-crypto -- Mailchimp member identifier.
        $memberHash = md5(strtolower($email));
        $lists = array_values(array_filter(
            array_map('trim', explode(',', (string) $form['mailchimp_list_id'])),
            static fn(string $list): bool => $list !== ''
        ));

        foreach ($lists as $list) {
            $resource = 'lists/' . rawurlencode($list) . '/members/' . $memberHash;
            try {
                if ($checked) {
                    $status = (int) ($form['mailchimp_double_optin'] ?? 0) === 1 ? 'pending' : 'subscribed';
                    $api->request($apiKey, 'PUT', $resource, [
                        'email_address' => $email,
                        'merge_fields' => (object) $mergeVars,
                        'status_if_new' => $status,
                        'status' => $status,
                        'email_type' => $htmlTextMobile,
                    ]);
                } elseif ($unsubscribe) {
                    $api->request($apiKey, 'PUT', $resource, ['status' => 'unsubscribed']);
                    if ((int) ($form['mailchimp_delete_member'] ?? 0) === 1) {
                        $api->request($apiKey, 'DELETE', $resource);
                    }
                }
            } catch (\Throwable $exception) {
                $this->reportFailure($form, $email, $exception);
            }
        }
    }

    /** @return array<string, mixed>|null */
    private function loadForm(int $formId): ?array
    {
        $db = $this->getDatabase();
        $query = $db->createQuery()
            ->select('*')
            ->from($db->quoteName('#__facileforms_forms'))
            ->where($db->quoteName('id') . ' = :formId')
            ->bind(':formId', $formId, ParameterType::INTEGER);

        $form = $db->setQuery($query)->loadAssoc();

        return is_array($form) ? $form : null;
    }

    private function inputHasValue(Input $input, string $field): bool
    {
        return $this->inputValue($input, $field) !== '';
    }

    private function inputValue(Input $input, string $field): string
    {
        $value = $input->get('ff_nm_' . $field, [''], 'string');
        if (is_array($value)) {
            return trim((string) ($value[0] ?? ''));
        }

        return trim((string) $value);
    }

    /** @param array<string, mixed> $form */
    private function reportFailure(array $form, string $email, \Throwable $exception): void
    {
        if ((int) ($form['mailchimp_send_errors'] ?? 0) !== 1) {
            return;
        }

        $application = $this->getApplication();
        if (!$application instanceof CMSApplication) {
            return;
        }
        $from = trim((string) ($form['alt_mailfrom'] ?? '')) ?: (string) $application->get('mailfrom');
        $fromName = trim((string) ($form['alt_fromname'] ?? '')) ?: (string) $application->get('fromname');
        try {
            $sender = new MailSender(
                Factory::getContainer()->get(MailerFactoryInterface::class),
                $application->getConfig()
            );
            $sender->send(
                $from,
                $fromName,
                [$from],
                'MailChimp API Error',
                'Could not send data to MailChimp for email: ' . $email . "\n\nReason: " . $exception->getMessage()
            );
        } catch (\Throwable) {
            Log::add('Mailchimp error notification failed.', Log::WARNING, 'plg_breezingforms_addons_mailchimp');
        }
    }
}
