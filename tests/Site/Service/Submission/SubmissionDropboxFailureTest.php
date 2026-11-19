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

use Joomla\Http\Http;
use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Site\Service\Integration\DropboxUploader;

if (!class_exists(Http::class)) {
    eval('namespace Joomla\\Http; class Http { public function post($url, $data, array $headers = [], $timeout = null) {} }');
}

/**
 * A Dropbox failure must never abort a submission (SEC-R2-011): the uploader reports errors by
 * exception, and the single SubmissionEngine entry point turns them into a generic log entry.
 */
final class SubmissionDropboxFailureTest extends TestCase
{
    public function testAnErrorResponseRaisesAnExceptionThatTheEngineMustCatch(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'bfdbx');
        file_put_contents($file, 'x');
        $http = new class extends Http {
            public function post($url, $data, array $headers = [], $timeout = null)
            {
                return new class {
                    public function getStatusCode(): int
                    {
                        return 401;
                    }

                    public function getBody(): string
                    {
                        return 'invalid_access_token';
                    }
                };
            }
        };

        try {
            (new DropboxUploader($http))->upload('token', '/f/a.txt', $file);
            self::fail('A non-2xx Dropbox response must raise an exception.');
        } catch (\RuntimeException $exception) {
            self::assertStringContainsString('Dropbox API error', $exception->getMessage());
        } finally {
            unlink($file);
        }
    }

    public function testEveryDropboxCallGoesThroughTheCatchingMethodWithAGenericLogMessage(): void
    {
        $source = (string) file_get_contents(
            dirname(__DIR__, 4) . '/components/com_breezingformsng/src/Service/Submission/SubmissionEngine.php'
        );

        self::assertSame(1, substr_count($source, '(new DropboxUploader())->upload('));
        self::assertMatchesRegularExpression(
            "/try \\{\\s*\\(new DropboxUploader\\(\\)\\)->upload\\(.*?\\} catch \\(\\\\Throwable\\) \\{\\s*Log::add\\('Dropbox upload failed; the submission continues without it\\.'/s",
            $source
        );
    }
}
