<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator\Service;

use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Administrator\Service\ExtensionAdminLink;
use Vcmb\Component\BreezingformsNG\Administrator\Service\NgExtensions;

if (!defined('_JEXEC')) {
    define('_JEXEC', 1);
}

final class ExtensionRegistrationTest extends TestCase
{
    public function testTheExtensionsTheNgPackageInstallsAreNotFormerOnes(): void
    {
        self::assertTrue(NgExtensions::owns('component', 'com_breezingformsng'));
        self::assertTrue(NgExtensions::owns('module', 'mod_breezingformsng'));
        self::assertTrue(NgExtensions::owns('plugin', 'breezingformsng', 'content'));
        self::assertTrue(NgExtensions::owns('plugin', 'bfcompat', 'system'));
        foreach (['mailchimp', 'salesforce', 'dropbox'] as $addon) {
            self::assertTrue(NgExtensions::owns('plugin', $addon, 'breezingforms_addons'));
        }
        self::assertTrue(NgExtensions::owns('plugin', 'breezingformsng', 'privacy'));
        self::assertTrue(NgExtensions::owns('plugin', 'breezingformsng', 'webservices'));
    }

    public function testWhatTheFormerBreezingFormsInstalledIsNot(): void
    {
        self::assertFalse(NgExtensions::owns('component', 'com_breezingforms'));
        // The module and the content plugin were named like this before they were renamed: what is still
        // registered under these names is a leftover.
        self::assertFalse(NgExtensions::owns('module', 'mod_breezingforms'));
        self::assertFalse(NgExtensions::owns('plugin', 'breezingforms', 'content'));
        self::assertFalse(NgExtensions::owns('plugin', 'breezingforms', 'system'));
        self::assertFalse(NgExtensions::owns('plugin', 'breezingforms', 'search'));
        // An administrator module of that name is not the site module of the package.
        self::assertFalse(NgExtensions::owns('module', 'mod_breezingformsng', '', 1));
        self::assertFalse(NgExtensions::owns('library', 'breezingforms'));
    }

    public function testAModuleRegistrationIsFoundByItsExtensionIdNotByModuleInstance(): void
    {
        $entry = ['type' => 'module', 'element' => 'mod_breezingforms', 'client_id' => 0, 'extension_id' => 11300];

        // The module manager's "id:" is the id of a module instance: it would show nothing here.
        self::assertSame(
            'index.php?option=com_installer&view=manage&filter[search]=id:11300',
            ExtensionAdminLink::forEntry($entry)
        );
        self::assertSame(
            'index.php?option=com_plugins&view=plugins&filter[search]=id:42',
            ExtensionAdminLink::forEntry(['type' => 'plugin', 'element' => 'x', 'extension_id' => 42])
        );
        self::assertSame(
            'index.php?option=com_templates&view=styles&client_id=0&filter[search]=old%20skin',
            ExtensionAdminLink::forEntry(['type' => 'template', 'element' => 'old skin', 'client_id' => 0, 'extension_id' => 9])
        );
    }
}
