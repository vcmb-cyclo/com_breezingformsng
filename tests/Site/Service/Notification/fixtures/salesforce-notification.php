<?php

declare(strict_types=1);

// Isolated process doubles deliberately share this fixture.
// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses, Squiz.Classes.ValidClassName.NotPascalCase

namespace Joomla\CMS\Log {
    final class Log
    {
        public const ERROR = 3;
        public static function add(string $message, int $priority, string $category): void
        {
            file_put_contents(SITE_ROOT . '/test-log', $message);
        }
    }
}

namespace Joomla\CMS\Language {
    final class Text
    {
        public static function _(string $key): string
        {
            return 'Generic error';
        }
    }
}

namespace Vcmb\Component\BreezingformsNG\Site\Service\Integration {
    final class SalesforceClient
    {
        public function createRecord(string $username, string $password, string $type, array $fields): string
        {
            if (FAIL_REQUEST) {
                throw new \RuntimeException('secret-marker');
            }

            return 'record-marker';
        }
    }
}

namespace {
    define('_JEXEC', 1);
    define('SITE_ROOT', $argv[1]);
    define('JPATH_SITE', SITE_ROOT);
    define('FAIL_REQUEST', $argv[2] === 'failure');

    require dirname(__DIR__, 5)
        . '/administrator/components/com_breezingformsng/plugins/breezingforms_addons/salesforce/src/Service/'
        . 'SalesforceSubmissionHandler.php';

    $handler = new \Vcmb\Plugin\BreezingformsAddons\Salesforce\Service\SalesforceSubmissionHandler();
    $handler->handle([
        'salesforce_enabled' => 1,
        'salesforce_fields' => '',
        'salesforce_username' => 'user',
        'salesforce_password' => 'password',
        'salesforce_token' => 'token',
        'salesforce_type' => 'Contact',
    ], []);
}
