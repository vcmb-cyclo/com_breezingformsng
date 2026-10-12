<?php

// PHPStan stub, not a real implementation.

/**
 * PHPStan stub for the Joomla core User Actions Log model. The component
 * lives outside joomla-stubs/libraries, so its class is declared here.
 */

namespace Joomla\Component\Actionlogs\Administrator\Model;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;

class ActionlogModel extends BaseDatabaseModel
{
    /**
     * @param array<int, array<string, mixed>> $messages
     * @param string                           $messageLanguageKey
     * @param string                           $context
     * @param int                              $userId
     *
     * @return void
     */
    public function addLog($messages, $messageLanguageKey, $context, $userId = 0)
    {
    }
}
