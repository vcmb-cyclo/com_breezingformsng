<?php

/**
 * BreezingForms NG - A Joomla Forms Application
 *
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 **/

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator\Service;

use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Administrator\Service\FormVersioning;

final class FormVersioningTest extends TestCase
{
    private const ROOT = __DIR__ . '/../../..';

    public function testElementsDeletedSinceTheVersionAreCreatedAgain(): void
    {
        $structure = [
            'properties' => ['type' => 'root'],
            'children' => [
                [
                    'properties' => ['type' => 'page'],
                    'children' => [
                        ['properties' => ['type' => 'element', 'dbId' => 11]],
                        ['properties' => ['type' => 'element', 'dbId' => 12]],
                        ['properties' => ['type' => 'section'], 'children' => [
                            ['properties' => ['type' => 'element', 'dbId' => 13]],
                        ]],
                        ['properties' => ['type' => 'element', 'dbId' => 0]],
                    ],
                ],
            ],
        ];

        $restored = FormVersioning::resetMissingElementIds($structure, [11, 13]);
        $page = $restored['children'][0]['children'];

        self::assertSame(11, $page[0]['properties']['dbId']);
        self::assertSame(0, $page[1]['properties']['dbId']);
        self::assertSame(13, $page[2]['children'][0]['properties']['dbId']);
        self::assertSame(0, $page[3]['properties']['dbId']);
        self::assertSame('section', $page[2]['properties']['type']);
    }

    public function testTheRestoreTaskMatchesTheTypeAlias(): void
    {
        // The Versions dialog sends Restore to <second part of the alias>.loadhistory.
        self::assertSame('com_breezingformsng.form', FormVersioning::TYPE_ALIAS);
        self::assertStringContainsString(
            'public function loadhistory(): void',
            $this->read('administrator/components/com_breezingformsng/src/Controller/FormController.php')
        );
        self::assertStringContainsString(
            "\$alias = 'com_breezingformsng.form';",
            $this->read('script.php')
        );
    }

    public function testEachSaveRecordsOneVersion(): void
    {
        $quickmode = $this->read('administrator/components/com_breezingformsng/src/Controller/QuickmodeController.php');
        $forms = $this->read('administrator/components/com_breezingformsng/src/Controller/FormsController.php');
        $app = $this->read('media/com_breezingformsng/js/admin/quickmode-app.js');

        // The QuickMode structure save records only when no Options post follows it.
        self::assertStringContainsString("recordVersion: JQuery('#bfOptionsFieldsWrap').length === 0 ? 1 : 0", $app);
        self::assertMatchesRegularExpression(
            "/if \\(\\\$input->getInt\\('recordVersion', 1\\) === 1\\) \\{\\s+try \\{\\s+\\(new FormVersioning\\(\\\$db\\)\\)->record\\(\\\$formId\\);/",
            $quickmode
        );
        self::assertStringContainsString('->record($id);', $forms);
    }

    public function testARestoreKeepsWhatIsNotTheFormContent(): void
    {
        $service = $this->read('administrator/components/com_breezingformsng/src/Service/FormVersioning.php');

        foreach (['id', 'package', 'ordering', 'published', 'version', 'checked_out', 'template_code'] as $column) {
            self::assertMatchesRegularExpression("/KEPT_COLUMNS = \\[[^\\]]*'" . $column . "'/s", $service, $column);
        }
    }

    public function testTheHistoryIsWrittenByTheNativeVersionablePlugin(): void
    {
        $service = $this->read('administrator/components/com_breezingformsng/src/Service/FormVersioning.php');
        $table = $this->read('components/com_breezingformsng/src/Table/FormTable.php');

        // Versioning is deprecated since Joomla 6: the Versionable behaviour
        // plugin writes and deletes the history of a VersionableTableInterface.
        self::assertStringNotContainsString('Versioning::', $service);
        self::assertStringContainsString('final class FormTable extends Table implements VersionableTableInterface', $table);
        self::assertStringContainsString("return 'com_breezingformsng.form';", $table);
        self::assertStringContainsString("PluginHelper::isEnabled('behaviour', 'versionable')", $service);
    }

    public function testRecordingIsAllOrNothingAndChecksTheHistory(): void
    {
        $service = $this->read('administrator/components/com_breezingformsng/src/Service/FormVersioning.php');

        self::assertMatchesRegularExpression(
            '/function record\(.*?transactionStart\(true\);.*?->store\(\).*?labelVersion\(.*?transactionCommit\(true\);'
            . '.*?catch \(\\\\Throwable \$e\) \{\s+\$db->transactionRollback\(true\);\s+throw \$e;/s',
            $service
        );
        self::assertMatchesRegularExpression(
            '/getHashMatch\(\);\s+if \(!\\\\is_object\(\$row\)\) \{\s+throw new/',
            $service
        );
    }

    public function testARestoreIsAllOrNothing(): void
    {
        $controller = $this->read('administrator/components/com_breezingformsng/src/Controller/FormController.php');

        // Options, structure and the new version in one transaction; the
        // ContentBuilder NG sync only after the commit.
        self::assertMatchesRegularExpression(
            '/transactionStart\(\);\s+try \{\s+\$versioning->restoreOptions.*?\$quickmode->save\(.*?'
            . '\$versioning->record\(.*?transactionCommit\(\);\s+\} catch \(\\\\Throwable \$e\) \{\s+'
            . '\$db->transactionRollback\(\);\s+throw \$e;/s',
            $controller
        );
        self::assertGreaterThan(
            strpos($controller, 'transactionCommit();'),
            strpos($controller, 'ContentBuilderFormSync(')
        );
    }

    private function read(string $path): string
    {
        $source = file_get_contents(self::ROOT . '/' . $path);

        self::assertIsString($source);

        return $source;
    }
}
