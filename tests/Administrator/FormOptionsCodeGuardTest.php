<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator;

use PHPUnit\Framework\TestCase;

/**
 * FormOptionsModel::saveForm() applies FormCodeGuard: what a user who may edit
 * forms but not code can write, seen through the SQL the model builds.
 */
final class FormOptionsCodeGuardTest extends TestCase
{
    /**
     * @param array<string, mixed> $data
     * @return array{error: ?array{string, int}, written: array<string, string>, ignored: list<string>}
     */
    private function save(int $id, string $rights, array $data): array
    {
        $process = proc_open(
            [PHP_BINARY, __DIR__ . '/fixtures/form-options-code-guard.php', (string) $id, $rights, json_encode($data, JSON_THROW_ON_ERROR)],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes
        );
        self::assertIsResource($process);
        $output = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        self::assertSame(0, proc_close($process), $error);

        return json_decode($output, true, 512, JSON_THROW_ON_ERROR);
    }

    public function testAnEditorWithoutTheCodeRightCannotChangeTheCodeOfAnExistingForm(): void
    {
        $result = $this->save(7, 'core.edit', [
            'title' => 'T',
            'piece1code' => 'system("id");',
            'script2code' => 'evil();',
            'piece3code' => '',
        ]);

        self::assertNull($result['error']);
        self::assertSame('T', $result['written']['title']);
        foreach (['script1code', 'script2code', 'piece1code', 'piece2code', 'piece3code', 'piece4code'] as $column) {
            self::assertArrayNotHasKey($column, $result['written'], $column . ' must be left as stored');
        }
        self::assertSame(['script2code', 'piece1code'], $result['ignored']);
    }

    public function testAnEditorWithoutTheCodeRightCanStillChooseWhichLibraryScriptOrPieceRuns(): void
    {
        $result = $this->save(7, 'core.edit', ['title' => 'T', 'script1id' => '4', 'piece2id' => '12', 'script2cond' => '2', 'piece2code' => 'evil();']);

        self::assertNull($result['error']);
        self::assertSame('4', $result['written']['script1id']);
        self::assertSame('12', $result['written']['piece2id']);
        self::assertSame('2', $result['written']['script2cond']);
        self::assertArrayNotHasKey('piece2code', $result['written']);
        self::assertSame(['piece2code'], $result['ignored']);
    }

    public function testAnEditorWithTheCodeRightChangesTheCodeAndThePiece(): void
    {
        $result = $this->save(7, 'core.edit,breezingformsng.code', ['title' => 'T', 'piece2id' => '12']);

        self::assertSame('12', $result['written']['piece2id']);
        self::assertSame([], $result['ignored']);
    }

    public function testAnEditorWithTheCodeRightChangesTheCode(): void
    {
        $result = $this->save(7, 'core.edit,breezingformsng.code', ['title' => 'T', 'piece1code' => 'echo 1;']);

        self::assertNull($result['error']);
        self::assertSame('echo 1;', $result['written']['piece1code']);
        self::assertSame([], $result['ignored']);
    }

    public function testANewFormCreatedWithoutTheCodeRightStartsWithoutAnyCode(): void
    {
        $result = $this->save(0, 'core.create', ['title' => 'T', 'piece2code' => 'evil();', 'script1code' => 'x();']);

        self::assertNull($result['error']);
        foreach (['script1code', 'script2code', 'piece1code', 'piece2code', 'piece3code', 'piece4code'] as $column) {
            self::assertSame('', $result['written'][$column], $column);
        }
        self::assertSame(['script1code', 'piece2code'], $result['ignored']);
    }

    public function testANewFormKeepsItsCodeWhenTheUserMayEditCode(): void
    {
        $result = $this->save(0, 'core.create,breezingformsng.code', ['title' => 'T', 'piece2code' => 'echo 2;']);

        self::assertSame('echo 2;', $result['written']['piece2code']);
    }

    public function testSavingWithoutTheEditRightIsRefusedBeforeAnythingIsWritten(): void
    {
        $result = $this->save(7, 'breezingformsng.code', ['title' => 'T', 'piece1code' => 'echo 1;']);

        self::assertSame('Joomla\\CMS\\Access\\Exception\\NotAllowed', $result['error'][0]);
        self::assertSame(403, $result['error'][1]);
        self::assertSame([], $result['written']);
    }

    public function testPublishingIsDroppedWithoutTheStateRight(): void
    {
        $result = $this->save(7, 'core.edit', ['title' => 'T', 'published' => 1]);

        self::assertArrayNotHasKey('published', $result['written']);
    }
}
