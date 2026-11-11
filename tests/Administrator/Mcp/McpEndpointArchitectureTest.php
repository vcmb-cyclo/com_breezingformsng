<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator\Mcp;

use PHPUnit\Framework\TestCase;

/** How the MCP endpoint is shipped, routed and protected. */
final class McpEndpointArchitectureTest extends TestCase
{
    private const ROOT = __DIR__ . '/../../..';

    private static function read(string $path): string
    {
        return (string) file_get_contents(self::ROOT . '/' . $path);
    }

    public function testThePackageShipsTheApiControllerAndThePlugin(): void
    {
        $manifest = self::read('breezingformsng.xml');
        self::assertStringContainsString('<files folder="api/components/com_breezingformsng">', $manifest);
        self::assertFileExists(self::ROOT . '/api/components/com_breezingformsng/src/Controller/McpController.php');
        self::assertMatchesRegularExpression('/^\s+api \\\\$/m', self::read('scripts/build-package.sh'));

        $plugin = self::read('administrator/components/com_breezingformsng/plugins/webservices/breezingformsng.xml');
        self::assertStringContainsString('group="webservices"', $plugin);
        foreach (['de-DE', 'en-GB', 'es-ES', 'fr-FR', 'hu-HU', 'it-IT', 'nl-NL', 'tr-TR'] as $tag) {
            self::assertStringContainsString($tag . '/plg_webservices_breezingformsng.sys.ini', $plugin);
        }
    }

    public function testMcpIsOffUntilEnabledInTheOptionsAndThenRoutedAndAnswered(): void
    {
        self::assertStringContainsString("\$plugins['webservices'] = ['breezingformsng'];", self::read('script.php'));

        $config = self::read('administrator/components/com_breezingformsng/config.xml');
        self::assertMatchesRegularExpression('/name="mcp_enabled"[^>]*default="0"/s', $config);

        $plugin = self::read('administrator/components/com_breezingformsng/plugins/webservices/src/Extension/Breezingformsng.php');
        $hook = substr($plugin, (int) strpos($plugin, 'public function onBeforeApiRoute'));
        self::assertLessThan(strpos($hook, 'addRoutes'), strpos($hook, 'if (!McpAvailability::isEnabled())'));

        $controller = self::read('api/components/com_breezingformsng/src/Controller/McpController.php');
        self::assertLessThan(strpos($controller, '$app->login('), strpos($controller, 'if (!McpAvailability::isEnabled())'));
    }

    public function testThePublicRouteIsAuthenticatedByTheControllerBeforeAnythingElse(): void
    {
        $plugin = self::read('administrator/components/com_breezingformsng/plugins/webservices/src/Extension/Breezingformsng.php');
        self::assertStringContainsString("'public' => true", $plugin);
        self::assertStringContainsString("'mcp.handle'", $plugin);

        $controller = self::read('api/components/com_breezingformsng/src/Controller/McpController.php');
        $handle = substr($controller, (int) strpos($controller, 'public function handle()'));
        $login = strpos($handle, "\$app->login(['username' => ''], ['silent' => true, 'action' => 'core.login.api'])");
        self::assertNotFalse($login);
        foreach (["authorise('core.manage', 'com_breezingformsng')", 'php://input', 'McpServerFactory::create'] as $later) {
            self::assertGreaterThan($login, strpos($handle, $later), $later . ' comes after the login');
        }
        self::assertStringContainsString('respond(401', $handle);
        self::assertStringContainsString('HTTP_ORIGIN', $handle);
    }

    public function testTheFormCatalogNeverReadsCredentialsOrAddresses(): void
    {
        $catalog = self::read('administrator/components/com_breezingformsng/src/Mcp/FormCatalog.php');
        preg_match_all("/'([a-z0-9_]+)'/", substr($catalog, 0, (int) strpos($catalog, 'public function __construct')), $columns);

        foreach (['mailchimp_api_key', 'salesforce_password', 'salesforce_token', 'dropbox_password', 'dropbox_email', 'emailadr', 'alt_mailfrom', 'opt_mail'] as $secret) {
            self::assertNotContains($secret, $columns[1], $secret . ' is not read');
        }
    }
}
