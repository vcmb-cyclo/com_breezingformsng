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
}
// phpcs:enable PSR1.Classes.ClassDeclaration.MultipleClasses
