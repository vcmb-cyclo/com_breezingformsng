<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator\Mcp;

use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Administrator\Mcp\McpVisibilityRules;
use Vcmb\Component\BreezingformsNG\Administrator\Mcp\ToolException;

final class McpVisibilityRulesTest extends TestCase
{
    public function testItSerializesTheSupportedLegacyGrammarFromStructuredRules(): void
    {
        self::assertSame(
            "turn on element message if topic is support\nturn off element phone if topic isnot sales",
            McpVisibilityRules::serialize([
                [
                    'target' => 'message',
                    'source' => 'topic',
                    'operator' => 'equals',
                    'value' => 'support',
                    'visible' => true,
                ],
                [
                    'target' => 'phone',
                    'source' => 'topic',
                    'operator' => 'not_equals',
                    'value' => 'sales',
                    'visible' => false,
                ],
            ], ['topic', 'message', 'phone'])
        );
    }

    public function testItReadsSupportedRulesAndRejectsUnknownLegacySyntax(): void
    {
        self::assertSame([
            'rules' => [[
                'target' => 'message',
                'source' => 'topic',
                'operator' => 'equals',
                'value' => 'support',
                'visible' => true,
            ]],
            'supported' => true,
        ], McpVisibilityRules::read('turn on element message if topic is support', ['topic', 'message']));

        self::assertSame(['rules' => [], 'supported' => false], McpVisibilityRules::read(
            'turn on section message if topic is support',
            ['topic', 'message']
        ));
    }

    public function testItRefusesUnknownFieldsSelfReferencesAndMarkupValues(): void
    {
        foreach (
            [
                [
                    'target' => 'missing',
                    'source' => 'topic',
                    'operator' => 'equals',
                    'value' => 'support',
                    'visible' => true,
                ],
                [
                    'target' => 'topic',
                    'source' => 'topic',
                    'operator' => 'equals',
                    'value' => 'support',
                    'visible' => true,
                ],
                [
                    'target' => 'message',
                    'source' => 'topic',
                    'operator' => 'equals',
                    'value' => '<script>',
                    'visible' => true,
                ],
            ] as $rule
        ) {
            try {
                McpVisibilityRules::serialize([$rule], ['topic', 'message']);
                self::fail('Unsafe visibility rule was accepted.');
            } catch (ToolException) {
                self::addToAssertionCount(1);
            }
        }
    }

    public function testSpecialLegacyValuePrefixesAreNotTreatedAsLiteralEquality(): void
    {
        foreach (['![', '!value', '#a|b'] as $value) {
            self::assertSame(['rules' => [], 'supported' => false], McpVisibilityRules::read(
                'turn on element message if topic is ' . $value,
                ['topic', 'message']
            ));
            try {
                McpVisibilityRules::serialize([[
                    'target' => 'message', 'source' => 'topic', 'operator' => 'equals',
                    'value' => $value, 'visible' => true,
                ]], ['topic', 'message']);
                self::fail('Special visibility syntax was accepted as literal text.');
            } catch (ToolException) {
                self::addToAssertionCount(1);
            }
        }
    }
}
