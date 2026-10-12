<?php

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator\Service;

use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Administrator\Service\SubmissionFileGuard;

final class SubmissionFileGuardTest extends TestCase
{
    private const UPLOADS = '{mospath}/media/breezingforms/uploads';

    private string $site;

    private string $outside;

    protected function setUp(): void
    {
        $base = sys_get_temp_dir() . '/bfng-file-guard-' . bin2hex(random_bytes(8));
        $this->site = $base . '/site';
        $this->outside = $base . '/private';

        foreach (
            [
                $this->site . '/media/breezingforms/uploads/jane_42',
                $this->site . '/media/breezingforms/signatures',
                $this->site . '/images',
                $this->outside . '/uploads',
            ] as $directory
        ) {
            self::assertTrue(mkdir($directory, 0777, true));
        }

        foreach (
            [
                $this->site . '/configuration.php',
                $this->site . '/images/logo.png',
                $this->site . '/media/breezingforms/uploads/cv.pdf',
                $this->site . '/media/breezingforms/uploads/jane_42/id.pdf',
                $this->site . '/media/breezingforms/signatures/sign-abc.png',
                $this->outside . '/uploads/cv.pdf',
                $this->outside . '/secret.txt',
            ] as $file
        ) {
            self::assertNotFalse(file_put_contents($file, 'x'));
        }
    }

    protected function tearDown(): void
    {
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(\dirname($this->site), \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            $item->isDir() && !$item->isLink() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir(\dirname($this->site));
    }

    public function testUploadInsideTheFieldDirectoryIsRemovable(): void
    {
        $guard = $this->guard();
        $path = $guard->locate('File Upload', $this->site . '/media/breezingforms/uploads/cv.pdf');

        self::assertSame($this->site . '/media/breezingforms/uploads/cv.pdf', $path);
        self::assertTrue($guard->isRemovable('File Upload', $path, '{ff_uploads}'));
    }

    public function testContentBuilderRelativeValueIsResolvedAgainstTheSite(): void
    {
        $guard = $this->guard();
        $path = $guard->locate('File Upload', '{cbsite}/media/breezingforms/uploads/cv.pdf');

        self::assertSame($this->site . '/media/breezingforms/uploads/cv.pdf', $path);
        self::assertTrue($guard->isRemovable('File Upload', $path, '{CBSite}/media/breezingforms/uploads'));
    }

    public function testPerSubmissionFoldersAndFileMasksStayInsideTheFixedPart(): void
    {
        $guard = $this->guard();
        $path = $this->site . '/media/breezingforms/uploads/jane_42/id.pdf';

        self::assertTrue($guard->isRemovable('File Upload', $path, '{ff_uploads}|{username}'));
        self::assertTrue($guard->isRemovable('File Upload', $path, '{ff_uploads}/jane_{userid}/{filemask:_random}'));
    }

    public function testUploadDirectoryOutsideTheSiteIsSupported(): void
    {
        $guard = $this->guard();

        self::assertTrue(
            $guard->isRemovable('File Upload', $this->outside . '/uploads/cv.pdf', $this->outside . '/uploads')
        );
    }

    public function testFileOutsideTheFieldDirectoryIsRefused(): void
    {
        $guard = $this->guard();

        self::assertFalse($guard->isRemovable('File Upload', $this->site . '/configuration.php', '{ff_uploads}'));
        self::assertFalse($guard->isRemovable('File Upload', $this->site . '/images/logo.png', '{ff_uploads}'));
        self::assertFalse(
            $guard->isRemovable('File Upload', $this->outside . '/secret.txt', $this->outside . '/uploads')
        );
        self::assertFalse(
            $guard->isRemovable(
                'File Upload',
                $this->site . '/media/breezingforms/uploads/../../../configuration.php',
                '{ff_uploads}'
            )
        );
    }

    public function testDirectoryAsWideAsTheSiteVouchesForNothing(): void
    {
        $guard = $this->guard();
        $path = $this->site . '/media/breezingforms/uploads/cv.pdf';

        self::assertFalse($guard->isRemovable('File Upload', $path, '{mospath}/{username}'));
        self::assertFalse($guard->isRemovable('File Upload', $path, '{mospath}'));
        self::assertFalse($guard->isRemovable('File Upload', $path, '/'));
        self::assertFalse($guard->isRemovable('File Upload', $path, '{username}'));
        self::assertFalse($guard->isRemovable('File Upload', $path, ''));
    }

    public function testSymbolicLinkIsRefused(): void
    {
        $link = $this->site . '/media/breezingforms/uploads/link.pdf';
        self::assertTrue(symlink($this->site . '/media/breezingforms/uploads/cv.pdf', $link));

        self::assertFalse($this->guard()->isRemovable('File Upload', $link, '{ff_uploads}'));
    }

    public function testSignatureIsLimitedToTheSignaturesDirectory(): void
    {
        $guard = $this->guard();
        $path = $guard->locate('Signature', 'sign-abc.png');

        self::assertSame($this->site . '/media/breezingforms/signatures/sign-abc.png', $path);
        self::assertTrue($guard->isRemovable('Signature', $path, ''));
        self::assertNull($guard->locate('Signature', '../uploads/cv.pdf'));
        self::assertNull($guard->locate('Signature', 'index.html'));
        self::assertFalse(
            $guard->isRemovable('Signature', $this->site . '/media/breezingforms/uploads/cv.pdf', '{ff_uploads}')
        );
    }

    public function testValuesThatNameNoLocalFileAreIgnored(): void
    {
        $guard = $this->guard();

        self::assertNull($guard->locate('File Upload', ''));
        self::assertNull($guard->locate('File Upload', 'https://example.org/media/cv.pdf'));
        self::assertNull($guard->locate('Text', $this->site . '/configuration.php'));
    }

    public function testFileKeptByAnotherSubmissionUnderAnotherSpellingIsNotPlanned(): void
    {
        $absolute = $this->site . '/media/breezingforms/uploads/cv.pdf';
        $relative = '{cbsite}/media/breezingforms/uploads/cv.pdf';

        foreach ([[$absolute, $relative], [$relative, $absolute]] as [$erased, $kept]) {
            $plan = $this->guard()->plan(
                [['record' => 7, 'type' => 'File Upload', 'value' => $erased, 'data1' => '{ff_uploads}']],
                [['type' => 'File Upload', 'value' => $kept]]
            );

            self::assertSame(['remove' => [], 'leave' => []], $plan);
        }
    }

    public function testPlanSeparatesRemovableFilesFromThoseLeftForManualHandling(): void
    {
        $uploads = $this->site . '/media/breezingforms/uploads';
        $plan = $this->guard()->plan(
            [
                ['record' => 7, 'type' => 'File Upload', 'value' => $uploads . '/cv.pdf', 'data1' => '{ff_uploads}'],
                ['record' => 7, 'type' => 'File Upload', 'value' => $uploads . '/gone.pdf', 'data1' => '{ff_uploads}'],
                ['record' => 7, 'type' => 'Signature', 'value' => 'sign-abc.png', 'data1' => null],
                [
                    'record' => 8,
                    'type' => 'File Upload',
                    'value' => $this->site . '/images/logo.png',
                    'data1' => '{ff_uploads}',
                ],
                ['record' => 8, 'type' => 'Text', 'value' => $this->site . '/configuration.php', 'data1' => null],
            ],
            [['type' => 'File Upload', 'value' => $uploads . '/jane_42/id.pdf']]
        );

        self::assertSame(
            [
                $uploads . '/cv.pdf' => 7,
                $this->site . '/media/breezingforms/signatures/sign-abc.png' => 7,
            ],
            $plan['remove']
        );
        self::assertSame([$this->site . '/images/logo.png' => 8], $plan['leave']);
    }

    public function testOneValidReferenceIsEnoughWhenAnEarlierOneIsStale(): void
    {
        $file = $this->site . '/media/breezingforms/uploads/cv.pdf';
        $plan = $this->guard()->plan(
            [
                ['record' => 7, 'type' => 'File Upload', 'value' => $file, 'data1' => $this->outside . '/uploads'],
                [
                    'record' => 9,
                    'type' => 'File Upload',
                    'value' => '{cbsite}/media/breezingforms/uploads/cv.pdf',
                    'data1' => '{ff_uploads}',
                ],
            ],
            []
        );

        self::assertSame(['remove' => [$file => 9], 'leave' => []], $plan);
    }

    public function testKeptReferencesAreNotReadWhenNothingIsToBeDeleted(): void
    {
        $kept = (static function (): \Generator {
            self::fail('Kept references must not be queried.');

            yield [];
        })();

        self::assertSame(
            ['remove' => [], 'leave' => []],
            $this->guard()->plan([['record' => 7, 'type' => 'Text', 'value' => 'x', 'data1' => null]], $kept)
        );
    }

    private function guard(): SubmissionFileGuard
    {
        return new SubmissionFileGuard($this->site, self::UPLOADS);
    }
}
