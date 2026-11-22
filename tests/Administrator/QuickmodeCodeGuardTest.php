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

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator;

use PHPUnit\Framework\TestCase;

/**
 * QuickmodeModel::save() applies FormCodeGuard to the tree of the editor: what an
 * editor without the "code" permission can change in the code of the elements,
 * the file of a payment and the script run after the submission.
 */
final class QuickmodeCodeGuardTest extends TestCase
{
    /**
     * @param array<string, mixed> $element
     * @return array<string, mixed>
     */
    private static function element(int $dbId, array $element = []): array
    {
        return ['attributes' => ['id' => 'e' . $dbId], 'properties' => $element + [
            'type' => 'element', 'dbId' => $dbId,
            'validationCondition' => 0, 'validationId' => 0, 'validationCode' => '', 'validationMessage' => '',
            'initCondition' => 0, 'initId' => 0, 'initCode' => '',
            'actionCondition' => 0, 'actionId' => 0, 'actionCode' => '',
        ]];
    }

    /**
     * @param list<array<string, mixed>> $elements
     * @param array<string, mixed>       $root
     * @return array<string, mixed>
     */
    private static function tree(array $elements, array $root = []): array
    {
        return ['attributes' => ['id' => 'root'], 'properties' => $root + ['type' => 'root', 'submittedScriptCondidtion' => -1], 'children' => $elements];
    }

    /**
     * @param array<string, mixed> $tree
     * @return array{tree: array<string, mixed>, ignored: list<string>}
     */
    private function guard(int $form, string $rights, array $tree): array
    {
        $process = proc_open(
            [PHP_BINARY, __DIR__ . '/fixtures/quickmode-code-guard.php', (string) $form, $rights, json_encode($tree, JSON_THROW_ON_ERROR)],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes
        );
        self::assertIsResource($process);
        $output = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        self::assertSame(0, proc_close($process), $error);

        $result = json_decode($output, true, 512, JSON_THROW_ON_ERROR);
        sort($result['ignored']);

        return $result;
    }

    /** The element of the stored form that has code, as the editor posts it back untouched. */
    private static function storedElement(): array
    {
        return self::element(11, [
            'initCondition' => '1', 'initCode' => "init();\r\n",
            'actionId' => 4,
            'validationCondition' => 1, 'validationId' => 9, 'validationMessage' => 'Required',
        ]);
    }

    public function testAnEditorWithoutTheCodeRightOpeningAndSavingAFormWithElementCodeChangesNothing(): void
    {
        $result = $this->guard(7, 'core.edit', self::tree([self::storedElement(), self::element(12)], [
            'submittedScriptCondidtion' => 1, 'submittedScriptCode' => 'submitted();',
        ]));

        self::assertSame([], $result['ignored']);
        $element = $result['tree']['children'][0]['properties'];
        self::assertSame("init();\n", $element['initCode']);
        self::assertSame(9, $element['validationId']);
    }

    public function testAnAbsentOrNullCodeIsNoChangeAndKeepsTheStoredOne(): void
    {
        $element = self::storedElement();
        unset($element['properties']['initCode']);
        $element['properties']['actionCode'] = null;
        $result = $this->guard(7, 'core.edit', self::tree([$element]));

        self::assertSame([], $result['ignored']);
        $kept = $result['tree']['children'][0]['properties'];
        self::assertSame("init();\n", $kept['initCode']);
        self::assertSame('', $kept['actionCode']);
    }

    public function testChangedElementCodeIsReplacedByTheStoredCodeAndReported(): void
    {
        $element = self::storedElement();
        $element['properties']['initCode'] = 'evil();';
        $element['properties']['actionCode'] = 'steal();';
        $element['properties']['validationCode'] = 'x();';
        $result = $this->guard(7, 'core.edit', self::tree([$element]));

        self::assertSame(['script1code', 'script2code', 'script3code'], $result['ignored']);
        $kept = $result['tree']['children'][0]['properties'];
        self::assertSame("init();\n", $kept['initCode']);
        self::assertSame('', $kept['actionCode']);
        self::assertSame('', $kept['validationCode']);
    }

    public function testChoosingALibraryScriptIsNotCodeAndNeedsNoCodeRight(): void
    {
        // A new element made required: the standard validation script, its condition and message.
        $new = self::element(0, ['validationCondition' => 1, 'validationId' => 9, 'validationMessage' => 'M']);
        // An existing one changes the script of its init and action.
        $changed = self::storedElement();
        $changed['properties']['initId'] = 5;
        $changed['properties']['initCondition'] = 1;
        $changed['properties']['actionId'] = 6;
        $result = $this->guard(7, 'core.edit', self::tree([$new, $changed]));

        self::assertSame([], $result['ignored']);
        $properties = $result['tree']['children'][0]['properties'];
        self::assertSame(1, $properties['validationCondition']);
        self::assertSame(9, $properties['validationId']);
        self::assertSame('M', $properties['validationMessage']);
        self::assertSame(5, $result['tree']['children'][1]['properties']['initId']);
        self::assertSame(6, $result['tree']['children'][1]['properties']['actionId']);
    }

    public function testANewElementWithoutTheCodeRightStartsWithoutCode(): void
    {
        $new = self::element(0, ['initCode' => 'evil();', 'validationCondition' => 2, 'validationCode' => 'evil();']);
        $result = $this->guard(7, 'core.edit', self::tree([$new]));

        self::assertSame(['script1code', 'script3code'], $result['ignored']);
        $kept = $result['tree']['children'][0]['properties'];
        self::assertSame('', $kept['initCode']);
        // "Custom code" without code runs nothing.
        self::assertSame(2, $kept['validationCondition']);
        self::assertSame('', $kept['validationCode']);
    }

    public function testAnElementOfAnotherFormGetsNoCodeThroughItsId(): void
    {
        $result = $this->guard(7, 'core.edit', self::tree([self::element(999, ['initCode' => 'evil();'])]));

        self::assertSame(['script1code'], $result['ignored']);
        self::assertSame('', $result['tree']['children'][0]['properties']['initCode']);
    }

    public function testTheScriptRunAfterTheSubmissionIsKeptAsStored(): void
    {
        $result = $this->guard(7, 'core.edit', self::tree([], ['submittedScriptCondidtion' => 2, 'submittedScriptCode' => 'evil();']));

        self::assertSame(['script2code'], $result['ignored']);
        // The condition alone selects nothing new: it is the user's.
        self::assertSame(2, $result['tree']['properties']['submittedScriptCondidtion']);
        self::assertSame('submitted();', $result['tree']['properties']['submittedScriptCode']);
    }

    public function testANewFormWithoutTheCodeRightStartsWithoutTheSubmittedScript(): void
    {
        // Form 0 is new: the fake database stored nothing for it, but the guard asks only when the form exists.
        $result = $this->guard(0, 'core.create', self::tree([], ['submittedScriptCondidtion' => 1, 'submittedScriptCode' => 'evil();']));

        self::assertSame(['script2code'], $result['ignored']);
        self::assertSame(1, $result['tree']['properties']['submittedScriptCondidtion']);
        self::assertSame('', $result['tree']['properties']['submittedScriptCode']);
    }

    public function testThePaymentFileCannotBeChangedWithoutTheCodeRight(): void
    {
        $changed = self::element(12, ['bfType' => 'bfStripe', 'filepath' => '/etc/passwd']);
        $new = self::element(0, ['bfType' => 'bfPayPal', 'filepath' => '/var/www/configuration.php']);
        $result = $this->guard(7, 'core.edit', self::tree([$changed, $new]));

        self::assertSame(['filepath'], $result['ignored']);
        self::assertSame('/srv/files/book.pdf', $result['tree']['children'][0]['properties']['filepath']);
        self::assertSame('', $result['tree']['children'][1]['properties']['filepath']);
    }

    public function testTheStoredPaymentFileIsNoChange(): void
    {
        $result = $this->guard(7, 'core.edit', self::tree([self::element(12, ['bfType' => 'bfStripe', 'filepath' => '/srv/files/book.pdf'])]));

        self::assertSame([], $result['ignored']);
    }

    public function testAnEditorWithTheCodeRightChangesEverything(): void
    {
        $element = self::storedElement();
        $element['properties']['initCode'] = 'new();';
        $element['properties']['filepath'] = '/srv/files/other.pdf';
        $tree = self::tree([$element], ['submittedScriptCondidtion' => 2, 'submittedScriptCode' => 'x();']);
        $result = $this->guard(7, 'core.edit,breezingformsng.code', $tree);

        self::assertSame([], $result['ignored']);
        self::assertSame($tree, $result['tree']);
    }

    private function nameRule(int $form, string $stored, string $name): string
    {
        $process = proc_open(
            [PHP_BINARY, __DIR__ . '/fixtures/quickmode-form-name.php', (string) $form, $stored, $name],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes
        );
        self::assertIsResource($process);
        $output = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        self::assertSame(0, proc_close($process), $error);

        return (string) $output;
    }

    public function testAnUnchangedLegacyNameStaysSaveable(): void
    {
        self::assertSame('ok', $this->nameRule(7, 'contact-us', 'contact-us'));
    }

    public function testANameChangedToAnInvalidOneIsRefused(): void
    {
        self::assertSame('COM_BREEZINGFORMSNG_ERROR_ENTER_NAME_CHARACTERS', $this->nameRule(7, 'contact-us', 'contact.us'));
        self::assertSame('COM_BREEZINGFORMSNG_ERROR_ENTER_NAME_CHARACTERS', $this->nameRule(7, 'contact', "abc\n"));
    }

    public function testANewFormWithAnInvalidNameIsRefusedByTheSharedRule(): void
    {
        self::assertSame('COM_BREEZINGFORMSNG_ERROR_ENTER_NAME_CHARACTERS', $this->nameRule(0, '', 'contact-us'));
        self::assertSame('COM_BREEZINGFORMSNG_ERROR_ENTER_NAME_CHARACTERS', $this->nameRule(0, '', "abc\n"));
        self::assertSame('COM_BREEZINGFORMSNG_ERROR_ENTER_NAME', $this->nameRule(0, '', ''));
        self::assertSame('ok', $this->nameRule(0, '', 'contact_us'));
    }
}
