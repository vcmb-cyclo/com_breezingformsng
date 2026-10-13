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

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Submission;

use PHPUnit\Framework\TestCase;

final class SubmissionEngineArchitectureTest extends TestCase
{
    public function testFileSubmissionDataUsesTheProcessorSalesforceDataBuffer(): void
    {
        $source = file_get_contents(
            __DIR__ . '/../../../../components/com_breezingformsng/src/Service/Submission/SubmissionEngine.php'
        );

        self::assertIsString($source);
        self::assertStringNotContainsString('->sfadata', $source);
        self::assertStringContainsString('$this->processor->sfdata[] = array(', $source);
    }

    public function testDoubleOptInUsesTheConcreteJoomlaMailerContract(): void
    {
        $source = file_get_contents(
            __DIR__ . '/../../../../components/com_breezingformsng/src/Service/Submission/SubmissionEngine.php'
        );

        self::assertIsString($source);
        self::assertStringContainsString('use Joomla\\CMS\\Mail\\Mail;', $source);
        self::assertStringContainsString('/** @var Mail $mailer */', $source);
        self::assertStringContainsString('$mailer->isHtml(true);', $source);
    }

    public function testSubmissionFlowContainsNoDisabledLegacyDebugOrPaymentModalBranches(): void
    {
        $submissionSource = file_get_contents(
            __DIR__ . '/../../../../components/com_breezingformsng/src/Service/Submission/SubmissionEngine.php'
        );
        $facadeSource = file_get_contents(
            __DIR__ . '/../../../../components/com_breezingformsng/src/Support/processor_facade.php'
        );
        $runtimeSource = file_get_contents(
            __DIR__ . '/../../../../components/com_breezingformsng/src/Service/Runtime/CodeToolsRuntime.php'
        );

        self::assertIsString($submissionSource);
        self::assertIsString($facadeSource);
        self::assertIsString($runtimeSource);
        self::assertStringNotContainsString('$j15', $submissionSource);
        self::assertStringNotContainsString('SqueezeBox.loadModal', $submissionSource);
        self::assertStringNotContainsString('$halt', $submissionSource);
        self::assertStringNotContainsString('_FF_DEBUG_', $facadeSource . $runtimeSource);
    }

    public function testServerRecaptchaVerificationIsInjectedFromItsDedicatedService(): void
    {
        $submissionSource = file_get_contents(
            __DIR__ . '/../../../../components/com_breezingformsng/src/Service/Submission/SubmissionEngine.php'
        );
        $facadeSource = file_get_contents(
            __DIR__ . '/../../../../components/com_breezingformsng/src/Support/processor_facade.php'
        );

        self::assertIsString($submissionSource);
        self::assertIsString($facadeSource);
        self::assertStringContainsString('private readonly RecaptchaVerifier $recaptchaVerifier', $submissionSource);
        self::assertStringContainsString('$this->recaptchaVerifier->verify(', $submissionSource);
        self::assertStringNotContainsString('new RecaptchaVerifier())->verify(', $submissionSource);
        self::assertStringContainsString('new RecaptchaVerifier()', $facadeSource);
        self::assertStringNotContainsString('google.com/recaptcha', $submissionSource);
    }

    public function testUploadFacadeDelegatesStorageAndStatusMappingToSubmissionEngine(): void
    {
        $submissionSource = file_get_contents(
            __DIR__ . '/../../../../components/com_breezingformsng/src/Service/Submission/SubmissionEngine.php'
        );
        $facadeSource = file_get_contents(
            __DIR__ . '/../../../../components/com_breezingformsng/src/Support/processor_facade.php'
        );

        self::assertIsString($submissionSource);
        self::assertIsString($facadeSource);
        self::assertStringContainsString('public function saveUpload(', $submissionSource);
        self::assertStringContainsString('$this->uploadRuntime()->store(', $submissionSource);
        self::assertStringContainsString('return $this->submissionEngine()->saveUpload(', $facadeSource);
        self::assertStringNotContainsString('UploadError::', $facadeSource);
    }

    public function testTimingFacadeDelegatesToSubmissionEngine(): void
    {
        $submissionSource = file_get_contents(
            __DIR__ . '/../../../../components/com_breezingformsng/src/Service/Submission/SubmissionEngine.php'
        );
        $facadeSource = file_get_contents(
            __DIR__ . '/../../../../components/com_breezingformsng/src/Support/processor_facade.php'
        );

        self::assertIsString($submissionSource);
        self::assertIsString($facadeSource);
        self::assertStringContainsString('public function measureTime(): float', $submissionSource);
        self::assertStringContainsString('return $this->submissionEngine()->measureTime();', $facadeSource);
        self::assertStringNotContainsString('microtime()', $facadeSource);
    }

    public function testSubmissionPipelineUsesItsOwnUploadAndCollectionOperations(): void
    {
        $source = file_get_contents(
            __DIR__ . '/../../../../components/com_breezingformsng/src/Service/Submission/SubmissionEngine.php'
        );

        self::assertIsString($source);
        self::assertStringContainsString('$this->saveUpload(', $source);
        self::assertStringContainsString('$this->collectSubmitdata(', $source);
        self::assertStringNotContainsString('$this->processor->saveUpload(', $source);
        self::assertStringNotContainsString('$this->processor->collectSubmitdata(', $source);
    }

    public function testSubmissionUploadPipelineUsesUploadRuntimeDirectly(): void
    {
        $source = file_get_contents(
            __DIR__ . '/../../../../components/com_breezingformsng/src/Service/Submission/SubmissionEngine.php'
        );

        self::assertIsString($source);
        self::assertStringContainsString('$this->uploadRuntime()->resizeFile(', $source);
        self::assertStringContainsString('$this->uploadRuntime()->findQuickModeElement(', $source);
        self::assertStringNotContainsString('$this->processor->resizeFile(', $source);
        self::assertStringNotContainsString('$this->processor->findQuickModeElement(', $source);
    }

    public function testSubmissionOrchestrationUsesInjectedServiceEngines(): void
    {
        $source = file_get_contents(
            __DIR__ . '/../../../../components/com_breezingformsng/src/Service/Submission/SubmissionEngine.php'
        );

        self::assertIsString($source);
        foreach (['ScriptingEngine', 'ExportEngine', 'NotificationEngine'] as $service) {
            self::assertStringContainsString('private readonly ' . $service . ' $', $source);
        }

        foreach ([
            '$this->scriptingEngine->execPiece(',
            '$this->exportEngine->logToDatabase(',
            '$this->exportEngine->exppdf(',
            '$this->exportEngine->expcsv(',
            '$this->exportEngine->expxml(',
            '$this->exportEngine->random_str(',
            '$this->notificationEngine->sendEmailNotification(',
            '$this->notificationEngine->sendMailbackNotification(',
            '$this->notificationEngine->sendMailChimpNotification(',
            '$this->notificationEngine->sendSalesforceNotification(',
        ] as $call) {
            self::assertStringContainsString($call, $source);
        }

        foreach ([
            'execPiece',
            'logToDatabase',
            'exppdf',
            'expcsv',
            'expxml',
            'random_str',
            'sendEmailNotification',
            'sendMailbackNotification',
            'sendMailChimpNotification',
            'sendSalesforceNotification',
        ] as $method) {
            self::assertStringNotContainsString('$this->processor->' . $method . '(', $source);
        }
    }

    public function testFacadeInjectsSharedEnginesIntoSubmissionOrchestration(): void
    {
        $source = file_get_contents(
            __DIR__ . '/../../../../components/com_breezingformsng/src/Support/processor_facade.php'
        );

        self::assertIsString($source);
        self::assertStringContainsString('$this->scriptingEngine(),', $source);
        self::assertStringContainsString('$this->exportEngine(),', $source);
        self::assertStringContainsString('$this->notificationEngine()', $source);
    }
}
