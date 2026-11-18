<?php

declare(strict_types=1);

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses

namespace Joomla\CMS\MVC\Controller {
    class BaseController
    {
        protected object $app;

        public function __construct(object $app)
        {
            $this->app = $app;
        }

        public function checkToken(string $method): void
        {
            if ($method !== 'post') {
                throw new \RuntimeException('Wrong token method');
            }
        }

        public function getModel(string $name): object
        {
            return $name === 'FormOptions'
                ? new \Vcmb\Component\BreezingformsNG\Administrator\Model\FormOptionsModel()
                : new \Vcmb\Component\BreezingformsNG\Administrator\Model\FormModel();
        }
    }
}

namespace Vcmb\Component\BreezingformsNG\Administrator\Model {
    class FormOptionsModel
    {
        public function getForm(int $id): object
        {
            return (object) ['id' => $id, 'published' => 1];
        }
    }

    class FormModel
    {
        public function checkout(int $id): bool
        {
            return true;
        }
    }
}

namespace {
    define('_JEXEC', 1);

    final class TestInput
    {
        public function __construct(private int $form, private bool $validChunk)
        {
        }

        public function getInt(string $key, int $default): int
        {
            return match ($key) {
                'form' => $this->form,
                'chunksLength' => $this->validChunk ? 1 : 0,
                default => $default,
            };
        }

        public function getAlnum(string $key, string $default): string
        {
            return $this->validChunk ? 'test123' : $default;
        }

        public function getString(string $key, string $default): string
        {
            return $default;
        }
    }

    final class TestIdentity
    {
        public string $checked = '';

        public function __construct(private string $allowed)
        {
        }

        public function authorise(string $action, string $asset): bool
        {
            $this->checked = $action . ':' . $asset;
            return $action === $this->allowed;
        }
    }

    final class TestApplication
    {
        public string $status = '';

        public function __construct(public TestInput $input, public TestIdentity $identity)
        {
        }

        public function getInput(): TestInput
        {
            return $this->input;
        }

        public function getIdentity(): TestIdentity
        {
            return $this->identity;
        }

        public function setHeader(string $name, string $value, bool $replace): void
        {
            $this->status = $value;
        }

        public function close(): void
        {
            throw new \RuntimeException('closed');
        }
    }

    require dirname(__DIR__, 3)
        . '/administrator/components/com_breezingformsng/src/Controller/QuickmodeController.php';
    $identity = new TestIdentity($argv[2]);
    $form = (int) $argv[1];
    $required = $form > 0 ? 'core.edit' : 'core.create';
    $app = new TestApplication(new TestInput($form, $argv[2] !== $required), $identity);
    $controller = new \Vcmb\Component\BreezingformsNG\Administrator\Controller\QuickmodeController($app);
    try {
        $controller->doAjaxSave();
    } catch (\RuntimeException $e) {
        if ($e->getMessage() !== 'closed') {
            throw $e;
        }
    }
    echo json_encode([$app->status, $identity->checked], JSON_THROW_ON_ERROR);
}
