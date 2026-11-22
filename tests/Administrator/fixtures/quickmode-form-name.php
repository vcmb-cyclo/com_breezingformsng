<?php

/**
 * Runs the name rule of QuickmodeModel::save() with a fake database.
 *
 * Usage: php quickmode-form-name.php <form id> <stored name> <name posted>
 *
 * It prints "ok" or the language key of the refusal. It runs in its own process because it stands in
 * for Joomla classes that other tests define differently.
 */

declare(strict_types=1);

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses

namespace {
    define('_JEXEC', 1);

    require dirname(__DIR__, 3) . '/vendor/autoload.php';
}

namespace Joomla\CMS\MVC\Model {
    class BaseDatabaseModel
    {
    }
}

namespace Joomla\CMS\Language {
    final class Text
    {
        public static function _(string $key): string
        {
            return $key;
        }
    }
}

namespace Joomla\Database {
    interface DatabaseInterface
    {
    }

    final class ParameterType
    {
        public const INTEGER = 1;
    }
}

namespace Fixture {
    use Joomla\Database\DatabaseInterface;
    use Vcmb\Component\BreezingformsNG\Administrator\Model\QuickmodeModel;

    final class Query
    {
        /** @param list<mixed> $arguments */
        public function __call(string $method, array $arguments): self
        {
            return $this;
        }
    }

    final class Database implements DatabaseInterface
    {
        public function __construct(private ?string $stored)
        {
        }

        public function createQuery(): Query
        {
            return new Query();
        }

        public function quoteName(mixed $name): mixed
        {
            return $name;
        }

        public function setQuery(Query $query): self
        {
            return $this;
        }

        public function loadResult(): ?string
        {
            return $this->stored;
        }
    }

    final class Probe extends QuickmodeModel
    {
        public function __construct(?string $stored)
        {
            (new \ReflectionProperty(QuickmodeModel::class, 'db'))->setValue($this, new Database($stored));
        }

        public function check(int $form, string $name): void
        {
            (new \ReflectionMethod(QuickmodeModel::class, 'assertFormNameAllowed'))->invoke($this, $form, $name);
        }
    }
}

namespace {
    [, $form, $stored, $name] = $argv;

    try {
        (new \Fixture\Probe((int) $form > 0 ? $stored : null))->check((int) $form, $name);
        echo 'ok';
    } catch (\InvalidArgumentException $e) {
        echo $e->getMessage();
    }
}
