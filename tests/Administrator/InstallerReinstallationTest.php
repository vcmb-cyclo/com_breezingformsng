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

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator;

use PHPUnit\Framework\TestCase;

final class InstallerReinstallationTest extends TestCase
{
    public function testColumnComparisonDetectsOnlyActualDefinitionChanges(): void
    {
        $source = file_get_contents(__DIR__ . '/../../script.php');
        $start = strpos($source, '    private static function columnDefinitionMatches(');
        $end = strpos($source, '    private function migrateQuickmodeIconPaths()', $start);
        $method = substr($source, $start, $end - $start);
        $comparison = eval('return new class { '
            . str_replace('private static function', 'public static function', $method) . ' };');

        $date = (object) ['Type' => 'datetime', 'Null' => 'YES', 'Default' => null, 'Collation' => null];
        self::assertTrue($comparison::columnDefinitionMatches($date, 'datetime', true, null));
        $date->Null = 'NO';
        self::assertFalse($comparison::columnDefinitionMatches($date, 'datetime', true, null));

        $audit = (object) [
            'Type' => 'varchar(255)', 'Null' => 'NO', 'Default' => '', 'Collation' => 'utf8mb4_unicode_ci',
        ];
        self::assertTrue($comparison::columnDefinitionMatches($audit, 'varchar(255)', false, '', 'utf8mb4_unicode_ci'));
        self::assertFalse($comparison::columnDefinitionMatches($audit, 'varchar(255)', false, '', 'utf8mb4_bin'));
        $audit->Default = null;
        self::assertFalse($comparison::columnDefinitionMatches($audit, 'varchar(255)', false, ''));

        $ip = (object) ['Type' => 'varchar(45)', 'Null' => 'NO', 'Default' => '', 'Collation' => 'utf8mb4_unicode_ci'];
        self::assertTrue($comparison::columnDefinitionMatches($ip, 'varchar(45)', false, ''));
        $ip->Type = 'varchar(255)';
        self::assertFalse($comparison::columnDefinitionMatches($ip, 'varchar(45)', false, ''));
    }

    public function testPluginActivationRestoresExistingStatesAndEnablesNewPlugins(): void
    {
        $source = file_get_contents(__DIR__ . '/../../script.php');
        $start = strpos($source, '        foreach ($this->getPlugins() as $folder => $plugins)');
        $end = strpos($source, "\n    private function removeOldUpdateSite()", $start);
        // Execute the activation block with a query recorder instead of Joomla.
        $block = substr($source, $start, $end - $start);
        $block = substr($block, 0, strrpos($block, '}'));
        $runner = eval('return new class { public array $logs = [];'
            . 'public function getPlugins() { return ["system" => ["bfcompat"], "content" => ["breezingforms"]]; }'
            . 'public function log($message) { $this->logs[] = $message; }'
            . 'public function run($standardDb, $existingPlugins) { ' . $block . ' } };');
        $db = new class {
            public array $states = [];
            public string $state;
            public function getQuery($new): self
            {
                return $this;
            }
            public function update($table): self
            {
                return $this;
            }
            public function set($value): self
            {
                $this->state = $value;
                return $this;
            }
            public function where($value): self
            {
                return $this;
            }
            public function quote($value): string
            {
                return "'" . $value . "'";
            }
            public function setQuery($query): self
            {
                return $this;
            }
            public function execute(): void
            {
                $this->states[] = $this->state;
            }
        };

        $runner->run($db, ['system' => ['bfcompat' => 0], 'content' => ['breezingforms' => 1]]);
        self::assertSame(['enabled = 0', 'enabled = 1'], $db->states);
        self::assertSame([], $runner->logs);
        $db->states = [];
        $runner->run($db, []);
        self::assertSame(['enabled = 1', 'enabled = 1'], $db->states);
        self::assertCount(2, $runner->logs);
    }
}
