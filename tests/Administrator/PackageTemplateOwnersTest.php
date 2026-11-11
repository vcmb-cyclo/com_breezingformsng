<?php

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator;

use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Administrator\Model\PackageTransferModel;

/**
 * The template suffixes overlap: a file name can belong to more than one form,
 * which the import checks before writing it.
 */
final class PackageTemplateOwnersTest extends TestCase
{
    #[RunInSeparateProcess]
    public function testMailbackOfFooIsAlsoTheTemplateOfFooMailback(): void
    {
        class_alias(PackageTemplateOwnersModelStub::class, 'Joomla\\CMS\\MVC\\Model\\BaseDatabaseModel');

        $owners = PackageTransferModel::templateOwnerNames('mailtpl', 'foo_mailback.html.php');
        self::assertSame(['foo', 'foo_mailback'], self::sorted($owners));

        self::assertSame(['foo'], PackageTransferModel::templateOwnerNames('mailtpl', 'foo.txt.php'));
        // The PDF suffixes do not overlap one another.
        $owners = PackageTransferModel::templateOwnerNames('pdftpl', 'foo_pdf_mailback_attachment.php');
        self::assertSame(['foo'], $owners);
        // A suffix alone names no form, and an unknown folder holds no template.
        self::assertSame([], PackageTransferModel::templateOwnerNames('mailtpl', '.html.php'));
        self::assertSame([], PackageTransferModel::templateOwnerNames('other', 'foo.html.php'));
    }

    /**
     * @param list<string> $names
     * @return list<string>
     */
    private static function sorted(array $names): array
    {
        sort($names);

        return $names;
    }
}

// Joomla is not loaded by the isolated unit-test bootstrap.
// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
abstract class PackageTemplateOwnersModelStub
{
}
