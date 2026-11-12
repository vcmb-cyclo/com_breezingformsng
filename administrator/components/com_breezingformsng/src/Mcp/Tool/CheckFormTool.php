<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Administrator\Mcp\Tool;

\defined('_JEXEC') or die;

use Joomla\CMS\User\User;
use Vcmb\Component\BreezingformsNG\Administrator\Mcp\FormChecker;
use Vcmb\Component\BreezingformsNG\Administrator\Mcp\McpTool;
use Vcmb\Component\BreezingformsNG\Administrator\Mcp\ToolException;

/**
 * Finds the inconsistencies of one form and proposes library validations for
 * its text fields. It changes nothing: applying a proposal is left to the user.
 */
final class CheckFormTool implements McpTool
{
    public function __construct(private readonly FormChecker $checker)
    {
    }

    public function name(): string
    {
        return 'bfng_check_form';
    }

    public function title(): string
    {
        return 'Check a form';
    }

    public function description(): string
    {
        return 'Checks one BreezingForms NG form, by id or by name, and changes nothing. '
            . 'It reports errors (input element without name, duplicate element names, library init, action or validation '
            . 'script that no longer exists, is unpublished or has the wrong type) and warnings (input element without '
            . 'label, element on a page the form does not have, custom script with empty code). '
            . 'It also proposes a library validation (by name and script id) for text fields without validation whose '
            . 'name or label suggests an e-mail address, a URL, a date, a whole number or an amount: these are proposals '
            . 'from the wording only, for the user to confirm and apply in the form editor. '
            . 'At most ' . FormChecker::MAX_ISSUES . ' issues are returned.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer', 'minimum' => 1, 'description' => 'Id of the form. Give id or name.'],
                'name' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 255, 'description' => 'Name of the form. Give id or name.'],
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

        $report = $this->checker->check($id === null ? null : (int) $id, $name === null ? null : (string) $name);
        if ($report === null) {
            throw new ToolException('No form has this ' . ($id === null ? 'name.' : 'id.'));
        }

        return $report;
    }
}
