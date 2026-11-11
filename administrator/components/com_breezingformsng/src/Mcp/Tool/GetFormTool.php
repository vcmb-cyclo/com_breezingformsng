<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Administrator\Mcp\Tool;

\defined('_JEXEC') or die;

use Joomla\CMS\User\User;
use Vcmb\Component\BreezingformsNG\Administrator\Mcp\FormCatalog;
use Vcmb\Component\BreezingformsNG\Administrator\Mcp\McpTool;
use Vcmb\Component\BreezingformsNG\Administrator\Mcp\ToolException;
use Vcmb\Component\BreezingformsNG\Administrator\Service\AreaAccess;

/**
 * Reads one form: its settings and its elements, page by page. Its PHP and
 * JavaScript code is given only to the users allowed to edit code.
 */
final class GetFormTool implements McpTool
{
    public function __construct(private readonly FormCatalog $catalog)
    {
    }

    public function name(): string
    {
        return 'bfng_get_form';
    }

    public function title(): string
    {
        return 'Get a form';
    }

    public function description(): string
    {
        return 'Reads one BreezingForms NG form, by id or by name: its title, description, state, settings and record count, '
            . 'and its elements in page and display order with their name, title, type and data (data1 to data3: '
            . 'the value, the options or the HTML of the element, as its type uses them). '
            . 'Texts over ' . FormCatalog::MAX_TEXT . ' characters end with [truncated]. '
            . 'Integration credentials and notification addresses are never returned. '
            . 'Set include_code to also get the scripts and pieces of the form and its elements: '
            . 'this needs the permission to edit code.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer', 'minimum' => 1, 'description' => 'Id of the form. Give id or name.'],
                'name' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 255, 'description' => 'Name of the form. Give id or name.'],
                'include_code' => ['type' => 'boolean', 'default' => false, 'description' => 'Also return the PHP and JavaScript code.'],
            ],
            'additionalProperties' => false,
        ];
    }

    public function readOnly(): bool
    {
        return true;
    }

    public function allows(User $user): bool
    {
        return $user->authorise('core.manage', 'com_breezingformsng');
    }

    public function call(array $arguments, User $user): array
    {
        $id = $arguments['id'] ?? null;
        $name = $arguments['name'] ?? null;
        if (($id === null) === ($name === null)) {
            throw new ToolException('Give either the id or the name of the form.');
        }
        if ($arguments['include_code'] && !AreaAccess::isAllowed($user, AreaAccess::CODE)) {
            throw new ToolException('You are not allowed to read the code of forms: call again without include_code.');
        }

        $form = $this->catalog->getForm($id === null ? null : (int) $id, $name === null ? null : (string) $name, (bool) $arguments['include_code']);
        if ($form === null) {
            throw new ToolException('No form has this ' . ($id === null ? 'name.' : 'id.'));
        }

        return $form;
    }
}
