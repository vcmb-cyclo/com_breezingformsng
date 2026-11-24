<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator;

use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Administrator\Helper\FormsAdvancedOptionsHtml;

if (!defined('_JEXEC')) {
    define('_JEXEC', 1);
}

/** The count on the Email tab is the sum of the counts of its two sub-tabs. */
final class FormsAdvancedOptionsEmailCountsTest extends TestCase
{
    public function testTheEmailTabCountsTheAdministratorNotificationAndTheUserConfirmation(): void
    {
        $form = (object) [
            'emailntf' => 1, 'emailadr' => 'admin@example.org', 'custom_mail_subject' => 'New message',
            'mb_emailntf' => 1, 'mb_custom_mail_subject' => 'Thank you',
        ];

        $counts = FormsAdvancedOptionsHtml::countEntries($form);

        self::assertSame(3, $counts['email-admin']);
        self::assertSame(2, $counts['email-user']);
        self::assertSame(5, $counts['email']);
    }

    public function testASubTabWithNothingSetCountsZero(): void
    {
        $counts = FormsAdvancedOptionsHtml::countEntries((object) ['emailntf' => 2, 'emailadr' => 'a@b.c']);

        self::assertSame(2, $counts['email-admin']);
        self::assertSame(0, $counts['email-user']);
        self::assertSame(2, $counts['email']);
    }
}
