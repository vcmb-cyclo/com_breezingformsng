<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator\Plugin;

use PHPUnit\Framework\TestCase;

final class MailchimpPluginArchitectureTest extends TestCase
{
    private const ROOT = __DIR__ . '/../../..';
    private const MANIFEST = self::ROOT
        . '/administrator/components/com_breezingformsng/plugins/breezingforms_addons/mailchimp/mailchimp.xml';
    private const PLUGIN = self::ROOT
        . '/administrator/components/com_breezingformsng/plugins/breezingforms_addons/mailchimp/src/'
        . 'Extension/Mailchimp.php';
    private const NOTIFICATION = self::ROOT
        . '/components/com_breezingformsng/src/Service/Notification/NotificationEngine.php';
    private const EVENT = self::ROOT . '/components/com_breezingformsng/src/Event/AfterSubmitEvent.php';

    public function testMailchimpIsInstalledAsASeparateAddonPlugin(): void
    {
        $manifest = (string) file_get_contents(self::MANIFEST);
        $installer = (string) file_get_contents(self::ROOT . '/script.php');

        self::assertStringContainsString('group="breezingforms_addons"', $manifest);
        self::assertStringContainsString('<folder plugin="mailchimp">services</folder>', $manifest);
        self::assertStringContainsString("\$plugins['breezingforms_addons']", $installer);
        self::assertStringContainsString("'mailchimp'", $installer);
    }

    public function testMailchimpConsumesTheSubmissionEventInsteadOfTheNotificationEngine(): void
    {
        $plugin = (string) file_get_contents(self::PLUGIN);
        $notification = (string) file_get_contents(self::NOTIFICATION);

        self::assertStringContainsString('AfterSubmitEvent::NAME', $plugin);
        self::assertStringContainsString('getMailData()', $plugin);
        self::assertStringContainsString('new MailchimpClient()', $plugin);
        self::assertStringNotContainsString('Mailchimp', $notification);
    }

    public function testSubmissionEventExposesMailDataForAddonConsumers(): void
    {
        $event = (string) file_get_contents(self::EVENT);

        self::assertStringContainsString("'mailData' => \$mailData", $event);
        self::assertStringContainsString('function getMailData()', $event);
    }
}
