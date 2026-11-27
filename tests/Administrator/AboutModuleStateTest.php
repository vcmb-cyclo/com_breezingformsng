<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator;

use PHPUnit\Framework\TestCase;

/**
 * The state of the package module on the Extensions screen.
 */
final class AboutModuleStateTest extends TestCase
{
    private const ADMIN = __DIR__ . '/../../administrator/components/com_breezingformsng';

    private function read(string $path): string
    {
        $source = file_get_contents(self::ADMIN . $path);
        self::assertIsString($source);

        return $source;
    }

    public function testTheModuleStateNeedsTheInstallerRightsAndModel(): void
    {
        $controller = $this->read('/src/Controller/AboutController.php');

        self::assertMatchesRegularExpression(
            "/\\\$allowed = \\\$isModule\s+\? \\\$user->authorise\('core\.manage', 'com_installer'\)\s+"
            . "&& \\\$user->authorise\('core\.edit\.state', 'com_installer'\)\s+"
            . ": \\\$user->authorise\('core\.edit\.state', 'com_plugins'\);/",
            $controller
        );
        self::assertStringContainsString("['com_installer', 'Manage'] : ['com_plugins', 'Plugin']", $controller);
    }

    public function testOnlyThePackageModuleCanBeToggled(): void
    {
        self::assertMatchesRegularExpression(
            "/function isPackageModule\(.*?=== 'module' && NgExtensions::owns\(/s",
            $this->read('/src/Model/AboutModel.php')
        );
        self::assertStringContainsString(
            "'module' => \$this->canEditModuleState",
            $this->read('/src/View/About/HtmlView.php')
        );
        self::assertStringContainsString("!empty(\$plugin['canToggle'])", $this->read('/tmpl/about/extensions.php'));
    }
}
