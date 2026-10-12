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

namespace Vcmb\Component\BreezingformsNG\Site\Service\Submission;

\defined('_JEXEC') or die;

use Exception;
use HTML_facileFormsProcessor;
use Securimage;
use Joomla\CMS\Filter\InputFilter;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\Event\Event;
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
use Joomla\CMS\Mail\Mail;
use Joomla\CMS\Mail\MailerFactoryInterface;
use Vcmb\Component\BreezingformsNG\Site\Service\Integration\DropboxUploader;
use Vcmb\Component\BreezingformsNG\Site\Service\Integration\RecaptchaVerifier;
use Vcmb\Component\BreezingformsNG\Site\Service\Export\ExportEngine;
use Vcmb\Component\BreezingformsNG\Site\Service\Notification\NotificationEngine;
use Vcmb\Component\BreezingformsNG\Site\Service\Runtime\SubmissionTimestampFormatter;
use Vcmb\Component\BreezingformsNG\Site\Service\Scripting\ScriptingEngine;
use Vcmb\Component\BreezingformsNG\Site\Service\Upload\TemporaryUploadDirectory;
use Vcmb\Component\BreezingformsNG\Site\Service\Upload\FlashUploadFileMatcher;
use Vcmb\Component\BreezingformsNG\Site\Service\Upload\UploadError;
use Vcmb\Component\BreezingformsNG\Site\Service\Upload\UploadRuntime;
use Vcmb\Component\BreezingformsNG\Site\Service\Security\HtmlSanitizer;
use Vcmb\Component\BreezingformsNG\Administrator\Helper\VendorHelper;
use CB\Component\Contentbuilderng\Administrator\Helper\ContentbuilderngHelper;
use CB\Component\Contentbuilderng\Administrator\Helper\FormSourceFactory;
use CB\Component\Contentbuilderng\Administrator\Service\ArticleService;
use CB\Component\Contentbuilderng\Administrator\Service\ListSupportService;
use CB\Component\Contentbuilderng\Administrator\Service\PermissionService;

/**
 * Submit data collection, submission pipeline and HTML sanitizing.
 */
final class SubmissionEngine
{
    private ?SubmissionTimestampFormatter $uploadTimestampFormatterService = null;
    private ?HtmlSanitizer $htmlSanitizerService = null;
    private ?FlashUploadFileMatcher $flashUploadFileMatcherService = null;
    private ?UploadRuntime $uploadRuntimeService = null;

    public function __construct(
        private readonly HTML_facileFormsProcessor $processor,
        private readonly MailerFactoryInterface $mailerFactory,
        private readonly RecaptchaVerifier $recaptchaVerifier,
        private readonly ScriptingEngine $scriptingEngine,
        private readonly ExportEngine $exportEngine,
        private readonly NotificationEngine $notificationEngine,
    ) {
    }

    public function saveUpload($filename, $userfile_name, $destpath, $timestamp, $useUrl = false, $useUrlDownloadDirectory = '', $resize_target_width = 0, $resize_target_height = 0, $resize_type = '', $resize_bgcolor = '#ffffff', $field_name = '')
    {
        global $mosConfig_fileperms;

        if ($this->processor->dying) {
            return '';
        }

        $identity = $this->processor->app->getIdentity();
        $filemode = isset($mosConfig_fileperms)
            ? ($mosConfig_fileperms === '' ? null : octdec($mosConfig_fileperms))
            : 0644;
        $result = $this->uploadRuntime()->store(
            (string) $filename,
            (string) $userfile_name,
            (string) $destpath,
            $this->processor->findtags,
            $this->processor->replacetags,
            $this->processor->rows,
            (string) $this->processor->submitted,
            (string) $this->processor->app->get('offset'),
            [
                'username' => $identity->get('username'),
                'id' => $identity->get('id'),
                'name' => $identity->get('name'),
            ],
            (bool) $this->processor->app->getSession()->get('bfFileUploadOverride', true),
            $filemode,
            (bool) $useUrl,
            (int) $resize_target_width,
            (int) $resize_target_height,
            (string) $resize_type,
            $resize_bgcolor === null ? null : (string) $resize_bgcolor
        );

        if ($result->error !== null) {
            $this->processor->status = _FF_STATUS_UPLOAD_FAILED;
            $this->processor->message = Text::_(match ($result->error) {
                UploadError::DirectoryMissing => 'COM_BREEZINGFORMSNG_PROCESS_DIRNOTEXISTS',
                UploadError::FileExists => 'COM_BREEZINGFORMSNG_PROCESS_FILEEXISTS',
                UploadError::MoveFailed => 'COM_BREEZINGFORMSNG_PROCESS_FILEMOVEFAILED',
                UploadError::ChmodFailed => 'COM_BREEZINGFORMSNG_PROCESS_FILECHMODFAILED',
            });

            return '';
        }

        return ['default' => $result->path, 'server' => $result->serverPath];
    }

    public function measureTime(): float
    {
        $time = explode(' ', microtime());

        return ((float) $time[0] + (float) $time[1]) / 1000;
    }

    function collectSubmitdata($cbResult = null)
    {
        if ($this->processor->dying || $this->processor->submitdata)
            return;

        $this->processor->submitdata = array();
        $this->processor->savedata = array();
        $this->processor->maildata = array();
        $this->processor->sfdata = array();
        $this->processor->xmldata = array();
        $names = array();
        if (count($this->processor->rows)) {
            $time_passed = 0;
            $start_time = $this->measureTime();
            $max_exec_time = ini_get('max_execution_time');
            $max_time = !empty($max_exec_time) ? intval($max_exec_time) / 2 : 15;
            foreach ($this->processor->rows as $row) {
                if (!in_array($row->name, $names)) {
                    switch ($row->type) {
                        case 'File Upload':

                            // CONTENTBUILDER
                            if ($cbResult !== null && isset($cbResult['data']) && $cbResult['data'] != null) {
                                $rowdata1 = Path::clean(str_replace($this->processor->findtags, $this->processor->replacetags, $row->data1));
                                if ($cbResult['data']['protect_upload_directory']) {
                                    if (is_dir($rowdata1) && !file_exists($rowdata1 . '/' . '.htaccess'))
                                        File::write($rowdata1 . '/' . '.htaccess', $def = 'deny from all');
                                } else {
                                    if (is_dir($rowdata1) && file_exists($rowdata1 . '/' . '.htaccess'))
                                        File::delete($rowdata1 . '/' . '.htaccess');
                                }
                            }

                            $areas = json_decode($this->processor->formrow->template_areas, true);
                            $useUrl = false;
                            $useUrlDownloadDirectory = '';
                            $resize_target_width = 0;
                            $resize_target_height = 0;
                            $resize_type = '';
                            $resize_bgcolor = '#ffffff';
                            if (trim($this->processor->formrow->template_code_processed) == 'QuickMode' && is_array($areas)) {
                                foreach ($areas as $area) { // don't worry, size is only 1 in QM
                                    if (isset($area['elements'])) {
                                        foreach ($area['elements'] as $element) {
                                            if (isset($element['options']) && isset($element['options']['useUrl']) && isset($element['name']) && trim($element['name']) == trim($row->name) && isset($element['internalType']) && $element['internalType'] == 'bfFile') {
                                                $useUrl = $element['options']['useUrl'];
                                                $useUrlDownloadDirectory = $element['options']['useUrlDownloadDirectory'];
                                                $resize_target_width = $element['options']['resize_target_width'];
                                                $resize_target_height = $element['options']['resize_target_height'];
                                                $resize_type = $element['options']['resize_type'];
                                                $resize_bgcolor = $element['options']['resize_bgcolor'];
                                                break;
                                            }
                                        }
                                    }
                                    break; // just in case
                                }
                            }

                            $uploadfiles = $this->processor->app->getInput()->files->get(
                                'ff_nm_' . $row->name,
                                null,
                                'array'
                            );

                            if ($this->processor->formrow->template_code != '' && ($uploadfiles['tmp_name'][0] ?? '') != '' && trim($row->data2) != '') {
                                $fileName = (string) ($uploadfiles['name'][0] ?? '');
                                $ext = strtolower(substr($fileName, strrpos($fileName, '.') + 1));
                                $allowedExtensions = explode(',', strtolower(str_replace(' ', '', trim($row->data2))));

                                if (!in_array($ext, $allowedExtensions)) {
                                    $this->processor->status = _FF_STATUS_FILE_EXTENSION_NOT_ALLOWED;
                                    return;
                                }
                            }

                            $paths = array();
                            $serverPaths = array();
                            // CONTENTBUILDER
                            $is_relative = array();

                            if ($uploadfiles) {
                                $name = $uploadfiles['name'];
                                $tmp_name = $uploadfiles['tmp_name'];
                                $cnt = count($name);
                                for ($i = 0; $i < $cnt; $i++) {
                                    $path = '';
                                    if ($name[$i] != '') {
                                        $rowpath1 = $row->data1;
                                        //if ($cbResult !== null && isset($cbResult['data']) && $cbResult['data'] != null) {
                                        $rowpath1 = $this->processor->cbCreatePathByTokens($rowpath1, $this->processor->rows, $row->name);
                                        //}

                                        $pathInfo = $this->saveUpload($tmp_name[$i], bf_sanitizeFilename($name[$i]), $rowpath1, $row->flag1, $useUrl, $useUrlDownloadDirectory, $resize_target_width, $resize_target_height, $resize_type, $resize_bgcolor, $row->name);
                                        $path = $pathInfo['default'];
                                        $serverPath = $pathInfo['server'];
                                        if ($this->processor->status != _FF_STATUS_OK)
                                            return;
                                        $paths[] = $path;

                                        $serverPaths[] = $serverPath;
                                        $this->processor->submitdata[] = array($row->id, $row->name, strip_tags($row->title), $row->type, $path);
                                        // CONTENTBUILDER
                                        if (strpos(strtolower($row->data1), '{cbsite}') === 0) {
                                            $is_relative[$serverPath] = true;
                                        }
                                    } // if
                                } // for
                            } // if
                            if ($this->processor->app->getInput()->getString('bfFlashUploadTicket', '') != '') {
                                $tickets = $this->processor->app->getSession()->get('bfFlashUploadTickets', array());
                                mt_srand();
                                if (isset($tickets[$this->processor->app->getInput()->getString('bfFlashUploadTicket', (string) mt_rand(0, mt_getrandmax()))])) {
                                    $sourcePath = (new TemporaryUploadDirectory((string) $this->processor->app->get('tmp_path')))->path();
                                    if (is_dir($sourcePath) && is_readable($sourcePath)) {

                                        $timezone = (string) $this->processor->app->get('offset');
                                        $date_stamp = $this->uploadTimestampFormatter()->formatPattern(
                                            (string) $this->processor->submitted,
                                            $timezone,
                                            'Y_m_d_H_i_s'
                                        );
                                        $date_stamp2 = $this->uploadTimestampFormatter()->formatPattern(
                                            (string) $this->processor->submitted,
                                            $timezone,
                                            'Y_m_d'
                                        );

                                        // trying glob instead of readdir()

                                        foreach ($this->flashUploadFileMatcher()->find(
                                            $sourcePath,
                                            $row->name,
                                            $this->processor->app->getInput()->getString('bfFlashUploadTicket', '')
                                        ) as $flashFile) {

                                            $glob_file = $flashFile['path'];

                                            $file = basename($glob_file);

                                            $parts = explode('_', $file);

                                                                unset($parts[count($parts) - 1]);
                                                                unset($parts[count($parts) - 1]);
                                                                unset($parts[count($parts) - 1]);
                                                                unset($parts[count($parts) - 1]);
                                                                $userfile_name = implode('_', $parts);
                                                                $rowpath1 = $row->data1;
                                                                //if ($cbResult !== null && isset($cbResult['data']) && $cbResult['data'] != null) {
                                                                $rowpath1 = $this->processor->cbCreatePathByTokens($rowpath1, $this->processor->rows, $row->name);
                                                                //}
                                                                $baseDir = Path::clean(str_replace($this->processor->findtags, $this->processor->replacetags, $rowpath1));

                                                                // test if there is a filemask and remove it from the basepath
                                                                $_baseDir = $baseDir;

                                                                $fmtest = str_replace('{filemask:', '', basename($baseDir));
                                                                if ($fmtest != basename($baseDir)) {
                                                                    $baseDir = rtrim(rtrim(str_replace(basename($baseDir), '', $baseDir), '/'), "\\");
                                                                }

                                                                if ($fmtest != basename($_baseDir)) {
                                                                    $fm = basename($_baseDir);

                                                                    foreach ($this->processor->rows as $row2) {

                                                                        $rawFname = $this->processor->app->getInput()->post->get('ff_nm_' . $row2->name, [], 'raw');
                                                        $permissiveFilter = InputFilter::getInstance([], [], 1, 1);
                                                        $fname = \is_array($rawFname)
                                                            ? array_map(static fn ($value) => $permissiveFilter->clean((string) $value, 'html'), $rawFname)
                                                            : [];

                                                                        foreach ($fname as $_fname) {
                                                                            $fm = str_replace('{filemask:' . strtolower($row2->name) . '}', File::makeSafe(trim($_fname)), $fm);
                                                                            // so it works the same like for folders
                                                                            $fm = str_replace('{' . strtolower($row2->name) . ':value}', File::makeSafe(trim($_fname)), $fm);
                                                                        }
                                                                    }

                                                                    $fm = str_replace('{filemask:_separator}', '_', $fm);
                                                                    $fm = str_replace('{filemask:_username}', trim($this->processor->app->getIdentity()->get('username')), $fm);
                                                                    $fm = str_replace('{filemask:_userid}', trim($this->processor->app->getIdentity()->get('id')), $fm);
                                                                    $fm = str_replace('{filemask:_name}', trim($this->processor->app->getIdentity()->get('name')), $fm);
                                                                    $fm = str_replace('{filemask:_datetime}', trim($date_stamp), $fm);
                                                                    $fm = str_replace('{filemask:_date}', trim($date_stamp2), $fm);
                                                                    $fm = str_replace('{filemask:_timestamp}', trim(time()), $fm);
                                                                    $fm = str_replace('{filemask:_random}', trim(mt_rand(0, mt_getrandmax())), $fm);
                                                                    $fm = str_replace('{filemask:_filename}', trim(basename($userfile_name, '.' . File::getExt($userfile_name))), $fm);
                                                                    if ($fm == '') {
                                                                        $fm = '__empty__';
                                                                    }
                                                                    $userfile_name = $fm . '.' . File::getExt($userfile_name);
                                                                }

                                                                //if ($row->flag1)
                                                                //	$userfile_name = $date_stamp . '_' . $userfile_name;
                                                                $path = $baseDir . '/' . $userfile_name;
                                                                //if ($row->flag1) $path .= '.'.date('YmdHis');
                                                                if (file_exists($path) && $this->processor->app->getSession()->get('bfFileUploadOverride', true)) {
                                                                    $rnd = md5(mt_rand(0, mt_getrandmax()));
                                                                    $path = $baseDir . '/' . $rnd . '_' . $userfile_name;
                                                                    //if ($row->flag1) $path .= '.'.date('YmdHis');
                                                                    if (file_exists($path)) {
                                                                        $this->processor->status = _FF_STATUS_UPLOAD_FAILED;
                                                                        $this->processor->message = Text::_('COM_BREEZINGFORMSNG_PROCESS_FILEEXISTS');
                                                                        return '';
                                                                    }
                                                                } else if (file_exists($path) && !$this->processor->app->getSession()->get('bfFileUploadOverride', true)) {
                                                                    unlink($path);
                                                                }

                                                                $ext = strtolower(substr($userfile_name, strrpos($userfile_name, '.') + 1));
                                                                $allowedExtensions = explode(',', strtolower(str_replace(' ', '', trim($row->data2))));

                                                                if (!in_array($ext, $allowedExtensions)) {
                                                                    $this->processor->status = _FF_STATUS_FILE_EXTENSION_NOT_ALLOWED;
                                                                }

                                                                if ($this->processor->status != _FF_STATUS_OK)
                                                                    return;

                                                                if (!is_readable($sourcePath . $file) || !file_exists($baseDir) || !is_dir($baseDir)) {
                                                                    $this->processor->status = _FF_STATUS_UPLOAD_FAILED;
                                                                    $this->processor->message = Text::_('COM_BREEZINGFORMSNG_PROCESS_FILEMOVEFAILED');
                                                                    return;
                                                                }

                                                                if (!File::copy($sourcePath . $file, $path)) {
                                                                    $this->processor->status = _FF_STATUS_UPLOAD_FAILED;
                                                                    $this->processor->message = Text::_('COM_BREEZINGFORMSNG_PROCESS_FILEMOVEFAILED');
                                                                    return;
                                                                }
                                                                if (!File::delete($sourcePath . $file)) {
                                                                    $this->processor->status = _FF_STATUS_UPLOAD_FAILED;
                                                                    $this->processor->message = Text::_('COM_BREEZINGFORMSNG_PROCESS_FILEMOVEFAILED');
                                                                    return;
                                                                }

                                                                $serverPath = $path;

                                                                if ($useUrl) {

                                                                    $cleaned = str_replace(JPATH_SITE . '/', '', $baseDir);

                                                                    $path = Uri::root() . rtrim($cleaned, '/') . '/' . basename($path);
                                                                }

                                                                $paths[] = $path;
                                                                $serverPaths[] = $serverPath;
                                                                $this->processor->submitdata[] = array($row->id, $row->name, strip_tags($row->title), $row->type, $path);

                                                                // resize if image
                                                                // last param = crop or simple. Nothing for exact.
                                                                if (intval($resize_target_height) > 0 && intval($resize_target_width) > 0) {
                                                                    $this->uploadRuntime()->resizeFile($serverPath, intval($resize_target_width), intval($resize_target_height), $resize_bgcolor, $resize_type);
                                                                }

                                                                // CONTENTBUILDER
                                                                if (strpos(strtolower($row->data1), '{cbsite}') === 0) {
                                                                    $is_relative[$serverPath] = true;
                                                                }
                                        }
                                    }
                                }
                            }
                            if (!count($paths))
                                $paths = array();
                            if ($row->logging == 1) {
                                // db and attachment
                                // DROPBOX SUPPORT request v2 API

                                foreach ($serverPaths as $serverPath) {

                                    // DROPBOX File Upload
                                    if ($this->processor->formrow->dropbox_email) {
                                        $this->uploadFileToDropbox($serverPath);
                                    }

                                    // CONTENTBUILDER: to keep the relative path with prefix
                                    $savedata_path = $serverPath;
                                    foreach ($this->processor->findtags as $tag) {
                                        if (strtolower($tag) == '{cbsite}' && isset($is_relative[$serverPath])) {
                                            $savedata_path = Path::clean(str_replace(array(JPATH_SITE, JPATH_SITE), array('{cbsite}', '{CBSite}'), $savedata_path));
                                        }
                                    }

                                    if (
                                        ($this->processor->formrow->dblog == 1 && $savedata_path != '') ||
                                        $this->processor->formrow->dblog == 2 || ($cbResult != null && $cbResult['record'] != null)
                                    )
                                        $this->processor->savedata[] = array($row->id, $row->name, strip_tags($row->title), $row->type, $savedata_path);
                                }

                                foreach ($paths as $path) {
                                    if (
                                        (($this->processor->formrow->emaillog == 1 && $this->processor->trim($path)) ||
                                            $this->processor->formrow->emaillog == 2) && ($this->processor->formrow->emailxml == 1 ||
                                            $this->processor->formrow->emailxml == 2 || $this->processor->formrow->emailxml == 3 || $this->processor->formrow->emailxml == 4)
                                    )
                                        $this->processor->xmldata[] = array($row->id, $row->name, strip_tags($row->title), $row->type, $path);
                                    if (
                                        (($this->processor->formrow->emaillog == 1 && $this->processor->trim($path)) ||
                                            $this->processor->formrow->mb_emaillog == 2) && ($this->processor->formrow->mb_emailxml == 1 ||
                                            $this->processor->formrow->mb_emailxml == 2 || $this->processor->formrow->mb_emailxml == 3 || $this->processor->formrow->mb_emailxml == 4)
                                    )
                                        $this->processor->mb_xmldata[] = array($row->id, $row->name, strip_tags($row->title), $row->type, $path);
                                } // foreach

                                if (!count($paths)) {
                                    if (
                                        ($this->processor->formrow->dblog == 1) ||
                                        $this->processor->formrow->dblog == 2
                                    )
                                        $this->processor->savedata[] = array($row->id, $row->name, strip_tags($row->title), $row->type, '');
                                    if (
                                        $this->processor->formrow->emaillog == 2 && ($this->processor->formrow->emailxml == 1 ||
                                            $this->processor->formrow->emailxml == 2 || $this->processor->formrow->emailxml == 3 || $this->processor->formrow->emailxml == 4)
                                    )
                                        $this->processor->xmldata[] = array($row->id, $row->name, strip_tags($row->title), $row->type, '');
                                    if (
                                        $this->processor->formrow->mb_emaillog == 2 && ($this->processor->formrow->mb_emailxml == 1 ||
                                            $this->processor->formrow->mb_emailxml == 2 || $this->processor->formrow->mb_emailxml == 3 || $this->processor->formrow->mb_emailxml == 4)
                                    )
                                        $this->processor->mb_xmldata[] = array($row->id, $row->name, strip_tags($row->title), $row->type, '');
                                }
                                // mail
                                $paths = implode(nl(), $paths);
                                $serverPaths = implode(nl(), $serverPaths);

                                if ($this->processor->trim($paths)) {
                                    $this->processor->sfdata[] = array($row->id, $row->name, strip_tags($row->title), $row->type, $paths, $serverPaths);
                                }

                                if (
                                    ($this->processor->formrow->emaillog == 1 && $this->processor->trim($paths)) ||
                                    $this->processor->formrow->emaillog == 2
                                ) {
                                    $this->processor->maildata[] = array(
                                        $row->id,
                                        $row->name,
                                        strip_tags($row->title),
                                        $row->type,
                                        $paths,
                                        $serverPaths
                                    );
                                }
                            } // if
                            break;
                        case 'Text':
                        case 'Textarea':
                        case 'Checkbox':
                        case 'Radio Button':
                        case 'Select List':
                        case 'Query List':
                        case 'Radio Group':
                        case 'Checkbox Group':
                        case 'Number Input':
                        case 'Calendar':
                        case 'Hidden Input':
                        case 'Signature':
                            if ($row->logging == 1) {

                                $rawValues = $this->processor->app->getInput()->post->get("ff_nm_" . $row->name, [''], 'raw');
                                $permissiveFilter = InputFilter::getInstance([], [], 1, 1);
                                $values = \is_array($rawValues)
                                    ? array_map(static fn ($value) => $permissiveFilter->clean((string) $value, 'html'), $rawValues)
                                    : [''];

                                if ($row->type == 'Textarea') {
                                    require_once(JPATH_SITE . '/administrator/components/com_breezingformsng/libraries/crosstec/functions/helpers.php');

                                    if (trim($this->processor->formrow->template_code_processed) == 'QuickMode') {
                                        $dataObject = json_decode(bf_b64dec($this->processor->formrow->template_code), true);
                                        $qmelement = $this->uploadRuntime()->findQuickModeElement($dataObject, $row->name);

                                        if ($qmelement !== null && isset($qmelement['properties']['is_html']) && $qmelement['properties']['is_html']) {
                                            $rawValues = $this->processor->app->getInput()->post->get("ff_nm_" . $row->name, [''], 'raw');
                                            $permissiveFilter = InputFilter::getInstance([], [], 1, 1);
                                            $values = \is_array($rawValues)
                                                ? array_map(static fn ($value) => $permissiveFilter->clean((string) $value, 'html'), $rawValues)
                                                : [''];

                                            $html_value_cnt = count($values);

                                            for ($html_i = 0; $html_i < $html_value_cnt; $html_i++) {

                                                //$values[$html_i] = $this->processor->removeDangerousHtml($values[$html_i]);

                                                /*
                                                  $input = $this->processor->app->getInput();
                                                  $input->set('cbCleanVar', $values[$html_i]);
                                                  $values[$html_i] = $input->getHtml('cbCleanVar'); */

                                                $values[$html_i] = $permissiveFilter->clean((string) $values[$html_i], 'html');
                                            }
                                        }
                                    }
                                }

                                $sigValues = '';

                                foreach ($values as $value) {

                                    if ($row->type == 'Signature' && $value != '') {
                                        if (!is_dir(JPATH_SITE . '/media/breezingforms/signatures/')) {
                                            Folder::create(JPATH_SITE . '/media/breezingforms/signatures/');
                                            $def = '';
                                            File::write(JPATH_SITE . '/media/breezingforms/signatures/index.html', $def);
                                        }
                                        $sig_decoded = bf_b64dec($value);
                                        $sig_file = JPATH_SITE . '/media/breezingforms/signatures/' . $row->name . '-' . md5($value) . '.png';
                                        File::write($sig_file, $sig_decoded);
                                        $value = basename($sig_file);

                                        $sigValues .= $value;

                                        // DROPBOX SUPPORT request v2 API
                                        // DROPBOX Signature upload
                                        if ($this->processor->formrow->dropbox_email) {
                                            $this->uploadFileToDropbox($sig_file);
                                        }
                                    }

                                    // for db
                                    if (
                                        ($this->processor->formrow->dblog == 1 && $value != '') ||
                                        $this->processor->formrow->dblog == 2 || ($cbResult != null && $cbResult['record'] != null)
                                    )
                                        $this->processor->savedata[] = array($row->id, $row->name, strip_tags($row->title), $row->type, $value);

                                    // CONTENTBUILDER
                                    $loadData = true;
                                    switch ($row->type) {
                                        case 'Checkbox':
                                        case 'Checkbox Group':
                                        case 'Radio Button':
                                        case 'Radio Group':
                                        case 'Select List':
                                            if ($value == 'cbGroupMark') {
                                                $loadData = false;
                                            }
                                            break;
                                    }

                                    if ($loadData) {
                                        // submitdata
                                        if ($this->processor->trim($value) != '')
                                            $this->processor->submitdata[] = array($row->id, $row->name, strip_tags($row->title), $row->type, $value);

                                        if (
                                            ($this->processor->formrow->emaillog == 1 && $this->processor->trim($value) != '') ||
                                            $this->processor->formrow->emaillog == 2 && (($this->processor->formrow->emailxml == 1 ||
                                                $this->processor->formrow->emailxml == 2 || $this->processor->formrow->emailxml == 3 || $this->processor->formrow->emailxml == 4))
                                        )
                                            $this->processor->xmldata[] = array($row->id, $row->name, strip_tags($row->title), $row->type, $value);
                                        if (
                                            ($this->processor->formrow->mb_emaillog == 1 && $this->processor->trim($value) != '') ||
                                            $this->processor->formrow->mb_emaillog == 2 && (($this->processor->formrow->mb_emailxml == 1 ||
                                                $this->processor->formrow->mb_emailxml == 2 || $this->processor->formrow->mb_emailxml == 3 || $this->processor->formrow->mb_emailxml == 4))
                                        )
                                            $this->processor->mb_xmldata[] = array($row->id, $row->name, strip_tags($row->title), $row->type, $value);
                                    }
                                } // foreach
                                // for mail

                                $sfvalues = $values;

                                if ($row->type == 'Signature') {

                                    $values = $sigValues;
                                    $sfvalues = $sigValues;
                                } else if ($row->type == 'Textarea') {

                                    $values = implode(nl(), $values);
                                    $sfvalues = implode(nl(), $sfvalues);
                                } else {

                                    // CONTENTBUILDER
                                    $useNewValues = false;
                                    $newValues = array();
                                    $sfnewValues = array();

                                    foreach ($values as $value) {
                                        switch ($row->type) {
                                            case 'Checkbox':
                                            case 'Checkbox Group':
                                            case 'Radio Button':
                                            case 'Radio Group':
                                            case 'Select List':
                                                if ($value != 'cbGroupMark') {
                                                    $newValues[] = $value;
                                                    $sfnewValues[] = $value;
                                                } else {
                                                    $useNewValues = true;
                                                }
                                                break;
                                        }
                                    }

                                    if ($useNewValues) {
                                        $values = implode(', ', $newValues);
                                        $sfvalues = implode(';', $sfnewValues);
                                    } else {
                                        $values = implode(', ', $values);
                                        $sfvalues = implode(';', $sfvalues);
                                    }
                                }

                                if ($this->processor->trim($sfvalues)) {
                                    $this->processor->sfdata[] = array($row->id, $row->name, strip_tags($row->title), $row->type, $sfvalues);
                                }

                                if (
                                    ($this->processor->formrow->emaillog == 1 && $this->processor->trim($values)) ||
                                    $this->processor->formrow->emaillog == 2
                                ) {
                                    $this->processor->maildata[] = array(
                                        $row->id,
                                        $row->name,
                                        strip_tags($row->title),
                                        $row->type,
                                        $values
                                    );
                                }
                            } // if logging
                            break;
                        default:
                            ;
                    } // switch
                    $names[] = $row->name;
                } // if
                            $time_passed = $this->measureTime();
                if (($time_passed - $start_time) > $max_time) {
                    //break;
                }
            } // for
        }
    }

    private function flashUploadFileMatcher(): FlashUploadFileMatcher
    {
        return $this->flashUploadFileMatcherService ??= new FlashUploadFileMatcher();
    }

    // collectSubmitdata

    function submit()
    {
        global $ff_config, $ff_comsite, $ff_mossite, $ff_otherparams;

        if (trim((string) $this->processor->formrow->template_code_processed) !== 'QuickMode') {
            echo '<div class="alert alert-warning">' . Text::_('COM_BREEZINGFORMSNG_QUICKMODE_ONLY') . '</div>';
            return;
        }

        // CONTENTBUILDER BEGIN
        $cbRecordId = 0;
        $cbEmailNotifications = false;
        $cbEmailUpdateNotifications = false;
        $cbResult = $this->processor->cbCheckPermissions();
        if ($cbResult['data'] !== null && $cbResult['data']['email_notifications']) {
            if (!$this->processor->app->getInput()->getInt('cb_record_id', 0)) {
                $cbEmailNotifications = true;
            } else {
                $cbEmailNotifications = false;
            }
        }
        if ($cbResult['data'] !== null && $cbResult['data']['email_update_notifications']) {
            if ($this->processor->app->getInput()->getInt('cb_record_id', 0)) {
                $cbEmailUpdateNotifications = true;
            } else {
                $cbEmailUpdateNotifications = false;
            }
        }
        if ($cbResult['data'] === null) {
            $cbEmailNotifications = true;
            $cbEmailUpdateNotifications = true;
        }
        // CONTENTBUILDER END
        if (!$this->processor->okrun)
            return;

        ob_start();
        $this->processor->record_id = '';
        $this->processor->status = _FF_STATUS_OK;
        $this->processor->message = '';
        $this->processor->sendNotificationAfterPayment = false;

        // handle Begin Submit piece
        $this->collectSubmitdata($cbResult);

            for ($i = 0; $i < $this->processor->rowcount; $i++) {
                $row = $this->processor->rows[$i];
                if ($row->type == "Captcha") {
                    VendorHelper::load();
                    $securimage = new Securimage();
                    if (!$securimage->check($this->processor->app->getInput()->getString('bfCaptchaEntry', ''))) {
                        $this->processor->status = _FF_STATUS_CAPTCHA_FAILED;
                        exit;
                    }
                    break;
                } else
                    if ($row->type == "ReCaptcha") {


                        $areas = json_decode($this->processor->formrow->template_areas, true);

                        foreach ($areas as $area) {
                            foreach ($area['elements'] as $element) {
                                if ($element['bfType'] == 'ReCaptcha') {

                                    try {
                                        $verified = $this->recaptchaVerifier->verify(
                                            (string) $element['privkey'],
                                            $this->processor->app->getInput()->getString('g-recaptcha-response', ''),
                                            $this->processor->app->getInput()->server->getString('REMOTE_ADDR', '')
                                        );
                                    } catch (\Throwable) {
                                        $verified = false;
                                    }

                                    if ($verified) {

                                        // all good
                                    } else {

                                        $this->processor->status = _FF_STATUS_CAPTCHA_FAILED;
                                        exit;
                                    }

                                    break;
                                }
                            }
                        }

                        break;
                    }
            }


            $areas = json_decode($this->processor->formrow->template_areas, true);

            if (is_array($areas)) {
                switch ($this->processor->app->getInput()->getString('ff_payment_method', '')) {
                    case 'Stripe':
                    case 'PayPal':
                    case 'Sofortueberweisung':
                        foreach ($areas as $area) {
                            foreach ($area['elements'] as $element) {
                                if ($element['internalType'] == 'bfStripe' || $element['internalType'] == 'bfPayPal' || $element['internalType'] == 'bfSofortueberweisung') {
                                    $options = $element['options'];
                                    if (isset($options['sendNotificationAfterPayment']) && $options['sendNotificationAfterPayment']) {
                                        $this->processor->sendNotificationAfterPayment = true;
                                    }
                                }
                            }
                        }
                }
            }
            $code = '';

            switch ($this->processor->formrow->piece3cond) {
                case 1: // library
                    $piece3id = (int) $this->processor->formrow->piece3id;
                    $query = $this->processor->database->getQuery(true)
                        ->select(['name', 'code'])
                        ->from($this->processor->database->quoteName('#__facileforms_pieces'))
                        ->where($this->processor->database->quoteName('id') . ' = :piece3id')
                        ->where($this->processor->database->quoteName('published') . ' = 1')
                        ->bind(':piece3id', $piece3id, ParameterType::INTEGER);
                    $this->processor->database->setQuery($query);
                    $rows = $this->processor->database->loadObjectList();
                    if (count($rows))
                        echo $this->scriptingEngine->execPiece(
                            $rows[0]->code,
                            Text::_('COM_BREEZINGFORMSNG_PROCESS_BSPIECE') . " " . $rows[0]->name,
                            'p',
                            $this->processor->formrow->piece3id,
                            null
                        );
                    break;
                case 2: // custom code
                    echo $this->scriptingEngine->execPiece(
                        $this->processor->formrow->piece3code,
                        Text::_('COM_BREEZINGFORMSNG_PROCESS_BSPIECEC'),
                        'f',
                        $this->processor->form,
                        3
                    );
                    break;
                default:
                    break;
            } // switch
            if ($this->processor->bury())
                return;

            if ($this->processor->status == _FF_STATUS_OK) {
                if (!$this->processor->formrow->published) {
                    $this->processor->status = _FF_STATUS_UNPUBLISHED;
                } else {
                    if ($this->processor->status == _FF_STATUS_OK) {
                        if ($this->processor->formrow->dblog > 0)
                            $cbRecordId = $this->exportEngine->logToDatabase($cbResult);

                        if ($this->processor->status == _FF_STATUS_OK) {
                            if ($this->processor->formrow->emailntf > 0 && ($cbEmailNotifications || $cbEmailUpdateNotifications)) { // CONTENTBUILDER
                                $this->notificationEngine->sendEmailNotification();
                            }
                            if ($this->processor->formrow->mb_emailntf > 0 && ($cbEmailNotifications || $cbEmailUpdateNotifications)) { // CONTENTBUILDER
                                $this->notificationEngine->sendMailbackNotification();
                            }

                            // DROPBOX request v2 API and PDF,CSV, XML upload
                            if ($this->processor->formrow->dropbox_submission_enabled) {
                                if ($this->processor->formrow->dropbox_email) {
                                    try {
                                        $dropbox_types = explode(',', $this->processor->formrow->dropbox_submission_types);
                                        foreach ($dropbox_types as $dropbox_type) {
                                            $dropbox_file = '';
                                            switch ($dropbox_type) {
                                                case 'pdf':
                                                    $dropbox_file = $this->exportEngine->exppdf();
                                                    break;
                                                case 'csv':
                                                    $dropbox_file = $this->exportEngine->expcsv();
                                                    break;
                                                case 'xml':
                                                    $dropbox_file = $this->exportEngine->expxml();
                                                    break;
                                            }
                                            if ($dropbox_file != '') {
                                                $this->uploadFileToDropbox($dropbox_file);
                                            }
                                        }
                                    } catch (Exception $e) {

                                    }
                                }
                            }


                            $this->notificationEngine->sendMailChimpNotification();
                            $this->notificationEngine->sendSalesforceNotification();

                            PluginHelper::importPlugin('breezingforms_addons');
                            $dispatcher = $this->processor->app->getDispatcher();
                            $dispatcher->dispatch('onPropertiesExecute', new Event('onPropertiesExecute',
                                array(
                                    $this
                                )
                            ));

                            $tickets = $this->processor->app->getSession()->get('bfFlashUploadTickets', array());
                            mt_srand();
                            if (isset($tickets[$this->processor->app->getInput()->getString('bfFlashUploadTicket', (string) mt_rand(0, mt_getrandmax()))])) {
                                unset($tickets[$this->processor->app->getInput()->getString('bfFlashUploadTicket', '')]);
                                $this->processor->app->getSession()->set('bfFlashUploadTickets', $tickets);
                            }
                        }
                    } // if
                } // if
            } // if
            // handle End Submit piece
            // DOUBLE OPT-IN

            if ($this->processor->formrow->double_opt) {
                $uri = Uri::getInstance();
                $domainAddress = $uri->toString(array('scheme', 'host', 'port', 'path'));

                /** @var Mail $mailer */
                $mailer = $this->mailerFactory->createMailer();
                $config = $this->processor->app->getConfig();

                $recipient = '';
                $email_field_name = $this->processor->formrow->opt_mail;

                // getting the email address from the form based on the setting in admin
                foreach ($this->processor->submitdata as $data) {

                    if ($data[_FF_DATA_NAME] == $email_field_name) {
                        $recipient = $data[_FF_DATA_VALUE];
                        break;
                    }
                }

                if (bf_is_email($recipient)) {

                    $formValue = $this->processor->form;
                    $existsQuery = $this->processor->database->getQuery(true)
                        ->select('s.record')
                        ->from($this->processor->database->quoteName('#__facileforms_subrecords', 's'))
                        ->from($this->processor->database->quoteName('#__facileforms_records', 'r'))
                        ->where('r.form = :formValue')
                        ->where('r.id = s.record')
                        ->where($this->processor->database->quoteName('s.name') . ' = :emailFieldName')
                        ->where($this->processor->database->quoteName('s.value') . ' = :recipient')
                        ->where($this->processor->database->quoteName('r.opted') . ' = 1')
                        ->bind(':formValue', $formValue, ParameterType::STRING)
                        ->bind(':emailFieldName', $email_field_name, ParameterType::STRING)
                        ->bind(':recipient', $recipient, ParameterType::STRING);
                    $this->processor->database->setQuery($existsQuery);
                    $exists = $this->processor->database->loadResult();

                    if (!$exists) {

                        $mailer->addRecipient($recipient);
                        $sender = array(
                            $config->get('mailfrom'),
                            $config->get('fromname')
                        );

                        $lastID = $this->processor->record_id;
                        $token = $this->exportEngine->random_str(20);
                        $optToken = bf_b64enc($token);
                        $updateQuery = $this->processor->database->getQuery(true)
                            ->update($this->processor->database->quoteName('#__facileforms_records'))
                            ->set($this->processor->database->quoteName('opt_token') . ' = :optToken')
                            ->where($this->processor->database->quoteName('id') . ' = :lastID')
                            ->bind(':optToken', $optToken, ParameterType::STRING)
                            ->bind(':lastID', $lastID, ParameterType::STRING);
                        $this->processor->database->setQuery($updateQuery);
                        $this->processor->database->execute();

                        $opt_in_link = $domainAddress . '?option=com_breezingformsng&opt_in=true&id=' . $lastID . '&' . 'token=' . bf_b64enc($token);
                        $opt_out_link = $domainAddress . '?option=com_breezingformsng&opt_out=true&id=' . $lastID . '&' . 'token=' . bf_b64enc($token);

                        $message = Text::_('COM_BREEZINGFORMSNG_FORMS_DOUBLE_OPT_EMAIL_TEXT');
                        $message .= '<a href="' . $opt_in_link . '">' . Text::_('COM_BREEZINGFORMSNG_FORMS_DOUBLE_OPT_VERIFY_HERE') . '</a>';
                        $message .= Text::_('COM_BREEZINGFORMSNG_FORMS_DOUBLE_OPT_OUT_EMAIL_TEXT');
                        $message .= '<a href="' . $opt_out_link . '">' . Text::_('COM_BREEZINGFORMSNG_FORMS_DOUBLE_OPT_UNVERIFY_HERE') . '</a>';
                        $message .= Text::_('COM_BREEZINGFORMSNG_FORMS_DOUBLE_OPT_EMAIL_TEXT_FOOTER');

                        $body = $message;
                        $mailer->isHtml(true);
                        $mailer->setSubject(Text::_('COM_BREEZINGFORMSNG_FORMS_DOUBLE_OPT_EMAIL_SUBJECT'));
                        $mailer->setBody($body);
                        $mailer->setSender($sender);
                        $mailer->send();
                    }
                }
            }

            // DOUBLE OPT-INT END

            $maxIdQuery = $this->processor->database->getQuery(true)
                ->select('MAX(id)')
                ->from($this->processor->database->quoteName('#__facileforms_records'));
            $this->processor->database->setQuery($maxIdQuery);
            $lastid = $this->processor->database->loadResult();
            $session = $this->processor->app->getSession();
            $session->set('virtuemart_bf_id', $lastid);

            $code = '';
            switch ($this->processor->formrow->piece4cond) {
                case 1: // library
                    $piece4id = (int) $this->processor->formrow->piece4id;
                    $query = $this->processor->database->getQuery(true)
                        ->select(['name', 'code'])
                        ->from($this->processor->database->quoteName('#__facileforms_pieces'))
                        ->where($this->processor->database->quoteName('id') . ' = :piece4id')
                        ->where($this->processor->database->quoteName('published') . ' = 1')
                        ->bind(':piece4id', $piece4id, ParameterType::INTEGER);
                    $this->processor->database->setQuery($query);
                    $rows = $this->processor->database->loadObjectList();
                    if (count($rows))
                        echo $this->scriptingEngine->execPiece(
                            $rows[0]->code,
                            Text::_('COM_BREEZINGFORMSNG_PROCESS_ESPIECE') . " " . $rows[0]->name,
                            'p',
                            $this->processor->formrow->piece4id,
                            null
                        );
                    break;
                case 2: // custom code
                    echo $this->scriptingEngine->execPiece(
                        $this->processor->formrow->piece4code,
                        Text::_('COM_BREEZINGFORMSNG_PROCESS_ESPIECEC'),
                        'f',
                        $this->processor->form,
                        3
                    );
                    break;
                default:
                    break;
            } // switch

            if ($this->processor->bury())
                return;
        switch ($this->processor->status) {
            case _FF_STATUS_OK:
                $message = Text::_('COM_BREEZINGFORMSNG_PROCESS_SUBMITSUCCESS');
                break;
            case _FF_STATUS_UNPUBLISHED:
                $message = Text::_('COM_BREEZINGFORMSNG_PROCESS_UNPUBLISHED');
                break;
            case _FF_STATUS_SAVERECORD_FAILED:
                $message = Text::_('COM_BREEZINGFORMSNG_PROCESS_SAVERECFAILED');
                break;
            case _FF_STATUS_SAVESUBRECORD_FAILED:
                $message = Text::_('COM_BREEZINGFORMSNG_PROCESS_SAVESUBFAILED');
                break;
            case _FF_STATUS_UPLOAD_FAILED:
                $message = Text::_('COM_BREEZINGFORMSNG_PROCESS_UPLOADFAILED');
                break;
            case _FF_STATUS_SENDMAIL_FAILED:
                $message = Text::_('COM_BREEZINGFORMSNG_PROCESS_SENDMAILFAILED');
                break;
            case _FF_STATUS_ATTACHMENT_FAILED:
                $message = Text::_('COM_BREEZINGFORMSNG_PROCESS_ATTACHMTFAILED');
                break;
            case _FF_STATUS_CAPTCHA_FAILED:
                $message = Text::_('COM_BREEZINGFORMSNG_CAPTCHA_ENTRY_FAILED');
                break;
            case _FF_STATUS_FILE_EXTENSION_NOT_ALLOWED:
                $message = Text::_('COM_BREEZINGFORMSNG_FILE_EXTENSION_NOT_ALLOWED');
                break;
            default:
                $message = '';
                // custom piece status and message
                break;
        } // switch
        // built in PayPal action
        $paymentAction = false;

        if ($this->processor->formrow->template_code != '') {


            $areas = json_decode($this->processor->formrow->template_areas, true);
            $head = json_decode(bf_b64dec($this->processor->formrow->template_code), true);

            if (is_array($areas)) {
                $paymentAction = true;

                switch ($this->processor->app->getInput()->getString('ff_payment_method', '')) {


                    case 'Stripe':

                        foreach ($areas as $area) {
                            foreach ($area['elements'] as $element) {

                            if ($element['internalType'] == 'bfStripe') {

                                $options = $element['options'];

                                $ppselect = $this->processor->app->getInput()->get('ff_nm_bfPaymentSelect', [], 'string');
                                if (count($ppselect) != 0) {
                                    $ppselected = explode('|', $ppselect[0]);
                                    if (count($ppselected) == 4) {
                                        // XDA - BUG : useless with normal itemname
                                        // Replace item name for Stripe button field with TEXT1 TEXT2 values from bfPaymentSelect text build as TEXT1|TEXT2|MNT1|MNT2
                                        // $options['itemname'] = $ppselected[0] . ' ' . $ppselected[1];
                                        $options['amount'] = floatval($ppselected[2]) + floatval($ppselected[3]);
                                    }
                                }

                                $options['amount'] = round(floatval($options['amount']), 2) * 100;

                                $this->processor->app->getSession()->set('bf_stripe_last_payment_amount' . $this->processor->record_id, $options['amount']);

                                $html = '';

                                if (!$this->processor->inline)
                                    /*$html .= '<html><head><style> .stripe_checkout_app { height: 580px !important; } </style>
<meta name="mobile-web-app-capable" content="yes">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style type="text/css">
.thebutton {
-webkit-box-sizing: border-box;
-moz-box-sizing: border-box;
-ms-box-sizing: border-box;
-o-box-sizing: border-box;
box-sizing: border-box;
padding: 0;
margin: 0;
width: 120px;
height: 37px;
border: 0;
text-decoration: none;
background: #45b1e8;
cursor: pointer;
background-image: -webkit-linear-gradient(#45b1e8,#3097de);
background-image: -moz-linear-gradient(#45b1e8,#3097de);
background-image: -ms-linear-gradient(#45b1e8,#3097de);
background-image: -o-linear-gradient(#45b1e8,#3097de);
background-image: -webkit-linear-gradient(#45b1e8,#3097de);
background-image: -moz-linear-gradient(#45b1e8,#3097de);
background-image: -ms-linear-gradient(#45b1e8,#3097de);
background-image: -o-linear-gradient(#45b1e8,#3097de);
background-image: linear-gradient(#45b1e8,#3097de);
-webkit-border-radius: 4px;
-moz-border-radius: 4px;
-ms-border-radius: 4px;
-o-border-radius: 4px;
border-radius: 4px;
-webkit-font-smoothing: antialiased;
-webkit-touch-callout: none;
-webkit-user-select: none;
-moz-user-select: none;
-ms-user-select: none;
-o-user-select: none;
user-select: none;
cursor: pointer;
font-family: "Helvetica Neue","Helvetica",Arial,sans-serif;
font-weight: bold;
font-size: 17px;
color: #fff;
text-shadow: 0 -1px 0 rgba(46,86,153,0.3);
-webkit-box-shadow: 0 1px 0 rgba(46,86,153,0.15),0 0 4px rgba(86,149,219,0),inset 0 2px 0 rgba(41,102,20,0);
-moz-box-shadow: 0 1px 0 rgba(46,86,153,0.15),0 0 4px rgba(86,149,219,0),inset 0 2px 0 rgba(41,102,20,0);
-ms-box-shadow: 0 1px 0 rgba(46,86,153,0.15),0 0 4px rgba(86,149,219,0),inset 0 2px 0 rgba(41,102,20,0);
-o-box-shadow: 0 1px 0 rgba(46,86,153,0.15),0 0 4px rgba(86,149,219,0),inset 0 2px 0 rgba(41,102,20,0);
box-shadow: 0 1px 0 rgba(46,86,153,0.15),0 0 4px rgba(86,149,219,0),inset 0 2px 0 rgba(41,102,20,0);
-webkit-transition: box-shadow .15s linear;
-moz-transition: box-shadow .15s linear;
-ms-transition: box-shadow .15s linear;
-o-transition: box-shadow .15s linear;
transition: box-shadow .15s linear;
}
</style></head><body>';*/
                                    \Vcmb\Component\BreezingformsNG\Administrator\Helper\VendorHelper::load();
                                \Stripe\Stripe::setApiKey($options['secretKey']);
                                $stripeemail = strtolower(($this->processor->app->getInput()->get('ff_nm_' . $options['emailfield'], '', 'string')[0] ?? ''));
                                $returnurl = Uri::root() . "index.php?option=com_breezingformsng&confirmStripe=true&form_id=" . $this->processor->form . "&record_id=" . $this->processor->record_id;
                                if (isset($options['emailfield']) && $options['emailfield'] !== '') {
                                    $stripeemail = strtolower(($this->processor->app->getInput()->get('ff_nm_' . $options['emailfield'], '', 'string')[0] ?? ''));
                                    $this->processor->app->getSession()->set('emailfield', $stripeemail);
                                }

                                $productName = $options['itemname'];

                                // ---------------------------------------------------------------------------------------------------------------------------------------------
// XDA : in the Stripe Checkout session, 2 changes :
// 1 - To disable address collection we will either need to pass billing_address_collection parameter with value auto or send API request without it.
//      billing_address_collection' => 'required' -> 'auto'
// ---------------------------------------------------------------------------------------------------------------------------------------------


                                $checkout_session = \Stripe\Checkout\Session::create([
                                    'customer_email' => $stripeemail,
                                    'billing_address_collection' => 'auto',
                                    /*
                                    'line_items' => [[
                                      'price' => 'price_1JYA3UDkYxK6vMJ2QF2S6fNh',
                                      'quantity' => 1,
                                      //'description' => var_dump($stripeemail);
                                    ]],*/
                                    'line_items' => [
                                        [
                                            'price_data' => [
                                                'currency' => strtolower($options['currencyCode']),
                                                'product_data' => [
                                                    'name' => "$productName",
                                                ],
                                                'unit_amount' => $options['amount'],
                                            ],
                                            'quantity' => 1,
                                        ]
                                    ],
                                    'payment_method_types' => [
                                        'card',
                                    ],
                                    'mode' => 'payment',
                                    'success_url' => $returnurl . "&session_id={CHECKOUT_SESSION_ID}",
                                    'cancel_url' => $returnurl . "&session_id={CHECKOUT_SESSION_ID}",
                                ]);

                                $this->processor->app->redirect((string) $checkout_session->url, 303);

                                $current_tag = $this->processor->app->getLanguage()->getTag();
                                $exploded = explode('-', $current_tag);

                                $locale = 'auto';

                                if (in_array(strtolower($exploded[0]), array('zh', 'nl', 'en', 'fr', 'de', 'it', 'ja', 'es'))) {

                                    $locale = strtolower($exploded[0]);
                                }

                                $returnurl = Uri::root() . "index.php?option=com_breezingformsng&confirmStripe=true&form_id=" . $this->processor->form . "&record_id=" . $this->processor->record_id;
                                if (isset($options['emailfield']) && $options['emailfield'] !== '') {
                                    $stripeemail = strtolower(($this->processor->app->getInput()->get('ff_nm_' . $options['emailfield'], '', 'string')[0] ?? ''));
                                    $this->processor->app->getSession()->set('emailfield', $stripeemail);
                                }

                                $html .= '
                        
                        <script src="https://checkout.stripe.com/checkout.js"></script>
        
        <script>
        var submitted_form = false;
        
        var handler = StripeCheckout.configure({
            key: ' . json_encode($options['publishableKey']) . ',
            image: ' . json_encode(Uri::root() . 'media/com_breezingformsng/images/site/icon_card.png') . ',
            locale: ' . json_encode($locale) . ',
            token: function(token) {
            submitted_form = true;
            location.href = ' . json_encode($returnurl) . '+"&token="+token.id
            }
                                                                });
                            
                            var options = {
                                        name: ' . json_encode(isset($head['properties']['title_translation' . $this->processor->app->getLanguage()->getTag()]) ? $head['properties']['title_translation' . $this->processor->app->getLanguage()->getTag()] : $this->processor->formrow->title) . ',
                                        description: ' . json_encode($options['itemname']) . ',
                                        currency: ' . json_encode(strtolower($options['currencyCode'])) . ',
                                        amount: ' . json_encode($options['amount']) . ',
                                        email: ' . json_encode($stripeemail) . ',
									    zipCode : true,
									    billingAddress: true,
									    closed: function () { 
									        if( !submitted_form ){
									        
									            location.href = ' . json_encode(Uri::root()) . '; 
                                                                                }
                                                                            },
                                                                            opened: function(){
                                                                                document.querySelector(".thebutton").style.display = "none";
                                                                            }
                                                                  };
                                                                
								// Close Checkout on page navigation:
								window.addEventListener(\'popstate\', function() {
								  handler.close();
								});
								
								window.onload = function(){
								  handler.open(options);
								};
								</script>
			                	
			                	';

                                if (!$this->processor->inline)
                                    $html .= "</form><div style='margin-top: 25%; text-align: center; width: 100%;'><button class='thebutton' onclick='handler.open(options);'>Click To Pay</button></body></div></html>";

                                echo $html;
                            }
                        }
                        }

                        break;

                    case 'PayPal':

                        foreach ($areas as $area) {

                            foreach ($area['elements'] as $element) {

                                if ($element['internalType'] == 'bfPayPal') {

                                    $options = $element['options'];

                                    $business = $options['business'];
                                    $paypal = 'https://www.paypal.com';

                                    if ($options['testaccount']) {
                                        $paypal = 'https://www.sandbox.paypal.com';
                                        $business = $options['testBusiness'];
                                    }

                                    $returnurl = htmlentities(Uri::root() . "index.php?option=com_breezingformsng&confirmPayPal=true&form_id=" . $this->processor->form . "&record_id=" . $this->processor->record_id);
                                    // $cancelurl = htmlentities(Uri::root() . "index.php?msg=" . Text::_('Transaction Cancelled'));
                                    $cancelurl = $options['cancelURL'];

                                    $html = '';
                                    if (!$this->processor->inline)
                                        $html .= '<html><head><meta charset="UTF-8"></head><body>';

                                    HTMLHelper::_('bootstrap.modal');

                                    $ppselect = $this->processor->app->getInput()->get('ff_nm_bfPaymentSelect', [], 'string');
                                    if (count($ppselect) != 0) {
                                        $ppselected = explode('|', $ppselect[0]);
                                        if (count($ppselected) == 4) {
                                            $options['itemname'] = htmlentities($ppselected[0], ENT_QUOTES, 'UTF-8');
                                            $options['itemnumber'] = htmlentities($ppselected[1], ENT_QUOTES, 'UTF-8');
                                            $options['amount'] = htmlentities($ppselected[2], ENT_QUOTES, 'UTF-8');
                                            $options['tax'] = htmlentities($ppselected[3], ENT_QUOTES, 'UTF-8');
                                        } else if (count($ppselected) == 5) {
                                            $options['itemname'] = htmlentities($ppselected[0], ENT_QUOTES, 'UTF-8');
                                            $options['itemnumber'] = htmlentities($ppselected[1], ENT_QUOTES, 'UTF-8');
                                            $options['amount'] = htmlentities($ppselected[2], ENT_QUOTES, 'UTF-8');
                                            $options['tax'] = htmlentities($ppselected[3], ENT_QUOTES, 'UTF-8');
                                            $options['shipping'] = htmlentities($ppselected[4] == 1 ? 1 : 0, ENT_QUOTES, 'UTF-8');
                                        } else if (count($ppselected) == 6) {
                                            $options['itemname'] = htmlentities($ppselected[0], ENT_QUOTES, 'UTF-8');
                                            $options['itemnumber'] = htmlentities($ppselected[1], ENT_QUOTES, 'UTF-8');
                                            $options['amount'] = htmlentities($ppselected[2], ENT_QUOTES, 'UTF-8');
                                            $options['tax'] = htmlentities($ppselected[3], ENT_QUOTES, 'UTF-8');
                                            $options['shipping'] = htmlentities($ppselected[4] == 1 ? 1 : 0, ENT_QUOTES, 'UTF-8');
                                            $options['currencyCode'] = htmlentities($ppselected[5] == '' ? 'USD' : $ppselected[5], ENT_QUOTES, 'UTF-8');
                                        }
                                    }

                                    // keeping this for compat reasons
                                    $ppselect = $this->processor->app->getInput()->get('ff_nm_PayPalSelect', [], 'string');
                                    if (count($ppselect) != 0) {
                                        $ppselected = explode('|', $ppselect[0]);
                                        if (count($ppselected) == 4) {
                                            $options['itemname'] = htmlentities($ppselected[0], ENT_QUOTES, 'UTF-8');
                                            $options['itemnumber'] = htmlentities($ppselected[1], ENT_QUOTES, 'UTF-8');
                                            $options['amount'] = htmlentities($ppselected[2], ENT_QUOTES, 'UTF-8');
                                            $options['tax'] = htmlentities($ppselected[3], ENT_QUOTES, 'UTF-8');
                                        } else if (count($ppselected) == 5) {
                                            $options['itemname'] = htmlentities($ppselected[0], ENT_QUOTES, 'UTF-8');
                                            $options['itemnumber'] = htmlentities($ppselected[1], ENT_QUOTES, 'UTF-8');
                                            $options['amount'] = htmlentities($ppselected[2], ENT_QUOTES, 'UTF-8');
                                            $options['tax'] = htmlentities($ppselected[3], ENT_QUOTES, 'UTF-8');
                                            $options['shipping'] = htmlentities($ppselected[4] == 1 ? 1 : 0, ENT_QUOTES, 'UTF-8');
                                        } else if (count($ppselected) == 6) {
                                            $options['itemname'] = htmlentities($ppselected[0], ENT_QUOTES, 'UTF-8');
                                            $options['itemnumber'] = htmlentities($ppselected[1], ENT_QUOTES, 'UTF-8');
                                            $options['amount'] = htmlentities($ppselected[2], ENT_QUOTES, 'UTF-8');
                                            $options['tax'] = htmlentities($ppselected[3], ENT_QUOTES, 'UTF-8');
                                            $options['shipping'] = htmlentities($ppselected[4] == 1 ? 1 : 0, ENT_QUOTES, 'UTF-8');
                                            $options['currencyCode'] = htmlentities($ppselected[5] == '' ? 'USD' : $ppselected[5], ENT_QUOTES, 'UTF-8');
                                        }
                                    }
                                    // compat end

                                    $html .= "<form name=\"ff_submitform\" action=\"" . $paypal . "/cgi-bin/webscr\" method=\"post\" accept-charset=\"UTF-8\">";
                                    $html .= "<input type=\"hidden\" name=\"cmd\" value=\"_xclick\"/>";
                                    $html .= "<input type=\"hidden\" name=\"business\" value=\"" . $business . "\"/>";
                                    $html .= "<input type=\"hidden\" name=\"item_name\" value=\"" . $options['itemname'] . "\"/>";
                                    $html .= "<input type=\"hidden\" name=\"item_number\" value=\"" . $options['itemnumber'] . "\"/>";
                                    $html .= "<input type=\"hidden\" name=\"amount\" value=\"" . $options['amount'] . "\"/>";
                                    $html .= "<input type=\"hidden\" name=\"tax\" value=\"" . $options['tax'] . "\"/>";
                                    $html .= "<input type=\"hidden\" name=\"no_shipping\" value=\"" . ($options['shipping'] == 1 ? 0 : 1) . "\"/>";
                                    $html .= "<input type=\"hidden\" name=\"no_note\" value=\"1\"/>";
                                    if ($options['useIpn']) {
                                        $html .= "<input type=\"hidden\" name=\"notify_url\" value=\"" . htmlentities(Uri::root() . "index.php?option=com_breezingformsng&confirmPayPalIpn=true&raw=true&form_id=" . $this->processor->form . "&record_id=" . $this->processor->record_id) . "\"/>";
                                        if ($options['testaccount']) {
                                            $html .= "<input type=\"hidden\" name=\"test_ipn\" value=\"1\"/>";
                                        }

                                        $html .= "<input type=\"hidden\" name=\"return\" value=\"" . $returnurl . "\"/>";
                                    } else {

                                        $html .= "<input type=\"hidden\" name=\"notify_url\" value=\"" . $returnurl . "\"/>";
                                        $html .= "<input type=\"hidden\" name=\"return\" value=\"" . $returnurl . "\"/>";
                                    }

                                    $html .= "<input type=\"hidden\" name=\"cancel_return\" value=\"" . $cancelurl . "\"/>";
                                    $html .= "<input type=\"hidden\" name=\"rm\" value=\"2\"/>";
                                    $html .= "<input type=\"hidden\" name=\"lc\" value=\"" . $options['locale'] . "\"/>";
                                    $html .= "<input type=\"hidden\" name=\"currency_code\" value=\"" . strtoupper($options['currencyCode']) . "\"/>";

                                    if (!$this->processor->inline)
                                        $html .= "</form></body></html>";

                                    $html .= '<script type="text/javascript"><!--' . nl() . 'document.ff_submitform.submit();' . nl() . '//--></script>';
                                    echo $html;

                                    break;
                                }
                            }
                        }

                        break;

                    case 'Sofortueberweisung':

                        foreach ($areas as $area) {
                            foreach ($area['elements'] as $element) {
                                if ($element['internalType'] == 'bfSofortueberweisung') {

                                    $html = '';
                                    if (!$this->processor->inline)
                                        $html .= '<html><head></head><body>';

                                    HTMLHelper::_('bootstrap.modal');

                                    $options = $element['options'];

                                    $ppselect = $this->processor->app->getInput()->get('ff_nm_bfPaymentSelect', [], 'string');
                                    if (count($ppselect) != 0) {
                                        $ppselected = explode('|', $ppselect[0]);
                                        if (count($ppselected) == 4) {
                                            $options['reason_1'] = htmlentities($ppselected[0], ENT_QUOTES, 'UTF-8');
                                            $options['reason_2'] = htmlentities($ppselected[1], ENT_QUOTES, 'UTF-8');
                                            $options['amount'] = htmlentities($ppselected[2], ENT_QUOTES, 'UTF-8');
                                            if ($ppselected[3] != '' && intval($ppselected[3]) > 0) {
                                                $options['amount'] = '' . (doubleval($options['amount']) + doubleval($ppselected[3]));
                                            }
                                        }
                                    }

                                    $options['amount'] = str_replace('.', ',', $options['amount']);

                                    $hash = '';
                                    if (isset($options['project_password']) && trim($options['project_password']) != '') {

                                        $data = array(
                                            $options['user_id'], // user_id
                                            $options['project_id'], // project_id
                                            '', // sender_holder
                                            '', // sender_account_number
                                            '', // sender_bank_code
                                            '', // sender_country_id
                                            $options['amount'], // amount
                                            // currency_id, Pflichtparameter bei Hash-Berechnung
                                            $options['currency_id'],
                                            $options['reason_1'], // reason_1
                                            $options['reason_2'], // reason_2
                                            $this->processor->form, // user_variable_0
                                            $this->processor->record_id, // user_variable_1
                                            (isset($options['mailback']) && $options['mailback'] ? implode('###', $this->processor->mailbackRecipients) : ''), // user_variable_2
                                            '', // user_variable_3
                                            '', // user_variable_4
                                            '', // user_variable_5
                                            $options['project_password']    // project_password
                                        );
                                        $data_implode = implode('|', $data);

                                        $gen = sha1($data_implode);

                                        $hash = '<input type="hidden" name="hash" value="' . $gen . '" />';
                                    }

                                    $mailback = '';
                                    if (isset($options['mailback']) && $options['mailback']) {
                                        $mailback = '<input type="hidden" name="user_variable_2" value="' . implode('###', $this->processor->mailbackRecipients) . '" />';
                                    }

                                    $html .= '
									<!-- sofortüberweisung.de -->
									<form method="post" name="ff_submitform" action="https://www.sofortueberweisung.de/payment/start">
									<input type="hidden" name="user_id" value="' . $options['user_id'] . '" />
									<input type="hidden" name="project_id" value="' . $options['project_id'] . '" />
									<input type="hidden" name="reason_1" value="' . $options['reason_1'] . '" />
									<input type="hidden" name="reason_2" value="' . $options['reason_2'] . '" />
									<input type="hidden" name="amount" value="' . $options['amount'] . '" />
									<input type="hidden" name="currency_id" value="' . $options['currency_id'] . '" />
									<input type="hidden" name="language_id" value="' . $options['language_id'] . '" />
									<input type="hidden" name="user_variable_0" value="' . $this->processor->form . '" />
									<input type="hidden" name="user_variable_1" value="' . $this->processor->record_id . '" />
									' . $mailback . '
									' . $hash . '
									</form>
									<!-- sofortüberweisung.de -->
									';

                                    $html .= '<script type="text/javascript"><!--' . nl() . 'document.ff_submitform.submit();' . nl() . '//--></script>';

                                    if (!$this->processor->inline)
                                        $html .= "</form></body></html>";

                                    echo $html;

                                    break;
                                }
                            }
                        }

                        break;

                    default:
                        $paymentAction = false;
                }
            }
        }

        // CONTENTBUILDER
        if ($this->processor->app->getInput()->get('cb_controller', null, 'string') != 'edit' && $cbRecordId && is_array($cbResult) && isset($cbResult['data']) && isset($cbResult['data']['id']) && $cbResult['data']['id']) {
            $return = $this->processor->app->getInput()->getString('return', '');
            if ($return) {
                $return = bf_b64dec($return);
                if (Uri::isInternal($return)) {
                    $this->processor->app->redirect($return);
                }
            }

            if ($cbResult['data']['force_login']) {
                $is15 = false;

                if (!$this->processor->app->getIdentity()->get('id', 0)) {
                    $this->processor->app->redirect(Route::_('index.php?option=com_users&view=login&Itemid=' . $this->processor->app->getInput()->getInt('Itemid', 0), false));
                } else {

                    $this->processor->app->redirect(Route::_('index.php?option=com_users&view=profile&Itemid=' . $this->processor->app->getInput()->getInt('Itemid', 0), false));
                }
            } else if (trim($cbResult['data']['force_url'])) {
                $this->processor->app->redirect(trim($cbResult['data']['force_url']));
            }

            $this->processor->app->enqueueMessage(Text::_('COM_CONTENTBUILDERNG_SAVED'), 'success');
            $this->processor->app->redirect(Route::_('index.php?option=com_contentbuilderng&task=details.display&Itemid=' . $this->processor->app->getInput()->getInt('Itemid', 0) . '&backtolist=' . $this->processor->app->getInput()->getInt('backtolist', 0) . '&id=' . $cbResult['data']['id'] . '&record_id=' . $cbRecordId . '&limitstart=' . $this->processor->app->getInput()->getInt('limitstart', 0) . '&filter_order=' . $this->processor->app->getInput()->getCmd('filter_order', ''), false));
        }

        if (!$paymentAction) {

            if (defined('CRBCBF_INLINE')) {

                return;
            }

            if (!defined('VMBFCF_RUNNING')) {
                $ob = 0;
                while (ob_get_level() > 0 && $ob <= 32) {
                    ob_end_clean();
                    $ob++;
                }
                ob_start();
                echo '<!DOCTYPE html>
                    <html>
                    <head></head>
                    <body>';
            }

            if ($message == '')
                $message = $this->processor->message;
            else {
                if ($this->processor->message != '')
                    $message .= ":" . nl() . $this->processor->message;
            } // if

            if (!$this->processor->inline) {
                $url = ($this->processor->inframe) ? $ff_mossite . '/index.php?format=html&tmpl=component' : (($this->processor->runmode == _FF_RUNMODE_FRONTEND) ? '' : 'index.php?format=html' . ($this->processor->app->getInput()->getCmd('tmpl', '') ? '&tmpl=' . $this->processor->app->getInput()->getCmd('tmpl', '') : ''));
                echo '<form name="ff_submitform" action="' . $url . '" method="post">' . nl();
            } // if

            switch ($this->processor->runmode) {
                case _FF_RUNMODE_FRONTEND:
                    echo indentc(1) . '<input type="hidden" name="ff_form" value="' . htmlentities((string) $this->processor->form, ENT_QUOTES, 'UTF-8') . '"/>' . nl();
                    if ($this->processor->target > 1)
                        echo indentc(1) . '<input type="hidden" name="ff_target" value="' . htmlentities((string) $this->processor->target, ENT_QUOTES, 'UTF-8') . '"/>' . nl();
                    if ($this->processor->inframe)
                        echo indentc(1) . '<input type="hidden" name="ff_frame" value="1"/>' . nl();
                    if ($this->processor->border)
                        echo indentc(1) . '<input type="hidden" name="ff_border" value="1"/>' . nl();
                    if ($this->processor->page != 1)
                        indentc(1) . '<input type="hidden" name="ff_page" value="' . htmlentities((string) $this->processor->page, ENT_QUOTES, 'UTF-8') . '"/>' . nl();
                    if ($this->processor->align != 1)
                        echo indentc(1) . '<input type="hidden" name="ff_align" value="' . htmlentities((string) $this->processor->align, ENT_QUOTES, 'UTF-8') . '"/>' . nl();
                    if ($this->processor->top != 0)
                        echo indentc(1) . '<input type="hidden" name="ff_top" value="' . htmlentities((string) $this->processor->top, ENT_QUOTES, 'UTF-8') . '"/>' . nl();
                    reset($ff_otherparams);
                    foreach ($ff_otherparams as $prop => $val)
                        echo indentc(1) . '<input type="hidden" name="' . htmlentities((string) $prop, ENT_QUOTES, 'UTF-8') . '" value="' . htmlentities((string) $val, ENT_QUOTES, 'UTF-8') . '"/>' . nl();
                    break;

                case _FF_RUNMODE_BACKEND:
                    echo indentc(1) . '<input type="hidden" name="option" value="com_breezingformsng"/>' . nl() .
                        indentc(1) . '<input type="hidden" name="act" value="run"/>' . nl() .
                        indentc(1) . '<input type="hidden" name="ff_form" value="' . htmlentities((string) $this->processor->form, ENT_QUOTES, 'UTF-8') . '"/>' . nl() .
                        indentc(1) . '<input type="hidden" name="ff_runmode" value="' . htmlentities((string) $this->processor->runmode, ENT_QUOTES, 'UTF-8') . '"/>' . nl();
                    if ($this->processor->target > 1)
                        echo indentc(1) . '<input type="hidden" name="ff_target" value="' . htmlentities((string) $this->processor->target, ENT_QUOTES, 'UTF-8') . '"/>' . nl();
                    if ($this->processor->inframe)
                        echo indentc(1) . '<input type="hidden" name="ff_frame" value="1"/>' . nl();
                    if ($this->processor->border)
                        echo indentc(1) . '<input type="hidden" name="ff_border" value="1"/>' . nl();
                    if ($this->processor->page != 1)
                        indentc(1) . '<input type="hidden" name="ff_page" value="' . htmlentities((string) $this->processor->page, ENT_QUOTES, 'UTF-8') . '"/>' . nl();
                    if ($this->processor->align != 1)
                        echo indentc(1) . '<input type="hidden" name="ff_align" value="' . htmlentities((string) $this->processor->align, ENT_QUOTES, 'UTF-8') . '"/>' . nl();
                    if ($this->processor->top != 0)
                        echo indentc(1) . '<input type="hidden" name="ff_top" value="' . htmlentities((string) $this->processor->top, ENT_QUOTES, 'UTF-8') . '"/>' . nl();
                    break;

                default: // _FF_RUNMODE_PREVIEW:
                    if ($this->processor->inframe) {
                        echo indentc(1) . '<input type="hidden" name="option" value="com_breezingformsng"/>' . nl() .
                            indentc(1) . '<input type="hidden" name="ff_frame" value="1"/>' . nl() .
                            indentc(1) . '<input type="hidden" name="ff_form" value="' . htmlentities((string) $this->processor->form, ENT_QUOTES, 'UTF-8') . '"/>' . nl() .
                            indentc(1) . '<input type="hidden" name="ff_runmode" value="' . htmlentities((string) $this->processor->runmode, ENT_QUOTES, 'UTF-8') . '"/>' . nl();
                        if ($this->processor->page != 1)
                            indentc(1) . '<input type="hidden" name="ff_page" value="' . htmlentities((string) $this->processor->page, ENT_QUOTES, 'UTF-8') . '"/>' . nl();
                    } // if
            } // if

            echo indentc(1) . '<input type="hidden" name="ff_contentid" value="' . $this->processor->app->getInput()->getInt('ff_contentid', 0) . '"/>' . nl() .
                indentc(1) . '<input type="hidden" name="ff_applic" value="' . $this->processor->app->getInput()->getWord('ff_applic', '') . '"/>' . nl() .
                indentc(1) . '<input type="hidden" name="ff_record_id" value="' . $this->processor->record_id . '"/>' . nl() .
                indentc(1) . '<input type="hidden" name="ff_module_id" value="' . $this->processor->app->getInput()->getInt('ff_module_id', 0) . '"/>' . nl() .
                indentc(1) . '<input type="hidden" name="ff_status" value="' . htmlentities((string) $this->processor->status, ENT_QUOTES, 'UTF-8') . '"/>' . nl() .
                indentc(1) . '<input type="hidden" name="ff_message" value="' . htmlentities((string) $message, ENT_QUOTES, 'UTF-8') . '"/>' . nl() .
                indentc(1) . '<input type="hidden" name="ff_form_submitted" value="1"/>' . nl();

            if ($this->processor->app->getInput()->getString('tmpl', '') == 'component') {
                echo indentc(1) . '<input type="hidden" name="tmpl" value="component"/>' . nl();
            }
            if ($this->processor->app->getInput()->getInt('cb_form_id', 0)) {
                echo indentc(1) . '<input type="hidden" name="cb_form_id" value="' . $this->processor->app->getInput()->getInt('cb_form_id', 0) . '"/>' . nl();
                if ($this->processor->app->getInput()->getInt('cb_record_id', 0)) {
                    echo indentc(1) . '<input type="hidden" name="cb_record_id" value="' . $this->processor->app->getInput()->getInt('cb_record_id', 0) . '"/>' . nl();
                }
                if ($this->processor->app->getInput()->getBool('cbIsNew', false)) {
                    echo indentc(1) . '<input type="hidden" name="cbIsNew" value="1"/>' . nl();
                }
            }
            if ($this->processor->app->getInput()->getString('return', '') !== '') {
                echo indentc(1) . '<input type="hidden" name="return" value="' . htmlentities($this->processor->app->getInput()->getString('return', ''), ENT_QUOTES, 'UTF-8') . '"/>' . nl();
            }
            // TODO: turn off tracing in the options
            if ($this->processor->traceMode & _FF_TRACEMODE_DIRECT) {
                $this->processor->dumpTrace();
                ob_end_flush();
                echo '</pre>';
            } else {

                ob_end_flush();
                $this->processor->dumpTrace();
            } // if
            restore_error_handler();

            if (!$this->processor->inline) {
                echo '</form>' . nl() .
                    '<script type="text/javascript">' . nl() .
                    indentc(1) . '<!--' . nl() .
                    indentc(2) . 'document.ff_submitform.submit();' . nl() .
                    indentc(1) . '// -->' . nl() .
                    '</script>' . nl();
            } // if

            if (!defined('VMBFCF_RUNNING')) {
                $c = ob_get_contents();
                ob_end_clean();
                echo $c;

                echo '</body>
                      </html>';
            }
        }

        $this->processor->app->getSession()->clear('ff_editable_overridePlg' . $this->processor->app->getInput()->getInt('ff_contentid', 0) . $this->processor->form_id);
        $this->processor->app->getSession()->clear('ff_editablePlg' . $this->processor->app->getInput()->getInt('ff_contentid', 0) . $this->processor->form_id);
        $this->processor->app->getSession()->set('ff_editableMod' . $this->processor->app->getInput()->getInt('ff_module_id', 0) . $this->processor->form_id, 0);
        $this->processor->app->getSession()->set('ff_editable_overrideMod' . $this->processor->app->getInput()->getInt('ff_module_id', 0) . $this->processor->form_id, 0);

        if (!defined('VMBFCF_RUNNING')) {
            exit;
        }
    }

    private function uploadFileToDropbox(string $localFile): void
    {
        $folder = trim((string) ($this->processor->formrow->dropbox_folder ?: $this->processor->formrow->name), '/');
        $remotePath = '/' . ($folder !== '' ? $folder . '/' : '') . basename($localFile);

        (new DropboxUploader())->upload(
            trim((string) $this->processor->formrow->dropbox_email),
            $remotePath,
            $localFile
        );
    }

    private function uploadTimestampFormatter(): SubmissionTimestampFormatter
    {
        return $this->uploadTimestampFormatterService ??= new SubmissionTimestampFormatter();
    }

    function removeDangerousHtml($value)
    {
        return $this->htmlSanitizer()->sanitize((string) $value);
    }

    private function uploadRuntime(): UploadRuntime
    {
        return $this->uploadRuntimeService ??= new UploadRuntime($this->processor->app->getInput());
    }

    private function htmlSanitizer(): HtmlSanitizer
    {
        return $this->htmlSanitizerService ??= new HtmlSanitizer();
    }

    // submit
}
