<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator\Plugin;

use PHPUnit\Framework\TestCase;

final class SalesforcePluginArchitectureTest extends TestCase
{
    private const ROOT = __DIR__ . '/../../..';
    private const MANIFEST = self::ROOT
        . '/administrator/components/com_breezingformsng/plugins/breezingforms_addons/salesforce/salesforce.xml';
    private const PLUGIN = self::ROOT
        . '/administrator/components/com_breezingformsng/plugins/breezingforms_addons/salesforce/src/'
        . 'Extension/Salesforce.php';
    private const HANDLER = self::ROOT
        . '/administrator/components/com_breezingformsng/plugins/breezingforms_addons/salesforce/src/Service/'
        . 'SalesforceSubmissionHandler.php';
    private const NOTIFICATION = self::ROOT
        . '/components/com_breezingformsng/src/Service/Notification/NotificationEngine.php';
    private const EVENT = self::ROOT . '/components/com_breezingformsng/src/Event/AfterSubmitEvent.php';

    public function testSalesforceIsInstalledAsASeparateAddonPlugin(): void
    {
        $manifest = (string) file_get_contents(self::MANIFEST);
        $installer = (string) file_get_contents(self::ROOT . '/script.php');

        self::assertStringContainsString('group="breezingforms_addons"', $manifest);
        self::assertStringContainsString('<folder plugin="salesforce">services</folder>', $manifest);
        self::assertStringContainsString("\$plugins['breezingforms_addons']", $installer);
        self::assertStringContainsString("'salesforce'", $installer);
    }

    public function testSalesforceConsumesTheSubmissionEventInsteadOfTheNotificationEngine(): void
    {
        $plugin = (string) file_get_contents(self::PLUGIN);
        $handler = (string) file_get_contents(self::HANDLER);
        $notification = (string) file_get_contents(self::NOTIFICATION);

        self::assertStringContainsString('AfterSubmitEvent::NAME', $plugin);
        self::assertStringContainsString('getSalesforceData()', $plugin);
        self::assertStringContainsString('SalesforceSubmissionHandler', $plugin);
        self::assertStringContainsString('new SalesforceClient()', $handler);
        self::assertStringNotContainsString('Salesforce', $notification);
    }

    public function testSubmissionEventExposesSalesforceDataForAddonConsumers(): void
    {
        $event = (string) file_get_contents(self::EVENT);

        self::assertStringContainsString("'salesforceData' => \$salesforceData", $event);
        self::assertStringContainsString('function getSalesforceData()', $event);
    }
}
