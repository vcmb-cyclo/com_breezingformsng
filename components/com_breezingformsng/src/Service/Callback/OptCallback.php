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
use Joomla\CMS\Session\Session;
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
    if (!(new OptLinkPolicy())->isUsable($userSubmitedID, $token)) {
        echo Text::_('JGLOBAL_RESOURCE_NOT_FOUND');

        return;
    }
        if (
            !$this->confirmed(
                OptConfirmationPage::IN,
                $userSubmitedID,
                $token,
                'COM_BREEZINGFORMSNG_FORMS_DOUBLE_OPT_CONFIRM_TEXT',
                'COM_BREEZINGFORMSNG_FORMS_DOUBLE_OPT_CONFIRM_BUTTON'
            )
        ) {
            return;
        }
    $optDate = HTMLHelper::date('now', 'Y-m-d H:i:s');
    $query = $database->createQuery()
        ->update($database->quoteName('#__facileforms_records'))
        ->set($database->quoteName('opted') . ' = 1')
        ->set($database->quoteName('opt_ip') . ' = :ip')
        ->set($database->quoteName('opt_date') . ' = :optDate')
        ->where('BINARY ' . $database->quoteName('opt_token') . ' = :token')
        ->where($database->quoteName('id') . ' = :userSubmitedID')
        ->where($database->quoteName('opted') . ' = 0')
        ->bind(':ip', $ip, ParameterType::STRING)
        ->bind(':optDate', $optDate, ParameterType::STRING)
        ->bind(':token', $token, ParameterType::STRING)
        ->bind(':userSubmitedID', $userSubmitedID, ParameterType::STRING);
    $database->setQuery($query);
    $database->execute();

    // A link used twice changes nothing but still names its record; a link that names none says so.
    if ($database->getAffectedRows() === 0 && !$this->recordHasToken((int) $userSubmitedID, $token)) {
        echo Text::_('JGLOBAL_RESOURCE_NOT_FOUND');

        return;
    }

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
    if (!(new OptLinkPolicy())->isUsable($userSubmitedID, $token)) {
        echo Text::_('JGLOBAL_RESOURCE_NOT_FOUND');

        return;
    }
        if (
            !$this->confirmed(
                OptConfirmationPage::OUT,
                $userSubmitedID,
                $token,
                'COM_BREEZINGFORMSNG_FORMS_DOUBLE_OPT_WITHDRAW_TEXT',
                'COM_BREEZINGFORMSNG_FORMS_DOUBLE_OPT_WITHDRAW_BUTTON'
            )
        ) {
            return;
        }
    $optDate = HTMLHelper::date('now', 'Y-m-d H:i:s');
    $query = $database->createQuery()
        ->update($database->quoteName('#__facileforms_records'))
        ->set($database->quoteName('opted') . ' = 0')
        ->set($database->quoteName('opt_ip') . ' = :ip')
        ->set($database->quoteName('opt_date') . ' = :optDate')
        ->where('BINARY ' . $database->quoteName('opt_token') . ' = :token')
        ->where($database->quoteName('id') . ' = :userSubmitedID')
        ->where($database->quoteName('opted') . ' = 1')
        ->bind(':ip', $ip, ParameterType::STRING)
        ->bind(':optDate', $optDate, ParameterType::STRING)
        ->bind(':token', $token, ParameterType::STRING)
        ->bind(':userSubmitedID', $userSubmitedID, ParameterType::STRING);
    $database->setQuery($query);
    $database->execute();

    // A link used twice changes nothing but still names its record; a link that names none says so.
    if ($database->getAffectedRows() === 0 && !$this->recordHasToken((int) $userSubmitedID, $token)) {
        echo Text::_('JGLOBAL_RESOURCE_NOT_FOUND');

        return;
    }

    echo Text::_("COM_BREEZINGFORMSNG_FORMS_DOUBLE_OPT_OUT_EMAIL_THANK_YOU");
    }

    /**
     * A link opened with GET only shows the confirmation form and changes nothing; the change is made by the
     * POST of its button, which carries the Joomla form token.
     */
    private function confirmed(string $mode, string $recordId, string $token, string $textKey, string $buttonKey): bool
    {
        if ($this->application->getInput()->getMethod() !== 'POST') {
            echo (new OptConfirmationPage())->render(
                $mode,
                Uri::root(true) . '/index.php?option=com_breezingformsng',
                $recordId,
                $token,
                Text::_($textKey),
                Text::_($buttonKey),
                HTMLHelper::_('form.token')
            );

            return false;
        }

        if (!Session::checkToken('post')) {
            echo Text::_('JINVALID_TOKEN_NOTICE');

            return false;
        }

        return true;
    }

    private function recordHasToken(int $recordId, string $token): bool
    {
        $database = $this->database;
        $query    = $database->createQuery()
            ->select('1')
            ->from($database->quoteName('#__facileforms_records'))
            ->where($database->quoteName('id') . ' = :recordId')
            ->where('BINARY ' . $database->quoteName('opt_token') . ' = :token')
            ->bind(':recordId', $recordId, ParameterType::INTEGER)
            ->bind(':token', $token, ParameterType::STRING);

        return (bool) $database->setQuery($query)->loadResult();
    }
}
