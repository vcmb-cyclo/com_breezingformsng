<?php

/**
 * Runs the code guard of QuickmodeModel::save() on an editor tree with a fake database.
 *
 * Usage: php quickmode-code-guard.php <form id> <rights, comma separated> <tree as JSON>
 *
 * It prints the tree the model would store and the code columns it left as stored.
 * It runs in its own process because it stands in for Joomla classes that other tests define differently.
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
    use Vcmb\Component\BreezingformsNG\Administrator\Model\QuickmodeModel;

    final class Query
    {
        public string $table = '';

        public function __call(string $method, array $arguments): self
        {
            if ($method === 'from') {
                $this->table = (string) $arguments[0];
            }

            return $this;
        }
    }

    /** The stored form 7: one element with code, one payment element with a download file. */
    final class Database implements DatabaseInterface
    {
        private Query $last;

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
            $this->last = $query;

            return $this;
        }

        /** @return list<array<string, mixed>> */
        public function loadAssocList(): array
        {
            return [
                ['id' => '11', 'script1cond' => '1', 'script1id' => '0', 'script1code' => "init();\n", 'script2cond' => '0', 'script2id' => '4', 'script2code' => '',
                    'script3cond' => '1', 'script3id' => '9', 'script3code' => '', 'script3msg' => 'Required'],
                ['id' => '12', 'script1cond' => '0', 'script1id' => '0', 'script1code' => '', 'script2cond' => '0', 'script2id' => '0', 'script2code' => '',
                    'script3cond' => '0', 'script3id' => '0', 'script3code' => '', 'script3msg' => ''],
            ];
        }

        /** @return array<string, mixed> */
        public function loadAssoc(): array
        {
            return [
                'script2cond' => '1',
                'script2code' => 'submitted();',
                'template_areas' => json_encode([['elements' => [['dbId' => 12, 'options' => ['filepath' => '/srv/files/book.pdf']]]]]),
            ];
        }
    }

    final class Probe extends QuickmodeModel
    {
        /** @param list<string> $rights */
        public function __construct(private array $rights)
        {
            (new \ReflectionProperty(QuickmodeModel::class, 'db'))->setValue($this, new Database());
        }

        public function getCurrentUser(): \Joomla\CMS\User\User
        {
            return new \Joomla\CMS\User\User($this->rights);
        }

        /**
         * @param array<string, mixed> $tree
         * @return array<string, mixed>
         */
        public function guard(int $form, array $tree): array
        {
            (new \ReflectionMethod(QuickmodeModel::class, 'guardCode'))->invokeArgs($this, [$form, &$tree]);

            return $tree;
        }
    }
}

namespace {
    [, $form, $rights, $json] = $argv;
    $model = new \Fixture\Probe($rights === '' ? [] : explode(',', $rights));
    $tree = $model->guard((int) $form, json_decode($json, true, 512, JSON_THROW_ON_ERROR));

    echo json_encode(['tree' => $tree, 'ignored' => $model->getIgnoredCodeColumns()], JSON_THROW_ON_ERROR);
}
