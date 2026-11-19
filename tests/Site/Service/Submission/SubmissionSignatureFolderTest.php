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

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Submission;

use PHPUnit\Framework\TestCase;

/**
 * submit() cannot run without a Joomla runtime: the source shows that the signatures folder is
 * protected before a signature is written (SEC-R2-009).
 */
final class SubmissionSignatureFolderTest extends TestCase
{
    public function testTheSignaturesFolderIsProtectedBeforeASignatureIsWritten(): void
    {
        $source = (string) file_get_contents(
            dirname(__DIR__, 4) . '/components/com_breezingformsng/src/Service/Submission/SubmissionEngine.php'
        );

        $protect = strpos($source, "UploadDirectoryProtection::ensure(JPATH_SITE . '/media/breezingforms/signatures', true);");
        $write = strpos($source, 'File::write($sig_file, $sig_decoded);');

        self::assertNotFalse($protect);
        self::assertNotFalse($write);
        self::assertLessThan($write, $protect);
    }
}
