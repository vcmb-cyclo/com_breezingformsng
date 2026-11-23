<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Integration;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Site\Event\AfterSubmitEvent;
use Vcmb\Component\BreezingformsNG\Site\Event\SubmissionArtifactsEvent;

/**
 * The submission events read by the add-on plugins (Dropbox, Mailchimp,
 * Salesforce) expose their values through Joomla's native argument accessor.
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class SubmissionEventsTest extends TestCase
{
    protected function setUp(): void
    {
        // A getter that calls itself again must fail fast, not hang the run.
        ini_set('memory_limit', '128M');

        $libraries = getenv('BFNG_JOOMLA_LIBRARIES');
        if (!$libraries || !is_file($libraries . '/vendor/autoload.php')) {
            self::markTestSkipped('Set BFNG_JOOMLA_LIBRARIES to a Joomla 6 libraries directory.');
        }
        $loader = require $libraries . '/vendor/autoload.php';
        $loader->setClassMapAuthoritative(false);
        $loader->addPsr4('Joomla\\CMS\\', $libraries . '/src', true);
        foreach ($loader->getClassMap() as $class => $path) {
            if (str_starts_with($class, 'Joomla\\CMS\\')) {
                $loader->addClassMap([$class => $libraries . '/src/'
                    . str_replace('\\', '/', substr($class, strlen('Joomla\\CMS\\'))) . '.php']);
            }
        }
    }

    public function testTheArtifactsEventGivesItsValuesToTheAddons(): void
    {
        $artifacts = [['path' => '/tmp/export.csv', 'name' => 'export.csv']];
        $event = new SubmissionArtifactsEvent(34, 'widgets', 7, $artifacts);

        self::assertSame(34, $event->getArgument('formId'));
        self::assertSame('widgets', $event->getArgument('formName'));
        self::assertSame(7, $event->getArgument('recordId'));
        self::assertSame($artifacts, $event->getArgument('artifacts'));
    }

    public function testTheAfterSubmitEventGivesItsValuesToTheAddons(): void
    {
        $event = new AfterSubmitEvent(34, 'widgets', 7, [['Name', 'Ada']], [['Mail', 'a@b.c']], [['Sf', 'x']]);

        self::assertSame(34, $event->getArgument('formId'));
        self::assertSame('widgets', $event->getArgument('formName'));
        self::assertSame(7, $event->getArgument('recordId'));
        self::assertSame([['Name', 'Ada']], $event->getArgument('submitData'));
        self::assertSame([['Mail', 'a@b.c']], $event->getArgument('mailData'));
        self::assertSame([['Sf', 'x']], $event->getArgument('salesforceData'));
    }

    public function testNoEventDefinesAMethodNamedAfterOneOfItsArguments(): void
    {
        // getArgument('x') calls getX() when it exists (deprecated in Joomla 6.2, removed in 7):
        // a getX() that went through getArgument('x') called itself until PHP ran out of stack.
        $events = [
            new SubmissionArtifactsEvent(1, 'f', 1, []),
            new AfterSubmitEvent(1, 'f', 1, []),
        ];

        foreach ($events as $event) {
            foreach (array_keys($event->getArguments()) as $name) {
                $suffix = ucfirst((string) $name);
                self::assertFalse(method_exists($event, 'get' . $suffix), $event::class . ' get' . $suffix);
                self::assertFalse(method_exists($event, 'onGet' . $suffix), $event::class . ' onGet' . $suffix);
            }
        }
    }
}
