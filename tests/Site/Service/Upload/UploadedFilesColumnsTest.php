<?php

/**
 * BreezingForms NG - A Joomla Forms Application
 *
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 **/

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Upload;

use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Site\Service\Upload\UploadedFilesColumns;

final class UploadedFilesColumnsTest extends TestCase
{
    public function testJoomlaGivesOneEntryPerFileAndTheSubmissionReadsOneListPerProperty(): void
    {
        $columns = UploadedFilesColumns::fromJoomla([
            0 => ['name' => 'spec.pdf', 'type' => 'application/pdf', 'tmp_name' => '/tmp/a', 'error' => 0, 'size' => 12],
            1 => ['name' => 'plan.pdf', 'type' => 'application/pdf', 'tmp_name' => '/tmp/b', 'error' => 0, 'size' => 34],
        ]);

        self::assertSame(['spec.pdf', 'plan.pdf'], $columns['name'] ?? null);
        self::assertSame(['/tmp/a', '/tmp/b'], $columns['tmp_name'] ?? null);
        self::assertSame([12, 34], $columns['size'] ?? null);
    }

    public function testAFieldWithoutBracketsIsASingleFile(): void
    {
        $columns = UploadedFilesColumns::fromJoomla(
            ['name' => 'spec.pdf', 'type' => 'application/pdf', 'tmp_name' => '/tmp/a', 'error' => 0, 'size' => 12]
        );

        self::assertSame(['spec.pdf'], $columns['name'] ?? null);
        self::assertSame(['/tmp/a'], $columns['tmp_name'] ?? null);
    }

    public function testNoFileGivesNullAndAnEmptyChoiceKeepsItsPlace(): void
    {
        self::assertNull(UploadedFilesColumns::fromJoomla(null));
        self::assertNull(UploadedFilesColumns::fromJoomla([]));

        $columns = UploadedFilesColumns::fromJoomla([0 => ['name' => '', 'tmp_name' => '', 'error' => 4]]);
        self::assertSame([''], $columns['name'] ?? null);
        self::assertSame([4], $columns['error'] ?? null);
    }
}
