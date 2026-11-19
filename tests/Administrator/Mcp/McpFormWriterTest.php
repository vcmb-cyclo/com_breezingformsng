<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator\Mcp;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Administrator\Mcp\ToolException;

require_once __DIR__ . '/McpWriterDoubles.php';

/**
 * Behaviour of the MCP write operations on doubles of the models and a fake
 * database: what each one refuses, and what it hands to the models.
 */
#[AllowMockObjectsWithoutExpectations]
final class McpFormWriterTest extends TestCase
{
    use BuildsFormWriter;

    protected function setUp(): void
    {
        $this->buildWriter();
    }

    private function expectRefusal(string $message): void
    {
        $this->expectException(ToolException::class);
        $this->expectExceptionMessage($message);
    }

    // --- create ---

    public function testCreateIsRefusedWithoutTheCreateRight(): void
    {
        $this->quickmode->expects(self::never())->method('save');

        $this->expectRefusal('not allowed to create');
        $this->writer->create(['name' => 'a', 'title' => 'A'], McpTestDoubles::user(['core.manage', 'core.edit']));
    }

    public function testCreateRefusesAnEmptyNameOrTitle(): void
    {
        $this->quickmode->expects(self::never())->method('save');

        $this->expectRefusal('must not be empty');
        $this->writer->create(['name' => '  ', 'title' => 'A'], McpTestDoubles::user(['core.create']));
    }

    /** @return iterable<string, array{list<array<string, mixed>>, string}> */
    public static function badFieldsProvider(): iterable
    {
        yield 'name starting with a digit' => [[['type' => 'text', 'name' => '1bad', 'label' => 'L']], 'Field names must start'];
        yield 'unknown type' => [[['type' => 'file', 'name' => 'up', 'label' => 'L']], 'Supported field types'];
        yield 'markup in the label' => [[['type' => 'text', 'name' => 'a', 'label' => '<b>L</b>']], 'plain text'];
        yield 'duplicate names' => [[
            ['type' => 'text', 'name' => 'a', 'label' => 'L'],
            ['type' => 'textarea', 'name' => 'A', 'label' => 'M'],
        ], 'unique'];
        yield 'a choice field without choices' => [[['type' => 'select', 'name' => 'c', 'label' => 'L']], 'at least one choice'];
        yield 'a submit control name' => [[['type' => 'text', 'name' => 'bfFakeName', 'label' => 'L']], 'reserved'];
    }

    /** @param list<array<string, mixed>> $fields */
    #[DataProvider('badFieldsProvider')]
    public function testCreateRefusesInvalidFieldsBeforeTouchingTheDatabase(array $fields, string $message): void
    {
        $this->quickmode->expects(self::never())->method('save');

        try {
            $this->writer->create(['name' => 'a', 'title' => 'A', 'fields' => $fields], McpTestDoubles::user(['core.create']));
            self::fail('The invalid field was accepted.');
        } catch (ToolException $e) {
            self::assertStringContainsString($message, $e->getMessage());
        }
        self::assertSame([], $this->db->log, 'No transaction may start for an invalid request.');
    }

    public function testCreateSavesAnUnpublishedFormWithItsFieldsInOneTransaction(): void
    {
        $saved = [];
        $this->quickmode->expects(self::once())->method('save')->willReturnCallback(
            function (int $id, array $tree, int $published, bool $flag) use (&$saved): int {
                $saved = [$id, $tree, $published];

                return 42;
            }
        );

        $result = $this->writer->create([
            'name' => ' contact ',
            'title' => 'Contact us',
            'fields' => [['type' => 'text', 'name' => 'email', 'label' => 'Email'], ['type' => 'textarea', 'name' => 'msg', 'label' => 'Message']],
        ], McpTestDoubles::user(['core.create']));

        self::assertSame(42, $result['id']);
        self::assertSame('contact', $result['name']);
        self::assertFalse($result['published']);
        self::assertSame(2, $result['field_count']);
        self::assertSame(0, $saved[0], 'A new form has no id yet.');
        self::assertSame(0, $saved[2], 'A form created through MCP is not published.');
        self::assertSame('contact', $saved[1]['properties']['name']);
        $names = array_map(static fn (array $node): string => $node['properties']['bfName'], $saved[1]['children'][0]['children']);
        self::assertSame(['email', 'msg'], $names);
        self::assertSame('start', $this->db->log[0]);
        self::assertContains('commit', $this->db->log);
        self::assertNotContains('rollback', $this->db->log);
    }

    public function testCreateRollsBackAndRethrowsWhenTheSaveFails(): void
    {
        $this->quickmode->method('save')->willThrowException(new \RuntimeException('disk full'));

        try {
            $this->writer->create(['name' => 'a', 'title' => 'A'], McpTestDoubles::user(['core.create']));
            self::fail('The failure was swallowed.');
        } catch (\RuntimeException $e) {
            self::assertSame('disk full', $e->getMessage());
        }
        self::assertContains('rollback', $this->db->log);
        self::assertNotContains('commit', $this->db->log);
    }

    // --- update ---

    public function testUpdateIsRefusedWithoutTheEditRight(): void
    {
        $this->options->expects(self::never())->method('getForm');
        $this->options->expects(self::never())->method('saveForm');

        $this->expectRefusal('not allowed to edit');
        $this->writer->update(['id' => 7, 'title' => 'X'], McpTestDoubles::user(['core.manage', 'core.create']));
    }

    /** @return iterable<string, array{array<string, mixed>, array<string, mixed>, list<string>, string}> */
    public static function updateRefusalsProvider(): iterable
    {
        yield 'a missing form' => [['title' => 'X'], ['published' => null], ['core.edit'], 'not found'];
        yield 'a trashed form' => [['title' => 'X'], ['published' => -2], ['core.edit'], 'not found'];
        yield 'an empty title' => [['title' => ' '], [], ['core.edit'], 'must not be empty'];
        yield 'an invalid recipient' => [['notification_recipient' => 'not-an-email'], [], ['core.edit'], 'one valid email'];
        yield 'notifications without a recipient' => [['email_notifications' => true], [], ['core.edit'], 'valid notification recipient'];
        yield 'publishing without the state right' => [['published' => true], [], ['core.edit'], 'not allowed to publish'];
        yield 'a percent width above 100' => [['widthmode' => 1, 'width' => 150], [], ['core.edit'], 'width must be between 1 and 100'];
        yield 'a pixel width below 100' => [['width' => 50], [], ['core.edit'], 'width must be between 100 and 2000'];
        yield 'no change at all' => [[], [], ['core.edit'], 'at least one'];
        yield 'a form checked out' => [['title' => 'X'], ['checked_out' => 5], ['core.edit'], 'checked out'];
    }

    /**
     * @param array<string, mixed> $arguments
     * @param array<string, mixed> $form
     * @param list<string>         $rights
     */
    #[DataProvider('updateRefusalsProvider')]
    public function testUpdateRefusesWithoutSavingAnything(array $arguments, array $form, array $rights, string $message): void
    {
        $missing = array_key_exists('published', $form) && $form['published'] === null;
        $this->options->method('getForm')->willReturn($missing ? null : self::storedForm($form));
        $this->options->expects(self::never())->method('saveForm');
        $this->forms->expects(self::never())->method('checkout');

        $this->expectRefusal($message);
        $this->writer->update(['id' => 7] + $arguments, McpTestDoubles::user($rights));
    }

    public function testUpdateRefusesAFormLockedByAnotherUser(): void
    {
        $this->options->method('getForm')->willReturn(self::storedForm());
        $this->forms->method('checkout')->willReturn(false);
        $this->options->expects(self::never())->method('saveForm');

        $this->expectRefusal('checked out by another user');
        $this->writer->update(['id' => 7, 'title' => 'X'], McpTestDoubles::user(['core.edit']));
    }

    public function testUpdateHandsOnlyTheAllowedColumnsToTheModelAndReleasesTheForm(): void
    {
        $this->options->method('getForm')->willReturn(self::storedForm());
        $this->forms->method('checkout')->willReturn(true);
        $this->forms->expects(self::once())->method('checkin')->with(7);
        $this->quickmode->method('getTemplateCode')->willReturn('{"properties":{}}');
        $saved = null;
        $this->options->expects(self::once())->method('saveForm')->willReturnCallback(
            function (array $data) use (&$saved): int {
                $saved = $data;

                return 7;
            }
        );

        // The code columns are not part of the operation: they are not forwarded even when sent.
        $result = $this->writer->update([
            'id' => 7,
            'title' => ' New title ',
            'piece1code' => 'system("id");',
            'script2code' => 'evil();',
            'checked_out' => 99,
        ], McpTestDoubles::user(['core.edit']));

        self::assertSame(['id' => 7, 'title' => 'New title'], $saved);
        self::assertSame('New title', $result['title']);
        self::assertFalse($result['published']);
        self::assertSame(600, $result['settings']['width']);
    }

    public function testUpdatePublishesOnlyWithTheStateRight(): void
    {
        $this->options->method('getForm')->willReturn(self::storedForm());
        $this->forms->method('checkout')->willReturn(true);
        $this->quickmode->method('getTemplateCode')->willReturn('{"properties":{}}');
        $saved = [];
        $this->options->method('saveForm')->willReturnCallback(
            function (array $data) use (&$saved): int {
                $saved = $data;

                return 7;
            }
        );

        $result = $this->writer->update(['id' => 7, 'published' => true], McpTestDoubles::user(['core.edit', 'core.edit.state']));

        self::assertSame(1, $saved['published']);
        self::assertTrue($result['published']);
    }

    public function testUpdateReleasesTheFormWhenTheSaveFails(): void
    {
        $this->options->method('getForm')->willReturn(self::storedForm());
        $this->forms->method('checkout')->willReturn(true);
        $this->forms->expects(self::once())->method('checkin')->with(7);
        $this->options->method('saveForm')->willThrowException(new \RuntimeException('failed'));

        $this->expectException(\RuntimeException::class);
        $this->writer->update(['id' => 7, 'title' => 'X'], McpTestDoubles::user(['core.edit']));
    }

    // --- manageFields ---

    public function testManageFieldsIsRefusedWithoutTheEditRight(): void
    {
        $this->options->expects(self::never())->method('getForm');

        $this->expectRefusal('not allowed to edit');
        $this->writer->manageFields(['id' => 7, 'add_pages' => 1], McpTestDoubles::user(['core.manage']));
    }

    /** @return iterable<string, array{array<string, mixed>, string}> */
    public static function pageArgumentProvider(): iterable
    {
        yield 'too many pages' => [['add_pages' => 11], 'At most 10'];
        yield 'adding and removing pages together' => [['add_pages' => 1, 'remove_pages' => [1]], 'separate calls'];
        yield 'removals that are not a list' => [['remove_pages' => ['a' => 1]], 'list of at most 10'];
    }

    /** @param array<string, mixed> $arguments */
    #[DataProvider('pageArgumentProvider')]
    public function testManageFieldsRefusesBadPageArgumentsBeforeReadingTheForm(array $arguments, string $message): void
    {
        $this->options->expects(self::never())->method('getForm');

        $this->expectRefusal($message);
        $this->writer->manageFields(['id' => 7] + $arguments, McpTestDoubles::user(['core.edit']));
    }

    public function testManageFieldsRefusesAMissingOrTrashedForm(): void
    {
        $this->options->method('getForm')->willReturn(self::storedForm(['published' => -2]));

        $this->expectRefusal('not found or cannot be edited');
        $this->writer->manageFields(['id' => 7, 'add_pages' => 1], McpTestDoubles::user(['core.edit']));
    }

    public function testManageFieldsOnlyEditsQuickModeFormsItCanPreserve(): void
    {
        $this->options->method('getForm')->willReturn(self::storedForm(['package' => 'Legacy']));
        $this->quickmode->method('getTemplateCode')->willReturn(self::quickmodeTemplate());
        $this->quickmode->expects(self::never())->method('save');

        $this->expectRefusal('Only QuickMode forms');
        $this->writer->manageFields(['id' => 7, 'add_pages' => 1], McpTestDoubles::user(['core.edit']));
    }

    public function testManageFieldsRefusesWhenThereIsNothingToDo(): void
    {
        $this->options->method('getForm')->willReturn(self::storedForm());
        $this->quickmode->method('getTemplateCode')->willReturn(self::quickmodeTemplate());

        $this->expectRefusal('at least one field or page change');
        $this->writer->manageFields(['id' => 7], McpTestDoubles::user(['core.edit']));
    }

    public function testManageFieldsRefusesANameAlreadyUsedOnTheForm(): void
    {
        $this->options->method('getForm')->willReturn(self::storedForm());
        $this->quickmode->method('getTemplateCode')->willReturn(self::quickmodeTemplate());
        $this->quickmode->expects(self::never())->method('save');

        $this->expectRefusal('already exists');
        $this->writer->manageFields(
            ['id' => 7, 'add' => [['type' => 'text', 'name' => 'EMAIL', 'label' => 'Again']]],
            McpTestDoubles::user(['core.edit'])
        );
    }

    public function testManageFieldsRefusesToUpdateAFieldThatDoesNotExist(): void
    {
        $this->options->method('getForm')->willReturn(self::storedForm());
        $this->quickmode->method('getTemplateCode')->willReturn(self::quickmodeTemplate());

        $this->expectRefusal('existing field');
        $this->writer->manageFields(['id' => 7, 'update' => [['name' => 'ghost', 'label' => 'X']]], McpTestDoubles::user(['core.edit']));
    }

    public function testManageFieldsDoesNotEditAFormCheckedOutByAnotherUser(): void
    {
        $this->options->method('getForm')->willReturn(self::storedForm(['checked_out' => 3]));
        $this->quickmode->method('getTemplateCode')->willReturn(self::quickmodeTemplate());
        $this->forms->expects(self::never())->method('checkout');
        $this->quickmode->expects(self::never())->method('save');

        $this->expectRefusal('checked out');
        $this->writer->manageFields(['id' => 7, 'add_pages' => 1], McpTestDoubles::user(['core.edit']));
    }

    public function testManageFieldsRefusesAChangeThatCameInAfterTheFormWasRead(): void
    {
        $this->options->method('getForm')->willReturnOnConsecutiveCalls(
            self::storedForm(),
            self::storedForm(),
            self::storedForm(['version' => 2]),
        );
        $this->quickmode->method('getTemplateCode')->willReturn(self::quickmodeTemplate());
        $this->forms->method('checkout')->willReturn(true);
        $this->forms->expects(self::once())->method('checkin')->with(7);
        $this->quickmode->expects(self::never())->method('save');

        $this->expectRefusal('changed while this request was being prepared');
        $this->writer->manageFields(['id' => 7, 'add_pages' => 1], McpTestDoubles::user(['core.edit']));
    }

    public function testManageFieldsRefusesToRemoveAFieldOnceTheFormHasSubmissions(): void
    {
        $this->options->method('getForm')->willReturn(self::storedForm());
        $this->quickmode->method('getTemplateCode')->willReturn(self::quickmodeTemplate());
        $this->db->result = '3';
        $this->quickmode->expects(self::never())->method('save');

        $this->expectRefusal('submissions');
        $this->writer->manageFields(['id' => 7, 'remove' => ['email']], McpTestDoubles::user(['core.edit']));
    }

    // --- trash and restore ---

    /** @return iterable<string, array{list<string>}> */
    public static function insufficientTrashRights(): iterable
    {
        yield 'neither right' => [['core.manage']];
        yield 'delete only' => [['core.manage', 'core.delete']];
        yield 'state only' => [['core.manage', 'core.edit.state']];
    }

    /** @param list<string> $rights */
    #[DataProvider('insufficientTrashRights')]
    public function testTrashNeedsTheDeleteAndTheStateRights(array $rights): void
    {
        $this->forms->expects(self::never())->method('publish');

        $this->expectRefusal('not allowed to move forms to the trash');
        $this->writer->trashForm(['id' => 7], McpTestDoubles::user($rights));
    }

    public function testTrashMovesTheFormToTheTrashAndOnlyThere(): void
    {
        $this->options->method('getForm')->willReturn(self::storedForm());
        $published = [];
        $this->forms->expects(self::once())->method('publish')->willReturnCallback(
            function (&$pks, $value) use (&$published): bool {
                $published = [$pks, $value];

                return true;
            }
        );

        $result = $this->writer->trashForm(['id' => 7], McpTestDoubles::user(['core.delete', 'core.edit.state']));

        self::assertSame([[7], -2], $published);
        self::assertSame(['id' => 7, 'state' => 'trashed'], $result);
    }

    public function testTrashRefusesAMissingOrAlreadyTrashedForm(): void
    {
        $this->options->method('getForm')->willReturnOnConsecutiveCalls(null, self::storedForm(['published' => -2]));
        $this->forms->expects(self::never())->method('publish');
        $user = McpTestDoubles::user(['core.delete', 'core.edit.state']);

        foreach ([0, 1] as $attempt) {
            try {
                $this->writer->trashForm(['id' => 7], $user);
                self::fail('Attempt ' . $attempt . ' was accepted.');
            } catch (ToolException $e) {
                self::assertStringContainsString('not found or already in the trash', $e->getMessage());
            }
        }
    }

    public function testTrashRefusesACheckedOutFormAndReportsAModelFailure(): void
    {
        $this->options->method('getForm')->willReturnOnConsecutiveCalls(self::storedForm(['checked_out' => 4]), self::storedForm());
        $this->forms->expects(self::once())->method('publish')->willReturn(false);
        $user = McpTestDoubles::user(['core.delete', 'core.edit.state']);

        try {
            $this->writer->trashForm(['id' => 7], $user);
            self::fail('The checked out form was trashed.');
        } catch (ToolException $e) {
            self::assertStringContainsString('checked out', $e->getMessage());
        }

        $this->expectRefusal('could not be moved to the trash');
        $this->writer->trashForm(['id' => 7], $user);
    }

    public function testRestoreNeedsTheStateRight(): void
    {
        $this->forms->expects(self::never())->method('publish');

        $this->expectRefusal('not allowed to restore');
        $this->writer->restoreForm(['id' => 7], McpTestDoubles::user(['core.manage', 'core.edit', 'core.delete']));
    }

    public function testRestoreRefusesAFormThatIsNotInTheTrash(): void
    {
        $this->options->method('getForm')->willReturn(self::storedForm(['published' => 1]));
        $this->forms->expects(self::never())->method('publish');

        $this->expectRefusal('No trashed form');
        $this->writer->restoreForm(['id' => 7], McpTestDoubles::user(['core.edit.state']));
    }

    public function testRestoreBringsTheFormBackUnpublished(): void
    {
        $this->options->method('getForm')->willReturn(self::storedForm(['published' => -2]));
        $published = [];
        $this->forms->expects(self::once())->method('publish')->willReturnCallback(
            function (&$pks, $value) use (&$published): bool {
                $published = [$pks, $value];

                return true;
            }
        );

        $result = $this->writer->restoreForm(['id' => 7], McpTestDoubles::user(['core.edit.state']));

        self::assertSame([[7], 0], $published, 'A restored form must not be published again.');
        self::assertSame(['id' => 7, 'state' => 'unpublished'], $result);
    }

    public function testRestoreRefusesACheckedOutForm(): void
    {
        $this->options->method('getForm')->willReturn(self::storedForm(['published' => -2, 'checked_out' => 2]));
        $this->forms->expects(self::never())->method('publish');

        $this->expectRefusal('checked out');
        $this->writer->restoreForm(['id' => 7], McpTestDoubles::user(['core.edit.state']));
    }
}
