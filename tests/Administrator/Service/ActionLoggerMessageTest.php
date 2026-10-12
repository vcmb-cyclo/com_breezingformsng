<?php

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator\Service;

use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Administrator\Service\ActionLogger;

final class ActionLoggerMessageTest extends TestCase
{
    public function testSingleItemMessageCarriesItsIdentifierAndTitle(): void
    {
        $message = ActionLogger::buildMessage(ActionLogger::SAVED, ActionLogger::PIECE, [12], 'ff_getvalue', 42, 'admin');

        self::assertSame(12, $message['id']);
        self::assertSame('#12', $message['ids']);
        self::assertSame('ff_getvalue', $message['title']);
        self::assertSame('COM_BREEZINGFORMSNG_ACTION_LOG_TYPE_PIECE', $message['type']);
        self::assertSame('admin', $message['username']);
        self::assertSame('index.php?option=com_users&task=user.edit&id=42', $message['accountlink']);
        self::assertSame('COM_BREEZINGFORMSNG_ACTION_LOG_SAVED', ActionLogger::languageKey(ActionLogger::SAVED));
    }

    public function testBatchMessageListsDistinctIdentifiers(): void
    {
        $message = ActionLogger::buildMessage(ActionLogger::DELETED, ActionLogger::RECORD, ['3', 5, 3, 0], '', 1, 'a');

        self::assertSame(0, $message['id']);
        self::assertSame('#3, #5', $message['ids']);
    }

    public function testRecordDeletionIsNotLoggedForAnEmptySelection(): void
    {
        $controller = file_get_contents(
            __DIR__ . '/../../../administrator/components/com_breezingformsng/src/Controller/RecordsController.php'
        );

        self::assertIsString($controller);
        self::assertMatchesRegularExpression(
            '/if \(\$ids !== \[\]\) \{\s+\(new ActionLogger\(\$app\)\)->log\(ActionLogger::DELETED, ActionLogger::RECORD/',
            $controller
        );
    }

    public function testEveryMessageKeyIsTranslatedInEnglish(): void
    {
        $ini = file_get_contents(
            __DIR__ . '/../../../administrator/components/com_breezingformsng/language/en-GB/com_breezingformsng.ini'
        );

        self::assertIsString($ini);

        $constants = (new \ReflectionClass(ActionLogger::class))->getConstants();
        unset($constants['EXTENSION']);

        foreach ($constants as $name => $value) {
            $isType = \in_array($name, ['PIECE', 'SCRIPT', 'FORM', 'RECORD', 'PACKAGE'], true);
            $key = 'COM_BREEZINGFORMSNG_ACTION_LOG_' . ($isType ? 'TYPE_' : '') . $value;

            self::assertStringContainsString("\n" . $key . '="', $ini, $key);
        }
    }
}
