<?php

/**
 * Runs one task of an administrator controller on stand-ins for the Joomla
 * runtime and prints what happened as JSON: the token checks, the models
 * reached, the HTTP status and the answer.
 *
 * Usage: php admin-controller-acl.php <forms|records|packages|integrator|recordstransfer|recordsimport|menus|pieces|scripts|piece|script> <task> <valid|invalid> <rights or "guest"> <posted data as JSON>
 *
 * It runs in its own process: the stand-ins would clash with the ones other tests define.
 */

declare(strict_types=1);

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses

namespace {
    define('_JEXEC', 1);

    require dirname(__DIR__, 3) . '/vendor/autoload.php';

    $GLOBALS['events'] = [];
}

namespace Joomla\CMS\Language {
    final class Text
    {
        public static function _(string $key): string
        {
            return $key;
        }

        public static function plural(string $key, mixed ...$arguments): string
        {
            return $key;
        }

        public static function sprintf(string $key, mixed ...$arguments): string
        {
            return $key;
        }
    }
}

namespace Joomla\CMS\Router {
    final class Route
    {
        public static function _(string $url, bool $xhtml = true): string
        {
            return $url;
        }
    }
}

namespace Joomla\CMS\Application {
    interface CMSApplicationInterface
    {
    }

    class CMSApplication implements CMSApplicationInterface
    {
    }
}

namespace Joomla\CMS\Component {
    final class ComponentHelper
    {
        public static function getParams(string $option): object
        {
            return new class {
                public function get(string $key, mixed $default = null): mixed
                {
                    return $default;
                }
            };
        }
    }
}

namespace Joomla\CMS\Form {
    final class Form
    {
    }
}

namespace Joomla\String {
    final class Inflector
    {
        public static function singularize(string $word): string
        {
            return rtrim($word, 's');
        }
    }
}

namespace Joomla\CMS\Access\Exception {
    class NotAllowed extends \RuntimeException
    {
    }
}

namespace Joomla\CMS\Response {
    final class JsonResponse
    {
        public function __construct(private mixed $data = null, private ?string $message = null, private bool $error = false)
        {
            $GLOBALS['events'][] = ['answer' => ['data' => $data, 'message' => $message, 'error' => $error]];
        }

        public function __toString(): string
        {
            return '';
        }
    }
}

namespace Joomla\Utilities {
    final class ArrayHelper
    {
        /** @return list<int> */
        public static function toInteger(mixed $array): array
        {
            return array_map('intval', (array) $array);
        }
    }
}

namespace Joomla\Database {
    interface DatabaseInterface
    {
    }
}

namespace Joomla\CMS\MVC\Factory {
    interface MVCFactoryInterface
    {
    }

    interface MVCFactoryServiceInterface
    {
    }
}

namespace Joomla\CMS\MVC\Model {
    class BaseDatabaseModel
    {
    }
}

namespace Joomla\CMS\User {
    final class User
    {
        public int $id = 3;

        /** @param list<string> $rights */
        public function __construct(private array $rights)
        {
        }

        public function authorise(string $action, string $asset): bool
        {
            $GLOBALS['events'][] = ['authorise' => $action . ':' . $asset];

            return in_array($action, $this->rights, true);
        }
    }
}

namespace Joomla\CMS {
    final class Factory
    {
        public static function getApplication(): object
        {
            return $GLOBALS['app'];
        }

        public static function getContainer(): object
        {
            return new class {
                public function get(string $id): object
                {
                    return new class {
                        public function __call(string $method, array $arguments): mixed
                        {
                            return match ($method) {
                                'loadColumn' => [],
                                'quoteName' => (string) $arguments[0],
                                default => $this,
                            };
                        }
                    };
                }
            };
        }
    }
}

namespace Vcmb\Component\BreezingformsNG\Administrator\Model {
    class FormModel
    {
        public function publish(array &$pks, int $state): bool
        {
            $GLOBALS['events'][] = ['model' => 'Form::publish', 'ids' => $pks, 'state' => $state];

            return true;
        }

        /** @param list<int> $ids */
        public function copy(array $ids): int
        {
            $GLOBALS['events'][] = ['model' => 'Form::copy', 'ids' => $ids];

            return count($ids);
        }
    }

    class FormOptionsModel
    {
        public function getForm(int $id): object
        {
            return (object) ['id' => $id, 'published' => 1];
        }

        public function setDebugMode(int $id, int $state): void
        {
            $GLOBALS['events'][] = ['model' => 'FormOptions::setDebugMode', 'id' => $id, 'state' => $state];
        }
    }

    class LegacyConversionModel
    {
        public function convert(int $id): void
        {
            $GLOBALS['events'][] = ['model' => 'LegacyConversion::convert', 'id' => $id];
        }
    }

    class RecordModel
    {
        public function setFlagSingle(int $id, string $column, int $flag): bool
        {
            $GLOBALS['events'][] = ['model' => 'Record::setFlagSingle', 'id' => $id, 'column' => $column, 'flag' => $flag];

            return true;
        }
    }

    class RecordsModel
    {
    }

    class MenuModel
    {
        public function copy(array $ids): int
        {
            $GLOBALS['events'][] = ['model' => 'Menu::copy', 'ids' => $ids];

            return count($ids);
        }
    }

    class PackageItemModel
    {
        public function __construct(private string $label = 'Piece')
        {
        }

        public function isTrashed(int $id): bool
        {
            return $id === 9;
        }

        public function publish(array &$pks, int $state): bool
        {
            $GLOBALS['events'][] = ['model' => $this->label . '::publish', 'ids' => $pks, 'state' => $state];

            return true;
        }

        /** @param list<int> $ids */
        public function copy(array $ids): int
        {
            $GLOBALS['events'][] = ['model' => $this->label . '::copy', 'ids' => $ids];

            return count($ids);
        }

        public function getError(): string
        {
            return '';
        }
    }

    class PieceModel extends PackageItemModel
    {
    }

    /** A model that records its creation and every method called on it. */
    abstract class RecordingModel
    {
        public function __construct()
        {
            $GLOBALS['events'][] = ['model' => $this->label()];
        }

        abstract protected function label(): string;

        /** @param list<mixed> $arguments */
        public function __call(string $method, array $arguments): mixed
        {
            $GLOBALS['events'][] = ['model' => $this->label() . '::' . $method];

            return $this->answer($method);
        }

        protected function answer(string $method): mixed
        {
            return [];
        }
    }

    class RecordstransferModel extends RecordingModel
    {
        protected function label(): string
        {
            return 'Recordstransfer';
        }
    }

    class RecordsimportModel extends RecordingModel
    {
        protected function label(): string
        {
            return 'Recordsimport';
        }

        protected function answer(string $method): mixed
        {
            return match ($method) {
                'getForm' => new \Joomla\CMS\Form\Form(),
                'validate' => false,
                default => [],
            };
        }
    }

    class IntegratorModel extends RecordingModel
    {
        protected function label(): string
        {
            return 'Integrator';
        }

        protected function answer(string $method): mixed
        {
            return match ($method) {
                'saveRule' => 7,
                'deleteRules' => ['refused' => [], 'deleted' => [1]],
                default => null,
            };
        }
    }

    class PackageTransferModel extends RecordingModel
    {
        public const SELECTABLE_PARTS = ['forms', 'scripts', 'pieces', 'menus'];

        protected function label(): string
        {
            return 'PackageTransfer';
        }

        protected function answer(string $method): mixed
        {
            return match ($method) {
                'export', 'exportSelection' => '{}',
                'deletePackage' => true,
                'getImportSummary' => ['package' => 'p', 'items' => [], 'templates' => 0, 'rules' => 0, 'unlinked' => 0, 'unresolved' => [], 'skipped' => []],
                'savePackage' => null,
                default => [],
            };
        }
    }
}

namespace Joomla\CMS\MVC\Controller {
    use Vcmb\Component\BreezingformsNG\Administrator\Model\FormModel;
    use Vcmb\Component\BreezingformsNG\Administrator\Model\FormOptionsModel;
    use Vcmb\Component\BreezingformsNG\Administrator\Model\MenuModel;
    use Vcmb\Component\BreezingformsNG\Administrator\Model\PieceModel;
    use Vcmb\Component\BreezingformsNG\Administrator\Model\RecordModel;
    use Vcmb\Component\BreezingformsNG\Administrator\Model\RecordsimportModel;
    use Vcmb\Component\BreezingformsNG\Administrator\Model\RecordstransferModel;

    final class TokenRejected extends \RuntimeException
    {
    }

    class BaseController
    {
        protected $app;
        protected $input;
        protected $factory;
        protected $option = 'com_breezingformsng';
        protected $text_prefix = '';
        protected $view_list = '';
        private string $task = '';

        /** @param array<string, mixed> $config */
        public function __construct($config = [], $factory = null, $app = null, $input = null)
        {
            $this->app = $app;
            $this->input = $input ?? $app->getInput();
            $this->factory = $factory;
            $this->view_list = strtolower((string) preg_replace('/Controller$/', '', (new \ReflectionClass($this))->getShortName()));
        }

        public function execute($task)
        {
            $this->task = (string) $task;
            $method = in_array($this->task, ['unpublish', 'trash', 'archive'], true) ? 'publish' : $this->task;

            if (method_exists($this, $method)) {
                $this->$method();
            } else {
                $GLOBALS['events'][] = ['task' => $this->task];
            }

            return true;
        }

        public function getTask(): string
        {
            return $this->task;
        }

        public function checkToken(string $method = 'post', bool $redirect = true): bool
        {
            $valid = $GLOBALS['token'] === 'valid';
            $GLOBALS['events'][] = ['token' => $method . ($redirect ? ':redirect' : ':return'), 'valid' => $valid];

            if (!$valid && $redirect) {
                // Joomla redirects with an error message and ends the request.
                throw new TokenRejected('invalid token');
            }

            return $valid;
        }

        public function getModel($name = 'Form', $prefix = 'Administrator', $config = [])
        {
            return match ($name) {
                'FormOptions' => new FormOptionsModel(),
                'Record' => new RecordModel(),
                'Menu' => new MenuModel(),
                'Piece', 'Script' => new PieceModel($name),
                'Recordstransfer' => new RecordstransferModel(),
                'Recordsimport' => new RecordsimportModel(),
                default => new FormModel(),
            };
        }

        public function setMessage(string $message, string $type = 'message'): void
        {
            $GLOBALS['events'][] = ['message' => $message, 'type' => $type];
        }

        public function setRedirect(string $url): void
        {
            $GLOBALS['events'][] = ['redirect' => $url];
        }
    }

    class FormController extends BaseController
    {
    }

    class AdminController extends BaseController
    {
        public function unregisterTask(string $task): void
        {
        }

        public function delete()
        {
            $GLOBALS['events'][] = ['parent' => 'AdminController::delete'];
        }

        public function publish()
        {
            $this->checkToken();
            $pks = [1];
            $this->getModel()->publish($pks, $this->getTask() === 'publish' ? 1 : 0);
        }
    }
}

namespace Joomla\CMS\Versioning {
    trait VersionableControllerTrait
    {
        public function loadhistory(): bool
        {
            return true;
        }
    }
}

namespace Fixture {
    final class Closed extends \RuntimeException
    {
    }

    final class PostInput
    {
        /** @param array<string, mixed> $data */
        public function __construct(private array $data)
        {
        }

        public function getInt(string $key, int $default = 0): int
        {
            return (int) ($this->data[$key] ?? $default);
        }

        public function getString(string $key, string $default = ''): string
        {
            return (string) ($this->data[$key] ?? $default);
        }

        public function getBool(string $key, bool $default = false): bool
        {
            return (bool) ($this->data[$key] ?? $default);
        }

        public function getRaw(string $key, mixed $default = null): mixed
        {
            return $this->data[$key] ?? $default;
        }

        public function get(string $key, mixed $default = null, string $filter = 'cmd'): mixed
        {
            return $this->data[$key] ?? $default;
        }
    }

    final class Input
    {
        public PostInput $post;
        public PostInput $files;

        /** @param array<string, mixed> $data */
        public function __construct(array $data)
        {
            $this->post = new PostInput($data);
            $this->files = new PostInput([]);
        }

        public function getInt(string $key, int $default = 0): int
        {
            return $this->post->getInt($key, $default);
        }

        public function getString(string $key, string $default = ''): string
        {
            return $this->post->getString($key, $default);
        }

        public function getBool(string $key, bool $default = false): bool
        {
            return $this->post->getBool($key, $default);
        }

        public function set(string $key, mixed $value): void
        {
        }

        public function get(string $key, mixed $default = null, string $filter = 'cmd'): mixed
        {
            return $this->post->get($key, $default, $filter);
        }
    }

    final class Application extends \Joomla\CMS\Application\CMSApplication
    {
        public ?string $mimeType = null;

        public function __construct(private Input $input, private ?\Joomla\CMS\User\User $identity)
        {
        }

        public function getInput(): Input
        {
            return $this->input;
        }

        public function getIdentity(): ?\Joomla\CMS\User\User
        {
            return $this->identity;
        }

        public function setHeader(string $name, string $value, bool $replace = false): void
        {
            if ($name === 'status') {
                $GLOBALS['events'][] = ['status' => $value];
            }
        }

        public function sendHeaders(): void
        {
        }

        public function enqueueMessage(string $message, string $type = 'message'): void
        {
            $GLOBALS['events'][] = ['message' => $message, 'type' => $type];
        }

        public function redirect(string $url): void
        {
            $GLOBALS['events'][] = ['redirect' => $url];
        }

        public function setUserState(string $key, mixed $value): void
        {
        }

        public function setBody(string $body): void
        {
        }

        public function close(): void
        {
            throw new Closed('closed');
        }

        public function bootComponent(string $name): object
        {
            return new class implements \Joomla\CMS\MVC\Factory\MVCFactoryServiceInterface {
                public function getMVCFactory(): object
                {
                    return new class {
                        public function createModel(string $name): object
                        {
                            return match ($name) {
                                'PackageTransfer' => new \Vcmb\Component\BreezingformsNG\Administrator\Model\PackageTransferModel(),
                                'Integrator' => new \Vcmb\Component\BreezingformsNG\Administrator\Model\IntegratorModel(),
                                default => new \Vcmb\Component\BreezingformsNG\Administrator\Model\RecordModel(),
                            };
                        }
                    };
                }
            };
        }
    }
}

namespace {
    [, $controller, $task, $token, $rights, $json] = $argv;
    $GLOBALS['token'] = $token;
    $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    $input = new \Fixture\Input($data);
    $user = $rights === 'guest' ? null : new \Joomla\CMS\User\User($rights === '' ? [] : explode(',', $rights));
    $factory = new class implements \Joomla\CMS\MVC\Factory\MVCFactoryInterface {
        public function createModel(string $name, string $prefix = '', array $config = [])
        {
            return new \Vcmb\Component\BreezingformsNG\Administrator\Model\LegacyConversionModel();
        }
    };

    $app = new \Fixture\Application($input, $user);
    $GLOBALS['app'] = $app;

    $class = match ($controller) {
        'forms' => \Vcmb\Component\BreezingformsNG\Administrator\Controller\FormsController::class,
        'records' => \Vcmb\Component\BreezingformsNG\Administrator\Controller\RecordsController::class,
        'packages' => \Vcmb\Component\BreezingformsNG\Administrator\Controller\PackagesController::class,
        'integrator' => \Vcmb\Component\BreezingformsNG\Administrator\Controller\IntegratorController::class,
        'recordstransfer' => \Vcmb\Component\BreezingformsNG\Administrator\Controller\RecordstransferController::class,
        'recordsimport' => \Vcmb\Component\BreezingformsNG\Administrator\Controller\RecordsimportController::class,
        'menus' => \Vcmb\Component\BreezingformsNG\Administrator\Controller\MenusController::class,
        'pieces' => \Vcmb\Component\BreezingformsNG\Administrator\Controller\PiecesController::class,
        'scripts' => \Vcmb\Component\BreezingformsNG\Administrator\Controller\ScriptsController::class,
        'piece' => \Vcmb\Component\BreezingformsNG\Administrator\Controller\PieceController::class,
        'script' => \Vcmb\Component\BreezingformsNG\Administrator\Controller\ScriptController::class,
    };
    $instance = new $class([], $factory, $app, $input);
    $stopped = null;

    // The controllers with a gate in execute() are run through it, as Joomla does.
    $throughExecute = in_array($controller, ['integrator', 'recordstransfer', 'recordsimport', 'pieces', 'scripts', 'piece', 'script'], true);

    ob_start();
    try {
        $throughExecute ? $instance->execute($task) : $instance->$task();
    } catch (\Throwable $e) {
        $stopped = $e::class;
    }
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    // Controllers that answer directly may have written before: the result follows the marker.
    echo "\n@@RESULT@@" . json_encode(['stopped' => $stopped, 'events' => $GLOBALS['events']], JSON_THROW_ON_ERROR);
}
