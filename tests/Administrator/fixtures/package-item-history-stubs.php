<?php

/**
 * Stand-ins for the Joomla runtime under PackageItemModel: a model with a
 * version history that records what it is asked, and a database that answers
 * the history queries of the model. Loaded by a test running in its own process.
 */

declare(strict_types=1);

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses

namespace Joomla\CMS\Language {
    final class Text
    {
        public static function _(string $key): string
        {
            return $key;
        }

        public static function sprintf(string $key, mixed ...$arguments): string
        {
            return $key;
        }
    }
}

namespace Joomla\CMS\Component {
    final class ComponentHelper
    {
        public static bool $historyEnabled = true;

        public static function getParams(string $option): object
        {
            return new class {
                public function get(string $key, mixed $default = null): mixed
                {
                    return $key === 'save_history' ? \Joomla\CMS\Component\ComponentHelper::$historyEnabled : $default;
                }
            };
        }
    }
}

namespace Joomla\CMS\Date {
    final class Date
    {
    }
}

namespace Joomla\CMS\Application {
    class CMSApplication
    {
    }
}

namespace Joomla\CMS {
    final class Factory
    {
    }
}

namespace Joomla\CMS\Form {
    interface FormFactoryInterface
    {
    }
}

namespace Joomla\CMS\MVC\Factory {
    interface MVCFactoryInterface
    {
    }
}

namespace Joomla\CMS\Versioning {
    interface VersionableModelInterface
    {
    }

    trait VersionableModelTrait
    {
        protected $ignoreChanges = [];

        /** What the model of the history stores: each call is a row, unless the state is already there. */
        public function saveHistory(array $data, string $context): bool
        {
            if (!\Joomla\CMS\Component\ComponentHelper::getParams('x')->get('save_history', 0)) {
                return false;
            }

            return $this->storeHistory($context, (int) $this->getState('piece.id'), (object) []);
        }

        protected function storeHistory(string $typeAlias, int $id, mixed $data, string $note = ''): bool
        {
            \Fixture\History::$events[] = 'store:' . $typeAlias . '.' . $id;
            \Fixture\History::$lastVersion++;

            return true;
        }
    }
}

namespace Joomla\CMS\MVC\Model {
    class AdminModel
    {
        public $typeAlias = '';
        /** @var array<string, mixed> */
        private array $state = [];

        public function __construct($config = [], $factory = null, $formFactory = null)
        {
        }

        protected function canDelete($record)
        {
            return true;
        }

        public function save($data)
        {
            return true;
        }

        public function delete(&$pks)
        {
            return true;
        }

        public function getName(): string
        {
            return 'piece';
        }

        public function setState(string $key, mixed $value): void
        {
            $this->state[$key] = $value;
        }

        public function getState(string $key): mixed
        {
            return $this->state[$key] ?? null;
        }

        public function getDatabase(): \Fixture\Db
        {
            return new \Fixture\Db();
        }

        public function getTable($name = '', $prefix = 'Administrator', $options = [])
        {
            return new class {
                public function load(int $id): bool
                {
                    return true;
                }
            };
        }
    }
}

namespace Joomla\Database {
    interface DatabaseInterface
    {
    }

    final class ParameterType
    {
        public const INTEGER = 'int';
        public const STRING = 'string';
    }
}

namespace Joomla\Utilities {
    final class ArrayHelper
    {
        public static function fromObject(object $object): array
        {
            return get_object_vars($object);
        }

        public static function toObject(array $array): object
        {
            return (object) $array;
        }
    }
}

namespace Fixture {
    final class History
    {
        /** @var list<string> */
        public static array $events = [];
        public static int $lastVersion = 0;
        /** Whether the item already has a version, for the version of origin. */
        public static bool $hasVersions = false;
    }

    final class Query
    {
        public string $text = '';

        public function __call(string $method, array $arguments): self
        {
            $this->text .= ' ' . $method . '(' . implode(',', array_map(static fn (mixed $a): string => (string) $a, $arguments)) . ')';

            return $this;
        }
    }

    final class Db implements \Joomla\Database\DatabaseInterface
    {
        private static ?Query $last = null;

        public function createQuery(): Query
        {
            return new Query();
        }

        public function quoteName(string $name): string
        {
            return $name;
        }

        public function setQuery(Query $query): self
        {
            self::$last = $query;

            return $this;
        }

        public function execute(): bool
        {
            $text = self::$last?->text ?? '';
            History::$events[] = str_contains($text, 'version_note') ? 'note' : (str_contains($text, 'keep_forever') ? 'keep_forever' : 'execute');

            return true;
        }

        public function loadResult(): mixed
        {
            return History::$lastVersion;
        }

        public function loadColumn(): array
        {
            return History::$hasVersions ? ['com_breezingformsng.piece.7'] : [];
        }
    }
}
