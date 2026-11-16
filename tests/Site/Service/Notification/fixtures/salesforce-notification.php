<?php

declare(strict_types=1);

// Isolated process doubles deliberately share this fixture and the legacy processor name.
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

namespace Vcmb\Component\BreezingformsNG\Site\Service\Export {
    final class ExportEngine
    {
    }
}

namespace {
    define('_JEXEC', 1);
    define('SITE_ROOT', $argv[1]);
    define('JPATH_SITE', SITE_ROOT);
    define('FAIL_REQUEST', $argv[2] === 'failure');

    class HTML_facileFormsProcessor
    {
        public object $formrow;
        public array $sfdata = [];
    }

    require dirname(__DIR__, 5) . '/components/com_breezingformsng/src/Service/Notification/NotificationEngine.php';

    $processor = new HTML_facileFormsProcessor();
    $processor->formrow = (object) [
        'salesforce_enabled' => 1,
        'salesforce_fields' => '',
        'salesforce_username' => 'user',
        'salesforce_password' => 'password',
        'salesforce_token' => 'token',
        'salesforce_type' => 'Contact',
    ];
    $engine = new \Vcmb\Component\BreezingformsNG\Site\Service\Notification\NotificationEngine(
        $processor,
        new \Vcmb\Component\BreezingformsNG\Site\Service\Export\ExportEngine()
    );
    $engine->sendSalesforceNotification();
}
