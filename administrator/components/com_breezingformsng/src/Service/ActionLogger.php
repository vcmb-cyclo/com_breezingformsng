<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Administrator\Service;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Factory\MVCFactoryServiceInterface;
use Joomla\Component\Actionlogs\Administrator\Model\ActionlogModel;

/** Records administrator actions in Joomla's User Actions Log. */
final class ActionLogger
{
    public const EXTENSION = 'com_breezingformsng';

    public const SAVED = 'SAVED';
    public const COPIED = 'COPIED';
    public const DELETED = 'DELETED';
    public const PUBLISHED = 'PUBLISHED';
    public const UNPUBLISHED = 'UNPUBLISHED';
    public const IMPORTED = 'IMPORTED';

    public const PIECE = 'PIECE';
    public const SCRIPT = 'SCRIPT';
    public const FORM = 'FORM';
    public const RECORD = 'RECORD';
    public const PACKAGE = 'PACKAGE';

    public function __construct(private readonly CMSApplicationInterface $app)
    {
    }

    /**
     * @param int[] $ids Identifiers of the affected items.
     */
    public function log(string $action, string $type, array $ids = [], string $title = ''): void
    {
        $loggable = (array) ComponentHelper::getParams('com_actionlogs')->get('loggable_extensions', []);

        if (!\in_array(self::EXTENSION, $loggable, true)) {
            return;
        }

        $component = $this->app->bootComponent('com_actionlogs');

        if (!$component instanceof MVCFactoryServiceInterface) {
            return;
        }

        $user = $this->app->getIdentity();
        $message = self::buildMessage($action, $type, $ids, $title, (int) $user->id, (string) $user->username);

        $model = $component->getMVCFactory()->createModel('Actionlog', 'Administrator', ['ignore_request' => true]);

        if (!$model instanceof ActionlogModel) {
            return;
        }

        $model->addLog([$message], self::languageKey($action), self::EXTENSION, (int) $user->id);
    }

    public static function languageKey(string $action): string
    {
        return 'COM_BREEZINGFORMSNG_ACTION_LOG_' . $action;
    }

    /**
     * @param int[] $ids
     *
     * @return array<string, int|string>
     */
    public static function buildMessage(
        string $action,
        string $type,
        array $ids,
        string $title,
        int $userId,
        string $username
    ): array {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));

        return [
            'action' => strtolower($action),
            'type' => 'COM_BREEZINGFORMSNG_ACTION_LOG_TYPE_' . $type,
            'id' => \count($ids) === 1 ? $ids[0] : 0,
            'ids' => $ids === [] ? '-' : '#' . implode(', #', $ids),
            'title' => $title,
            'userid' => $userId,
            'username' => $username,
            'accountlink' => 'index.php?option=com_users&task=user.edit&id=' . $userId,
        ];
    }
}
