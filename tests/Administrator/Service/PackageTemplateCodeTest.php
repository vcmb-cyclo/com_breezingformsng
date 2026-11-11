<?php

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator\Service;

use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Administrator\Service\PackageTemplateCode;

/**
 * A package file holds the template code of a form in clear, and the import
 * stores it back exactly as the database had it.
 */
final class PackageTemplateCodeTest extends TestCase
{
    public function testQuickModeCodeIsAStructureInTheFile(): void
    {
        $structure = ['attributes' => ['id' => 'bfQuickModeRoot'], 'properties' => ['title' => 'Inscription é/è'], 'children' => [[], ['a' => 1.5]]];
        $stored = base64_encode((string) json_encode($structure));

        self::assertSame([$structure, null], PackageTemplateCode::toFile($stored));
        self::assertSame($stored, PackageTemplateCode::fromFile($structure, null));
    }

    public function testCodeNotWrittenBackTheSameIsText(): void
    {
        // An empty object would come back as an empty array: kept as text.
        foreach (['{"a":{}}', '{"a": 1}', '<p>Classic</p>', ''] as $code) {
            $stored = base64_encode($code);
            [$value, $encoding] = PackageTemplateCode::toFile($stored);
            self::assertSame($code, $value);
            self::assertNull($encoding);
            self::assertSame($stored, PackageTemplateCode::fromFile($value, $encoding));
        }
    }

    public function testCodeClearTextCannotHoldIsKeptAsStored(): void
    {
        foreach ([base64_encode("\xFF\xFE"), 'not base64!', base64_encode('abc') . "\n"] as $stored) {
            self::assertSame([$stored, PackageTemplateCode::STORED], PackageTemplateCode::toFile($stored));
            self::assertSame($stored, PackageTemplateCode::fromFile($stored, PackageTemplateCode::STORED));
        }
    }

    public function testInvalidValuesAreRefused(): void
    {
        foreach ([[1, null], ['x', 'base64'], [['a'], PackageTemplateCode::STORED]] as [$value, $encoding]) {
            try {
                PackageTemplateCode::fromFile($value, $encoding);
                self::fail('Accepted: ' . json_encode([$value, $encoding]));
            } catch (\UnexpectedValueException) {
                self::addToAssertionCount(1);
            }
        }
    }

    public function testIdsArePointedToThoseOfTheSite(): void
    {
        $element = static fn (int $dbId, int $validation, string $init): array => [
            'properties' => ['type' => 'element', 'dbId' => $dbId, 'validationId' => $validation, 'initId' => $init, 'actionId' => 0],
        ];
        $tree = ['properties' => ['type' => 'root'], 'children' => [
            ['properties' => ['type' => 'section'], 'children' => [$element(5, 3, '4'), $element(6, 9, '0')]],
        ]];
        $stored = base64_encode((string) json_encode($tree));

        $remapped = PackageTemplateCode::remapIds($stored, [5 => 50, 6 => 60], [3 => 30, 4 => 40]);
        $children = json_decode(base64_decode($remapped), true)['children'][0]['children'];
        self::assertSame(['type' => 'element', 'dbId' => 50, 'validationId' => 30, 'initId' => 40, 'actionId' => 0], $children[0]['properties']);
        // A script the file does not hold keeps its id.
        self::assertSame(['type' => 'element', 'dbId' => 60, 'validationId' => 9, 'initId' => '0', 'actionId' => 0], $children[1]['properties']);

        // An id the file does not hold is found by the name of the element.
        $stale = base64_encode((string) json_encode(['properties' => ['type' => 'element', 'dbId' => 999, 'bfName' => 'email'], 'children' => []]));
        self::assertSame(70, json_decode(base64_decode(PackageTemplateCode::remapIds($stale, [5 => 50], [], ['email' => 70])), true)['properties']['dbId']);
        self::assertSame($stale, PackageTemplateCode::remapIds($stale, [5 => 50], [], ['other' => 70]));

        // Nothing pointing elsewhere: the stored value is left as it is.
        self::assertSame($stored, PackageTemplateCode::remapIds($stored, [5 => 5], []));
        self::assertSame('', PackageTemplateCode::remapIds('', [5 => 50], []));
        self::assertSame(base64_encode('<p>x</p>'), PackageTemplateCode::remapIds(base64_encode('<p>x</p>'), [5 => 50], []));
    }
}
