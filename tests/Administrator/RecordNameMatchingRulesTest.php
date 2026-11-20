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
 * The record screen and the saving of a record attach a value whose element no longer
 * exists to an element by name under the rules of the About audit: unique name in the
 * form, same type.
 */
final class RecordNameMatchingRulesTest extends TestCase
{
    private function model(): string
    {
        return (string) file_get_contents(
            __DIR__ . '/../../administrator/components/com_breezingformsng/src/Model/RecordModel.php'
        );
    }

    public function testTheDisplayMatchesByNameOnlyWhenTheNameIsUniqueAndTheTypeIsTheSame(): void
    {
        $source = $this->model();
        $matcher = substr($source, (int) strpos($source, 'private function matchSubrecords'));
        $matcher = substr($matcher, 0, (int) strpos($matcher, 'public function getEditableRows'));

        self::assertStringContainsString("(\$nameCounts[\$element['name']] ?? 0) === 1", $matcher);
        self::assertStringContainsString("self::stringValue(\$sub['type'] ?? '') === \$element['type']", $matcher);
        self::assertStringContainsString('isset($existing[$elementId])', $matcher);
    }

    public function testSavingUsesTheSameRules(): void
    {
        $source = $this->model();
        $helper = substr($source, (int) strpos($source, 'private function getNameMatchedSubrecordIds'));
        $helper = substr($helper, 0, (int) strpos($helper, 'private function splitValue'));

        self::assertStringContainsString('$sameName !== 1', $helper);
        self::assertStringContainsString("'type') . ' = :type'", $helper);
        self::assertStringContainsString('NOT EXISTS (', $helper);
        // Rows of the element itself win; the name is only a fallback.
        self::assertStringContainsString('if ($subrecordIds === [])', $source);
    }

    public function testTheOrphanListIsWhatTheMatchingLeavesOut(): void
    {
        $source = $this->model();
        $orphans = substr($source, (int) strpos($source, 'public function getOrphanRows'));
        $orphans = substr($orphans, 0, (int) strpos($orphans, 'private function saveRecord'));

        self::assertStringContainsString("isset(\$matched['shown']", $orphans);
    }
}
