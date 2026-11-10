<?php

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator;

use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Administrator\Exception\InvalidPackageException;
use Vcmb\Component\BreezingformsNG\Administrator\Model\PackageTransferModel;

final class PackageImportErrorsTest extends TestCase
{
    #[RunInSeparateProcess]
    public function testInvalidInputReportsItsReasonBeforeDatabaseAccess(): void
    {
        class_alias(PackageImportTextStub::class, 'Joomla\\CMS\\Language\\Text');
        class_alias(PackageImportModelStub::class, 'Joomla\\CMS\\MVC\\Model\\BaseDatabaseModel');

        $path = tempnam(sys_get_temp_dir(), 'bfng-package-');
        self::assertIsString($path);

        try {
            $model = new PackageTransferModel();
            $cases = [
                ['{', InvalidPackageException::JSON],
                [
                    '{"format":"breezingformsng-package","version":99,"package":"demo"}',
                    InvalidPackageException::VERSION,
                ],
                ['{"format":"other","version":2,"package":"demo"}', InvalidPackageException::INVALID],
            ];
            foreach ($cases as [$contents, $reason]) {
                file_put_contents($path, $contents);
                try {
                    $model->import($path);
                    self::fail('An invalid package must be rejected.');
                } catch (InvalidPackageException $exception) {
                    self::assertSame($reason, $exception->getReason());
                }
            }
        } finally {
            unlink($path);
        }
    }

    #[RunInSeparateProcess]
    public function testIntegratorPermissionIsCheckedBeforeDatabaseAccess(): void
    {
        class_alias(PackageImportTextStub::class, 'Joomla\\CMS\\Language\\Text');
        class_alias(PackageImportModelStub::class, 'Joomla\\CMS\\MVC\\Model\\BaseDatabaseModel');

        $path = tempnam(sys_get_temp_dir(), 'bfng-package-');
        self::assertIsString($path);

        try {
            $model = new PackageTransferModel();
            $payload = ['format' => 'breezingformsng-package', 'version' => 2, 'package' => 'demo'];
            $denied = [
                [['form_source_id' => 1, 'rules' => []]],
                [['form_source_id' => 1, 'rules' => [['data' => ['finalize_code' => 'php']]]]],
            ];
            // Without the permission, the rules are left out before any database
            // access, and the rest of the package goes on to be imported.
            foreach ($denied as $entries) {
                file_put_contents($path, json_encode($payload + ['integrator' => $entries], JSON_THROW_ON_ERROR));
                try {
                    $model->import($path);
                    self::fail('The test database stub should stop the import.');
                } catch (\LogicException $exception) {
                    self::assertSame('database reached', $exception->getMessage());
                }
                self::assertSame(['integrator'], $model->getImportSummary()['skipped']);
            }

            // Permitted Integrator data and older files proceed to the database.
            $accepted = [
                [$payload, false],
                [$payload + ['integrator' => []], false],
                [$payload + ['integrator' => [['rules' => []]]], true],
            ];
            foreach ($accepted as [$contents, $allowed]) {
                file_put_contents($path, json_encode($contents, JSON_THROW_ON_ERROR));
                try {
                    $model->import($path, false, '', $allowed);
                    self::fail('The test database stub should stop the import.');
                } catch (\LogicException $exception) {
                    self::assertSame('database reached', $exception->getMessage());
                }
            }
        } finally {
            unlink($path);
        }
    }
}

// The two Joomla stubs are loaded only in the isolated test process.
// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
final class PackageImportTextStub
{
    public static function _(string $key): string
    {
        return $key;
    }
}

class PackageImportModelStub
{
    public function getDatabase(): never
    {
        throw new \LogicException('database reached');
    }
}
// phpcs:enable PSR1.Classes.ClassDeclaration.MultipleClasses
