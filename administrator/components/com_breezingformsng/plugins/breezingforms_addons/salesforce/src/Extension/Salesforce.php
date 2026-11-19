<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Vcmb\Plugin\BreezingformsAddons\Salesforce\Extension;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\ParameterType;
use Joomla\Event\SubscriberInterface;
use Vcmb\Component\BreezingformsNG\Site\Event\AfterSubmitEvent;
use Vcmb\Plugin\BreezingformsAddons\Salesforce\Service\SalesforceSubmissionHandler;

/** Sends configured form submissions to Salesforce after the component accepts them. */
final class Salesforce extends CMSPlugin implements SubscriberInterface
{
    use DatabaseAwareTrait;

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
        if ($form === null || (int) ($form['salesforce_enabled'] ?? 0) !== 1) {
            return;
        }

        (new SalesforceSubmissionHandler())->handle($form, $event->getSalesforceData());
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
}
