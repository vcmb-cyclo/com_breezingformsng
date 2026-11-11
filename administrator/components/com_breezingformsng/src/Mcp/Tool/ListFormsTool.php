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

/** Lists the forms of the site, with how many elements and records each has. */
final class ListFormsTool implements McpTool
{
    public function __construct(private readonly FormCatalog $catalog)
    {
    }

    public function name(): string
    {
        return 'bfng_list_forms';
    }

    public function title(): string
    {
        return 'List forms';
    }

    public function description(): string
    {
        return 'Lists the BreezingForms NG forms of the site, in their admin order, with their id, name, title, '
            . 'publication state, package, page count, editor (quickmode or classic), element count and record count. '
            . 'Results are paged: use limit and offset, and total to know how many match.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'search' => ['type' => 'string', 'maxLength' => 100, 'default' => '', 'description' => 'Text the name or the title contains.'],
                'state' => ['type' => 'string', 'enum' => ['all', 'published', 'unpublished'], 'default' => 'all', 'description' => 'Publication state of the forms.'],
                'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 25, 'description' => 'Forms to return.'],
                'offset' => ['type' => 'integer', 'minimum' => 0, 'default' => 0, 'description' => 'Forms to skip.'],
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
        $published = match ($arguments['state']) {
            'published' => true,
            'unpublished' => false,
            default => null,
        };
        $result = $this->catalog->listForms(trim((string) $arguments['search']), $published, (int) $arguments['limit'], (int) $arguments['offset']);

        return ['total' => $result['total'], 'offset' => (int) $arguments['offset'], 'forms' => $result['forms']];
    }
}
