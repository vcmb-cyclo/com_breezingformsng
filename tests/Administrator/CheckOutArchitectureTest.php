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

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Menus, scripts, pieces and records are edited through Joomla's
 * FormController, which checks an item out while it is open when its table
 * has the check-out columns. Forms are edited in QuickMode, which does it
 * itself.
 */
final class CheckOutArchitectureTest extends TestCase
{
    private const ROOT = __DIR__ . '/../..';

    /** @return iterable<string, array{string, string, string, string}> */
    public static function itemProvider(): iterable
    {
        yield 'menus' => ['compmenus', 'MenuTable', 'menus', 'Menus'];
        yield 'scripts' => ['scripts', 'ScriptTable', 'scripts', 'Scripts'];
        yield 'pieces' => ['pieces', 'PieceTable', 'pieces', 'Pieces'];
        yield 'records' => ['records', 'RecordTable', 'records', 'Records'];
        yield 'forms' => ['forms', 'FormTable', 'forms', 'Forms'];
    }

    #[DataProvider('itemProvider')]
    public function testTheItemCanBeCheckedOut(string $table, string $tableClass, string $list, string $view): void
    {
        $install = $this->read('administrator/components/com_breezingformsng/sql/install.mysql.utf8.sql');
        $start = strpos($install, 'CREATE TABLE IF NOT EXISTS `#__facileforms_' . $table . '`');
        self::assertNotFalse($start);
        $create = substr($install, $start, (int) strpos($install, ';', $start) - $start);

        self::assertStringContainsString('`checked_out` int unsigned NULL DEFAULT NULL', $create);
        self::assertStringContainsString('`checked_out_time` datetime NULL DEFAULT NULL', $create);
        self::assertStringContainsString('KEY `idx_checkout` (`checked_out`)', $create);

        $class = $this->read('components/com_breezingformsng/src/Table/' . $tableClass . '.php');
        self::assertStringContainsString('protected $_supportNullValue = true;', $class);
        self::assertStringContainsString('public $checked_out = null;', $class);
        self::assertStringContainsString('public $checked_out_time = null;', $class);

        $template = $this->read('administrator/components/com_breezingformsng/tmpl/' . $list . '/default.php');
        self::assertStringContainsString("HTMLHelper::_('jgrid.checkedout', \$i,", $template);
        self::assertStringContainsString("'" . $list . ".', \$canManageCheckin", $template);

        self::assertMatchesRegularExpression(
            '/[cC]heckin\\(\'' . $list . '\\.checkin\'\\)/',
            $this->read('administrator/components/com_breezingformsng/src/View/' . $view . '/HtmlView.php')
        );
    }

    public function testTheInstallerAddsTheColumnsToExistingSites(): void
    {
        $script = $this->read('script.php');

        self::assertStringContainsString(
            "foreach ([\$formsTable, \$prefix . 'facileforms_compmenus', \$scriptsTable, \$piecesTable, \$recordsTable] as \$tableName) {",
            $script
        );
        self::assertStringContainsString("isset(\$tables[\$tableName]['checked_out'])", $script);
    }

    public function testMovingToAnotherScriptOrPieceChecksItOut(): void
    {
        $controller = $this->read('administrator/components/com_breezingformsng/src/Controller/PackageItemController.php');

        self::assertMatchesRegularExpression(
            '/if \(!\$model->checkout\(\$targetId\)\) \{.*?return;\s+\}\s+\$model->checkin\(\$itemId\);/s',
            $controller
        );
    }

    public function testQuickModeChecksTheFormOutAndCloseChecksItIn(): void
    {
        $controller = $this->read('administrator/components/com_breezingformsng/src/Controller/QuickmodeController.php');
        $app = $this->read('media/com_breezingformsng/js/admin/quickmode-app.js');
        $forms = $this->read('administrator/components/com_breezingformsng/src/Controller/FormsController.php');

        self::assertMatchesRegularExpression('/if \(!\$model->checkout\(\$formId\)\) \{/', $controller);
        self::assertMatchesRegularExpression(
            '/public function close\(\): void\s+\{\s+\$this->checkToken\(\'get\'\);.*?->checkin\(\$formId\)/s',
            $controller
        );
        self::assertStringContainsString('task=quickmode.close&form=', $app);
        self::assertStringContainsString("Joomla.getOptions('csrf.token')", $app);
        self::assertMatchesRegularExpression('/public function cancel\(\): void\s+\{\s+\$this->checkToken\(\);.*?->checkin\(\$id\)/s', $forms);
    }

    /**
     * AdminModel::checkin() goes through FormModel::checkin(), which refuses
     * a lock held by someone else unless the user can manage check-in;
     * Table::checkIn() would not. Every check-in must therefore go through
     * the model, and a refusal is reported.
     */
    public function testCheckInGoesThroughTheModelAndReportsARefusal(): void
    {
        $controllers = [
            'QuickmodeController' => "\$this->setMessage(Text::sprintf('JLIB_APPLICATION_ERROR_CHECKIN_FAILED', \$model->getError()), 'error');",
            'FormsController' => "\$this->app->enqueueMessage(Text::sprintf('JLIB_APPLICATION_ERROR_CHECKIN_FAILED', \$model->getError()), 'error');",
        ];

        foreach ($controllers as $controller => $message) {
            $source = $this->read('administrator/components/com_breezingformsng/src/Controller/' . $controller . '.php');

            self::assertMatchesRegularExpression('/if \(!\$model->checkin\(\$(formId|id)\)\) \{/', $source, $controller);
            self::assertStringContainsString($message, $source, $controller);
        }

        foreach (glob(self::ROOT . '/administrator/components/com_breezingformsng/src/Controller/*.php') ?: [] as $file) {
            self::assertStringNotContainsString('->checkIn(', (string) file_get_contents($file), basename($file));
        }
    }

    private function read(string $path): string
    {
        $source = file_get_contents(self::ROOT . '/' . $path);

        self::assertIsString($source);

        return $source;
    }
}
