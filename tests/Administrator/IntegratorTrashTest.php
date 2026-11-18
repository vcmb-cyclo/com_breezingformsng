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

use PHPUnit\Framework\TestCase;

/**
 * The trash of the Integrator rules list: rules hold PHP code, so a trashed
 * rule never runs and only the trashed ones can be deleted for good.
 */
final class IntegratorTrashTest extends TestCase
{
    private const ADMIN = __DIR__ . '/../../administrator/components/com_breezingformsng';
    private const SITE = __DIR__ . '/../../components/com_breezingformsng';

    private function read(string $path): string
    {
        $source = file_get_contents($path);
        self::assertIsString($source);

        return $source;
    }

    private function method(string $source, string $name): string
    {
        self::assertSame(1, preg_match('/function ' . $name . '\(.*?\n    \}\n/s', $source, $match), $name);

        return $match[0];
    }

    public function testControllerTrashesBehindTheTokenAndTheCodeArea(): void
    {
        $controller = $this->read(self::ADMIN . '/src/Controller/IntegratorController.php');

        self::assertMatchesRegularExpression('/AreaAccess::assert\([^;]+AreaAccess::CODE\);/', $this->method($controller, 'execute'));

        foreach (['trash', 'remove', 'publish', 'unpublish', 'setPublished'] as $task) {
            self::assertStringContainsString('$this->checkToken();', $this->method($controller, $task), $task);
        }

        self::assertStringContainsString('trashRules(', $this->method($controller, 'trash'));
    }

    public function testMutationsRequireTheirJoomlaAclAction(): void
    {
        $controller = $this->read(self::ADMIN . '/src/Controller/IntegratorController.php');

        foreach (['trash', 'setPublished', 'publish', 'unpublish'] as $task) {
            self::assertStringContainsString("assertPermission('core.edit.state')", $this->method($controller, $task), $task);
        }

        self::assertStringContainsString("assertPermission('core.delete')", $this->method($controller, 'remove'));

        $view = $this->read(self::ADMIN . '/src/View/Integrator/HtmlView.php');
        self::assertStringContainsString("authorise('core.edit.state', 'com_breezingformsng')", $view);
        self::assertStringContainsString("authorise('core.delete', 'com_breezingformsng')", $view);
    }

    public function testStateChangesAcceptSelectionsAndOnlyKnownAjaxStates(): void
    {
        $controller = $this->read(self::ADMIN . '/src/Controller/IntegratorController.php');

        self::assertStringContainsString('in_array($state, [0, 1], true)', $this->method($controller, 'setPublished'));
        self::assertStringContainsString("'cid'", $this->method($controller, 'changeState'));
    }

    public function testDeletingRefusesRulesThatAreNotTrashed(): void
    {
        $model = $this->read(self::ADMIN . '/src/Model/IntegratorModel.php');
        $delete = $this->method($model, 'deleteRules');

        self::assertStringContainsString("'published') . ' = :trashed'", $delete);
        self::assertStringContainsString("'refused'", $delete);
        self::assertStringContainsString('#__facileforms_integrator_criteria_fixed', $delete);
        self::assertStringContainsString('JLIB_APPLICATION_ERROR_DELETE_NOT_PERMITTED', $this->read(self::ADMIN . '/src/Controller/IntegratorController.php'));
        self::assertStringContainsString('public const int TRASHED = -2;', $model);
    }

    public function testAboutTrashBulkDeleteAlsoRequiresCoreDelete(): void
    {
        $controller = $this->read(self::ADMIN . '/src/Controller/AboutController.php');
        $method = $this->method($controller, 'emptyItemsTrash');

        self::assertStringContainsString("authorise('core.delete', 'com_breezingformsng')", $method);
        self::assertStringContainsString("\$factory->createModel('Integrator'", $method);
    }

    public function testListHidesTheTrashUnlessAskedFor(): void
    {
        $model = $this->read(self::ADMIN . '/src/Model/IntegratorRulesModel.php');

        self::assertStringContainsString("elseif (\$published !== '*')", $model);
        self::assertStringContainsString("'rules.published') . ' > :trashed'", $model);
        self::assertStringContainsString('optionsFilter="*,-2,0,1"', $this->read(self::ADMIN . '/forms/filter_integratorrules.xml'));
    }

    public function testDeleteButtonOnlyExistsInTheTrashFilter(): void
    {
        $view = $this->read(self::ADMIN . '/src/View/Integrator/HtmlView.php');

        self::assertStringNotContainsString('deleteList(', $view);
        self::assertSame(1, substr_count($view, "->delete('integrator.remove', 'JTOOLBAR_EMPTY_TRASH')"));
        self::assertStringContainsString("publish('integrator.publish')", $view);
        self::assertStringContainsString("unpublish('integrator.unpublish')", $view);
        self::assertMatchesRegularExpression(
            "/filter\.published'\) === \(string\) IntegratorModel::TRASHED\) \{[^}]*if \(\\\$canDelete\) \{[^}]*delete\('integrator\.remove'[^}]*\}[^}]*\} elseif \(\\\$canEditState\) \{[^}]*trash\('integrator\.trash'/s",
            $view
        );
    }

    public function testTemplateNeverTreatsTheTrashedStateAsPublished(): void
    {
        $template = $this->read(self::ADMIN . '/tmpl/integrator/default.php');

        self::assertStringNotContainsString('$rule->published ?', $template);
        self::assertStringContainsString('(int) $rule->published === -2', $template);
        self::assertStringContainsString('(int) $rule->published === 1', $template);
        self::assertStringContainsString('JTRASHED', $template);
    }

    public function testTheRuntimeOnlyRunsPublishedRules(): void
    {
        $runtime = $this->read(self::SITE . '/src/Service/Integration/IntegratorRuntime.php');

        self::assertStringContainsString("quoteName('rules.published') . ' = 1'", $runtime);
    }
}
