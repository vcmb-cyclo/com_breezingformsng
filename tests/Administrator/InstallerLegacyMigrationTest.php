<?php

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator;

use PHPUnit\Framework\TestCase;

final class InstallerLegacyMigrationTest extends TestCase
{
    public function testInstallationMigratesPreservedTablesBeforeImportingLibrary(): void
    {
        $installer = $this->createLifecycleRunner();
        $installer->postflight('install', null);

        $this->assertMigrationOrder($installer->calls);
    }

    public function testDiscoveryInstallationAlsoMigratesPreservedTables(): void
    {
        $installer = $this->createLifecycleRunner();
        $installer->postflight('discover_install', null);

        $this->assertMigrationOrder($installer->calls);
    }

    public function testUpdateMigratesOnceAndPreservesConfigurationBeforeCleanup(): void
    {
        $installer = $this->createLifecycleRunner();
        $installer->update(null);
        $installer->postflight('update', null);

        $this->assertMigrationOrder($installer->calls);
    }

    public function testUninstallDoesNotMigrateOrImportData(): void
    {
        $installer = $this->createLifecycleRunner();
        $installer->postflight('uninstall', null);

        self::assertNotContains('migrateDatabase', $installer->calls);
        self::assertNotContains('migrateLegacyConfig', $installer->calls);
        self::assertNotContains('importStandardLibrary', $installer->calls);
    }

    private function assertMigrationOrder(array $calls): void
    {
        self::assertSame(1, count(array_keys($calls, 'migrateDatabase', true)));
        self::assertSame(1, count(array_keys($calls, 'migrateLegacyConfig', true)));

        $orderedCalls = [
            ['ensureUtf8mb4Columns', 'migrateDatabase'],
            ['migrateDatabase', 'importStandardLibrary'],
            ['migrateLegacyConfig', 'importStandardLibrary'],
            ['migrateLegacyConfig', 'cleanupLegacyBreezingFormsAfterInstall'],
        ];

        foreach ($orderedCalls as [$before, $after]) {
            self::assertContains($before, $calls);
            self::assertContains($after, $calls);
            self::assertLessThan(
                array_search($after, $calls, true),
                array_search($before, $calls, true),
                $before . ' must run before ' . $after
            );
        }
    }

    private function createLifecycleRunner(): object
    {
        $source = file_get_contents(__DIR__ . '/../../script.php');
        self::assertIsString($source);

        $updateStart = strpos($source, '    public function update(');
        self::assertIsInt($updateStart);
        // The next private method terminates update() in both the original and
        // extracted implementation; its database side effects are recorded below.
        $updateEnd = strpos($source, "\n    private ", $updateStart);
        self::assertIsInt($updateEnd);
        $update = substr($source, $updateStart, $updateEnd - $updateStart);

        $postflightStart = strpos($source, '    function postflight(');
        self::assertIsInt($postflightStart);
        $postflightEnd = strpos($source, '        $durationSeconds = ', $postflightStart);
        self::assertIsInt($postflightEnd);
        // Execute the real lifecycle branches, excluding only the final Joomla
        // notification/reporting block, which does not perform migrations.
        $postflight = substr($source, $postflightStart, $postflightEnd - $postflightStart) . "    }\n";

        return eval('return new class { public array $calls = [];'
            . 'public function __call(string $name, array $arguments): mixed {'
            . '$this->calls[] = $name; return $name === "getCurrentInstalledVersion" ? "5.0.0-RC11" : null; }'
            . $update . $postflight . ' };');
    }
}
