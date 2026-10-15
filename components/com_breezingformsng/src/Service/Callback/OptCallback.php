<?php
/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Site\Service\Callback;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\ParameterType;
use Joomla\Database\DatabaseInterface;
use Joomla\Filesystem\File;

/**
 * Double-opt-in / opt-out email confirmation endpoints.
 */
final class OptCallback
{
    public function __construct(
        private readonly CMSApplication $application,
        private readonly DatabaseInterface $database,
    ) {
    }

    public function optIn(): void
    {

        $database = $this->database;



    // DOUBLE OPT IN

    $jinput = $this->application->getInput();
    $ip = $jinput->server->get('REMOTE_ADDR');

    $userSubmitedID = $jinput->getString('id', '');
    $token = $jinput->getString('token', '');
    $optDate = HTMLHelper::date('now', 'Y-m-d H:i:s');
    $query = $database->createQuery()
        ->update($database->quoteName('#__facileforms_records'))
        ->set($database->quoteName('opted') . ' = 1')
        ->set($database->quoteName('opt_ip') . ' = :ip')
        ->set($database->quoteName('opt_date') . ' = :optDate')
        ->where($database->quoteName('opt_token') . ' = :token')
        ->where($database->quoteName('id') . ' = :userSubmitedID')
        ->where($database->quoteName('opted') . ' = 0')
        ->bind(':ip', $ip, ParameterType::STRING)
        ->bind(':optDate', $optDate, ParameterType::STRING)
        ->bind(':token', $token, ParameterType::STRING)
        ->bind(':userSubmitedID', $userSubmitedID, ParameterType::STRING);
    $database->setQuery($query);
    $database->execute();

    echo Text::_("COM_BREEZINGFORMSNG_FORMS_DOUBLE_OPT_EMAIL_THANK_YOU");

    // DOUBLE OPT IN END
    }

    public function optOut(): void
    {

        $database = $this->database;



    $jinput = $this->application->getInput();
    $ip = $jinput->server->get('REMOTE_ADDR');

    $userSubmitedID = $jinput->getString('id', '');
    $token = $jinput->getString('token', '');
    $optDate = HTMLHelper::date('now', 'Y-m-d H:i:s');
    $query = $database->createQuery()
        ->update($database->quoteName('#__facileforms_records'))
        ->set($database->quoteName('opted') . ' = 0')
        ->set($database->quoteName('opt_ip') . ' = :ip')
        ->set($database->quoteName('opt_date') . ' = :optDate')
        ->where($database->quoteName('opt_token') . ' = :token')
        ->where($database->quoteName('id') . ' = :userSubmitedID')
        ->where($database->quoteName('opted') . ' = 1')
        ->bind(':ip', $ip, ParameterType::STRING)
        ->bind(':optDate', $optDate, ParameterType::STRING)
        ->bind(':token', $token, ParameterType::STRING)
        ->bind(':userSubmitedID', $userSubmitedID, ParameterType::STRING);
    $database->setQuery($query);
    $database->execute();

    echo Text::_("COM_BREEZINGFORMSNG_FORMS_DOUBLE_OPT_OUT_EMAIL_THANK_YOU");
    }
}
