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
 * Salesforce), on the real Joomla event classes: Joomla's getArgument()
 * calls a get<Name>() method of the event when one exists, so a getter
 * that went through getArgument() called itself until PHP ran out of stack.
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

        self::assertSame(34, $event->getFormId());
        self::assertSame('widgets', $event->getFormName());
        self::assertSame(7, $event->getRecordId());
        self::assertSame($artifacts, $event->getArtifacts());
        // Joomla's own accessor, which goes through the getters.
        self::assertSame(34, $event->getArgument('formId'));
        self::assertSame($artifacts, $event->getArgument('artifacts'));
    }

    public function testTheAfterSubmitEventGivesItsValuesToTheAddons(): void
    {
        $event = new AfterSubmitEvent(34, 'widgets', 7, [['Name', 'Ada']], [['Mail', 'a@b.c']], [['Sf', 'x']]);

        self::assertSame(34, $event->getFormId());
        self::assertSame('widgets', $event->getFormName());
        self::assertSame(7, $event->getRecordId());
        self::assertSame([['Name', 'Ada']], $event->getSubmitData());
        self::assertSame([['Mail', 'a@b.c']], $event->getMailData());
        self::assertSame([['Sf', 'x']], $event->getSalesforceData());
        self::assertSame(7, $event->getArgument('recordId'));
        self::assertSame([['Sf', 'x']], $event->getArgument('salesforceData'));
    }
}
