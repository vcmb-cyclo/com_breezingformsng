<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Administrator\Service;

\defined('_JEXEC') or die;

use Joomla\CMS\Access\Exception\NotAllowed;
use Joomla\CMS\Language\Text;
use Joomla\CMS\User\User;

/** Permissions guarding the sensitive areas of the component. */
final class AreaAccess
{
    /** Pieces (PHP), scripts (JS) and integrator code. */
    public const CODE = 'breezingformsng.code';

    /** Package and configuration import/export. */
    public const CONFIGURATION = 'breezingformsng.configuration';

    public static function isAllowed(User $user, string $action): bool
    {
        return $user->authorise($action, 'com_breezingformsng');
    }

    /**
     * Repairs offered by the audit: site administrators (global "Access
     * Administration Interface") or users allowed to configure the component.
     */
    public static function canRepair(User $user): bool
    {
        return $user->authorise('core.manage') || $user->authorise('core.admin', 'com_breezingformsng');
    }

    public static function assertRepair(User $user): void
    {
        if (!self::canRepair($user)) {
            throw new NotAllowed(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }
    }

    public static function assert(User $user, string $action): void
    {
        if (!self::isAllowed($user, $action)) {
            throw new NotAllowed(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }
    }
}
