<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator\Mcp;

use Joomla\CMS\User\User;

if (!class_exists(User::class)) {
    eval(
        'namespace Joomla\\CMS\\User;'
        . 'class User {'
        . '  public $guest = 0;'
        . '  /** @var array<string, bool> */ public array $permissions = [];'
        . '  public function authorise($action, $assetname = null) { return $this->permissions[$action . "@" . $assetname] ?? false; }'
        . '}'
    );
}
if (!interface_exists(\Joomla\Database\DatabaseInterface::class)) {
    eval('namespace Joomla\\Database; interface DatabaseInterface {}');
}

final class McpTestDoubles
{
    /**
     * @param list<string> $actions Actions allowed on com_breezingformsng.
     */
    public static function user(array $actions): User
    {
        $user = new User();
        foreach ($actions as $action) {
            $user->permissions[$action . '@com_breezingformsng'] = true;
        }

        return $user;
    }
}
