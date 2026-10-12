<?php

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator;

use PHPUnit\Framework\TestCase;

final class InstallerLegacyMigrationTest extends TestCase
{
    public function testInstallationMigratesPreservedTablesBeforeImportingLibrary(): void
    {
        $installer = $this->createLifecycleRunner();
        $installer->postflight('install', $this->adapter());

        $this->assertMigrationOrder($installer->calls);
    }

    public function testDiscoveryInstallationAlsoMigratesPreservedTables(): void
    {
        $installer = $this->createLifecycleRunner();
        $installer->postflight('discover_install', $this->adapter());

        $this->assertMigrationOrder($installer->calls);
    }

    public function testUpdateMigratesOnceAndPreservesConfigurationBeforeCleanup(): void
    {
        $installer = $this->createLifecycleRunner();
        $installer->update($this->adapter());
        $installer->postflight('update', $this->adapter());

        $this->assertMigrationOrder($installer->calls);
    }

    public function testUninstallDoesNotMigrateOrImportData(): void
    {
        $installer = $this->createLifecycleRunner();
        $installer->postflight('uninstall', $this->adapter());

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

    private function adapter(): object
    {
        if (!class_exists(\Joomla\CMS\Installer\InstallerAdapter::class)) {
            eval('namespace Joomla\\CMS\\Installer; class InstallerAdapter {}');
        }

        return (new \ReflectionClass(\Joomla\CMS\Installer\InstallerAdapter::class))->newInstanceWithoutConstructor();
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

        $postflightStart = strpos($source, '    public function postflight(');
        self::assertIsInt($postflightStart);
        $postflightEnd = strpos($source, '        $durationSeconds = ', $postflightStart);
        self::assertIsInt($postflightEnd);
        // Execute the real lifecycle branches, excluding only the final Joomla
        // notification/reporting block, which does not perform migrations.
        $postflight = substr($source, $postflightStart, $postflightEnd - $postflightStart) . "        return true;\n    }\n";

        return eval('use Joomla\\CMS\\Installer\\InstallerAdapter; return new class { public array $calls = [];'
            . 'public function __call(string $name, array $arguments): mixed {'
            . '$this->calls[] = $name; return $name === "getCurrentInstalledVersion" ? "5.0.0-RC11" : null; }'
            . $update . $postflight . ' };');
    }
}
