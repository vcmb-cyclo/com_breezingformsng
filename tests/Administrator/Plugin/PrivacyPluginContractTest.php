<?php

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator\Plugin;

use PHPUnit\Framework\TestCase;

/**
 * The plugin needs Joomla's privacy component to run; its behaviour is
 * exercised in the install smoke environment. These checks pin the contract
 * that must not regress: nothing identifying survives the anonymisation.
 */
final class PrivacyPluginContractTest extends TestCase
{
    private const PLUGIN = __DIR__ . '/../../../administrator/components/com_breezingformsng/plugins/privacy';

    public function testAnonymisationErasesValuesBeforeBreakingTheLinkToTheAccount(): void
    {
        $source = file_get_contents(self::PLUGIN . '/src/Extension/Breezingformsng.php');

        self::assertIsString($source);

        $values = strpos($source, "->set(\$db->quoteName('value') . ' = ' . \$db->quote(''))");
        $unlink = strpos($source, "->set(\$db->quoteName('user_id') . ' = 0')");

        self::assertIsInt($values);
        self::assertIsInt($unlink);
        self::assertLessThan($unlink, $values);
    }

    public function testEveryIdentifyingRecordColumnIsCleared(): void
    {
        $source = file_get_contents(self::PLUGIN . '/src/Extension/Breezingformsng.php');

        self::assertIsString($source);

        foreach (
            ['ip', 'browser', 'opsys', 'provider', 'username', 'user_full_name', 'opt_ip', 'opt_token', 'paypal_tx_id'] as $column
        ) {
            self::assertStringContainsString("'" . $column . "' => ''", $source, $column);
        }

        self::assertStringContainsString("->set(\$db->quoteName('modified_user_id') . ' = 0')", $source);
    }

    public function testExportNeverExposesTheOptInToken(): void
    {
        $source = file_get_contents(self::PLUGIN . '/src/Extension/Breezingformsng.php');

        self::assertIsString($source);

        $start = strpos($source, 'public function onPrivacyExportRequest(');
        $end = strpos($source, 'public function onPrivacyCanRemoveData(');

        self::assertIsInt($start);
        self::assertIsInt($end);

        $export = substr($source, $start, $end - $start);

        self::assertStringNotContainsString('opt_token', $export);
        self::assertStringContainsString("'paypal_tx_id'", $export);
        self::assertStringContainsString("'modified_by'", $export);
    }

    public function testAnonymisationIsTheDefaultRemovalMode(): void
    {
        $manifest = simplexml_load_file(self::PLUGIN . '/breezingformsng.xml');

        self::assertNotFalse($manifest);
        self::assertSame('0', (string) $manifest->xpath('//field[@name="remove_records"]')[0]['default']);
    }
}
