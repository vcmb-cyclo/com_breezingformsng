<?php

/**
 * Runs FormOptionsModel::saveForm() with a fake database and prints what it wrote.
 *
 * Usage: php form-options-code-guard.php <form id> <rights, comma separated> <posted data as JSON>
 *
 * It runs in its own process because it stands in for the Joomla classes the
 * model needs, which other tests define differently.
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

namespace Joomla\CMS\Access\Exception {
    class NotAllowed extends \RuntimeException
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

namespace Joomla\CMS\Date {
    final class Date
    {
        public function toSql(): string
        {
            return '2026-01-01 00:00:00';
        }
    }
}

namespace Joomla\CMS\Plugin {
    final class PluginHelper
    {
        public static function importPlugin(string $group): void
        {
        }
    }
}

namespace Joomla\Event {
    class Event
    {
        public function __construct(string $name, array $arguments = [])
        {
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

namespace Joomla\CMS\User {
    class User
    {
        public string $username = 'editor';

        /** @param list<string> $rights */
        public function __construct(private array $rights)
        {
        }

        public function authorise(string $action, string $asset): bool
        {
            return in_array($action, $this->rights, true);
        }
    }
}

namespace Fixture {
    use Joomla\Database\DatabaseInterface;
    use Vcmb\Component\BreezingformsNG\Administrator\Model\FormOptionsModel;

    final class Query
    {
        /** @var list<string> */
        public array $sets = [];
        /** @var list<string> */
        public array $columns = [];

        public function __call(string $method, array $arguments): self
        {
            if ($method === 'set') {
                $this->sets[] = (string) $arguments[0];
            }
            if ($method === 'columns') {
                $this->columns = $arguments[0];
            }

            return $this;
        }
    }

    final class Database implements DatabaseInterface
    {
        /** @var list<Query> */
        public array $queries = [];
        /** @var list<string> */
        public array $quoted = [];

        public function createQuery(): Query
        {
            return $this->queries[] = new Query();
        }

        public function quoteName(string $name): string
        {
            return $name;
        }

        public function quote(string $value): string
        {
            $this->quoted[] = $value;

            return "'" . $value . "'";
        }

        public function setQuery(mixed $query): self
        {
            return $this;
        }

        public function execute(): bool
        {
            return true;
        }

        public function insertid(): int
        {
            return 5;
        }

        /** @return list<int> */
        public function loadColumn(): array
        {
            return [];
        }
    }

    final class Probe extends FormOptionsModel
    {
        public Database $db;

        /** @param list<string> $rights */
        public function __construct(private array $rights, private ?\stdClass $stored)
        {
            $this->db = new Database();
        }

        public function getCurrentUser(): \Joomla\CMS\User\User
        {
            return new \Joomla\CMS\User\User($this->rights);
        }

        public function getDatabase(): Database
        {
            return $this->db;
        }

        public function getDispatcher(): object
        {
            return new class {
                public function dispatch(string $name, object $event): void
                {
                }
            };
        }

        public function getForm(int $id, bool $forUpdate = false): ?\stdClass
        {
            return $this->stored;
        }
    }
}

namespace {
    [, $id, $rights, $json] = $argv;
    $stored = (object) [
        'id' => (int) $id,
        'published' => 0,
        'package' => '',
        'template_areas' => '',
        'piece1code' => "echo 'a';",
        'piece2code' => '',
        'piece3code' => '',
        'piece4code' => '',
        'script1code' => '',
        'script2code' => '',
    ];
    $model = new \Fixture\Probe($rights === '' ? [] : explode(',', $rights), (int) $id > 0 ? $stored : null);
    $result = ['error' => null, 'written' => [], 'ignored' => []];

    try {
        $model->saveForm(['id' => (int) $id] + json_decode($json, true, 512, JSON_THROW_ON_ERROR));
    } catch (\Throwable $e) {
        $result['error'] = [$e::class, $e->getCode()];
    }

    foreach ($model->db->queries as $query) {
        foreach ($query->sets as $set) {
            // `column = 'value'`
            [$column, $value] = explode(' = ', $set, 2);
            $result['written'][$column] = trim($value, "'");
        }
        foreach ($query->columns as $position => $column) {
            $result['written'][$column] = $model->db->quoted[$position] ?? null;
        }
    }
    $result['ignored'] = $model->getIgnoredCodeColumns();

    echo json_encode($result, JSON_THROW_ON_ERROR);
}
