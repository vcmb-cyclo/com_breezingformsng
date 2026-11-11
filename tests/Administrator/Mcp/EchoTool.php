<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator\Mcp;

use Joomla\CMS\User\User;
use Vcmb\Component\BreezingformsNG\Administrator\Mcp\McpTool;

require_once __DIR__ . '/McpTestDoubles.php';

/** A tool answering with what it is given, or failing as told. */
final class EchoTool implements McpTool
{
    public ?\Throwable $failure = null;

    public function __construct(private readonly string $name = 'echo', private readonly string $requires = 'core.manage')
    {
    }

    public function name(): string
    {
        return $this->name;
    }

    public function title(): string
    {
        return 'Echo';
    }

    public function description(): string
    {
        return 'Returns its arguments.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => ['text' => ['type' => 'string', 'maxLength' => 10, 'default' => 'none']],
            'additionalProperties' => false,
        ];
    }

    public function readOnly(): bool
    {
        return true;
    }

    public function allows(User $user): bool
    {
        return $user->authorise($this->requires, 'com_breezingformsng');
    }

    public function call(array $arguments, User $user): array
    {
        if ($this->failure !== null) {
            throw $this->failure;
        }

        return ['echo' => $arguments['text']];
    }
}
