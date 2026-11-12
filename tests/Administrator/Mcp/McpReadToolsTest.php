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

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator\Mcp;

use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Administrator\Mcp\ArgumentValidator;

require_once __DIR__ . '/McpTestDoubles.php';

/** What the check and audit tools promise through their definition. */
final class McpReadToolsTest extends TestCase
{
    private const ROOT = __DIR__ . '/../../../administrator/components/com_breezingformsng/src/Mcp';

    private static function source(string $file): string
    {
        return (string) file_get_contents(self::ROOT . '/' . $file);
    }

    public function testBothToolsAreRegisteredInTheFactory(): void
    {
        $factory = self::source('McpServerFactory.php');

        self::assertStringContainsString('new CheckFormTool(new FormChecker($db, $catalog))', $factory);
        self::assertStringContainsString('new AuditDatabaseTool(', $factory);
    }

    public function testBothToolsOnlyRead(): void
    {
        foreach (['Tool/CheckFormTool.php', 'Tool/AuditDatabaseTool.php'] as $file) {
            $source = self::source($file);
            self::assertMatchesRegularExpression('/function readOnly\(\): bool\s*\{\s*return true;/', $source, $file);
            self::assertStringContainsString("authorise('core.manage', 'com_breezingformsng')", $source, $file);
            // Nothing writes: no query other than selects, no file or repair call.
            foreach (['->insert(', '->update(', '->delete(', '->repair', 'RepairService', 'file_put_contents', 'unlink('] as $write) {
                self::assertStringNotContainsString($write, $source, $file . ' must not contain ' . $write);
            }
        }
    }

    public function testTheCheckReadsNoCodeOfTheElements(): void
    {
        $checker = self::source('FormChecker.php');

        // Only whether a custom script has text: the code itself is never selected.
        self::assertStringContainsString("TRIM(' . \$db->quoteName('script3code')", $checker);
        self::assertDoesNotMatchRegularExpression("/quoteName\(\[[^\]]*'script[123]code'/", $checker);
    }

    public function testTheAuditTakesNoArgumentAndKeepsEveryListShort(): void
    {
        $tool = self::source('Tool/AuditDatabaseTool.php');

        self::assertStringContainsString("return ['type' => 'object', 'additionalProperties' => false];", $tool);
        self::assertSame([], ArgumentValidator::validate(null, ['type' => 'object', 'additionalProperties' => false]));

        $this->expectException(\InvalidArgumentException::class);
        ArgumentValidator::validate(['limit' => 5], ['type' => 'object', 'additionalProperties' => false]);
    }

    public function testTheCheckAcceptsAnIdOrANameOnly(): void
    {
        $tool = self::source('Tool/CheckFormTool.php');

        self::assertStringContainsString("'additionalProperties' => false", $tool);
        self::assertStringContainsString('Give either the id or the name of the form.', $tool);
    }
}
