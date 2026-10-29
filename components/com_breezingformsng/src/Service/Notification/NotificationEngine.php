<?php
/**
 * BreezingForms NG - A Joomla Forms Application
 *
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2008-2020 by Markus Bopp
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 **/

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Site\Service\Notification;

\defined('_JEXEC') or die;

use HTML_facileFormsProcessor;
use Joomla\Database\DatabaseInterface;
use Joomla\Event\Event;
use Joomla\Event\EventInterface;
use Joomla\CMS\Uri\Uri;
use Joomla\Filesystem\Folder;
use Joomla\Filesystem\File;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\Filesystem\Path;
use Joomla\CMS\Environment\Browser;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Log\Log;
use Vcmb\Component\BreezingformsNG\Site\Service\Export\ExportEngine;
use Vcmb\Component\BreezingformsNG\Site\Service\Integration\MailchimpClient;
use Vcmb\Component\BreezingformsNG\Site\Service\Integration\SalesforceClient;
use Vcmb\Component\BreezingformsNG\Site\Service\QuickMode\TranslationResolver;
use Vcmb\Component\BreezingformsNG\Site\Service\Runtime\SubmissionTimestampFormatter;
use CB\Component\Contentbuilderng\Administrator\Helper\ContentbuilderngHelper;
use CB\Component\Contentbuilderng\Administrator\Helper\FormSourceFactory;
use CB\Component\Contentbuilderng\Administrator\Service\ArticleService;
use CB\Component\Contentbuilderng\Administrator\Service\ListSupportService;
use CB\Component\Contentbuilderng\Administrator\Service\PermissionService;

/**
 * Admin/mailback/Salesforce/MailChimp notifications and translations.
 */
final class NotificationEngine
{
    private ?TranslationResolver $quickModeTranslationResolverService = null;
    private ?SubmissionTimestampFormatter $notificationTimestampFormatterService = null;
    private ?MailTemplateResolver $mailTemplateResolverService = null;

    public function __construct(
        private readonly HTML_facileFormsProcessor $processor,
        private readonly ExportEngine $exportEngine
    )
    {
    }

    /**
     * @return null|void
     */
    function sendEmailNotification()
    {
        global $ff_config;

        if ($this->processor->dying)
            return;


        $from = $this->processor->formrow->alt_mailfrom != '' ? $this->processor->formrow->alt_mailfrom : $this->processor->app->get('mailfrom');
        $fromname = $this->processor->formrow->alt_fromname != '' ? $this->processor->formrow->alt_fromname : $this->processor->app->get('fromname');
        if ($this->processor->formrow->emailntf == 2)
            $recipient = $this->processor->formrow->emailadr;
        else
            $recipient = $ff_config->emailadr;

        $recipients = explode(';', $recipient);
        $cleaned_recipients = array();

        $alt_sender = '';

        foreach ($recipients as $recipient) {

            $test = explode(':', $recipient);
            if (count($test) == 2 && strtolower(trim($test[0])) == 'sender') {
                $alt_sender = trim($test[1]);
            } else {
                $cleaned_recipients[] = $recipient;
            }
        }

        /*
          $customSender = false;
          $sender = $this->processor->app->getInput()->get('mailbackSender', array(), 'string');

          for ($i = 0; $i < $this->processor->rowcount; $i++) {
          $row = $this->processor->rows[$i];
          $mb = $this->processor->app->getInput()->get('ff_nm_' . $row->name, '', 'string');
          if ($row->mailback == 1) {
          $mbCnt = count($mb);
          for ($x = 0; $x < $mbCnt; $x++) {
          if (isset($mb[$x]) && trim($mb[$x]) != '' && bf_is_email(trim($mb[$x]))) {

          if (isset($sender[$row->name]) && !$customSender) {
          $alt_sender = trim($mb[$x]);
          $customSender = true;
          }
          }
          }
          }
          } */

        $recipients = $cleaned_recipients;
        $recipientsSize = count($recipients);

        // dynamic receipients
        $all_recipients = array();
        for ($i = 0; $i < $recipientsSize; $i++) {
            if (bf_startsWith(trim($recipients[$i]), '{') && bf_endsWith(trim($recipients[$i]), '}')) {
                $from_ = trim($recipients[$i]);
                $from_ = trim($from_, '{}');
                $froms = explode(':', $from_);
                $field = $froms[0];

                if (count($this->processor->maildata ?? [])) {
                    foreach ($this->processor->maildata ?? [] as $DATA) {
                        if (strtolower($DATA[_FF_DATA_NAME]) == strtolower($field)) {
                            if (isset($froms[1])) {
                                $valuepairs = explode(',', $froms[1]);
                                foreach ($valuepairs as $valuepair) {
                                    $keyval = explode('>', trim($valuepair));
                                    $key = trim($keyval[0]);
                                    if (isset($keyval[1])) {
                                        $value = trim($keyval[1]);
                                        $value_exploded = explode("|", $value);

                                        if ($DATA[_FF_DATA_TYPE] == 'Checkbox Group') {

                                            $data_value = explode(', ', strtolower($DATA[_FF_DATA_VALUE]));

                                            if (in_array(strtolower($key), $data_value)) {
                                                foreach ($value_exploded as $value2) {
                                                    $all_recipients[] = trim($value2);
                                                    unset($recipients[$i]);
                                                }
                                            }
                                        } else {

                                            if (strtolower($key) == strtolower($DATA[_FF_DATA_VALUE])) {
                                                foreach ($value_exploded as $value2) {
                                                    $all_recipients[] = trim($value2);
                                                    unset($recipients[$i]);
                                                }
                                                break;
                                            }
                                        }
                                    }
                                }
                            } else {
                                $all_recipients[] = $DATA[_FF_DATA_VALUE];
                                unset($recipients[$i]);
                            }
                            break;
                        }
                    }
                }
            }
        }

        if (count($all_recipients)) {
            $recipients = array_merge($all_recipients, $recipients);
            $recipientsSize = count($recipients);
        }

        $subject = Text::_('COM_BREEZINGFORMSNG_PROCESS_FORMRECRECEIVED');
        if ($this->processor->formrow->custom_mail_subject != '') {
            $subject = (string) $this->processor->formrow->custom_mail_subject;
        }
        $body = '';
        $isHtml = false;

        $user = $this->processor->app->getIdentity()
            ?? throw new \RuntimeException('The current user is not loaded.');

        if ($this->processor->formrow->email_type == 0) {

            $template = $this->mailTemplateResolver()->resolve(
                JPATH_SITE . '/media/breezingforms/mailtpl',
                (string) $this->processor->formrow->name,
                '',
                'mailtpl'
            );
            $tplFile = $template['file'];
            $foundTpl = $tplFile !== '';
            $isHtml = $template['isHtml'];

            if ($foundTpl) {

                $NL = nl();

                $PROCESS_RECORDSAVEDID = '';
                $RECORD_ID = '';

                if ($this->processor->record_id != '') {
                    $PROCESS_RECORDSAVEDID = Text::_('COM_BREEZINGFORMSNG_PROCESS_RECORDSAVEDID');
                    $RECORD_ID = $this->processor->record_id;
                }

                $PROCESS_FORMID = Text::_('COM_BREEZINGFORMSNG_PROCESS_FORMID');
                $FORM = $this->processor->form;

                $PROCESS_FORMTITLE = Text::_('COM_BREEZINGFORMSNG_PROCESS_FORMTITLE');
                $TITLE = $this->processor->formrow->title;

                $PROCESS_FORMNAME = Text::_('COM_BREEZINGFORMSNG_PROCESS_FORMNAME');
                $NAME = $this->processor->formrow->name;

                $PROCESS_SUBMITTEDAT = Text::_('COM_BREEZINGFORMSNG_PROCESS_SUBMITTEDAT');

                $SUBMITTED = $this->formattedNotificationTimestamp();

                $PROCESS_SUBMITTERIP = Text::_('COM_BREEZINGFORMSNG_PROCESS_SUBMITTERIP');
                $IP = $this->processor->ip;

                $PROCESS_PROVIDER = Text::_('COM_BREEZINGFORMSNG_PROCESS_PROVIDER');
                $PROVIDER = $this->processor->provider;

                $PROCESS_BROWSER = Text::_('COM_BREEZINGFORMSNG_PROCESS_BROWSER');
                $BROWSER = $this->processor->browser;

                $PROCESS_OPSYS = Text::_('COM_BREEZINGFORMSNG_PROCESS_OPSYS');
                $OPSYS = $this->processor->opsys;

                $PROCESS_SUBMITTERID = Text::_('COM_BREEZINGFORMSNG_PROCESS_SUBMITTERID');
                $SUBMITTERID = 0;

                $PROCESS_SUBMITTERUSERNAME = Text::_('COM_BREEZINGFORMSNG_PROCESS_SUBMITTERUSERNAME');
                $SUBMITTERUSERNAME = '-';

                $PROCESS_SUBMITTERFULLNAME = Text::_('COM_BREEZINGFORMSNG_PROCESS_SUBMITTERFULLNAME');
                $SUBMITTERFULLNAME = '-';

                if ($user->get('id', 0) > 0) {
                    $SUBMITTERID = $user->get('id', 0);
                    $SUBMITTERUSERNAME = $user->get('username', '');
                    $SUBMITTERFULLNAME = $user->get('name', '');
                }

                $MAILDATA = array();
                if (count($this->processor->maildata ?? [])) {
                    foreach ($this->processor->maildata ?? [] as $DATA) {
                        $subject = str_replace('{' . $DATA[_FF_DATA_NAME] . ':label}', strip_tags($DATA[_FF_DATA_TITLE]), $subject);
                        $subject = str_replace('{' . $DATA[_FF_DATA_NAME] . ':title}', strip_tags($DATA[_FF_DATA_TITLE]), $subject);
                        $subject = str_replace('{' . $DATA[_FF_DATA_NAME] . ':value}', $DATA[_FF_DATA_VALUE], $subject);
                        $subject = str_replace('{' . $DATA[_FF_DATA_NAME] . '}', $DATA[_FF_DATA_VALUE], $subject);
                        $MAILDATA[] = $DATA;
                    }
                }

                ob_start();
                include($tplFile);
                $body = ob_get_contents();
                ob_end_clean();
            } else {
                // fallback if no template exists

                $submitted = $this->formattedNotificationTimestamp();

                if ($this->processor->record_id != '')
                    $body .= Text::_('COM_BREEZINGFORMSNG_PROCESS_RECORDSAVEDID') . " " . $this->processor->record_id . nl() . nl();
                $body .= Text::_('COM_BREEZINGFORMSNG_PROCESS_FORMID') . ": " . $this->processor->form . nl() .
                    Text::_('COM_BREEZINGFORMSNG_PROCESS_FORMTITLE') . ": " . $this->processor->formrow->title . nl() .
                    Text::_('COM_BREEZINGFORMSNG_PROCESS_FORMNAME') . ": " . $this->processor->formrow->name . nl() . nl() .
                    Text::_('COM_BREEZINGFORMSNG_PROCESS_SUBMITTEDAT') . ": " . $submitted . nl() .
                    Text::_('COM_BREEZINGFORMSNG_PROCESS_SUBMITTERIP') . ": " . $this->processor->ip . nl() .
                    Text::_('COM_BREEZINGFORMSNG_PROCESS_SUBMITTERID') . ": " . $user->get('id', 0) . nl() .
                    Text::_('COM_BREEZINGFORMSNG_PROCESS_SUBMITTERUSERNAME') . ": " . $user->get('username', '') . nl() .
                    Text::_('COM_BREEZINGFORMSNG_PROCESS_SUBMITTERFULLNAME') . ": " . $user->get('name', '') . nl() .
                    Text::_('COM_BREEZINGFORMSNG_PROCESS_PROVIDER') . ": " . $this->processor->provider . nl() .
                    Text::_('COM_BREEZINGFORMSNG_PROCESS_BROWSER') . ": " . $this->processor->browser . nl() .
                    Text::_('COM_BREEZINGFORMSNG_PROCESS_OPSYS') . ": " . $this->processor->opsys . nl() . nl();
                if (count($this->processor->maildata ?? [])) {
                    foreach ($this->processor->maildata ?? [] as $data) {
                        $subject = str_replace('{' . $data[_FF_DATA_NAME] . ':label}', strip_tags($data[_FF_DATA_TITLE]), $subject);
                        $subject = str_replace('{' . $data[_FF_DATA_NAME] . ':title}', strip_tags($data[_FF_DATA_TITLE]), $subject);
                        $subject = str_replace('{' . $data[_FF_DATA_NAME] . ':value}', $data[_FF_DATA_VALUE], $subject);
                        $subject = str_replace('{' . $data[_FF_DATA_NAME] . '}', $data[_FF_DATA_VALUE], $subject);
                        $body .= strip_tags($data[_FF_DATA_TITLE]) . ": " . $data[_FF_DATA_VALUE] . nl();
                    }
                }
            }
        } else {

            $body = (string) $this->processor->formrow->email_custom_template;

            $RECORD_ID = '';
            if ($this->processor->record_id != '') {
                $RECORD_ID = (string) $this->processor->record_id;
            }

            $FORM = (string) $this->processor->form;
            $TITLE = (string) $this->processor->formrow->title;
            $FORMNAME = (string) $this->processor->formrow->name;
            $SUBMITTED = $this->formattedNotificationTimestamp();

            $IP = $this->processor->ip ?? '';
            $PROVIDER = $this->processor->provider ?? '';
            $BROWSER = $this->processor->browser ?? '';
            $OPSYS = $this->processor->opsys ?? '';
            $SUBMITTERID = '0';
            $SUBMITTERUSERNAME = '-';
            $SUBMITTERFULLNAME = '-';
            if ($user->get('id', 0) > 0) {
                $SUBMITTERID = (string) $user->get('id', 0);
                $SUBMITTERUSERNAME = $user->get('username', '');
                $SUBMITTERFULLNAME = $user->get('name', '');
            }

            $body = str_replace('{BF_RECORD_ID:label}', Text::_('COM_BREEZINGFORMSNG_PROCESS_RECORDSAVEDID'), $body);
            $body = str_replace('{BF_RECORD_ID:value}', $RECORD_ID, $body);

            $body = str_replace('{BF_FORM_ID:label}', Text::_('Form ID'), $body);
            $body = str_replace('{BF_FORM_ID:value}', (string) $this->processor->form_id, $body);

            $body = str_replace('{BF_FORM:label}', Text::_('COM_BREEZINGFORMSNG_PROCESS_FORMID'), $body);
            $body = str_replace('{BF_FORM:value}', $FORM, $body);

            $body = str_replace('{BF_TITLE:label}', Text::_('COM_BREEZINGFORMSNG_PROCESS_FORMTITLE'), $body);
            $body = str_replace('{BF_TITLE:value}', $TITLE, $body);

            $body = str_replace('{BF_FORMNAME:label}', Text::_('COM_BREEZINGFORMSNG_PROCESS_FORMNAME'), $body);
            $body = str_replace('{BF_FORMNAME:value}', $FORMNAME, $body);

            $body = str_replace('{BF_SUBMITTED:label}', Text::_('COM_BREEZINGFORMSNG_PROCESS_SUBMITTEDAT'), $body);
            $body = str_replace('{BF_SUBMITTED:value}', $SUBMITTED, $body);

            $body = str_replace('{BF_IP:label}', Text::_('COM_BREEZINGFORMSNG_PROCESS_SUBMITTERIP'), $body);
            $body = str_replace('{BF_IP:value}', $IP, $body);

            $body = str_replace('{BF_PROVIDER:label}', Text::_('COM_BREEZINGFORMSNG_PROCESS_PROVIDER'), $body);
            $body = str_replace('{BF_PROVIDER:value}', $PROVIDER, $body);

            $body = str_replace('{BF_BROWSER:label}', Text::_('COM_BREEZINGFORMSNG_PROCESS_BROWSER'), $body);
            $body = str_replace('{BF_BROWSER:value}', $BROWSER, $body);

            $body = str_replace('{BF_OPSYS:label}', Text::_('COM_BREEZINGFORMSNG_PROCESS_OPSYS'), $body);
            $body = str_replace('{BF_OPSYS:value}', $OPSYS, $body);

            $body = str_replace('{BF_SUBMITTERID:label}', Text::_('COM_BREEZINGFORMSNG_PROCESS_SUBMITTERID'), $body);
            $body = str_replace('{BF_SUBMITTERID:value}', $SUBMITTERID, $body);

            $body = str_replace('{BF_SUBMITTERUSERNAME:label}', Text::_('COM_BREEZINGFORMSNG_PROCESS_SUBMITTERUSERNAME'), $body);
            $body = str_replace('{BF_SUBMITTERUSERNAME:value}', $SUBMITTERUSERNAME, $body);

            $body = str_replace('{BF_SUBMITTERFULLNAME:label}', Text::_('COM_BREEZINGFORMSNG_PROCESS_SUBMITTERFULLNAME'), $body);
            $body = str_replace('{BF_SUBMITTERFULLNAME:value}', $SUBMITTERFULLNAME, $body);

            if (count($this->processor->savedata ?? [])) {
                foreach ($this->processor->savedata ?? [] as $data) {

                    $regex = "/([\{]hide " . $data[_FF_DATA_NAME] . "[\}])(.*)([\{][\/]hide[\}])/isU";

                    if ($data[_FF_DATA_VALUE] == '') {
                        $body = preg_replace($regex, "", $body) ?? '';
                    } else {
                        $body = preg_replace($regex, '$2', $body) ?? '';
                    }

                    if ($data[_FF_DATA_VALUE] == '') {
                        $subject = preg_replace($regex, "", $subject) ?? '';
                    } else {
                        $subject = preg_replace($regex, '$2', $subject) ?? '';
                    }
                }
            }

            if (count($this->processor->maildata ?? [])) {
                foreach ($this->processor->maildata ?? [] as $data) {

                    $subject = str_replace('{' . $data[_FF_DATA_NAME] . ':label}', strip_tags($data[_FF_DATA_TITLE]), $subject);
                    $subject = str_replace('{' . $data[_FF_DATA_NAME] . ':title}', strip_tags($data[_FF_DATA_TITLE]), $subject);
                    $subject = str_replace('{' . $data[_FF_DATA_NAME] . ':value}', $data[_FF_DATA_VALUE], $subject);
                    $subject = str_replace('{' . $data[_FF_DATA_NAME] . '}', $data[_FF_DATA_VALUE], $subject);
                    $body = str_replace('{' . $data[_FF_DATA_NAME] . ':label}', strip_tags($data[_FF_DATA_TITLE]), $body);
                    if ($this->processor->formrow->email_custom_html) {
                        $body = str_replace('{' . $data[_FF_DATA_NAME] . ':value}', str_replace(array("\n", "\r"), array('<br/>', ''), $data[_FF_DATA_VALUE]), $body);
                    } else {
                        $body = str_replace('{' . $data[_FF_DATA_NAME] . ':value}', $data[_FF_DATA_VALUE], $body);
                    }
                }
            }

            $body = preg_replace("/{([a-zA-Z0-9_\-])*:(label|value)}/", '', $body) ?? '';

            if ($this->processor->formrow->email_custom_html) {
                $isHtml = true;
            }
        }

        $attachment = NULL;
        if ($this->processor->formrow->emailxml > 0 && $this->processor->formrow->emailxml < 3) {
            $attachment = $this->exportEngine->expxml();
            if ($this->processor->status != _FF_STATUS_OK)
                return;
        } else if ($this->processor->formrow->emailxml == 3) {
            $attachment = $this->exportEngine->expcsv();
            if ($this->processor->status != _FF_STATUS_OK)
                return;
        } else if ($this->processor->formrow->emailxml == 4) {
            $attachment = $this->exportEngine->exppdf();
            if ($this->processor->status != _FF_STATUS_OK)
                return;
        }

        $sender = $this->processor->app->getInput()->get('mailbackSender', array(), 'string');
        for ($i = 0; $i < $this->processor->rowcount; $i++) {
            $row = ($this->processor->rows ?? [])[$i];
            $mb = $this->processor->app->getInput()->get('ff_nm_' . $row->name, '', 'string');
            if ($row->mailback == 1 && is_array($mb)) {
                $mbCnt = count($mb);
                for ($x = 0; $x < $mbCnt; $x++) {
                    if (isset($mb[$x]) && trim($mb[$x]) != '' && bf_is_email(trim($mb[$x]))) {
                        if (isset($sender[$row->name])) {
                            $from = trim($mb[$x]);
                            //$fromname = trim($mb[$x]);
                            break;
                        }
                    }
                }
            }
        }

        // dynamic mailfroms

        if (bf_startsWith(trim($from), '{') && bf_endsWith(trim($from), '}')) {
            $from_ = trim($from);
            $from_ = trim($from_, '{}');
            $froms = explode(':', $from_);
            $field = $froms[0];
            if (count($this->processor->maildata ?? [])) {
                foreach ($this->processor->maildata ?? [] as $DATA) {
                    if (strtolower($DATA[_FF_DATA_NAME]) == strtolower($field)) {
                        if (isset($froms[1])) {
                            $valuepairs = explode(',', $froms[1]);
                            foreach ($valuepairs as $valuepair) {
                                $keyval = explode('>', trim($valuepair));
                                $key = trim($keyval[0]);
                                if (isset($keyval[1])) {
                                    $value = trim($keyval[1]);

                                    if ($DATA[_FF_DATA_TYPE] == 'Checkbox Group') {

                                        $data_value = explode(', ', strtolower($DATA[_FF_DATA_VALUE]));

                                        if (in_array(strtolower($key), $data_value)) {
                                            $from = $value;
                                        }
                                    } else {

                                        if (strtolower($key) == strtolower($DATA[_FF_DATA_VALUE])) {
                                            $from = $value;
                                            break;
                                        }
                                    }
                                }
                            }
                        } else {
                            $from = $DATA[_FF_DATA_VALUE];
                        }
                        break;
                    }
                }
            }
        }

        if (bf_startsWith(trim($fromname), '{') && bf_endsWith(trim($fromname), '}')) {
            $fromname_ = trim($fromname);
            $fromname_ = trim($fromname_, '{}');
            $froms = explode(':', $fromname_);
            $field = $froms[0];
            if (count($this->processor->maildata ?? [])) {
                foreach ($this->processor->maildata ?? [] as $DATA) {
                    if (strtolower($DATA[_FF_DATA_NAME]) == strtolower($field)) {

                        if (isset($froms[1])) {
                            $valuepairs = explode(',', $froms[1]);
                            foreach ($valuepairs as $valuepair) {
                                $keyval = explode('>', trim($valuepair));
                                $key = trim($keyval[0]);
                                if (isset($keyval[1])) {
                                    $value = trim($keyval[1]);

                                    if ($DATA[_FF_DATA_TYPE] == 'Checkbox Group') {

                                        $data_value = explode(', ', strtolower($DATA[_FF_DATA_VALUE]));

                                        if (strtolower($key) == strtolower($DATA[_FF_DATA_VALUE])) {
                                            $fromname = $value;
                                        }
                                    } else {
                                        if (strtolower($key) == strtolower($DATA[_FF_DATA_VALUE])) {
                                            $fromname = $value;
                                            break;
                                        }
                                    }
                                }
                            }
                        } else {
                            $fromname = $DATA[_FF_DATA_VALUE];
                        }
                        break;
                    }
                }
            }
        }

        $signatures = array();

        $attachToAdminMail = $this->processor->app->getInput()->get('attachToAdminMail', array(), 'string');
        if (count($this->processor->maildata ?? [])) {
            foreach ($this->processor->maildata ?? [] as $data) {
                if (isset($attachToAdminMail[$data[_FF_DATA_NAME]])) {
                    if (isset($data[_FF_DATA_FILE_SERVERPATH])) {
                        $testEx = explode("\n", trim($data[_FF_DATA_FILE_SERVERPATH]));
                        $cntTestEx = count($testEx);
                        if ($cntTestEx > 1) {
                            for ($ex = 0; $ex < $cntTestEx; $ex++) {

                                if (strpos(strtolower($testEx[$ex]), '{cbsite}') === 0) {
                                    $testEx[$ex] = str_replace(array('{cbsite}', '{CBSite}'), array(JPATH_SITE, JPATH_SITE), $testEx[$ex]);
                                }

                                if (strpos(strtolower($testEx[$ex]), '{site}') === 0) {
                                    $testEx[$ex] = str_replace(array('{site}', '{site}'), array(JPATH_SITE, JPATH_SITE), $testEx[$ex]);
                                }

                                if (!is_array($attachment) && $attachment != '') {
                                    $attachment = array_merge(array(trim($testEx[$ex])), array($attachment));
                                } else if (is_array($attachment)) {
                                    $attachment = array_merge(array(trim($testEx[$ex])), $attachment);
                                } else {
                                    $attachment = trim($testEx[$ex]);
                                }
                            }
                        } else {

                            if (strpos(strtolower(trim($data[_FF_DATA_FILE_SERVERPATH])), '{cbsite}') === 0) {
                                $data[_FF_DATA_FILE_SERVERPATH] = str_replace(array('{cbsite}', '{CBSite}'), array(JPATH_SITE, JPATH_SITE), trim($data[_FF_DATA_FILE_SERVERPATH]));
                            }

                            if (strpos(strtolower(trim($data[_FF_DATA_FILE_SERVERPATH])), '{site}') === 0) {
                                $data[_FF_DATA_FILE_SERVERPATH] = str_replace(array('{site}', '{site}'), array(JPATH_SITE, JPATH_SITE), trim($data[_FF_DATA_FILE_SERVERPATH]));
                            }

                            if (!is_array($attachment) && $attachment != '') {
                                $attachment = array_merge(array(trim($data[_FF_DATA_FILE_SERVERPATH])), array($attachment));
                            } else if (is_array($attachment)) {
                                $attachment = array_merge(array(trim($data[_FF_DATA_FILE_SERVERPATH])), $attachment);
                            } else {
                                $attachment = trim($data[_FF_DATA_FILE_SERVERPATH]);
                            }
                        }
                    }
                }

                if ($data[_FF_DATA_TYPE] == 'Signature' && $this->processor->formrow->emailxml != 4) {

                    $signatures[] = JPATH_SITE . '/media/breezingforms/signatures/' . $data[_FF_DATA_VALUE];
                }
            }
        }

        if (is_array($attachment) && count($signatures) > 0) {

            $attachment = array_merge($attachment, $signatures);
        } else if (!is_array($attachment) && count($signatures) > 0) {
            $attachment = array_merge(array($attachment), $signatures);
        } else if (count($signatures) > 0) {
            $attachment = $signatures;
        }

        if (!$this->processor->sendNotificationAfterPayment) {
            for ($i = 0; $i < $recipientsSize; $i++) {
                $this->exportEngine->sendMail($from, $fromname, $recipients[$i], $subject, $body, $attachment, $isHtml, null, null, $alt_sender);
            }
        } else {

            $paymentCache = JPATH_SITE . '/media/breezingforms/payment_cache/';
            mt_srand();
            $paymentFile = $this->processor->form . '_' . $this->processor->record_id . '_admin_' . md5(date('YmdHis') . mt_rand(0, mt_getrandmax())) . '.txt';
            $i = 0;
            while (file_exists($paymentCache . $paymentFile)) {
                if ($i > 1000) {
                    break;
                }
                mt_srand();
                $paymentFile = $this->processor->form . '_' . $this->processor->record_id . '_admin_' . md5(date('YmdHis') . mt_rand(0, mt_getrandmax())) . '.txt';
                $i++;
            }

            if (!file_exists($paymentCache . $paymentFile)) {
                $later_content = serialize(
                    array(
                        'from' => $from,
                        'fromname' => $fromname,
                        'recipients' => $recipients,
                        'subject' => $subject,
                        'body' => $body,
                        'attachment' => $attachment,
                        'isHtml' => $isHtml,
                        'alt_sender' => $alt_sender
                    )
                );
                File::write($paymentCache . $paymentFile, $later_content);
            }
        }
    }

    // sendEmailNotification

    /**
     * @return string|void
     */
    function getFormTitleTranslated()
    {
        if (trim((string) $this->processor->formrow->template_code_processed) == 'QuickMode') {
            $dataObject = json_decode(bf_b64dec($this->processor->formrow->template_code), true);
            $default = ComponentHelper::getParams('com_languages')->get('site');

            if (!is_array($dataObject)) {
                return '';
            }

            $language = $this->processor->app->getLanguage()
                ?? throw new \RuntimeException('The application language is not loaded.');

            return $this->quickModeTranslationResolver()->formTitle(
                $dataObject,
                $language->getTag(),
                (string) $default
            );
        }
    }

    /**
     * @param string $field
     * @param mixed $name
     * @param mixed $res
     * @param mixed $dataObject
     * @param int $childrenLength
     *
     * @return null|void
     */
    function getFieldTranslated($field, $name, &$res, $dataObject = null, $childrenLength = 0)
    {

        if (count(LanguageHelper::getLanguages()) == 1) {
            return;
        }

        if (trim((string) $this->processor->formrow->template_code_processed) != 'QuickMode') {
            return;
        }

        if ($dataObject === null && $childrenLength == 0) {
            $dataObject = json_decode(bf_b64dec($this->processor->formrow->template_code), true);
        }

        if (!is_array($dataObject)) {
            return;
        }

        $default = ComponentHelper::getParams('com_languages')->get('site');
        $language = $this->processor->app->getLanguage()
            ?? throw new \RuntimeException('The application language is not loaded.');
        $translation = $this->quickModeTranslationResolver()->field(
            $dataObject,
            (string) $field,
            (string) $name,
            $language->getTag(),
            (string) $default
        );

        if ($translation !== null) {
            $res = $translation;
        }
    }

    private function quickModeTranslationResolver(): TranslationResolver
    {
        return $this->quickModeTranslationResolverService ??= new TranslationResolver();
    }

    private function formattedNotificationTimestamp(): string
    {
        return $this->notificationTimestampFormatter()->format(
            (string) $this->processor->submitted,
            (string) $this->processor->app->get('offset')
        )->submittedAt;
    }

    private function notificationTimestampFormatter(): SubmissionTimestampFormatter
    {
        return $this->notificationTimestampFormatterService ??= new SubmissionTimestampFormatter();
    }

    /**
     * @return null|void
     */
    function sendMailbackNotification()
    {
        global $ff_config;

        $signatures = array();

        if ($this->processor->dying)
            return;
        $from = $this->processor->formrow->mb_alt_mailfrom != '' ? $this->processor->formrow->mb_alt_mailfrom : $this->processor->app->get('mailfrom');
        $fromname = $this->processor->formrow->mb_alt_fromname != '' ? $this->processor->formrow->mb_alt_fromname : $this->processor->app->get('fromname');

        $_senders = '';
        if ($this->processor->formrow->emailntf == 2)
            $_senders = $this->processor->formrow->emailadr;
        else
            $_senders = $ff_config->emailadr;

        $_senders = explode(';', $_senders);

        $alt_sender = '';
        foreach ($_senders as $_sender) {

            $test = explode(':', $_sender);
            if (count($test) == 2 && strtolower(trim($test[0])) == 'sender') {
                $alt_sender = trim($test[1]);
            }
        }

        $accept = $this->processor->app->getInput()->get('mailbackConnectWith', array(), 'string');
        $sender = $this->processor->app->getInput()->get('mailbackSender', array(), 'string');
        $attachToUserMail = $this->processor->app->getInput()->get('attachToUserMail', array(), 'string');

        $mailbackfiles = array();
        $recipients = array();
        for ($i = 0; $i < $this->processor->rowcount; $i++) {
            $row = ($this->processor->rows ?? [])[$i];
            $mb = $this->processor->app->getInput()->get('ff_nm_' . $row->name, '', 'string');
            if ($row->mailback == 1) {
                $mbCnt = count($mb);
                for ($x = 0; $x < $mbCnt; $x++) {
                    if (isset($mb[$x]) && trim($mb[$x]) != '' && bf_is_email(trim($mb[$x]))) {
                        $yesno = array('false', '');
                        $checked = array('');
                        if (isset($accept[$row->name])) {
                            $yesno = explode('_', $accept[$row->name]);
                            $checked = $this->processor->app->getInput()->get('ff_nm_' . $yesno[1], '', 'string');
                        }

                        //if (isset($sender[$row->name]) && !$customSender) {
                        //    $from = trim($mb[$x]);
                        //    $fromname = trim($mb[$x]);
                        //    $customSender = true;
                        //}
                        if (!isset($accept[$row->name]) || (isset($accept[$row->name]) && $yesno[0] == 'true' && $checked[0] != '')) {
                            $recipients[] = trim($mb[$x]);
                            if (!isset($mailbackfiles[trim($mb[$x])]))
                                $mailbackfiles[trim($mb[$x])] = array();
                            if (count($this->processor->maildata ?? [])) {
                                foreach ($this->processor->maildata ?? [] as $data) {
                                    if (isset($data[_FF_DATA_FILE_SERVERPATH])) {
                                        if (isset($attachToUserMail[$data[_FF_DATA_NAME]])) {
                                            $testEx = explode("\n", trim($data[_FF_DATA_FILE_SERVERPATH]));
                                            $cntTestEx = count($testEx);
                                            if ($cntTestEx > 1) {
                                                for ($ex = 0; $ex < $cntTestEx; $ex++) {

                                                    if (strpos(strtolower(trim($testEx[$ex])), '{cbsite}') === 0) {
                                                        $testEx[$ex] = str_replace(array('{cbsite}', '{CBSite}'), array(JPATH_SITE, JPATH_SITE), trim($testEx[$ex]));
                                                    }

                                                    if (strpos(strtolower(trim($testEx[$ex])), '{site}') === 0) {
                                                        $testEx[$ex] = str_replace(array('{site}', '{site}'), array(JPATH_SITE, JPATH_SITE), trim($testEx[$ex]));
                                                    }

                                                    $mailbackfiles[trim($mb[$x])][] = trim($testEx[$ex]);
                                                }
                                            } else {

                                                if (strpos(strtolower(trim($data[_FF_DATA_FILE_SERVERPATH])), '{cbsite}') === 0) {
                                                    $data[_FF_DATA_FILE_SERVERPATH] = str_replace(array('{cbsite}', '{CBSite}'), array(JPATH_SITE, JPATH_SITE), trim($data[_FF_DATA_FILE_SERVERPATH]));
                                                }

                                                if (strpos(strtolower(trim($data[_FF_DATA_FILE_SERVERPATH])), '{site}') === 0) {
                                                    $data[_FF_DATA_FILE_SERVERPATH] = str_replace(array('{site}', '{site}'), array(JPATH_SITE, JPATH_SITE), trim($data[_FF_DATA_FILE_SERVERPATH]));
                                                }

                                                $mailbackfiles[trim($mb[$x])][] = trim($data[_FF_DATA_FILE_SERVERPATH]);
                                            }
                                        }
                                    }
                                }
                            }
                            if (trim($row->mailbackfile) != '' && file_exists(trim($row->mailbackfile))) {
                                $mailbackfiles[trim($mb[$x])][] = trim($row->mailbackfile);
                            }
                        }
                    }
                }
            }
        }

        $recipientsSize = count($recipients);

        $subject = Text::_('COM_BREEZINGFORMSNG_PROCESS_FORMRECRECEIVED');
        if ($this->processor->formrow->mb_custom_mail_subject != '') {
            $subject = (string) $this->processor->formrow->mb_custom_mail_subject;
        }

        $body = '';
        $isHtml = false;
        $filter = array();

        $areas = json_decode((string) $this->processor->formrow->template_areas, true);
        if (trim((string) $this->processor->formrow->template_code_processed) == 'QuickMode' && is_array($areas)) {
            foreach ($areas as $area) { // don't worry, size is only 1 in QM
                if (isset($area['elements'])) {
                    foreach ($area['elements'] as $element) {
                        if (isset($element['hideInMailback']) && $element['hideInMailback'] && isset($element['name'])) {
                            $filter[] = $element['name'];
                        }
                    }
                }
                break; // just in case
            }
        }

        // dynamic mailfroms

        if (bf_startsWith(trim($from), '{') && bf_endsWith(trim($from), '}')) {
            $from_ = trim($from);
            $from_ = trim($from_, '{}');
            $froms = explode(':', $from_);
            $field = $froms[0];
            if (count($this->processor->maildata ?? [])) {
                foreach ($this->processor->maildata ?? [] as $DATA) {
                    if (!in_array($DATA[_FF_DATA_NAME], $filter)) {
                        if (strtolower($DATA[_FF_DATA_NAME]) == strtolower($field)) {
                            if (isset($froms[1])) {
                                $valuepairs = explode(',', $froms[1]);
                                foreach ($valuepairs as $valuepair) {
                                    $keyval = explode('>', trim($valuepair));
                                    $key = trim($keyval[0]);
                                    if (isset($keyval[1])) {
                                        $value = trim($keyval[1]);

                                        if ($DATA[_FF_DATA_TYPE] == 'Checkbox Group') {

                                            $data_value = explode(', ', strtolower($DATA[_FF_DATA_VALUE]));

                                            if (in_array(strtolower($key), $data_value)) {
                                                $from = $value;
                                            }
                                        } else {

                                            if (strtolower($key) == strtolower($DATA[_FF_DATA_VALUE])) {
                                                $from = $value;
                                                break;
                                            }
                                        }
                                    }
                                }
                            } else {
                                $from = $DATA[_FF_DATA_VALUE];
                            }
                            break;
                        }
                    }
                }
            }
        }

        if (bf_startsWith(trim($fromname), '{') && bf_endsWith(trim($fromname), '}')) {
            $fromname_ = trim($fromname);
            $fromname_ = trim($fromname_, '{}');
            $froms = explode(':', $fromname_);
            $field = $froms[0];
            if (count($this->processor->maildata ?? [])) {
                foreach ($this->processor->maildata ?? [] as $DATA) {
                    if (!in_array($DATA[_FF_DATA_NAME], $filter)) {
                        if (strtolower($DATA[_FF_DATA_NAME]) == strtolower($field)) {
                            if (isset($froms[1])) {
                                $valuepairs = explode(',', $froms[1]);
                                foreach ($valuepairs as $valuepair) {
                                    $keyval = explode('>', trim($valuepair));
                                    $key = trim($keyval[0]);
                                    if (isset($keyval[1])) {
                                        $value = trim($keyval[1]);
                                        if ($DATA[_FF_DATA_TYPE] == 'Checkbox Group') {

                                            $data_value = explode(', ', strtolower($DATA[_FF_DATA_VALUE]));

                                            if (in_array(strtolower($key), $data_value)) {
                                                $fromname = $value;
                                            }
                                        } else {
                                            if (strtolower($key) == strtolower($DATA[_FF_DATA_VALUE])) {
                                                $fromname = $value;
                                                break;
                                            }
                                        }
                                    }
                                }
                            } else {
                                $fromname = $DATA[_FF_DATA_VALUE];
                            }
                            break;
                        }
                    }
                }
            }
        }

        $user = $this->processor->app->getIdentity()
            ?? throw new \RuntimeException('The current user is not loaded.');

        if ($this->processor->formrow->mb_email_type == 0) {

            $template = $this->mailTemplateResolver()->resolve(
                JPATH_SITE . '/media/breezingforms/mailtpl',
                (string) $this->processor->formrow->name,
                '_mailback',
                'mailbacktpl'
            );
            $tplFile = $template['file'];
            $foundTpl = $tplFile !== '';
            $isHtml = $template['isHtml'];

            if ($foundTpl) {

                $NL = nl();

                $PROCESS_RECORDSAVEDID = '';
                $RECORD_ID = '';

                if ($this->processor->record_id != '') {
                    $PROCESS_RECORDSAVEDID = Text::_('COM_BREEZINGFORMSNG_PROCESS_RECORDSAVEDID');
                    $RECORD_ID = $this->processor->record_id;
                }

                $PROCESS_FORMID = Text::_('COM_BREEZINGFORMSNG_PROCESS_FORMID');
                $FORM = $this->processor->form;

                $PROCESS_FORMTITLE = Text::_('COM_BREEZINGFORMSNG_PROCESS_FORMTITLE');

                $form_title_translated = $this->getFormTitleTranslated();
                $TITLE = $form_title_translated != '' ? $form_title_translated : $this->processor->formrow->title;

                $PROCESS_FORMNAME = Text::_('COM_BREEZINGFORMSNG_PROCESS_FORMNAME');
                $NAME = $this->processor->formrow->name;

                $PROCESS_SUBMITTEDAT = Text::_('COM_BREEZINGFORMSNG_PROCESS_SUBMITTEDAT');
                $SUBMITTED = $this->formattedNotificationTimestamp();

                $PROCESS_SUBMITTERIP = Text::_('COM_BREEZINGFORMSNG_PROCESS_SUBMITTERIP');
                $IP = $this->processor->ip;

                $PROCESS_PROVIDER = Text::_('COM_BREEZINGFORMSNG_PROCESS_PROVIDER');
                $PROVIDER = $this->processor->provider;

                $PROCESS_BROWSER = Text::_('COM_BREEZINGFORMSNG_PROCESS_BROWSER');
                $BROWSER = $this->processor->browser;

                $PROCESS_OPSYS = Text::_('COM_BREEZINGFORMSNG_PROCESS_OPSYS');
                $OPSYS = $this->processor->opsys;

                $PROCESS_SUBMITTERID = Text::_('COM_BREEZINGFORMSNG_PROCESS_SUBMITTERID');
                $SUBMITTERID = 0;

                $PROCESS_SUBMITTERUSERNAME = Text::_('COM_BREEZINGFORMSNG_PROCESS_SUBMITTERUSERNAME');
                $SUBMITTERUSERNAME = '-';

                $PROCESS_SUBMITTERFULLNAME = Text::_('COM_BREEZINGFORMSNG_PROCESS_SUBMITTERFULLNAME');
                $SUBMITTERFULLNAME = '-';

                if ($user->get('id', 0) > 0) {
                    $SUBMITTERID = $user->get('id', 0);
                    $SUBMITTERUSERNAME = $user->get('username', '');
                    $SUBMITTERFULLNAME = $user->get('name', '');
                }

                $MAILDATA = array();
                if (count($this->processor->maildata ?? [])) {
                    foreach ($this->processor->maildata ?? [] as $DATA) {
                        if (!in_array($DATA[_FF_DATA_NAME], $filter)) {
                            $trans_title = '';
                            $this->getFieldTranslated('label', $DATA[_FF_DATA_NAME], $trans_title);
                            $subject = str_replace('{' . $DATA[_FF_DATA_NAME] . ':label}', $trans_title != '' ? $trans_title : strip_tags($DATA[_FF_DATA_TITLE]), $subject);
                            $subject = str_replace('{' . $DATA[_FF_DATA_NAME] . ':title}', $trans_title != '' ? $trans_title : strip_tags($DATA[_FF_DATA_TITLE]), $subject);
                            $subject = str_replace('{' . $DATA[_FF_DATA_NAME] . ':value}', $DATA[_FF_DATA_VALUE], $subject);
                            $subject = str_replace('{' . $DATA[_FF_DATA_NAME] . '}', $DATA[_FF_DATA_VALUE], $subject);
                            $DATA[_FF_DATA_TITLE] = $trans_title != '' ? $trans_title : strip_tags($DATA[_FF_DATA_TITLE]);
                            $MAILDATA[] = $DATA;

                            if ($DATA[_FF_DATA_TYPE] == 'Signature' && $this->processor->formrow->mb_emailxml != 4) {

                                $signatures[] = JPATH_SITE . '/media/breezingforms/signatures/' . $DATA[_FF_DATA_VALUE];
                            }
                        }
                    }
                }

                ob_start();
                include($tplFile);
                $body = ob_get_contents();
                ob_end_clean();
            } else {
                // fallback if no template exists

                if ($this->processor->record_id != '')
                    $body .= Text::_('COM_BREEZINGFORMSNG_PROCESS_RECORDSAVEDID') . " " . $this->processor->record_id . nl() . nl();

                $form_title_translated = $this->getFormTitleTranslated();

                $submitted = $this->formattedNotificationTimestamp();

                $body .= Text::_('COM_BREEZINGFORMSNG_PROCESS_FORMID') . ": " . $this->processor->form . nl() .
                    Text::_('COM_BREEZINGFORMSNG_PROCESS_FORMTITLE') . ": " . ($form_title_translated != '' ? $form_title_translated : $this->processor->formrow->title) . nl() .
                    Text::_('COM_BREEZINGFORMSNG_PROCESS_FORMNAME') . ": " . $this->processor->formrow->name . nl() . nl() .
                    Text::_('COM_BREEZINGFORMSNG_PROCESS_SUBMITTEDAT') . ": " . $submitted . nl() .
                    Text::_('COM_BREEZINGFORMSNG_PROCESS_SUBMITTERIP') . ": " . $this->processor->ip . nl() .
                    Text::_('COM_BREEZINGFORMSNG_PROCESS_SUBMITTERID') . ": " . $user->get('id', 0) . nl() .
                    Text::_('COM_BREEZINGFORMSNG_PROCESS_SUBMITTERUSERNAME') . ": " . $user->get('username', '') . nl() .
                    Text::_('COM_BREEZINGFORMSNG_PROCESS_SUBMITTERFULLNAME') . ": " . $user->get('name', '') . nl() .
                    Text::_('COM_BREEZINGFORMSNG_PROCESS_PROVIDER') . ": " . $this->processor->provider . nl() .
                    Text::_('COM_BREEZINGFORMSNG_PROCESS_BROWSER') . ": " . $this->processor->browser . nl() .
                    Text::_('COM_BREEZINGFORMSNG_PROCESS_OPSYS') . ": " . $this->processor->opsys . nl() . nl();
                if (count($this->processor->maildata ?? [])) {
                    foreach ($this->processor->maildata ?? [] as $data) {
                        $trans_title = '';
                        $this->getFieldTranslated('label', $data[_FF_DATA_NAME], $trans_title);
                        $subject = str_replace('{' . $data[_FF_DATA_NAME] . ':label}', $trans_title != '' ? $trans_title : strip_tags($data[_FF_DATA_TITLE]), $subject);
                        $subject = str_replace('{' . $data[_FF_DATA_NAME] . ':title}', $trans_title != '' ? $trans_title : strip_tags($data[_FF_DATA_TITLE]), $subject);
                        $subject = str_replace('{' . $data[_FF_DATA_NAME] . ':value}', $data[_FF_DATA_VALUE], $subject);
                        $subject = str_replace('{' . $data[_FF_DATA_NAME] . '}', $data[_FF_DATA_VALUE], $subject);
                        if (!in_array($data[_FF_DATA_NAME], $filter)) {
                            $body .= strip_tags($data[_FF_DATA_TITLE]) . ": " . $data[_FF_DATA_VALUE] . nl();
                        }

                        if ($data[_FF_DATA_TYPE] == 'Signature' && $this->processor->formrow->mb_emailxml != 4) {

                            $signatures[] = JPATH_SITE . '/media/breezingforms/signatures/' . $data[_FF_DATA_VALUE];
                        }
                    }
                }
            }
        } else {

            $body = (string) $this->processor->formrow->mb_email_custom_template;

            $RECORD_ID = '';
            if ($this->processor->record_id != '') {
                $RECORD_ID = (string) $this->processor->record_id;
            }

            $FORM = (string) $this->processor->form;

            $form_title_translated = $this->getFormTitleTranslated();

            $TITLE = $form_title_translated != '' ? $form_title_translated : (string) $this->processor->formrow->title;
            $FORMNAME = (string) $this->processor->formrow->name;
            $SUBMITTED = $this->formattedNotificationTimestamp();

            $IP = $this->processor->ip ?? '';
            $PROVIDER = $this->processor->provider ?? '';
            $BROWSER = $this->processor->browser ?? '';
            $OPSYS = $this->processor->opsys ?? '';
            $SUBMITTERID = '0';
            $SUBMITTERUSERNAME = '-';
            $SUBMITTERFULLNAME = '-';
            if ($user->get('id', 0) > 0) {
                $SUBMITTERID = (string) $user->get('id', 0);
                $SUBMITTERUSERNAME = $user->get('username', '');
                $SUBMITTERFULLNAME = $user->get('name', '');
            }

            $body = str_replace('{BF_RECORD_ID:label}', Text::_('COM_BREEZINGFORMSNG_PROCESS_RECORDSAVEDID'), $body);
            $body = str_replace('{BF_RECORD_ID:value}', $RECORD_ID, $body);

            $body = str_replace('{BF_FORM_ID:label}', Text::_('Form ID'), $body);
            $body = str_replace('{BF_FORM_ID:value}', (string) $this->processor->form_id, $body);

            $body = str_replace('{BF_FORM:label}', Text::_('COM_BREEZINGFORMSNG_PROCESS_FORMID'), $body);
            $body = str_replace('{BF_FORM:value}', $FORM, $body);

            $body = str_replace('{BF_TITLE:label}', Text::_('COM_BREEZINGFORMSNG_PROCESS_FORMTITLE'), $body);
            $body = str_replace('{BF_TITLE:value}', $TITLE, $body);

            $body = str_replace('{BF_FORMNAME:label}', Text::_('COM_BREEZINGFORMSNG_PROCESS_FORMNAME'), $body);
            $body = str_replace('{BF_FORMNAME:value}', $FORMNAME, $body);

            $body = str_replace('{BF_SUBMITTED:label}', Text::_('COM_BREEZINGFORMSNG_PROCESS_SUBMITTEDAT'), $body);
            $body = str_replace('{BF_SUBMITTED:value}', $SUBMITTED, $body);

            $body = str_replace('{BF_IP:label}', Text::_('COM_BREEZINGFORMSNG_PROCESS_SUBMITTERIP'), $body);
            $body = str_replace('{BF_IP:value}', $IP, $body);

            $body = str_replace('{BF_PROVIDER:label}', Text::_('COM_BREEZINGFORMSNG_PROCESS_PROVIDER'), $body);
            $body = str_replace('{BF_PROVIDER:value}', $PROVIDER, $body);

            $body = str_replace('{BF_BROWSER:label}', Text::_('COM_BREEZINGFORMSNG_PROCESS_BROWSER'), $body);
            $body = str_replace('{BF_BROWSER:value}', $BROWSER, $body);

            $body = str_replace('{BF_OPSYS:label}', Text::_('COM_BREEZINGFORMSNG_PROCESS_OPSYS'), $body);
            $body = str_replace('{BF_OPSYS:value}', $OPSYS, $body);

            $body = str_replace('{BF_SUBMITTERID:label}', Text::_('COM_BREEZINGFORMSNG_PROCESS_SUBMITTERID'), $body);
            $body = str_replace('{BF_SUBMITTERID:value}', $SUBMITTERID, $body);

            $body = str_replace('{BF_SUBMITTERUSERNAME:label}', Text::_('COM_BREEZINGFORMSNG_PROCESS_SUBMITTERUSERNAME'), $body);
            $body = str_replace('{BF_SUBMITTERUSERNAME:value}', $SUBMITTERUSERNAME, $body);

            $body = str_replace('{BF_SUBMITTERFULLNAME:label}', Text::_('COM_BREEZINGFORMSNG_PROCESS_SUBMITTERFULLNAME'), $body);
            $body = str_replace('{BF_SUBMITTERFULLNAME:value}', $SUBMITTERFULLNAME, $body);

            if (count($this->processor->savedata ?? [])) {
                foreach ($this->processor->savedata ?? [] as $data) {

                    $regex = "/([\{]hide " . $data[_FF_DATA_NAME] . "[\}])(.*)([\{][\/]hide[\}])/isU";

                    if ($data[_FF_DATA_VALUE] == '') {
                        $body = preg_replace($regex, "", $body) ?? '';
                    } else {
                        $body = preg_replace($regex, '$2', $body) ?? '';
                    }

                    if ($data[_FF_DATA_VALUE] == '') {
                        $subject = preg_replace($regex, "", $subject) ?? '';
                    } else {
                        $subject = preg_replace($regex, '$2', $subject) ?? '';
                    }
                }
            }

            if (count($this->processor->maildata ?? [])) {
                foreach ($this->processor->maildata ?? [] as $data) {

                    $trans_title = '';
                    $this->getFieldTranslated('label', $data[_FF_DATA_NAME], $trans_title);
                    $subject = str_replace('{' . $data[_FF_DATA_NAME] . ':label}', $trans_title != '' ? $trans_title : strip_tags($data[_FF_DATA_TITLE]), $subject);
                    $subject = str_replace('{' . $data[_FF_DATA_NAME] . ':title}', $trans_title != '' ? $trans_title : strip_tags($data[_FF_DATA_TITLE]), $subject);
                    $subject = str_replace('{' . $data[_FF_DATA_NAME] . ':value}', $data[_FF_DATA_VALUE], $subject);
                    $subject = str_replace('{' . $data[_FF_DATA_NAME] . '}', $data[_FF_DATA_VALUE], $subject);
                    if (!in_array($data[_FF_DATA_NAME], $filter)) {
                        $body = str_replace('{' . $data[_FF_DATA_NAME] . ':label}', $trans_title != '' ? $trans_title : strip_tags($data[_FF_DATA_TITLE]), $body);
                        if ($this->processor->formrow->mb_email_custom_html) {
                            $body = str_replace('{' . $data[_FF_DATA_NAME] . ':value}', str_replace(array("\n", "\r"), array('<br/>', ''), $data[_FF_DATA_VALUE]), $body);
                        } else {
                            $body = str_replace('{' . $data[_FF_DATA_NAME] . ':value}', $data[_FF_DATA_VALUE], $body);
                        }
                    } else {
                        $body = str_replace('{' . $data[_FF_DATA_NAME] . ':label}', '', $body);
                        $body = str_replace('{' . $data[_FF_DATA_NAME] . ':value}', '', $body);
                    }

                    if ($data[_FF_DATA_TYPE] == 'Signature' && $this->processor->formrow->mb_emailxml != 4) {

                        $signatures[] = JPATH_SITE . '/media/breezingforms/signatures/' . $data[_FF_DATA_VALUE];
                    }
                }
            }

            $body = preg_replace("/{([a-zA-Z0-9_\-])*:(label|value)}/", '', $body) ?? '';

            if ($this->processor->formrow->mb_email_custom_html) {
                $isHtml = true;
            }
        }

        $attachment = NULL;
        if ($this->processor->formrow->mb_emailxml > 0 && $this->processor->formrow->mb_emailxml < 3) {
            $attachment = $this->exportEngine->expxml($filter, true, true);
            if ($this->processor->status != _FF_STATUS_OK)
                return;
        } else if ($this->processor->formrow->mb_emailxml == 3) {
            $attachment = $this->exportEngine->expcsv($filter, true);
            if ($this->processor->status != _FF_STATUS_OK)
                return;
        } else if ($this->processor->formrow->mb_emailxml == 4) {
            $attachment = $this->exportEngine->exppdf($filter, true, true);
            if ($this->processor->status != _FF_STATUS_OK)
                return;
        }

        if (!$this->processor->sendNotificationAfterPayment) {
            for ($i = 0; $i < $recipientsSize; $i++) {
                if (isset($mailbackfiles[$recipients[$i]])) {
                    if (!is_array($attachment) && $attachment != '') {
                        $attachment = array_merge($mailbackfiles[$recipients[$i]], array($attachment));
                    } else if (is_array($attachment)) {
                        $attachment = array_merge($mailbackfiles[$recipients[$i]], $attachment);
                    } else {
                        $attachment = $mailbackfiles[$recipients[$i]];
                    }
                }

                if (is_array($attachment) && count($signatures) > 0) {

                    $attachment = array_merge($attachment, $signatures);
                } else if (!is_array($attachment) && count($signatures) > 0) {
                    $attachment = array_merge(array($attachment), $signatures);
                } else if (count($signatures) > 0) {
                    $attachment = $signatures;
                }

                $this->exportEngine->sendMail($from, $fromname, $recipients[$i], $subject, $body, $attachment, $isHtml, null, null, $alt_sender);
            }
        } else {

            $paymentCache = JPATH_SITE . '/media/breezingforms/payment_cache/';
            mt_srand();
            $paymentFile = $this->processor->form . '_' . $this->processor->record_id . '_mailback_' . md5(date('YmdHis') . mt_rand(0, mt_getrandmax())) . '.txt';
            $i = 0;
            while (file_exists($paymentCache . $paymentFile)) {
                if ($i > 1000) {
                    break;
                }
                mt_srand();
                $paymentFile = $this->processor->form . '_' . $this->processor->record_id . '_mailback_' . md5(date('YmdHis') . mt_rand(0, mt_getrandmax())) . '.txt';
                $i++;
            }

            if (!file_exists($paymentCache . $paymentFile)) {

                for ($i = 0; $i < $recipientsSize; $i++) {
                    if (isset($mailbackfiles[$recipients[$i]])) {
                        if (!is_array($attachment) && $attachment != '') {
                            $attachment = array_merge($mailbackfiles[$recipients[$i]], array($attachment));
                        } else if (is_array($attachment)) {
                            $attachment = array_merge($mailbackfiles[$recipients[$i]], $attachment);
                        } else {
                            $attachment = $mailbackfiles[$recipients[$i]];
                        }
                    }
                }

                if (is_array($attachment) && count($signatures) > 0) {

                    $attachment = array_merge($attachment, $signatures);
                } else if (!is_array($attachment) && count($signatures) > 0) {
                    $attachment = array_merge(array($attachment), $signatures);
                } else if (count($signatures) > 0) {
                    $attachment = $signatures;
                }

                $later_content = serialize(
                    array(
                        'from' => $from,
                        'fromname' => $fromname,
                        'recipients' => $recipients,
                        'subject' => $subject,
                        'body' => $body,
                        'attachment' => $attachment,
                        'isHtml' => $isHtml,
                        'alt_sender' => $alt_sender
                    )
                );
                File::write($paymentCache . $paymentFile, $later_content);
            }
        }

        $this->processor->mailbackRecipients = $recipients;
    }

    /**
     * @return null|void
     */
    function sendSalesforceNotification()
    {

        if ($this->processor->formrow->salesforce_enabled != 1) {
            return;
        }

        try {
            $fields = array();
            $salesforceFields = $this->processor->formrow->salesforce_fields;
            $this->processor->formrow->salesforce_fields = is_array($salesforceFields) ? $salesforceFields : explode(',', (string) $salesforceFields);

            foreach ($this->processor->formrow->salesforce_fields as $sfields) {
                foreach ($this->processor->sfdata ?? [] as $savedata) {
                    $sfield = explode('::', $sfields);
                    if ($sfield[0] == $savedata[1]) {
                        $fields[$sfield[1]] = $savedata[4];
                        break;
                    }
                }
            }

            $recordId = (new SalesforceClient())->createRecord(
                trim((string) $this->processor->formrow->salesforce_username),
                (string) $this->processor->formrow->salesforce_password . (string) $this->processor->formrow->salesforce_token,
                trim((string) $this->processor->formrow->salesforce_type),
                $fields
            );
            $c = date('Y-m-d H:i:s') . ': Salesforce record created: ' . $recordId . "\r\n";

            file_put_contents(JPATH_SITE . '/sf.log', $c, FILE_APPEND);
        } catch (\Throwable $e) {

            $c = date('Y-m-d H:i:s') . ': ' . $e->getMessage() . "\r\n";
            file_put_contents(JPATH_SITE . '/sf.log', $c, FILE_APPEND);

            echo 'Salesforce Exception: ' . $e->getMessage();
            session_write_close();
            exit;
        }
    }

    /**
     * @return null|void
     */
    function sendMailChimpNotification()
    {

        if (trim((string) $this->processor->formrow->mailchimp_email_field) != '' && trim((string) $this->processor->formrow->mailchimp_api_key) != '' && trim((string) $this->processor->formrow->mailchimp_list_id) != '' && count($this->processor->maildata ?? [])) {

            $email = '';
            $htmlTextMobile = 'text';
            $checked = true;
            $unsubscribe = false;
            $mergeVars = array();
            $htmlTextMobileField = trim((string) $this->processor->formrow->mailchimp_text_html_mobile_field);
            $checkboxField = trim((string) $this->processor->formrow->mailchimp_checkbox_field);
            $unsubscribeField = trim((string) $this->processor->formrow->mailchimp_unsubscribe_field);
            $emailField = trim((string) $this->processor->formrow->mailchimp_email_field);
            $mergeVarFields = explode(',', str_replace(' ', '', (string) $this->processor->formrow->mailchimp_mergevars));
            $api = new MailchimpClient();
            $apiKey = trim((string) $this->processor->formrow->mailchimp_api_key);
            $list_ids = explode(',', trim((string) $this->processor->formrow->mailchimp_list_id));

            if ($checkboxField != '') {
                $box = $this->processor->app->getInput()->get('ff_nm_' . $checkboxField, array(''), 'string');
                if (isset($box[0]) && $box[0] != '') {
                    $checked = true;
                } else {
                    $checked = false;
                }
            }

            if ($unsubscribeField != '') {
                $box = $this->processor->app->getInput()->get('ff_nm_' . $unsubscribeField, array(''), 'string');
                if (isset($box[0]) && $box[0] != '') {
                    $unsubscribe = true;
                }
            }

            if ($htmlTextMobileField != '') {
                $selection = $this->processor->app->getInput()->get('ff_nm_' . $htmlTextMobileField, array(''), 'string');
                if (isset($selection[0]) && $selection[0] != '') {
                    $htmlTextMobile = $selection[0];
                }
            } else {
                $htmlTextMobile = $this->processor->formrow->mailchimp_default_type;
            }

            foreach ($this->processor->maildata ?? [] as $data) {
                switch ($data[_FF_DATA_NAME]) {
                    case $emailField:
                        $email = bf_is_email(trim($data[_FF_DATA_VALUE])) ? trim($data[_FF_DATA_VALUE]) : '';
                        break;
                    default:
                        if (in_array($data[_FF_DATA_NAME], $mergeVarFields)) {
                            $mergeVars[$data[_FF_DATA_NAME]] = $data[_FF_DATA_VALUE];
                        }
                }
            }

            // MailChimp API v3 update
            foreach ($list_ids as $list_id) {
                $resource = 'lists/' . rawurlencode(trim($list_id)) . '/members/' . md5(strtolower($email));

                try {
                    if ($email != '' && $checked) {
                        $api->request($apiKey, 'PUT', $resource, [
                            'email_address' => $email,
                            'merge_fields' => (object) $mergeVars,
                            'status_if_new' => ($this->processor->formrow->mailchimp_double_optin ? 'pending' : 'subscribed'),
                            'status' => ($this->processor->formrow->mailchimp_double_optin ? 'pending' : 'subscribed'),
                            'email_type' => $htmlTextMobile,
                        ]);
                    } else if ($email != '' && $unsubscribe) {
                        $api->request($apiKey, 'PUT', $resource, ['status' => 'unsubscribed']);
                        if ($this->processor->formrow->mailchimp_delete_member) {
                            $api->request($apiKey, 'DELETE', $resource);
                        }
                    }
                } catch (\Throwable $exception) {
                    if ($this->processor->formrow->mailchimp_send_errors) {
                        $from = $this->processor->formrow->alt_mailfrom != '' ? $this->processor->formrow->alt_mailfrom : $this->processor->app->get('mailfrom');
                        $fromname = $this->processor->formrow->alt_fromname != '' ? $this->processor->formrow->alt_fromname : $this->processor->app->get('fromname');
                        $this->exportEngine->sendMail($from, $fromname, $from, 'MailChimp API Error', 'Could not send data to MailChimp for email: ' . $email . "\n\nReason: " . $exception->getMessage());
                    }
                }
            }
        }
    }

    private function mailTemplateResolver(): MailTemplateResolver
    {
        return $this->mailTemplateResolverService ??= new MailTemplateResolver();
    }
}
