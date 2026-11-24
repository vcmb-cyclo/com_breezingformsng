<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Notification;

use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Site\Service\Notification\NotificationRecipients;

if (!defined('_JEXEC')) {
    define('_JEXEC', 1);
}

final class NotificationRecipientsTest extends TestCase
{
    public function testAddressesAndFieldPlaceholdersAreKeptInOrder(): void
    {
        $parsed = NotificationRecipients::parse('a@example.org; {email:name} ;b@example.org');

        self::assertSame(['a@example.org', '{email:name}', 'b@example.org'], $parsed['recipients']);
        self::assertSame('', $parsed['sender']);
    }

    public function testTheSenderEntryIsTheSenderNotARecipient(): void
    {
        $parsed = NotificationRecipients::parse('a@example.org;sender:{email}');

        self::assertSame(['a@example.org'], $parsed['recipients']);
        self::assertSame('{email}', $parsed['sender']);
    }

    public function testTheTextEarlierVersionsOfferedAsAnAddressIsNoRecipient(): void
    {
        self::assertSame([], NotificationRecipients::parse('Enter your email address here')['recipients']);
        self::assertSame(
            ['a@example.org'],
            NotificationRecipients::parse('Enter your email address here; a@example.org')['recipients']
        );
    }

    public function testNothingConfiguredMeansNoRecipient(): void
    {
        self::assertSame([], NotificationRecipients::parse('')['recipients']);
        self::assertSame([], NotificationRecipients::parse(' ; ; ')['recipients']);
    }
}
