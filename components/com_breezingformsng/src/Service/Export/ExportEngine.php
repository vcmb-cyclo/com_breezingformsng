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

namespace Vcmb\Component\BreezingformsNG\Site\Service\Export;

\defined('_JEXEC') or die;

use DateTimeZone;
use Exception;
use Vcmb\Component\BreezingformsNG\Site\Table\RecordTable;
use Vcmb\Component\BreezingformsNG\Site\Table\SubrecordTable;
use HTML_facileFormsProcessor;
use Throwable;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\Event\Event;
use Joomla\Event\EventInterface;
use Joomla\CMS\Uri\Uri;
use Vcmb\Component\BreezingformsNG\Site\Service\Integration\IntegratorRuntime;
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
use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Mail\MailerFactoryInterface;
use CB\Component\Contentbuilderng\Administrator\Helper\ContentbuilderngHelper;
use CB\Component\Contentbuilderng\Administrator\Helper\FormSourceFactory;
use CB\Component\Contentbuilderng\Administrator\Service\ArticleService;
use CB\Component\Contentbuilderng\Administrator\Service\ListSupportService;
use CB\Component\Contentbuilderng\Administrator\Service\PermissionService;
use Vcmb\Component\BreezingformsNG\Administrator\Service\PdfDocument;
use Vcmb\Component\BreezingformsNG\Administrator\Service\PdfFontDirectoryScanner;
use Vcmb\Component\BreezingformsNG\Site\Service\Notification\MailSender;
use Vcmb\Component\BreezingformsNG\Site\Service\Runtime\SubmissionTimestampFormatter;
use Vcmb\Component\BreezingformsNG\Site\Service\Integration\ContentBuilderInstallation;
use Vcmb\Component\BreezingformsNG\Site\Service\Integration\ContentBuilderRecordCleanup;

/**
 * Database logging, mailing primitives and PDF/CSV/XML exports.
 */
final class ExportEngine
{
    private ?MailSender $mailSenderService = null;
    private ?SubmissionTimestampFormatter $submissionTimestampFormatterService = null;

    public function __construct(
        private readonly HTML_facileFormsProcessor $processor,
        private readonly MailerFactoryInterface $mailerFactory,
        private readonly CacheControllerFactoryInterface $cacheControllerFactory,
    ) {
    }

    public function logToDatabase($cbResult = null)
    { // CONTENTBUILDER
        global $ff_config;
        if ($this->processor->dying)
            return;

        if (!is_object($cbResult['form']) && $this->processor->editable && $this->processor->editable_override) {
            $editableFormValue = $this->processor->form;
            $editableUserId = $this->processor->app->getIdentity()->get('id', 0);
            $recordsQuery = $this->processor->database->createQuery()
                ->select('id')
                ->from($this->processor->database->quoteName('#__facileforms_records'))
                ->where($this->processor->database->quoteName('form') . ' = :editableFormValue')
                ->where($this->processor->database->quoteName('user_id') . ' = :editableUserId')
                ->where($this->processor->database->quoteName('user_id') . ' <> 0')
                ->bind(':editableFormValue', $editableFormValue, ParameterType::STRING)
                ->bind(':editableUserId', $editableUserId, ParameterType::INTEGER);
            $this->processor->database->setQuery($recordsQuery);
            $records = $this->processor->database->loadObjectList();
            (new ContentBuilderRecordCleanup(
                $this->processor->database,
                $this->processor->app,
                $this->cacheControllerFactory
            ))->remove((int) $editableFormValue, array_column($records, 'id'));
            foreach ($records as $record) {
                $recordIdToDelete = (int) $record->id;
                $delSubrecordsQuery = $this->processor->database->createQuery()
                    ->delete($this->processor->database->quoteName('#__facileforms_subrecords'))
                    ->where($this->processor->database->quoteName('record') . ' = :recordIdToDelete')
                    ->bind(':recordIdToDelete', $recordIdToDelete, ParameterType::INTEGER);
                $this->processor->database->setQuery($delSubrecordsQuery);
                $this->processor->database->execute();
                $delRecordQuery = $this->processor->database->createQuery()
                    ->delete($this->processor->database->quoteName('#__facileforms_records'))
                    ->where($this->processor->database->quoteName('id') . ' = :recordIdToDelete')
                    ->bind(':recordIdToDelete', $recordIdToDelete, ParameterType::INTEGER);
                $this->processor->database->setQuery($delRecordQuery);
                $this->processor->database->execute();
            }
        }

        $record = new RecordTable($this->processor->database);
        $record->submitted = $this->processor->submitted;
        $record->form = $this->processor->form;
        $record->title = $this->processor->formrow->title;
        $record->name = $this->processor->formrow->name;
        $record->ip = $this->processor->ip;
        $record->browser = $this->processor->browser;
        $record->opsys = $this->processor->opsys;
        $record->provider = $this->processor->provider;
        $record->viewed = 0;
        $record->exported = 0;
        $record->state = 1;
        if ($this->processor->app->getIdentity()->get('id', 0) > 0) {
            $record->user_id = $this->processor->app->getIdentity()->get('id', 0);
            $record->username = $this->processor->app->getIdentity()->get('username', '');
            $record->user_full_name = $this->processor->app->getIdentity()->get('name', '');
        } else {
            $record->user_id = $this->processor->app->getIdentity()->get('id', 0);
            $record->username = '-';
            $record->user_full_name = '-';
        }
        // CONTENTBUILDER WILL TAKE OVER SAVING/UPDATE IF EXISTS
        $cbFileFields = array();
        if (!is_object($cbResult['form'])) {
            if (!$record->store()) {
                $this->processor->status = _FF_STATUS_SAVERECORD_FAILED;
                $this->processor->message = Text::_('COM_BREEZINGFORMSNG_PROCESS_SAVERECFAILED');
                return;
            } // if

            $record_return = $record->id;

            if ($record_return && ContentBuilderInstallation::isInstalled()) {
                $last_update = new \Joomla\CMS\Date\Date();
                $last_update = $last_update->toSql();
                $db = $this->processor->database;
                $cbFormValue = $this->processor->form;
                $cbRecordReturn = $record_return;
                $existsQuery = $db->createQuery()
                    ->select('id')
                    ->from($db->quoteName('#__contentbuilderng_records'))
                    ->where($db->quoteName('type') . ' = ' . $db->quote('com_breezingformsng'))
                    ->where($db->quoteName('reference_id') . ' = :cbFormValue')
                    ->where($db->quoteName('record_id') . ' = :cbRecordReturn')
                    ->bind(':cbFormValue', $cbFormValue, ParameterType::STRING)
                    ->bind(':cbRecordReturn', $cbRecordReturn, ParameterType::STRING);
                $db->setQuery($existsQuery);
                $res = $db->loadResult();
                if (!$res) {
                    $sessionId = $this->processor->app->getSession()->getId();
                    $cbType = 'com_breezingformsng';
                    $insertQuery = $db->createQuery()
                        ->insert($db->quoteName('#__contentbuilderng_records'))
                        ->columns($db->quoteName(['session_id', 'type', 'last_update', 'published', 'record_id', 'reference_id']))
                        ->values(':sessionId, :cbType, :lastUpdate, 0, :cbRecordReturn, :cbFormValue')
                        ->bind(':sessionId', $sessionId, ParameterType::STRING)
                        ->bind(':cbType', $cbType, ParameterType::STRING)
                        ->bind(':lastUpdate', $last_update, ParameterType::STRING)
                        ->bind(':cbRecordReturn', $cbRecordReturn, ParameterType::STRING)
                        ->bind(':cbFormValue', $cbFormValue, ParameterType::STRING);
                    $db->setQuery($insertQuery);
                    $db->execute();
                } else {
                    $updateQuery = $db->createQuery()
                        ->update($db->quoteName('#__contentbuilderng_records'))
                        ->set($db->quoteName('last_update') . ' = :lastUpdate')
                        ->set($db->quoteName('edited') . ' = ' . $db->quoteName('edited') . ' + 1')
                        ->where($db->quoteName('type') . ' = ' . $db->quote('com_breezingformsng'))
                        ->where($db->quoteName('reference_id') . ' = :cbFormValue')
                        ->where($db->quoteName('record_id') . ' = :cbRecordReturn')
                        ->bind(':lastUpdate', $last_update, ParameterType::STRING)
                        ->bind(':cbFormValue', $cbFormValue, ParameterType::STRING)
                        ->bind(':cbRecordReturn', $cbRecordReturn, ParameterType::STRING);
                    $db->setQuery($updateQuery);
                    $db->execute();
                }
            }
        }

        $this->processor->record_id = $record->id;

        $names = array();
        $subrecord = new SubrecordTable($this->processor->database);
        $subrecord->record = $record->id;
        if (count($this->processor->savedata)) {

            $cbData = array();

            // CONTENTBUILDER file deletion/upgrade
            if (is_object($cbResult['form'])) {

                $db = $this->processor->database;
                $cbFormIdInput = $this->processor->app->getInput()->getInt('cb_form_id', 0);
                $cbFormQuery = $db->createQuery()
                    ->select('SQL_CALC_FOUND_ROWS *')
                    ->from($db->quoteName('#__contentbuilderng_forms'))
                    ->where($db->quoteName('id') . ' = :cbFormIdInput')
                    ->where($db->quoteName('published') . ' = 1')
                    ->bind(':cbFormIdInput', $cbFormIdInput, ParameterType::INTEGER);
                $db->setQuery($cbFormQuery);
                $_settings = $db->loadObject();

                $_record = $cbResult['form']->getRecord($this->processor->app->getInput()->getInt('record_id', 0), $_settings->published_only, $cbResult['frontend'] ? ($_settings->own_only_fe ? $this->processor->app->getIdentity()->get('id', 0) : -1) : ($_settings->own_only ? $this->processor->app->getIdentity()->get('id', 0) : -1), true);
                foreach ($_record as $_rec) {
                    $_files_deleted = array();
                    if ($_rec->recType == 'File Upload') {
                        $_array = $this->processor->app->getInput()->get('cb_delete_' . $_rec->recElementId, [], 'array');
                        foreach ($_array as $_key => $_arr) {
                            if ($_arr == 1) {
                                $_values = explode("\n", $_rec->recValue);
                                if (isset($_values[$_key])) {
                                    if (str_starts_with(strtolower($_values[$_key]), '{cbsite}')) {
                                        $_values[$_key] = str_replace(array('{cbsite}', '{CBSite}'), array(JPATH_SITE, JPATH_SITE), $_values[$_key]);
                                    }
                                    if (file_exists($_values[$_key])) {
                                        File::delete($_values[$_key]);
                                    }
                                    if (!isset($_files_deleted[$_rec->recElementId])) {
                                        $_files_deleted[$_rec->recElementId] = array();
                                    }
                                    $_files_deleted[$_rec->recElementId][] = $_key;
                                }
                            }
                        }

                        if (isset($_files_deleted[$_rec->recElementId]) && is_array($_files_deleted[$_rec->recElementId]) && count($_files_deleted[$_rec->recElementId])) {
                            $_i = 0;
                            foreach ($this->processor->savedata as $data) {
                                if ($data[_FF_DATA_ID] == $_rec->recElementId) {
                                    $_is_values = explode("\n", $_rec->recValue);
                                    $_j = 0;
                                    foreach ($_is_values as $_is_value) {
                                        if (!in_array($_j, $_files_deleted[$_rec->recElementId])) {
                                            $this->processor->savedata[$_i][_FF_DATA_VALUE] .= $_is_value . "\n";
                                        }
                                        $_j++;
                                    }
                                    $this->processor->savedata[$_i][_FF_DATA_VALUE] = rtrim($this->processor->savedata[$_i][_FF_DATA_VALUE]);
                                    break;
                                }
                                $_i++;
                            }
                        } else {
                            $next = count($this->processor->savedata);
                            $this->processor->savedata[$next] = array();
                            $this->processor->savedata[$next][_FF_DATA_ID] = $_rec->recElementId;
                            $this->processor->savedata[$next][_FF_DATA_NAME] = $_rec->recName;
                            $this->processor->savedata[$next][_FF_DATA_TITLE] = strip_tags($_rec->recTitle);
                            $this->processor->savedata[$next][_FF_DATA_TYPE] = $_rec->recType;
                            $this->processor->savedata[$next][_FF_DATA_VALUE] = '';
                            $_is_values = explode("\n", $_rec->recValue);
                            foreach ($_is_values as $_is_value) {
                                $this->processor->savedata[$next][_FF_DATA_VALUE] .= $_is_value . "\n";
                            }
                            $this->processor->savedata[$next][_FF_DATA_VALUE] = rtrim($this->processor->savedata[$next][_FF_DATA_VALUE]);
                        }
                    }
                }
            }
            $_savedata = array();
            if (!is_object($cbResult['form'])) {
                foreach ($this->processor->savedata as $data) {
                    if ($data[_FF_DATA_TYPE] == 'File Upload') {
                        if (!isset($_savedata[$data[_FF_DATA_ID]])) {
                            $_savedata[$data[_FF_DATA_ID]] = '';
                        }
                        $_savedata[$data[_FF_DATA_ID]] .= $data[_FF_DATA_VALUE] . "\n";
                    }
                }
            }
            $isset = array();
            foreach ($this->processor->savedata as $data) {
                // CONTENTBUILDER WILL TAKE OVER SAVING/UPDATE IF EXISTS
                if (!is_object($cbResult['form'])) {
        $subrecord->id = null;
                    $subrecord->element = $data[_FF_DATA_ID];
                    $subrecord->name = $data[_FF_DATA_NAME];
                    $subrecord->title = strip_tags($data[_FF_DATA_TITLE]);
                    $subrecord->type = $data[_FF_DATA_TYPE];
                    if (isset($_savedata[$data[_FF_DATA_ID]]) && !isset($isset[$data[_FF_DATA_ID]])) {
                        $subrecord->value = trim($_savedata[$data[_FF_DATA_ID]]);
                    } else {
                        $subrecord->value = $data[_FF_DATA_VALUE];
                    }
                    if (!isset($isset[$data[_FF_DATA_ID]])) {
                        if (!$subrecord->store()) {
                            $this->processor->status = _FF_STATUS_SAVESUBRECORD_FAILED;
                            $this->processor->message = Text::_('COM_BREEZINGFORMSNG_PROCESS_SAVESUBFAILED');
                            return;
                        }
                    }
                    if ($data[_FF_DATA_TYPE] == 'File Upload') {
                        $isset[$data[_FF_DATA_ID]] = true;
                    }
                } else {

                    $cbNonEditableFields = ListSupportService::createFromRuntimeContext()->getListNonEditableElements($cbResult['data']['id']);

                    if (!in_array($data[_FF_DATA_ID], $cbNonEditableFields)) {

                        switch ($data[_FF_DATA_TYPE]) {
                            case 'Checkbox':
                            case 'Checkbox Group':
                            case 'Radio Button':
                            case 'Radio Group':
                            case 'Select List':
                                if (!isset($cbData[$data[_FF_DATA_ID]])) {
                                    $cbData[$data[_FF_DATA_ID]] = array();
                                }
                                $cbData[$data[_FF_DATA_ID]][] = $data[_FF_DATA_VALUE];
                                break;
                            case 'File Upload':
                                if (!isset($cbData[$data[_FF_DATA_ID]])) {
                                    $cbData[$data[_FF_DATA_ID]] = '';
                                    $cbFileFields[] = $data[_FF_DATA_ID];
                                }
                                $cbData[$data[_FF_DATA_ID]] .= $data[_FF_DATA_VALUE] . "\n";
                                break;
                            default:
                                $cbData[$data[_FF_DATA_ID]] = $data[_FF_DATA_VALUE];
                        }
                    }
                }
            } // foreach
            // CONTENTBUILDER BEGIN
            if (is_object($cbResult['form'])) {

                PluginHelper::importPlugin('contentbuilderng_submit');

                $is15 = false;

                $values = array();
                $names = $cbResult['form']->getAllElements();

                foreach ($names as $id => $name) {
                    if (isset($cbData[$id])) {
                        if (in_array($id, $cbFileFields) && trim($cbData[$id]) == '') {
                            $values[$id] = '';
                        } else if (in_array($id, $cbFileFields) && trim($cbData[$id]) != '') {
                            $values[$id] = trim($cbData[$id]);
                        } else {
                            $values[$id] = $cbData[$id];
                        }
                    }
                }

                $dispatcher = $this->processor->app->getDispatcher();
                $dispatcher->dispatch('onBeforeSubmit', new Event('onBeforeSubmit', array(
                    $this->processor->app->getInput()->getInt('cb_record_id', 0),
                    $cbResult['form'],
                    $values
                )
            ));

                $record_return = $cbResult['form']->saveRecord($this->processor->app->getInput()->getInt('cb_record_id', 0), $values);

                $db = $this->processor->database;
                $cbFormIdInput = $this->processor->app->getInput()->getInt('cb_form_id', 0);
                $cbFormQuery = $db->createQuery()
                    ->select('SQL_CALC_FOUND_ROWS *')
                    ->from($db->quoteName('#__contentbuilderng_forms'))
                    ->where($db->quoteName('id') . ' = :cbFormIdInput')
                    ->where($db->quoteName('published') . ' = 1')
                    ->bind(':cbFormIdInput', $cbFormIdInput, ParameterType::INTEGER);
                $db->setQuery($cbFormQuery);
                $cbData = $db->loadObject();

                if ($record_return) {

                    $this->processor->record_id = $record_return;

                    $sef = '';
                    $ignore_lang_code = '*';
                    if ($cbResult['data']['default_lang_code_ignore']) {

                        $langSef = trim($this->processor->app->getInput()->getCmd('lang', ''));
                        $langQuery = $db->createQuery()
                            ->select($db->quoteName('lang_code'))
                            ->from($db->quoteName('#__languages'))
                            ->where($db->quoteName('published') . ' = 1')
                            ->where($db->quoteName('sef') . ' = :langSef')
                            ->bind(':langSef', $langSef, ParameterType::STRING);
                        $db->setQuery($langQuery);
                        $ignore_lang_code = $db->loadResult();
                        if (!$ignore_lang_code) {
                            $ignore_lang_code = '*';
                        }

                        $sef = trim($this->processor->app->getInput()->getCmd('lang', ''));
                        if ($ignore_lang_code == '*') {
                            $sef = '';
                        }
                        if ($ignore_lang_code == '*') {
                            $sef = '';
                        }
                    } else {
                        $defaultLangCode = (string) $cbResult['data']['default_lang_code'];
                        $sefQuery = $db->createQuery()
                            ->select($db->quoteName('sef'))
                            ->from($db->quoteName('#__languages'))
                            ->where($db->quoteName('published') . ' = 1')
                            ->where($db->quoteName('lang_code') . ' = :defaultLangCode')
                            ->bind(':defaultLangCode', $defaultLangCode, ParameterType::STRING);
                        $db->setQuery($sefQuery);
                        $sef = $db->loadResult();
                    }

                    $language = $cbResult['data']['default_lang_code_ignore'] ? $ignore_lang_code : $cbResult['data']['default_lang_code'];
                    $res = $db->loadResult();
                    $last_update = new \Joomla\CMS\Date\Date();
                    $last_update = $last_update->toSql();
                    if (!$res) {

                        $is_future = 0;
                        $created_up = (new \Joomla\CMS\Date\Date())->toSql();
                        if (intval($cbData->default_publish_up_days) != 0) {
                            $is_future = 1;
                            $date = new \Joomla\CMS\Date\Date((string) strtotime('now +' . intval($cbData->default_publish_up_days) . ' days'));
                            $created_up = $date->toSql();
                        }
                        $created_down = '0000-00-00 00:00:00';
                        if (intval($cbData->default_publish_down_days) != 0) {
                            $date = new \Joomla\CMS\Date\Date((string) strtotime($created_up . ' +' . intval($cbData->default_publish_down_days) . ' days'));
                            $created_down = $date->toSql();
                        }

                        $langSessionId = $this->processor->app->getSession()->getId();
                        $langType = 'com_breezingformsng';
                        $langPublished = $cbData->auto_publish && !$is_future ? 1 : 0;
                        $langReferenceId = $cbResult['form']->getReferenceId();
                        $langSefTrimmed = trim($sef);
                        $insertRecordQuery = $db->createQuery()
                            ->insert($db->quoteName('#__contentbuilderng_records'))
                            ->columns($db->quoteName([
                                'session_id', 'type', 'last_update', 'is_future', 'lang_code', 'sef',
                                'published', 'record_id', 'reference_id', 'publish_up', 'publish_down',
                            ]))
                            ->values(
                                ':langSessionId, :langType, :lastUpdate2, :isFuture, :language, :langSefTrimmed,'
                                . ' :langPublished, :recordReturn, :langReferenceId, :createdUp, :createdDown'
                            )
                            ->bind(':langSessionId', $langSessionId, ParameterType::STRING)
                            ->bind(':langType', $langType, ParameterType::STRING)
                            ->bind(':lastUpdate2', $last_update, ParameterType::STRING)
                            ->bind(':isFuture', $is_future, ParameterType::INTEGER)
                            ->bind(':language', $language, ParameterType::STRING)
                            ->bind(':langSefTrimmed', $langSefTrimmed, ParameterType::STRING)
                            ->bind(':langPublished', $langPublished, ParameterType::INTEGER)
                            ->bind(':recordReturn', $record_return, ParameterType::STRING)
                            ->bind(':langReferenceId', $langReferenceId, ParameterType::STRING)
                            ->bind(':createdUp', $created_up, ParameterType::STRING)
                            ->bind(':createdDown', $created_down, ParameterType::STRING);
                        $db->setQuery($insertRecordQuery);
                        $db->execute();

                    } else {

                        $langType = 'com_breezingformsng';
                        $langReferenceId = $cbResult['form']->getReferenceId();
                        $langSefTrimmed = trim($sef);
                        $updateRecordQuery = $db->createQuery()
                            ->update($db->quoteName('#__contentbuilderng_records'))
                            ->set($db->quoteName('last_update') . ' = :lastUpdate2')
                            ->set($db->quoteName('lang_code') . ' = :language')
                            ->set($db->quoteName('sef') . ' = :langSefTrimmed')
                            ->set($db->quoteName('edited') . ' = ' . $db->quoteName('edited') . ' + 1')
                            ->where($db->quoteName('type') . ' = :langType')
                            ->where($db->quoteName('reference_id') . ' = :langReferenceId')
                            ->where($db->quoteName('record_id') . ' = :recordReturn')
                            ->bind(':lastUpdate2', $last_update, ParameterType::STRING)
                            ->bind(':language', $language, ParameterType::STRING)
                            ->bind(':langSefTrimmed', $langSefTrimmed, ParameterType::STRING)
                            ->bind(':langType', $langType, ParameterType::STRING)
                            ->bind(':langReferenceId', $langReferenceId, ParameterType::STRING)
                            ->bind(':recordReturn', $record_return, ParameterType::STRING);
                        $db->setQuery($updateRecordQuery);
                        $db->execute();
                    }
                }

                $article_id = 0;

                // creating the article
                if (is_object($cbData) && $cbData->create_articles) {

                    $this->processor->app->getInput()->set('cb_category_id', null);
                    $this->processor->app->getInput()->set('cb_controller', null);

                    if ($this->processor->app->isClient('site') && $this->processor->app->getInput()->getInt('Itemid', 0)) {
                        $menu = $this->processor->app->getMenu();
                        $item = $menu->getActive();
                        if (is_object($item)) {
                            $this->processor->app->getInput()->set('cb_category_id', $item->getParams()->get('cb_category_id', null));
                            $this->processor->app->getInput()->set('cb_controller', $item->getParams()->get('cb_controller', null));
                        }
                    }

                    $cbData->page_title = $cbData->use_view_name_as_title ? $cbData->name : $cbResult['form']->getPageTitle();
                    $cbData->labels = $cbResult['form']->getElementLabels();
                    $ids = array();
                    foreach ($cbData->labels as $reference_id => $label) {
                        $ids[] = $reference_id;
                    }
                    $cbData->labels = array();
                    if (count($ids)) {
                        $cbFormIdForLabels = $this->processor->app->getInput()->getInt('cb_form_id', 0);
                        $labelsQuery = $db->createQuery()
                            ->select('DISTINCT ' . $db->quoteName('label') . ', ' . $db->quoteName('reference_id'))
                            ->from($db->quoteName('#__contentbuilderng_elements'))
                            ->where($db->quoteName('form_id') . ' = :cbFormIdForLabels')
                            ->whereIn($db->quoteName('reference_id'), $ids, ParameterType::STRING)
                            ->where($db->quoteName('published') . ' = 1')
                            ->order($db->quoteName('ordering'))
                            ->bind(':cbFormIdForLabels', $cbFormIdForLabels, ParameterType::INTEGER);
                        $db->setQuery($labelsQuery);
                        $rows = $db->loadAssocList();
                        $ids = array();
                        foreach ($rows as $row) {
                            $ids[] = $row['reference_id'];
                        }
                    }
                    $cbData->items = $cbResult['form']->getRecord($record_return, $cbData->published_only, $cbResult['frontend'] ? ($cbData->own_only_fe ? $this->processor->app->getIdentity()->get('id', 0) : -1) : ($cbData->own_only ? $this->processor->app->getIdentity()->get('id', 0) : -1), true);
                    if (!count($cbData->items)) {
                        throw new Exception(Text::_('COM_CONTENTBUILDERNG_RECORD_NOT_FOUND'), 404);
                    }
                    $config = array();
                    foreach ($this->processor->savedata as $data) {
                        if ($data[_FF_DATA_NAME] == 'cb_article_category_id') {
                            $config['bf_catid'] = intval($data[_FF_DATA_VALUE]);
                            break;
                        }
                    }
                    $full = false;
                    $article_id = $this->processor->app->bootComponent('com_contentbuilderng')->getContainer()->get(ArticleService::class)->createArticle($this->processor->app->getInput()->getInt('cb_form_id', 0), $record_return, $cbData->items, $ids, $cbData->title_field, $cbResult['form']->getRecordMetadata($record_return), $config, $full, true, $this->processor->app->getInput()->get('cb_category_id', null, 'string'));

                    $cacheFactory = $this->cacheControllerFactory;
                    $cache = $cacheFactory->createCacheController('callback', ['defaultgroup' => 'com_content']);
                    $cache->clean();
                    $cache = $cacheFactory->createCacheController('callback', ['defaultgroup' => 'com_contentbuilderng']);
                    $cache->clean();
                }

                $dispatcher = $this->processor->app->getDispatcher();
                $dispatcher->dispatch('onAfterSubmit', new Event('onAfterSubmit',
                    array(
                        $record_return,
                        $article_id,
                        $cbResult['form'],
                        $values
                    )
                ));
            }
            // CONTENTBUILDER END
        }

        $integrate = new IntegratorRuntime((int) $this->processor->form, $this->processor->database);
        if (count($this->processor->savedata))
            foreach ($this->processor->savedata as $data) {
                $integrate->field($data);
            }
        $integrate->commit();

        if (isset($record_return)) {
            return $record_return;
        }
    }

    /*
     * https://stackoverflow.com/questions/4356289/php-random-string-generator/31107425#31107425
     */

    public function random_str(
        int $length,
        string $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'
    ): string
    {
        $pieces = [];
        $max = mb_strlen($keyspace, '8bit') - 1;
        for ($i = 0; $i < $length; ++$i) {
            $pieces[] = $keyspace[random_int(0, $max)];
        }
        return implode('', $pieces);
    }

    // logToDatabase

    public function sendMail($from, $fromname, $recipient, $subject, $body, $attachment = null, $html = null, $cc = null, $bcc = null, $alt_sender = '')
    {
        if ($this->processor->dying)
            return;

        try {
            $this->mailSender()->send(
                (string) $from,
                (string) $fromname,
                $this->normalizeMailList($recipient),
                (string) $subject,
                (string) $body,
                $this->normalizeMailList($attachment),
                isset($html) ? (bool) $html : null,
                $this->normalizeMailList($cc),
                $this->normalizeMailList($bcc),
                (string) $alt_sender
            );
        } catch (Throwable $e) {
            $this->processor->status = _FF_STATUS_SENDMAIL_FAILED;
            $this->processor->message = $e->getMessage();
            $this->processor->app->enqueueMessage($this->processor->message, 'error');
        }
    }

    // sendMail

    private function mailSender(): MailSender
    {
        return $this->mailSenderService ??= new MailSender(
            $this->mailerFactory,
            $this->processor->app->getConfig()
        );
    }

    /**
     * @return list<string>
     */
    private function normalizeMailList(mixed $values): array
    {
        if ($values === null || $values === false) {
            return [];
        }

        return array_map('strval', is_array($values) ? array_values($values) : [$values]);
    }

    public function endsWith(string $haystack, string $needle): bool
    {
        return str_ends_with($haystack, $needle);
    }

    public function exppdf($filter = [], $mailback = false, $translate = true)
    {
        global $ff_compath;

        $tz = 'UTC';
        $tz = new DateTimeZone($this->processor->app->get('offset'));

        $file = JPATH_SITE . '/media/breezingforms/pdftpl/' . $this->processor->formrow->name . '_pdf_attachment.php';
        if (!file_exists($file)) {
            $file = JPATH_ADMINISTRATOR . '/components/com_breezingformsng/pdftpl/pdf_attachment.php';
        }

        if ($mailback) {
            $mb_file = JPATH_SITE . '/media/breezingforms/pdftpl/' . $this->processor->formrow->name . '_pdf_mailback_attachment.php';
            if (file_exists($mb_file)) {
                $file = $mb_file;
            } else {
                $mb_file = JPATH_SITE . '/media/breezingforms/pdftpl/pdf_mailback_attachment.php';
                if (file_exists($mb_file)) {
                    $file = $mb_file;
                }
            }
        }

        $processed = array();
        $xmldata = array();

        $_xmldata = $this->processor->xmldata;
        if ($mailback) {
            $_xmldata = $this->processor->mb_xmldata;
        }

        foreach ($_xmldata as $data) {
            if (!in_array($data[_FF_DATA_NAME], $filter) && !in_array($data[_FF_DATA_NAME], $processed)) {
                if ($translate) {
                    $title_translated = '';
                    $this->processor->getFieldTranslated('label', $data[_FF_DATA_NAME], $title_translated);
                    $data[_FF_DATA_TITLE] = $title_translated != '' ? $title_translated : strip_tags($data[_FF_DATA_TITLE]);
                }
                $xmldata[] = $data;
                //$processed[] = $data[_FF_DATA_NAME];
            }
        }

        $submitted = $this->processor->submitted;
        $timestamp = $this->submissionTimestampFormatter()->format(
            (string) $this->processor->submitted,
            (string) $this->processor->app->get('offset')
        );
        $this->processor->submitted = $timestamp->submittedAt;
        $date_stamp = $timestamp->fileStamp;
        $date_stamp2 = $this->submissionTimestampFormatter()->format(
            (string) $this->processor->submitted,
            (string) $this->processor->app->get('offset')
        )->fileStamp;

        $this->processor->submitted = $submitted;

        $pdf = new PdfDocument();
        $pdf->setMailback($mailback);
        $pdf->setFormName($this->processor->formrow->name);

        ob_start();
        require($file);
        $c = ob_get_contents();
        ob_end_clean();

        $active_found = false;
        $font_loaded = false;
        $ttf_name = '';

        if (is_dir(JPATH_SITE . '/media/breezingforms/pdftpl/fonts/')) {

            $sourcePath = JPATH_SITE . '/media/breezingforms/pdftpl/fonts/';
            foreach ((new PdfFontDirectoryScanner())->scan($sourcePath) as $file) {
                    if ($file != "." && $file != ".." && $this->processor->endsWith(strtolower($file), '.php')) {
                        $file_sep = explode('.', $file);
                        if (count($file_sep) > 1) {
                            unset($file_sep[count($file_sep) - 1]);
                            $pdf->AddFont(implode('_', $file_sep), '', $sourcePath . $file);
                            $font_loaded = true;
                        }
                    }
                    if ($file != "." && $file != ".." && $this->processor->endsWith(strtolower($file), '.ttf')) {
                        $file_sep = explode('.', $file);
                        if (count($file_sep) > 1) {
                            unset($file_sep[count($file_sep) - 1]);
                            $ttf_name = PdfDocument::importTtfFont($sourcePath . $file);
                            $font_loaded = true;
                        }
                    }
                    if ($this->processor->endsWith(strtolower($file), '_active')) {
                        $active = explode('_', $file);
                        if (count($active) > 1) {
                            unset($active[count($active) - 1]);
                            $font_name = '';
                            if ($ttf_name != '') {
                                $font_name = $ttf_name;
                            } else {
                                $font_name = implode('_', $active);
                            }
                            $pdf->SetFont($font_name);
                            if ($font_loaded) {
                                $active_found = true;
                            }
                        }
                    }
            }
        }

        if (!$active_found) {
            PdfDocument::importTtfFont(JPATH_SITE . '/media/com_breezingformsng/fonts/verdana.ttf');
            $pdf->SetFont('verdana');
        }

        if ($pdf->getFooterTemplate() == '') {

            $pdf->setPrintFooter(false);
        } else {

            $pdf->setPrintFooter(true);
        }

        if ($pdf->getHeaderTemplate() == '') {

            $pdf->setPrintHeader(false);
        } else {

            $pdf->setPrintHeader(true);
        }

        $pdf->AddPage();
        $pdf->writeHTML($c);
        mt_srand();

        $matches_array = array();
        $regex = '<!--(.+?)=(.+?)-->';
        preg_match_all($regex, str_replace(' ', '', $c), $matches_array);

        if (isset($matches_array[1][0]) && trim($matches_array[1][0]) == 'fm' && isset($matches_array[2][0])) {

            $fm = $matches_array[2][0];

            if (str_starts_with(trim($fm), '{mospath}')) {
                $fm = str_replace('{mospath}', $this->processor->mospath, $fm);
            }

            foreach ($_xmldata as $data) {

                $value = str_replace(array('.', 'ä', 'ü', 'ö', 'Ä', 'Ü', 'Ö', 'ß'), array('_', 'ae', 'ue', 'oe', 'Ae', 'Ue', 'Oe', 'ss'), $data[_FF_DATA_VALUE]);

                $fm = str_replace('{' . strtolower($data[_FF_DATA_NAME]) . ':value}', File::makeSafe(trim($value)), $fm);
            }

            $fm = str_replace('{filemask:_separator}', '_', $fm);
            $fm = str_replace('{filemask:_username}', trim($this->processor->app->getIdentity()->get('username')), $fm);
            $fm = str_replace('{filemask:_userid}', trim($this->processor->app->getIdentity()->get('id')), $fm);
            $fm = str_replace('{filemask:_name}', trim($this->processor->app->getIdentity()->get('name')), $fm);
            $fm = str_replace('{filemask:_datetime}', trim($date_stamp), $fm);
            $fm = str_replace('{filemask:_date}', trim($date_stamp2), $fm);
            $fm = str_replace('{filemask:_timestamp}', (string) time(), $fm);
            $fm = str_replace('{filemask:_random}', (string) mt_rand(0, mt_getrandmax()), $fm);
            if ($fm == '') {
                $fm = '__empty__';
            }

            $uploads = $this->processor->uploads . '/';

            $trimmedFilename = trim($fm);
            if (str_starts_with($trimmedFilename, '/') || (($trimmedFilename[1] ?? '') === ':')) {
                $uploads = '';
            }

            $pdfname = $uploads . $fm . '.pdf';

            if (file_exists($pdfname)) {

                $pdfname = $uploads . $fm . '-' . $date_stamp . '-' . mt_rand(0, mt_getrandmax()) . '.pdf';
            }
        } else {

            $pdfname = $this->processor->uploads . '/ffexport-pdf-' . $date_stamp . '-' . mt_rand(0, mt_getrandmax()) . '.pdf';
        }

        $pdf->lastPage();
        $pdf->Output($pdfname, "F");
        return $pdfname;
    }

    public function expcsv($filter = [], $mailback = false)
    {
        global $ff_config;

        $inverted = isset($ff_config->csvinverted) ? $ff_config->csvinverted : false;

        $csvdelimiter = stripslashes($ff_config->csvdelimiter);
        $csvquote = stripslashes($ff_config->csvquote);
        $cellnewline = $ff_config->cellnewline == 0 ? "\n" : "\\n";

        $fields = array();
        $lines = array();

        $lineNum = count($lines);

        $fields['ZZZ_A_FORM'] = true;
        $fields['ZZZ_B_SUBMITTED'] = true;
        $fields['ZZZ_C_IP'] = true;
        $fields['ZZZ_D_BROWSER'] = true;
        $fields['ZZZ_E_OPSYS'] = true;

        $lines[$lineNum]['ZZZ_A_FORM'][] = $this->processor->form;

        $timestamp = $this->submissionTimestampFormatter()->format(
            (string) $this->processor->submitted,
            (string) $this->processor->app->get('offset')
        );
        $submitted = $timestamp->submittedAt;
        $date_stamp = $timestamp->fileStamp;

        $lines[$lineNum]['ZZZ_B_SUBMITTED'][] = $submitted;
        $lines[$lineNum]['ZZZ_C_IP'][] = $this->processor->ip;
        $lines[$lineNum]['ZZZ_D_BROWSER'][] = $this->processor->browser;
        $lines[$lineNum]['ZZZ_E_OPSYS'][] = $this->processor->opsys;

        $xmldata = $this->processor->xmldata;
        if ($mailback) {
            $xmldata = $this->processor->mb_xmldata;
        }

        $processed = array();
        if (count($xmldata)) {
            foreach ($xmldata as $data) {
                if (!in_array($data[_FF_DATA_NAME], $filter) && !in_array($data[_FF_DATA_NAME], $processed)) {
                    $fields[strip_tags($data[_FF_DATA_TITLE])] = true;
                    $lines[$lineNum][strip_tags($data[_FF_DATA_TITLE])][] = is_array($data[_FF_DATA_VALUE]) ? implode('|', $data[_FF_DATA_VALUE]) : $data[_FF_DATA_VALUE];
                    //$processed[] = $data[_FF_DATA_NAME];
                }
            } // foreach
        }

        $head = '';
        ksort($fields);
        $lineLength = count($lines);
        foreach ($fields as $fieldName => $null) {
            if ($inverted == false) {
                $head .= $csvquote . $fieldName . $csvquote . $csvdelimiter;
            }
        }

        if ($inverted == false) {
            $head = substr($head, 0, -1) . nl();
        }

        $out = '';
        for ($i = 0; $i < $lineLength; $i++) {
            ksort($lines[$i]);
            foreach ($lines[$i] as $fieldName => $line) {
                if ($inverted == true) {
                    $out .= $csvquote . str_replace($csvquote, $csvquote . $csvquote, str_replace("\n", $cellnewline, str_replace("\r", "", $fieldName))) . $csvquote . $csvdelimiter;
                }
                $out .= $csvquote . str_replace($csvquote, $csvquote . $csvquote, str_replace("\n", $cellnewline, str_replace("\r", "", implode('|', $line)))) . $csvquote . $csvdelimiter;

                if ($inverted == true) {
                    $out .= nl();
                }
            }

            if ($inverted == false) {
                $out = substr($out, 0, -1);
                $out .= nl();
            }
        }
        mt_srand();
        $csvname = $this->processor->uploads . '/ffexport-' . $date_stamp . '-' . mt_rand(0, mt_getrandmax()) . '.csv';
        File::makeSafe($csvname);
        if (function_exists('mb_convert_encoding')) {
            $to_encoding = 'UTF-16LE';
            $from_encoding = 'UTF-8';
            $chrchr = chr(255) . chr(254) . mb_convert_encoding($head . $out, $to_encoding, $from_encoding);
            if (!File::write($csvname, $chrchr)) {
                $this->processor->status = _FF_STATUS_ATTACHMENT_FAILED;
            } // if
        } else {
            $head_out = $head . $out;
            if (!File::write($csvname, $head_out)) {
                $this->processor->status = _FF_STATUS_ATTACHMENT_FAILED;
            } // if
        }
        return $csvname;
    }

    public function expxml($filter = [], $mailback = false, $translate = false)
    {
        global $ff_compath, $ff_version, $mosConfig_fileperms;

        $timestamp = $this->submissionTimestampFormatter()->format(
            (string) $this->processor->submitted,
            (string) $this->processor->app->get('offset')
        );
        $submitted = $timestamp->submittedAt;
        $date_stamp = $timestamp->fileStamp;
        $date_file = $submitted;

        if ($this->processor->dying)
            return '';
        mt_srand();
        $xmlname = $this->processor->uploads . '/ffexport-' . $date_stamp . '-' . mt_rand(0, mt_getrandmax()) . '.xml';

        $xml = '<?xml version="1.0" encoding="utf-8" ?>' . nl() .
            '<FacileFormsExport type="records" version="' . $ff_version . '">' . nl() .
            indent(1) . '<exportdate>' . $date_file . '</exportdate>' . nl();
        if ($this->processor->record_id != '')
            $xml .= indent(1) . '<record id="' . $this->processor->record_id . '">' . nl();
        else
            $xml .= indent(1) . '<record>' . nl();

        $title_translated = $this->processor->getFormTitleTranslated();

        $xml .= indent(2) . '<submitted>' . $submitted . '</submitted>' . nl() .
            indent(2) . '<form>' . $this->processor->form . '</form>' . nl() .
            indent(2) . '<title>' . htmlspecialchars($title_translated != '' ? $title_translated : $this->processor->formrow->title, ENT_QUOTES, 'UTF-8') . '</title>' . nl() .
            indent(2) . '<name>' . $this->processor->formrow->name . '</name>' . nl() .
            indent(2) . '<ip>' . $this->processor->ip . '</ip>' . nl() .
            indent(2) . '<browser>' . htmlspecialchars($this->processor->browser, ENT_QUOTES, 'UTF-8') . '</browser>' . nl() .
            indent(2) . '<opsys>' . htmlspecialchars($this->processor->opsys, ENT_QUOTES, 'UTF-8') . '</opsys>' . nl() .
            indent(2) . '<provider>' . $this->processor->provider . '</provider>' . nl() .
            indent(2) . '<viewed>0</viewed>' . nl() .
            indent(2) . '<exported>0</exported>' . nl() .
            indent(2) . '<state>1</state>' . nl();
        $processed = array();

        $xmldata = $this->processor->xmldata;
        if ($mailback) {
            $xmldata = $this->processor->mb_xmldata;
        }

        if (count($xmldata))
            foreach ($xmldata as $data) {

                if ($translate) {
                    $title_translated = '';
                    $this->processor->getFieldTranslated('label', $data[_FF_DATA_NAME], $title_translated);
                }

                if (!in_array($data[_FF_DATA_NAME], $filter) && !in_array($data[_FF_DATA_NAME], $processed)) {
                    $xml .= indent(2) . '<subrecord>' . nl() .
                        indent(3) . '<element>' . $data[_FF_DATA_ID] . '</element>' . nl() .
                        indent(3) . '<name>' . $data[_FF_DATA_NAME] . '</name>' . nl() .
                        indent(3) . '<title>' . htmlspecialchars($title_translated != '' ? $title_translated : strip_tags($data[_FF_DATA_TITLE]), ENT_QUOTES, 'UTF-8') . '</title>' . nl() .
                        indent(3) . '<type>' . $data[_FF_DATA_TYPE] . '</type>' . nl() .
                        indent(3) . '<value>' . htmlspecialchars(is_array($data[_FF_DATA_VALUE]) ? implode('|', $data[_FF_DATA_VALUE]) : $data[_FF_DATA_VALUE], ENT_QUOTES, 'UTF-8') . '</value>' . nl() .
                        indent(2) . '</subrecord>' . nl();
                    //$processed[] = $data[_FF_DATA_NAME];
                }
            } // foreach
        $xml .= indent(1) . '</record>' . nl() .
            '</FacileFormsExport>' . nl();

        File::makeSafe($xmlname);
        if (!File::write($xmlname, $xml)) {
            $this->processor->status = _FF_STATUS_ATTACHMENT_FAILED;
        } // if

        return $xmlname;
    }

    // expxml

    private function submissionTimestampFormatter(): SubmissionTimestampFormatter
    {
        return $this->submissionTimestampFormatterService ??= new SubmissionTimestampFormatter();
    }

}
