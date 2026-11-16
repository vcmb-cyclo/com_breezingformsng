<?php

declare(strict_types=1);

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses

namespace Joomla\Database {
    interface DatabaseInterface
    {
    }
    final class ParameterType
    {
        public const INTEGER = 1;
    }
}

namespace Joomla\CMS\MVC\Model {
    class BaseDatabaseModel
    {
        public function getCurrentUser(): object
        {
            return (object) ['username' => 'test'];
        }
    }
}

namespace Joomla\CMS\Date {
    final class Date
    {
        public function toSql(): string
        {
            return '2026-01-01 00:00:00';
        }
    }
}

namespace {
    define('_JEXEC', 1);

    final class TestQuery
    {
        public function __call(string $name, array $arguments): self
        {
            return $this;
        }
    }

    final class TestDatabase implements \Joomla\Database\DatabaseInterface
    {
        public function createQuery(): TestQuery
        {
            return new TestQuery();
        }

        public function quoteName(string $name): string
        {
            return $name;
        }

        public function setQuery(TestQuery $query): self
        {
            return $this;
        }

        public function loadObjectList(): array
        {
            return [];
        }
    }

    require dirname(__DIR__, 3) . '/administrator/components/com_breezingformsng/src/Model/QuickmodeModel.php';
    $class = new \ReflectionClass(\Vcmb\Component\BreezingformsNG\Administrator\Model\QuickmodeModel::class);
    $model = $class->newInstanceWithoutConstructor();
    $class->getProperty('db')->setValue($model, new TestDatabase());
    $method = $class->getMethod('save2');
    try {
        $method->invoke($model, 123, '', '', '', base64_encode('{"properties":{}}'), [], 1);
        throw new \LogicException('Missing form was accepted');
    } catch (\RuntimeException $e) {
        echo $e->getMessage();
    }
}
