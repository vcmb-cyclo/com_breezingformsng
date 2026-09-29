<?php

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator;

use PHPUnit\Framework\TestCase;

final class PackageTransferArchitectureTest extends TestCase
{
    public function testTransferUsesTheVersionedBfngFormat(): void
    {
        $source = file_get_contents(__DIR__ . '/../../administrator/components/com_breezingformsng/src/Model/PackageTransferModel.php');

        self::assertIsString($source);
        self::assertStringContainsString("private const FORMAT = 'breezingformsng-package';", $source);
        self::assertStringContainsString('private const VERSION = 1;', $source);
        self::assertStringContainsString("'scripts' =>", $source);
        self::assertStringContainsString("'pieces' =>", $source);
        self::assertStringContainsString("'forms' =>", $source);
        self::assertStringContainsString('transactionStart()', $source);
        self::assertStringContainsString('transactionRollback()', $source);
    }

    public function testPackageScreenIsLinkedFromTheConfigurationForm(): void
    {
        $config = file_get_contents(__DIR__ . '/../../administrator/components/com_breezingformsng/config.xml');
        $field = file_get_contents(__DIR__ . '/../../administrator/components/com_breezingformsng/src/Field/PackageTransferField.php');

        self::assertIsString($config);
        self::assertIsString($field);
        // Must match PackageTransferField's own class name exactly beyond the
        // first letter: Joomla's FormHelper::loadClass() only applies ucfirst()
        // to the field's `type` attribute (via Normalise::toSpaceSeparated() +
        // ucwords(), which don't split camelCase), so type="packagetransfer"
        // silently resolves to a *different*, nonexistent "PackagetransferField"
        // class and falls back to a plain text input instead of this field's
        // actual button.
        self::assertStringContainsString('type="packageTransfer"', $config);
        self::assertStringContainsString("view=packages", $field);
    }

    /**
     * Reproduces Joomla's own Joomla\CMS\Form\FormHelper::loadClass() class-name
     * derivation (Normalise::toSpaceSeparated() + ucwords(), neither of which
     * split camelCase) against the field's `type` attribute, and asserts it
     * lands on the field's *actual* class name. This is what silently broke:
     * a form field's `type` value can look reasonable and still resolve to a
     * class that doesn't exist, with Joomla falling back to a plain text
     * input with no visible error.
     */
    public function testFieldTypeAttributeResolvesToTheActualFieldClassName(): void
    {
        $config = file_get_contents(__DIR__ . '/../../administrator/components/com_breezingformsng/config.xml');
        $field = file_get_contents(
            __DIR__ . '/../../administrator/components/com_breezingformsng/src/Field/PackageTransferField.php'
        );

        self::assertIsString($config);
        self::assertIsString($field);

        // config.xml declares several fields with their own `type=` - scope the
        // match to the <field name="package_transfer"> element specifically.
        self::assertMatchesRegularExpression(
            '/<field\s+name="package_transfer"\s+type="([^"]+)"/',
            $config
        );
        preg_match('/<field\s+name="package_transfer"\s+type="([^"]+)"/', $config, $typeMatch);

        self::assertMatchesRegularExpression('/final class (\w+) extends FormField/', $field);
        preg_match('/final class (\w+) extends FormField/', $field, $classMatch);

        self::assertSame(
            $classMatch[1],
            self::joomlaFormFieldClassName($typeMatch[1]),
            'config.xml\'s field type attribute must resolve to the actual class declared in PackageTransferField.php.'
        );
    }

    /**
     * @see \Joomla\CMS\Form\FormHelper::loadClass()
     * @see \Joomla\String\Normalise::toSpaceSeparated()
     */
    private static function joomlaFormFieldClassName(string $type): string
    {
        $name = preg_replace('#[ \-_]+#', ' ', $type);
        $name = str_ireplace(' ', '\\', ucwords((string) $name));

        return $name . 'Field';
    }
}
