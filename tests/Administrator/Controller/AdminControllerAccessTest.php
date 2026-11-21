<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator\Controller;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * What the state-changing tasks of the administrator controllers (forms, records,
 * packages, integrator, records transfer and import, menus, pieces, scripts) do for a
 * request with a bad token, a user without the right, and a user with it.
 *
 * Each case runs the real controller in its own process on stand-ins for the
 * Joomla runtime (tests/Administrator/fixtures/admin-controller-acl.php): the
 * models record what they are asked, so "refused" means that no model was reached.
 */
final class AdminControllerAccessTest extends TestCase
{
    /**
     * @param array<string, mixed> $post
     * @return array{stopped: ?string, events: list<array<string, mixed>>}
     */
    private function runTask(string $controller, string $task, string $token, string $rights, array $post = []): array
    {
        $process = proc_open(
            [PHP_BINARY, dirname(__DIR__) . '/fixtures/admin-controller-acl.php', $controller, $task, $token, $rights, json_encode($post, JSON_THROW_ON_ERROR)],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes
        );
        self::assertIsResource($process);
        $output = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        self::assertSame(0, proc_close($process), $error . $output);

        // A controller that answers directly writes before the result, which follows the marker.
        $marker = strrpos($output, "\n@@RESULT@@");
        self::assertNotFalse($marker, 'No result from the fixture: ' . $error . $output);

        return json_decode(substr($output, $marker + strlen("\n@@RESULT@@")), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @param list<array<string, mixed>> $events
     * @return list<string>
     */
    private static function models(array $events): array
    {
        return array_values(array_map(static fn (array $e): string => $e['model'], array_filter($events, static fn (array $e): bool => isset($e['model']))));
    }

    /**
     * @param list<array<string, mixed>> $events
     * @return list<string>
     */
    private static function statuses(array $events): array
    {
        return array_values(array_map(static fn (array $e): string => $e['status'], array_filter($events, static fn (array $e): bool => isset($e['status']))));
    }

    /** @param list<array<string, mixed>> $events */
    private static function answer(array $events): array
    {
        foreach ($events as $event) {
            if (isset($event['answer'])) {
                return $event['answer'];
            }
        }

        return [];
    }

    // --- the token is demanded before anything else ---

    /** @return iterable<string, array{string, string, array<string, mixed>}> */
    public static function tokenGuardedTasks(): iterable
    {
        yield 'forms copy' => ['forms', 'copy', ['cid' => [3]]];
        yield 'forms delete' => ['forms', 'delete', ['cid' => [3]]];
        yield 'forms convertLegacy' => ['forms', 'convertLegacy', ['cid' => [3]]];
        yield 'forms setPublished' => ['forms', 'setPublished', ['id' => 5, 'state' => 1]];
        yield 'forms setDebug' => ['forms', 'setDebug', ['id' => 5, 'state' => 1]];
        yield 'records delete' => ['records', 'delete', ['cid' => [3]]];
        yield 'records exportCsv' => ['records', 'exportCsv', []];
        yield 'records setFlag' => ['records', 'setFlag', ['record_id' => 4, 'column' => 'viewed', 'flag' => 1]];
        yield 'records setState' => ['records', 'setState', ['record_id' => 4, 'state' => 2]];
    }

    /** @param array<string, mixed> $post */
    #[DataProvider('tokenGuardedTasks')]
    public function testAnInvalidTokenReachesNoModel(string $controller, string $task, array $post): void
    {
        // Even a user holding every right is refused without a valid token.
        $all = 'core.manage,core.create,core.edit,core.edit.state,core.delete';
        $result = $this->runTask($controller, $task, 'invalid', $all, $post);

        $tokens = array_values(array_filter($result['events'], static fn (array $e): bool => isset($e['token'])));
        self::assertNotSame([], $tokens, $task . ' did not check the token');
        self::assertFalse($tokens[0]['valid']);
        self::assertSame([], self::models($result['events']), $task . ' reached a model with an invalid token');
        self::assertNotContains('parent', array_merge(...array_map('array_keys', $result['events'])), $task . ' went on to the Joomla task');
    }

    public function testAnAjaxTaskAnswersAnInvalidTokenWith403(): void
    {
        $result = $this->runTask('records', 'setFlag', 'invalid', 'core.edit.state', ['record_id' => 4, 'column' => 'viewed']);

        self::assertSame(['403'], self::statuses($result['events']));
        self::assertSame('JINVALID_TOKEN', self::answer($result['events'])['message']);
    }

    // --- the right of the user ---

    public function testPublishingFromTheListNeedsTheStateRight(): void
    {
        $refused = $this->runTask('forms', 'setPublished', 'valid', 'core.manage,core.edit,core.create', ['id' => 5, 'state' => 1]);

        self::assertSame(['403'], self::statuses($refused['events']));
        self::assertSame('ERROR', self::answer($refused['events'])['data']['Result']);
        self::assertSame([], self::models($refused['events']));

        $allowed = $this->runTask('forms', 'setPublished', 'valid', 'core.edit.state', ['id' => 5, 'state' => 1]);

        self::assertSame(['Form::publish'], self::models($allowed['events']));
        self::assertSame([], self::statuses($allowed['events']));
        self::assertSame('OK', self::answer($allowed['events'])['data']['Result']);
    }

    public function testAGuestCannotSwitchDebugMode(): void
    {
        $result = $this->runTask('forms', 'setDebug', 'valid', 'guest', ['id' => 5, 'state' => 1]);

        self::assertSame(['403'], self::statuses($result['events']));
        self::assertSame([], self::models($result['events']));
    }

    public function testDebugModeNeedsTheEditRightNotTheStateRight(): void
    {
        $refused = $this->runTask('forms', 'setDebug', 'valid', 'core.edit.state', ['id' => 5, 'state' => 1]);

        self::assertSame(['403'], self::statuses($refused['events']));
        self::assertSame([], self::models($refused['events']));

        $allowed = $this->runTask('forms', 'setDebug', 'valid', 'core.edit', ['id' => 5, 'state' => 1]);

        self::assertSame(['FormOptions::setDebugMode'], self::models($allowed['events']));
    }

    public function testCopyingAFormNeedsTheCreateRight(): void
    {
        foreach (['', 'core.edit,core.edit.state,core.delete', 'guest'] as $rights) {
            $refused = $this->runTask('forms', 'copy', 'valid', $rights, ['cid' => [3]]);

            self::assertNotNull($refused['stopped'], 'copy was allowed with rights: ' . $rights);
            self::assertSame([], self::models($refused['events']));
        }

        $allowed = $this->runTask('forms', 'copy', 'valid', 'core.create', ['cid' => [3]]);

        self::assertNull($allowed['stopped']);
        self::assertSame(['Form::copy'], self::models($allowed['events']));
    }

    /** @return iterable<string, array{string}> */
    public static function incompleteConversionRights(): iterable
    {
        yield 'no right' => [''];
        yield 'create only' => ['core.create'];
        yield 'edit only' => ['core.edit'];
        yield 'guest' => ['guest'];
    }

    #[DataProvider('incompleteConversionRights')]
    public function testConvertingALegacyFormNeedsTheCreateAndTheEditRights(string $rights): void
    {
        $result = $this->runTask('forms', 'convertLegacy', 'valid', $rights, ['cid' => [3]]);

        self::assertSame('RuntimeException', $result['stopped']);
        self::assertSame([], self::models($result['events']));
    }

    public function testConvertingALegacyFormWorksWithBothRights(): void
    {
        $result = $this->runTask('forms', 'convertLegacy', 'valid', 'core.create,core.edit', ['cid' => [3]]);

        self::assertNull($result['stopped']);
        self::assertSame(['LegacyConversion::convert'], self::models($result['events']));
    }

    public function testARecordFlagNeedsTheStateRight(): void
    {
        $post = ['record_id' => 4, 'column' => 'viewed', 'flag' => 1];

        $refused = $this->runTask('records', 'setFlag', 'valid', 'core.manage,core.edit,core.delete', $post);
        self::assertSame(['403'], self::statuses($refused['events']));
        self::assertSame([], self::models($refused['events']));

        $allowed = $this->runTask('records', 'setFlag', 'valid', 'core.edit.state', $post);
        self::assertSame(['Record::setFlagSingle'], self::models($allowed['events']));
        self::assertSame([], self::statuses($allowed['events']));
    }

    public function testARecordFlagIsRefusedForAGuest(): void
    {
        $result = $this->runTask('records', 'setFlag', 'valid', 'guest', ['record_id' => 4, 'column' => 'viewed', 'flag' => 1]);

        self::assertSame('RuntimeException', $result['stopped']);
        self::assertSame([], self::models($result['events']));
    }

    public function testARecordFlagOnlyWritesTheColumnsOfTheAllowList(): void
    {
        $result = $this->runTask('records', 'setFlag', 'valid', 'core.edit.state', ['record_id' => 4, 'column' => 'bfrecord_name; DROP TABLE x', 'flag' => 1]);

        self::assertSame(['400'], self::statuses($result['events']));
        self::assertSame([], self::models($result['events']));
    }

    // --- the other state-changing controllers ---

    private const EVERY_RIGHT = 'core.manage,core.create,core.edit,core.edit.state,core.delete,core.admin,breezingformsng.code,breezingformsng.configuration';

    private const NOT_ALLOWED = 'Joomla\\CMS\\Access\\Exception\\NotAllowed';

    /** @return iterable<string, array{string, string, array<string, mixed>}> */
    public static function otherTokenGuardedTasks(): iterable
    {
        foreach (['export', 'installSample', 'import', 'preview', 'update', 'remove'] as $task) {
            yield 'packages ' . $task => ['packages', $task, ['package' => 'demo', 'samples' => ['contact'], 'update_select' => ['forms' => ['3']], 'update_parts' => ['forms']]];
        }

        foreach (
            ['save', 'remove', 'trash', 'setPublished', 'publish', 'unpublish', 'addItem', 'removeItem', 'saveCode', 'saveFinalizeCode', 'addCriteria',
                'removeCriteria', 'addCriteriaJoomla', 'removeCriteriaJoomla', 'addCriteriaFixed', 'removeCriteriaFixed', 'publishItem', 'unpublishItem'] as $task
        ) {
            yield 'integrator ' . $task => ['integrator', $task, ['id' => 5, 'state' => 1, 'cid' => [3], 'publish_id' => 3]];
        }

        foreach (['start', 'saveSelection', 'saveMapping', 'transfer', 'cancel'] as $task) {
            yield 'recordstransfer ' . $task => ['recordstransfer', $task, ['cid' => [3], 'target' => 2]];
        }

        foreach (['import', 'cancel'] as $task) {
            yield 'recordsimport ' . $task => ['recordsimport', $task, []];
        }

        yield 'menus copy' => ['menus', 'copy', ['cid' => [3]]];

        foreach (['pieces', 'scripts'] as $controller) {
            foreach (['delete', 'copy', 'setPublished', 'publish', 'unpublish', 'trash'] as $task) {
                yield $controller . ' ' . $task => [$controller, $task, ['cid' => [9], 'id' => 9, 'state' => 1]];
            }
        }

        yield 'piece runTest' => ['piece', 'runTest', ['id' => 5]];
        yield 'piece runTestsAjax' => ['piece', 'runTestsAjax', ['id' => 5]];
    }

    /** @param array<string, mixed> $post */
    #[DataProvider('otherTokenGuardedTasks')]
    public function testAnInvalidTokenReachesNoModelOnTheOtherControllers(string $controller, string $task, array $post): void
    {
        // Even a user holding every right is refused without a valid token.
        $result = $this->runTask($controller, $task, 'invalid', self::EVERY_RIGHT, $post);

        $tokens = array_values(array_filter($result['events'], static fn (array $e): bool => isset($e['token'])));
        self::assertNotSame([], $tokens, $controller . '::' . $task . ' did not check the token');
        self::assertFalse($tokens[0]['valid']);
        self::assertSame([], self::models($result['events']), $task . ' reached a model with an invalid token');
        self::assertNotContains('parent', array_merge(...array_map('array_keys', $result['events'])), $task . ' went on to the Joomla task');
    }

    /** @param list<array<string, mixed>> $events */
    private static function reached(array $events): bool
    {
        return self::models($events) !== [];
    }

    // packages: the Configure area right comes first, the code right only for the integrator rules

    /** @return iterable<string, array{string, array<string, mixed>}> */
    public static function packageTasks(): iterable
    {
        yield 'export' => ['export', ['package' => 'demo']];
        yield 'installSample' => ['installSample', []];
        yield 'import' => ['import', []];
        yield 'preview' => ['preview', []];
        yield 'update' => ['update', ['package' => 'demo', 'update_select' => ['forms' => ['3']], 'update_parts' => ['forms']]];
        yield 'remove' => ['remove', ['package' => 'demo']];
    }

    /** @param array<string, mixed> $post */
    #[DataProvider('packageTasks')]
    public function testPackageTasksNeedTheConfigurationRight(string $task, array $post): void
    {
        foreach (['', 'core.manage,core.admin,core.create,core.edit,core.delete,breezingformsng.code', 'guest'] as $rights) {
            $refused = $this->runTask('packages', $task, 'valid', $rights, $post);

            self::assertNotNull($refused['stopped'], $task . ' was allowed with rights: ' . $rights);
            self::assertSame([], self::models($refused['events']));
            self::assertSame([], array_filter($refused['events'], static fn (array $e): bool => isset($e['token'])), 'the token was checked for a user without the right');
        }

        $allowed = $this->runTask('packages', $task, 'valid', 'breezingformsng.configuration', $post);

        self::assertNotSame(self::NOT_ALLOWED, $allowed['stopped']);
        self::assertNotSame('RuntimeException', $allowed['stopped']);
        $tokens = array_values(array_filter($allowed['events'], static fn (array $e): bool => isset($e['token'])));
        self::assertTrue($tokens[0]['valid'] ?? false, $task . ' did not go on to the token check');
    }

    #[DataProvider('packageTasks')]
    public function testPackageTasksRefuseAnotherRightThanConfiguration(string $task, array $post): void
    {
        $refused = $this->runTask('packages', $task, 'valid', 'breezingformsng.code', $post);

        self::assertSame(self::NOT_ALLOWED, $refused['stopped']);
    }

    public function testCreatingAPackageWithTheRightSavesItsDetails(): void
    {
        $allowed = $this->runTask('packages', 'update', 'valid', 'breezingformsng.configuration', ['package' => 'demo', 'package_new' => 1, 'update_parts' => []]);

        self::assertContains('PackageTransfer::assertNewPackageId', self::models($allowed['events']));
        self::assertContains('PackageTransfer::savePackage', self::models($allowed['events']));
    }

    public function testUpdatingAPackageWithTheRightReachesTheModel(): void
    {
        $allowed = $this->runTask('packages', 'update', 'valid', 'breezingformsng.configuration', ['package' => 'demo', 'update_select' => ['forms' => ['3']], 'update_parts' => ['forms']]);

        self::assertContains('PackageTransfer::updatePackage', self::models($allowed['events']));
    }

    public function testExportWithoutANewPackageIdKeepsTheSubmittedDraft(): void
    {
        $post = [
            // The explicit flag also supports a page opened before the select option gained its sentinel value.
            'selected_package' => '',
            'package_new' => 1,
            'package' => '',
            'metadata' => ['title' => 'My package'],
            'forms' => ['3'],
            'include_integrator' => 1,
        ];

        $result = $this->runTask('packages', 'export', 'valid', 'breezingformsng.configuration,breezingformsng.code', $post);
        $states = array_values(array_filter($result['events'], static fn (array $event): bool => ($event['state'] ?? '') === 'com_breezingformsng.packages.export_draft'));
        $redirects = array_column(array_filter($result['events'], static fn (array $event): bool => isset($event['redirect'])), 'redirect');

        self::assertSame([], self::models($result['events']));
        self::assertCount(1, $states);
        self::assertTrue($states[0]['value']['new']);
        self::assertSame('My package', $states[0]['value']['metadata']['title']);
        self::assertSame([3], $states[0]['value']['select']['forms']);
        self::assertTrue($states[0]['value']['include_integrator']);
        self::assertContains('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_NEW_PACKAGE_ID_REQUIRED', array_column($result['events'], 'message'));
        self::assertStringContainsString('layout=export&package=__new__', $redirects[0] ?? '');
    }

    public function testExportWithoutChoosingAPackageDoesNotSelectNewPackage(): void
    {
        $result = $this->runTask('packages', 'export', 'valid', 'breezingformsng.configuration', [
            'selected_package' => '',
            'package' => '',
        ]);
        $states = array_values(array_filter($result['events'], static fn (array $event): bool => ($event['state'] ?? '') === 'com_breezingformsng.packages.export_draft'));
        $redirects = array_column(array_filter($result['events'], static fn (array $event): bool => isset($event['redirect'])), 'redirect');

        self::assertCount(1, $states);
        self::assertFalse($states[0]['value']['new']);
        self::assertContains('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_PACKAGE_REQUIRED', array_column($result['events'], 'message'));
        self::assertStringNotContainsString('__new__', $redirects[0] ?? '');
    }

    public function testExportingTheIntegratorNeedsTheCodeRight(): void
    {
        $post = ['package' => 'demo', 'include_integrator' => 1];

        $refused = $this->runTask('packages', 'export', 'valid', 'breezingformsng.configuration', $post);
        self::assertSame([], self::models($refused['events']), 'integrator rules were exported without the code right');
        self::assertContains('error', array_column($refused['events'], 'type'));

        $allowed = $this->runTask('packages', 'export', 'valid', 'breezingformsng.configuration,breezingformsng.code', $post);
        self::assertContains('PackageTransfer::export', self::models($allowed['events']));
    }

    public function testExportingTemplatesNeedsTheAdminRight(): void
    {
        $post = ['package' => 'demo', 'include_templates' => 1];

        $refused = $this->runTask('packages', 'export', 'valid', 'breezingformsng.configuration,breezingformsng.code', $post);
        self::assertSame([], self::models($refused['events']), 'templates (PHP) were exported without core.admin');

        $allowed = $this->runTask('packages', 'export', 'valid', 'breezingformsng.configuration,core.admin', $post);
        self::assertContains('PackageTransfer::export', self::models($allowed['events']));
    }

    public function testInstallingASampleNeedsTheAdminRight(): void
    {
        $refused = $this->runTask('packages', 'installSample', 'valid', 'breezingformsng.configuration', ['samples' => ['contact']]);

        self::assertSame([], self::models($refused['events']));
        self::assertContains('COM_BREEZINGFORMSNG_TOOLS_SAMPLE_NEEDS_ADMIN', array_column($refused['events'], 'message'));
    }

    public function testThePackagePreviewAnswersAnInvalidTokenWithAnError(): void
    {
        $result = $this->runTask('packages', 'preview', 'invalid', 'breezingformsng.configuration', []);

        self::assertSame('JINVALID_TOKEN', self::answer($result['events'])['message']);
        self::assertTrue(self::answer($result['events'])['error']);
        self::assertSame([], self::models($result['events']));
    }

    // integrator: every task needs the code right; some also need a right of the component

    /** @return iterable<string, array{string}> */
    public static function integratorTasks(): iterable
    {
        foreach (
            ['save', 'remove', 'trash', 'setPublished', 'publish', 'unpublish', 'addItem', 'removeItem', 'saveCode', 'saveFinalizeCode', 'addCriteria',
                'removeCriteria', 'addCriteriaJoomla', 'removeCriteriaJoomla', 'addCriteriaFixed', 'removeCriteriaFixed', 'publishItem', 'unpublishItem'] as $task
        ) {
            yield $task => [$task];
        }
    }

    #[DataProvider('integratorTasks')]
    public function testIntegratorTasksNeedTheCodeRight(string $task): void
    {
        $post = ['id' => 5, 'state' => 1, 'cid' => [3], 'publish_id' => 3];
        $others = 'core.manage,core.create,core.edit,core.edit.state,core.delete,core.admin,breezingformsng.configuration';

        foreach ([$others, 'guest'] as $rights) {
            $refused = $this->runTask('integrator', $task, 'valid', $rights, $post);

            self::assertNotNull($refused['stopped'], $task . ' was allowed with rights: ' . $rights);
            self::assertSame([], self::models($refused['events']));
            self::assertSame([], array_filter($refused['events'], static fn (array $e): bool => isset($e['token'])));
        }

        $allowed = $this->runTask('integrator', $task, 'valid', self::EVERY_RIGHT, $post);

        self::assertTrue(self::reached($allowed['events']), $task . ' did not reach the model with the right');
        self::assertContains($allowed['stopped'], [null, 'Fixture\\Closed']);
    }

    /** @return iterable<string, array{string, string}> */
    public static function integratorTasksWithAComponentRight(): iterable
    {
        yield 'remove' => ['remove', 'core.delete'];
        yield 'trash' => ['trash', 'core.edit.state'];
        yield 'setPublished' => ['setPublished', 'core.edit.state'];
        yield 'publish' => ['publish', 'core.edit.state'];
        yield 'unpublish' => ['unpublish', 'core.edit.state'];
    }

    #[DataProvider('integratorTasksWithAComponentRight')]
    public function testIntegratorStateTasksNeedTheirComponentRightAsWell(string $task, string $right): void
    {
        $post = ['id' => 5, 'state' => 1, 'cid' => [3], 'publish_id' => 3];

        $refused = $this->runTask('integrator', $task, 'valid', 'breezingformsng.code,core.manage,core.create,core.edit', $post);
        self::assertSame(self::NOT_ALLOWED, $refused['stopped']);
        self::assertSame([], array_values(array_filter(self::models($refused['events']), static fn (string $m): bool => $m !== 'Integrator')), 'the model was used without ' . $right);

        $allowed = $this->runTask('integrator', $task, 'valid', 'breezingformsng.code,' . $right, $post);
        self::assertNotSame(self::NOT_ALLOWED, $allowed['stopped']);
        self::assertTrue(self::reached($allowed['events']));
    }

    // records transfer and records import: the manage and create rights, from execute()

    /** @return iterable<string, array{string, string, string}> */
    public static function recordsMovingTasks(): iterable
    {
        foreach (['start', 'saveSelection', 'saveMapping', 'transfer', 'cancel'] as $task) {
            yield 'recordstransfer ' . $task => ['recordstransfer', $task, 'Recordstransfer'];
        }

        yield 'recordsimport import' => ['recordsimport', 'import', 'Recordsimport'];
        yield 'recordsimport cancel' => ['recordsimport', 'cancel', ''];
    }

    #[DataProvider('recordsMovingTasks')]
    public function testRecordsTransferAndImportNeedTheManageAndCreateRights(string $controller, string $task, string $model): void
    {
        $post = ['cid' => [3], 'target' => 2];

        foreach (['', 'core.manage', 'core.create', 'core.edit,core.edit.state,core.delete,breezingformsng.code', 'guest'] as $rights) {
            $refused = $this->runTask($controller, $task, 'valid', $rights, $post);

            self::assertSame('RuntimeException', $refused['stopped'], $task . ' was allowed with rights: ' . $rights);
            self::assertSame([], self::models($refused['events']));
            self::assertSame([], array_filter($refused['events'], static fn (array $e): bool => isset($e['token'])));
        }

        $allowed = $this->runTask($controller, $task, 'valid', 'core.manage,core.create', $post);

        self::assertNull($allowed['stopped']);
        $tokens = array_values(array_filter($allowed['events'], static fn (array $e): bool => isset($e['token'])));
        self::assertTrue($tokens[0]['valid'] ?? false, 'the token was not checked');

        if ($model !== '') {
            self::assertContains($model, self::models($allowed['events']));
        }
    }

    // menus

    public function testCopyingAMenuNeedsTheCreateRight(): void
    {
        foreach (['', 'core.manage,core.edit,core.edit.state,core.delete', 'guest'] as $rights) {
            $refused = $this->runTask('menus', 'copy', 'valid', $rights, ['cid' => [3]]);

            self::assertNotNull($refused['stopped'], 'copy was allowed with rights: ' . $rights);
            self::assertSame([], self::models($refused['events']));
        }

        $allowed = $this->runTask('menus', 'copy', 'valid', 'core.create', ['cid' => [3]]);

        self::assertNull($allowed['stopped']);
        self::assertSame(['Menu::copy'], self::models($allowed['events']));
    }

    // pieces and scripts: list tasks

    /** @return iterable<string, array{string}> */
    public static function codeLists(): iterable
    {
        yield 'pieces' => ['pieces'];
        yield 'scripts' => ['scripts'];
    }

    #[DataProvider('codeLists')]
    public function testCodeListTasksNeedTheManageAndTheCodeRights(string $controller): void
    {
        foreach (['delete', 'copy', 'setPublished', 'publish', 'unpublish', 'trash'] as $task) {
            foreach (['core.manage,core.create,core.edit,core.edit.state,core.delete,breezingformsng.configuration', 'breezingformsng.code', 'guest'] as $rights) {
                $refused = $this->runTask($controller, $task, 'valid', $rights, ['cid' => [9], 'id' => 9, 'state' => 1]);

                self::assertNotNull($refused['stopped'], $controller . ' ' . $task . ' was allowed with rights: ' . $rights);
                self::assertSame([], self::models($refused['events']));
                self::assertSame([], array_filter($refused['events'], static fn (array $e): bool => isset($e['token'])));
            }
        }

        // A copy is a new item: the code right is not enough, the right to create is needed too.
        $refusedCopy = $this->runTask($controller, 'copy', 'valid', 'core.manage,breezingformsng.code', ['cid' => [3]]);
        self::assertNotNull($refusedCopy['stopped']);
        self::assertSame([], self::models($refusedCopy['events']));

        $copied = $this->runTask($controller, 'copy', 'valid', 'core.manage,breezingformsng.code,core.create', ['cid' => [3]]);
        self::assertNull($copied['stopped']);
        self::assertCount(1, self::models($copied['events']));
        self::assertStringEndsWith('::copy', self::models($copied['events'])[0]);
    }

    #[DataProvider('codeLists')]
    public function testPublishingFromACodeListNeedsTheStateRight(string $controller): void
    {
        $post = ['id' => 5, 'state' => 1];

        $refused = $this->runTask($controller, 'setPublished', 'valid', 'core.manage,breezingformsng.code,core.edit,core.create', $post);
        self::assertSame([], self::models($refused['events']));
        self::assertSame('ERROR', self::answer($refused['events'])['data']['Result']);

        $allowed = $this->runTask($controller, 'setPublished', 'valid', 'core.manage,breezingformsng.code,core.edit.state', $post);
        self::assertCount(1, self::models($allowed['events']));
        self::assertStringEndsWith('::publish', self::models($allowed['events'])[0]);
    }

    #[DataProvider('codeLists')]
    public function testOnlyTrashedCodeItemsAreDeletedForGood(string $controller): void
    {
        $rights = 'core.manage,breezingformsng.code';

        $kept = $this->runTask($controller, 'delete', 'valid', $rights, ['cid' => [3]]);
        self::assertNotContains('parent', array_merge(...array_map('array_keys', $kept['events'])));
        self::assertContains('error', array_column($kept['events'], 'type'));

        $trashed = $this->runTask($controller, 'delete', 'valid', $rights, ['cid' => [9]]);
        self::assertContains('parent', array_merge(...array_map('array_keys', $trashed['events'])));
    }

    // a single piece or script: only the display tasks need less than the code right

    /** @return iterable<string, array{string, string, bool}> */
    public static function codeItemTasks(): iterable
    {
        yield 'piece edit' => ['piece', 'edit', true];
        yield 'piece cancel' => ['piece', 'cancel', true];
        yield 'piece navigate' => ['piece', 'navigate', true];
        yield 'piece test' => ['piece', 'test', false];
        yield 'piece save' => ['piece', 'save', false];
        yield 'piece apply' => ['piece', 'apply', false];
        yield 'piece runTest' => ['piece', 'runTest', false];
        yield 'piece runTestsAjax' => ['piece', 'runTestsAjax', false];
        yield 'script edit' => ['script', 'edit', true];
        yield 'script test' => ['script', 'test', true];
        yield 'script save' => ['script', 'save', false];
        yield 'script apply' => ['script', 'apply', false];
    }

    #[DataProvider('codeItemTasks')]
    public function testASinglePieceOrScriptTaskNeedsTheCodeRightUnlessItOnlyDisplays(string $controller, string $task, bool $readOnly): void
    {
        $manageOnly = $this->runTask($controller, $task, 'valid', 'core.manage', ['id' => 5]);

        if ($readOnly) {
            self::assertNotSame(self::NOT_ALLOWED, $manageOnly['stopped']);
        } else {
            self::assertSame(self::NOT_ALLOWED, $manageOnly['stopped'], $task . ' needs the code right');
            self::assertSame([], array_filter($manageOnly['events'], static fn (array $e): bool => isset($e['token'])));
        }

        $noManage = $this->runTask($controller, $task, 'valid', 'breezingformsng.code', ['id' => 5]);
        self::assertSame('RuntimeException', $noManage['stopped'], $task . ' needs core.manage');

        $guest = $this->runTask($controller, $task, 'valid', 'guest', ['id' => 5]);
        self::assertSame('RuntimeException', $guest['stopped']);
    }
}
