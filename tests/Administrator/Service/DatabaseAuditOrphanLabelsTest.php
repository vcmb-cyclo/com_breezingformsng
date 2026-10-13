<?php

/**
 * BreezingForms NG - A Joomla Forms Application
 *
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 **/

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator\Service;

use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Administrator\Service\DatabaseAuditService;

final class DatabaseAuditOrphanLabelsTest extends TestCase
{
    public function testElementsBorrowTheFormNameKeptByOrphanRecords(): void
    {
        $group = static fn(int $id, string $label): array => [
            'parent_id' => $id,
            'count' => 1,
            'label' => $label,
            'first' => '',
            'last' => '',
        ];

        $checks = DatabaseAuditService::labelOrphanForms([
            ['id' => 'records_without_form', 'parent' => 'form', 'groups' => [$group(12, 'contact')]],
            ['id' => 'elements_without_form', 'parent' => 'form', 'groups' => [$group(12, ''), $group(27, '')]],
            ['id' => 'subrecords_without_element', 'parent' => 'element', 'groups' => [$group(12, '')]],
        ]);

        self::assertSame('contact', $checks[1]['groups'][0]['label']);
        self::assertSame('', $checks[1]['groups'][1]['label']);
        self::assertSame('', $checks[2]['groups'][0]['label']);
    }
}
