<?php

/**
 * BreezingForms NG - A Joomla Forms Application
 * 
 * @version 6.0.0
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2008-2020 by Markus Bopp
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 * */
defined('_JEXEC') or die('Direct Access to this location is not allowed.');

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Mail\MailerFactoryInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\Environment\Browser;
use Vcmb\Component\BreezingformsNG\Site\Service\Runtime\CodeToolsRuntime;
use Vcmb\Component\BreezingformsNG\Site\Service\Runtime\ErrorHandlerRuntime;
use Vcmb\Component\BreezingformsNG\Site\Service\Runtime\FormElementLoader;
use Vcmb\Component\BreezingformsNG\Site\Service\Runtime\FormDisplayContextResolver;
use Vcmb\Component\BreezingformsNG\Site\Service\Runtime\FormPathResolver;
use Vcmb\Component\BreezingformsNG\Site\Service\Runtime\ProcessorRuntimeContextInitializer;
use Vcmb\Component\BreezingformsNG\Site\Service\Runtime\RequestMetadataResolver;
use Vcmb\Component\BreezingformsNG\Site\Service\Runtime\SubmissionTimestampFactory;
use Vcmb\Component\BreezingformsNG\Site\Service\Runtime\TraceRuntime;
use Vcmb\Component\BreezingformsNG\Site\Service\Scripting\ScriptingEngine;
use Vcmb\Component\BreezingformsNG\Site\Service\Export\ExportEngine;
use Vcmb\Component\BreezingformsNG\Site\Service\Integration\RecaptchaVerifier;
use Vcmb\Component\BreezingformsNG\Site\Service\Notification\NotificationEngine;
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\RenderingEngine;
use Vcmb\Component\BreezingformsNG\Site\Service\Submission\SubmissionEngine;
use Vcmb\Component\BreezingformsNG\Site\Service\Upload\UploadRuntime;
use Vcmb\Component\BreezingformsNG\Site\Table\FormTable;
use Vcmb\Component\BreezingformsNG\Site\Service\Integration\ContentBuilderInstallation;

$ff_processor = null;

define('_FF_PACKBREAKAFTER', 250);

define('_FF_STATUS_OK', 0);
define('_FF_STATUS_UNPUBLISHED', 1);
define('_FF_STATUS_SAVERECORD_FAILED', 2);
define('_FF_STATUS_SAVESUBRECORD_FAILED', 3);
define('_FF_STATUS_UPLOAD_FAILED', 4);
define('_FF_STATUS_SENDMAIL_FAILED', 5);
define('_FF_STATUS_ATTACHMENT_FAILED', 6);
define('_FF_STATUS_CAPTCHA_FAILED', 7);
define('_FF_STATUS_FILE_EXTENSION_NOT_ALLOWED', 8);
define('_FF_STATUS_SALESFORCE_SOAP_ERROR', 9);

define('_FF_DATA_ID', 0);
define('_FF_DATA_NAME', 1);
define('_FF_DATA_TITLE', 2);
define('_FF_DATA_TYPE', 3);
define('_FF_DATA_VALUE', 4);
define('_FF_DATA_FILE_SERVERPATH', 5);

define('_FF_IGNORE_STRICT', 1);
define('_FF_TRACE_NAMELIMIT', 100);

// tracemode bits
define('_FF_TRACEMODE_EVAL', 8);
define('_FF_TRACEMODE_PIECE', 16);
define('_FF_TRACEMODE_FUNCTION', 32);
define('_FF_TRACEMODE_MESSAGE', 64);
define('_FF_TRACEMODE_LOCAL', 128);
define('_FF_TRACEMODE_DIRECT', 256);
define('_FF_TRACEMODE_APPEND', 512);
define('_FF_TRACEMODE_DISABLE', 1024);
define('_FF_TRACEMODE_FIRST', 2048);

// tracemode masks
define('_FF_TRACEMODE_PRIORITY', 7);
define('_FF_TRACEMODE_TOPIC', 120);
define('_FF_TRACEMODE_VARIABLE', 248);

$cbngBasePath = JPATH_ADMINISTRATOR . '/components/com_contentbuilderng';
if (ContentBuilderInstallation::isInstalled($cbngBasePath)) {
    require_once $cbngBasePath . '/src/Helper/ContentbuilderngHelper.php';
    require_once $cbngBasePath . '/src/Helper/FormSourceFactory.php';
    require_once $cbngBasePath . '/src/Service/ArticleService.php';
    require_once $cbngBasePath . '/src/Service/ListSupportService.php';
    require_once $cbngBasePath . '/src/Service/PermissionService.php';
}

function ff_trace($msg = null)
{
    global $ff_processor;
    if (!$ff_processor instanceof HTML_facileFormsProcessor) {
        return;
    }

    (new TraceRuntime($ff_processor))->trace($msg);
}

// ff_trace

function _ff_trace($line, $msg = null)
{
    global $ff_processor;
    if (!$ff_processor instanceof HTML_facileFormsProcessor) {
        return;
    }

    (new TraceRuntime($ff_processor))->traceLine($line, $msg);
}

// _ff_trace

function _ff_getMode(&$newmode, &$name)
{
    global $ff_processor;
    if (!$ff_processor instanceof HTML_facileFormsProcessor) {
        return null;
    }

    return (new TraceRuntime($ff_processor))->getMode($newmode, $name);
}

// _ff_getmode

function _ff_tracePiece($newmode, $name, $line, $type, $id, $pane)
{
    global $ff_processor;
    if (!$ff_processor instanceof HTML_facileFormsProcessor) {
        return;
    }

    (new TraceRuntime($ff_processor))->tracePiece($newmode, $name, $line, $type, $id, $pane);
}

// _ff_tracePiece

function _ff_traceFunction($newmode, $name, $line, $type, $id, $pane, &$args)
{
    global $ff_processor;
    if (!$ff_processor instanceof HTML_facileFormsProcessor) {
        return;
    }

    (new TraceRuntime($ff_processor))->traceFunction($newmode, $name, $line, $type, $id, $pane, $args);
}

// _ff_traceFunction

function _ff_traceExit($line, $retval = null)
{
    global $ff_processor;
    if (!$ff_processor instanceof HTML_facileFormsProcessor) {
        return $retval;
    }

    return (new TraceRuntime($ff_processor))->traceExit($line, $retval);
}

// _ff_traceExit

function _ff_errorHandler($errno, $errstr, $errfile, $errline)
{
    global $ff_processor, $ff_mossite;
    if (!$ff_processor instanceof HTML_facileFormsProcessor) {
        return;
    }

    (new ErrorHandlerRuntime($ff_processor, (string) $ff_mossite))->handle(
        (int) $errno,
        (string) $errstr,
        (string) $errfile,
        (int) $errline
    );
}

// _ff_errorHandler

class HTML_facileFormsProcessor
{
    public $okrun = null;     // running is allowed
    public $ip = null;     // visitor ip
    public $agent = null;     // visitor agent
    public $browser = null;     // visitors browser
    public $opsys = null;     // visitors operating system
    public $provider = null;     // visitors provider
    public $submitted = null;     // submit date/time
    public $formrow = null;     // form row
    public $form = null;     // form #
    public $form_id = null;     // html form id
    public $page = null;     // page id
    public $target = null;     // target form name
    public $rows = null;     // element rows
    public $rowcount = null;     // # of element rows
    public $runmode = null;     // current run mode _FF_RUNMODE_...
    public $inline = null;     // inline preview
    public $inframe = null;     // running in a frame
    public $template = null;     // 0-frontend 1-backend
    public $homepage = null;     // home page
    public $mospath = null;     // mos absolute path
    public $mossite = null;     // mos site
    public $images = null;     // ff_images path
    public $uploads = null;     // ff_uploads path
    public $border = null;     // show border
    public $align = null;     // form alignment
    public $top = null;     // top margin
    public $suffix = null;     // class name suffix
    public $status = null;     // submit return status
    public $message = null;     // submit return message
    public $record_id = null;     // id of saved record
    public $submitdata = null;     // submitted data
    public $savedata = null;     // data for db save
    public $maildata = null;     // data for mail notification
    public $sfdata = null;
    public $xmldata = null;     // data for xml attachment
    public $mb_xmldata = null;     // data for mailback attachments
    public $queryCols = null;     // query column definitions
    public $queryRows = null;     // query rows
    public $showgrid = null;     // show grid in preview
    public $findtags = null;     // tags to be replaced
    public $replacetags = null;     // tag replacements
    public $dying = null;     // form is dying
    public $errrep = null;     // remember old error reporting
    public $traceMode = null;     // trace mode
    public $traceStack = null;     // trace stack
    public $traceBuffer = null;     // trace buffer
    public $user_id = null;
    public $username = null;
    public $user_full_name = null;
    public $mailbackRecipients = array();
    public $editable = null;
    public $editable_override = null;
    public $sendNotificationAfterPayment = false;
    public $opt_token = "9562384751";
    public $draggableDivIds = array();
    public $quickmode = null;
    public $app;
    public $database;
    private ?UploadRuntime $uploadRuntimeService = null;
    private ?CodeToolsRuntime $codeToolsRuntimeService = null;
    private ?TraceRuntime $traceRuntimeService = null;
    private ?ScriptingEngine $scriptingEngineService = null;
    private ?ExportEngine $exportEngineService = null;
    private ?NotificationEngine $notificationEngineService = null;
    private ?RenderingEngine $renderingEngineService = null;
    private ?SubmissionEngine $submissionEngineService = null;
    private MailerFactoryInterface $mailerFactory;
    private CacheControllerFactoryInterface $cacheControllerFactory;

    public function header()
    {
        return $this->renderingEngine()->header();
    }

    public function cbCreatePathByTokens($path, array $rows, $fieldName)
    {
        return $this->renderingEngine()->cbCreatePathByTokens($path, $rows, $fieldName);
    }

    public function makeSafeFolder($path)
    {
        return $this->renderingEngine()->makeSafeFolder($path);
    }

    /** @phpstan-impure */
    public function cbCheckPermissions()
    {
        return $this->renderingEngine()->cbCheckPermissions();
    }

    public function view(): void
    {
        $this->renderingEngine()->view();
    }

    private function renderingEngine(): RenderingEngine
    {
        return $this->renderingEngineService ??= new RenderingEngine($this);
    }

    /** @phpstan-impure */
    public function collectSubmitdata($cbResult = null)
    {
        return $this->submissionEngine()->collectSubmitdata($cbResult);
    }

    public function submit()
    {
        return $this->submissionEngine()->submit();
    }

    public function removeDangerousHtml($value)
    {
        return $this->submissionEngine()->removeDangerousHtml($value);
    }

    private function submissionEngine(): SubmissionEngine
    {
        return $this->submissionEngineService ??= new SubmissionEngine(
            $this,
            $this->mailerFactory,
            new RecaptchaVerifier(),
            $this->scriptingEngine(),
            $this->exportEngine(),
            $this->notificationEngine()
        );
    }

    public function sendEmailNotification()
    {
        return $this->notificationEngine()->sendEmailNotification();
    }

    public function getFormTitleTranslated()
    {
        return $this->notificationEngine()->getFormTitleTranslated();
    }

    public function getFieldTranslated($field, $name, &$res, $dataObject = null, $childrenLength = 0)
    {
        return $this->notificationEngine()->getFieldTranslated(
            $field,
            $name,
            $res,
            $dataObject,
            $childrenLength
        );
    }

    public function sendMailbackNotification()
    {
        return $this->notificationEngine()->sendMailbackNotification();
    }

    public function sendSalesforceNotification()
    {
        return $this->notificationEngine()->sendSalesforceNotification();
    }

    public function sendMailChimpNotification()
    {
        return $this->notificationEngine()->sendMailChimpNotification();
    }

    private function notificationEngine(): NotificationEngine
    {
        return $this->notificationEngineService ??= new NotificationEngine($this, $this->exportEngine());
    }

    /** @phpstan-impure */
    public function logToDatabase($cbResult = null)
    {
        return $this->exportEngine()->logToDatabase($cbResult);
    }

    public function random_str(
        int $length,
        string $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'
    ): string
    {
        return $this->exportEngine()->random_str($length, $keyspace);
    }

    public function sendMail($from, $fromname, $recipient, $subject, $body, $attachment = null, $html = null, $cc = null, $bcc = null, $alt_sender = '')
    {
        return $this->exportEngine()->sendMail(
            $from,
            $fromname,
            $recipient,
            $subject,
            $body,
            $attachment,
            $html,
            $cc,
            $bcc,
            $alt_sender
        );
    }

    public function endsWith(string $haystack, string $needle): bool
    {
        return $this->exportEngine()->endsWith($haystack, $needle);
    }

    public function exppdf($filter = [], $mailback = false, $translate = true)
    {
        return $this->exportEngine()->exppdf($filter, $mailback, $translate);
    }

    public function expcsv($filter = [], $mailback = false)
    {
        return $this->exportEngine()->expcsv($filter, $mailback);
    }

    public function expxml($filter = [], $mailback = false, $translate = false)
    {
        return $this->exportEngine()->expxml($filter, $mailback, $translate);
    }

    private function exportEngine(): ExportEngine
    {
        return $this->exportEngineService ??= new ExportEngine(
            $this,
            $this->mailerFactory,
            $this->cacheControllerFactory
        );
    }

    public function getPieceById($id, $name = null)
    {
        return $this->scriptingEngine()->getPieceById($id, $name);
    }

    public function getPieceByName($name, $id = null)
    {
        return $this->scriptingEngine()->getPieceByName($name, $id);
    }

    /** @phpstan-impure */
    public function execPiece($code, $name, $type, $id, $pane)
    {
        return $this->scriptingEngine()->execPiece($code, $name, $type, $id, $pane);
    }

    public function execPieceById($id)
    {
        return $this->scriptingEngine()->execPieceById($id);
    }

    public function execPieceByName($name)
    {
        return $this->scriptingEngine()->execPieceByName($name);
    }

    public function replaceCode($code, $name, $type, $id, $pane)
    {
        return $this->scriptingEngine()->replaceCode($code, $name, $type, $id, $pane);
    }

    public function compileQueryCol(&$elem, &$coldef)
    {
        return $this->scriptingEngine()->compileQueryCol($elem, $coldef);
    }

    public function execQueryValue($code, &$elem, &$row, &$coldef, $value)
    {
        return $this->scriptingEngine()->execQueryValue($code, $elem, $row, $coldef, $value);
    }

    public function execQuery(&$elem, &$valrows, &$coldefs)
    {
        return $this->scriptingEngine()->execQuery($elem, $valrows, $coldefs);
    }

    public function script2clause(&$row)
    {
        return $this->scriptingEngine()->script2clause($row);
    }

    public function loadBuiltins(&$library)
    {
        return $this->scriptingEngine()->loadBuiltins($library);
    }

    public function loadScripts(&$library)
    {
        return $this->scriptingEngine()->loadScripts($library);
    }

    public function compressJavascript($str)
    {
        return $this->scriptingEngine()->compressJavascript($str);
    }

    /** @phpstan-impure */
    public function linkcode($func, &$library, &$linked, $code, $type = null, $id = null, $pane = null)
    {
        return $this->scriptingEngine()->linkcode($func, $library, $linked, $code, $type, $id, $pane);
    }

    public function addFunction($cond, $id, $name, $code, &$library, &$linked, $type, $rowid, $pane)
    {
        return $this->scriptingEngine()->addFunction(
            $cond,
            $id,
            $name,
            $code,
            $library,
            $linked,
            $type,
            $rowid,
            $pane
        );
    }

    private function scriptingEngine(): ScriptingEngine
    {
        return $this->scriptingEngineService ??= new ScriptingEngine($this);
    }

    public function dispTraceMode($mode)
    {
        return $this->codeToolsRuntime()->dispTraceMode($mode);
    }

    /** @phpstan-impure */
    public function trim(&$code)
    {
        return $this->codeToolsRuntime()->trim($code);
    }

    /** @phpstan-impure */
    public function nonblank(&$code)
    {
        return $this->codeToolsRuntime()->nonblank($code);
    }

    public function getClassName($classdef)
    {
        return $this->codeToolsRuntime()->getClassName($classdef);
    }

    public function expJsValue($mixed, $indent = '')
    {
        return $this->codeToolsRuntime()->expJsValue($mixed, $indent);
    }

    public function expJsVar($name, $mixed)
    {
        return $this->codeToolsRuntime()->expJsVar($name, $mixed);
    }

    public function dumpTrace()
    {
        $this->traceRuntime()->dumpTrace();
    }

    public function traceEval($name)
    {
        $this->traceRuntime()->traceEval($name);
    }

    public function suicide()
    {
        return $this->traceRuntime()->suicide();
    }

    /** @phpstan-impure */
    public function bury()
    {
        return $this->traceRuntime()->bury();
    }

    public function findToken(&$code, &$spos, &$offs)
    {
        return $this->codeToolsRuntime()->findToken($code, $spos, $offs);
    }

    public function findRealToken(&$code, &$spos, &$offs, &$line)
    {
        return $this->codeToolsRuntime()->findRealToken($code, $spos, $offs, $line);
    }

    public function patchCode($mode, $code, $name, $type, $id, $pane)
    {
        return $this->codeToolsRuntime()->patchCode($mode, $code, $name, $type, $id, $pane);
    }

    public function prepareEvalCode(&$code, $name, $type, $id, $pane)
    {
        return $this->codeToolsRuntime()->prepareEvalCode($code, $name, $type, $id, $pane);
    }

    private function codeToolsRuntime(): CodeToolsRuntime
    {
        return $this->codeToolsRuntimeService ??= new CodeToolsRuntime($this);
    }

    private function traceRuntime(): TraceRuntime
    {
        return $this->traceRuntimeService ??= new TraceRuntime($this);
    }

    /**
     * Historical upload facade retained for custom PHP stored with forms.
     */
    public function saveUpload($filename, $userfile_name, $destpath, $timestamp, $useUrl = false, $useUrlDownloadDirectory = '', $resize_target_width = 0, $resize_target_height = 0, $resize_type = '', $resize_bgcolor = '#ffffff', $field_name = '')
    {
        return $this->submissionEngine()->saveUpload(
            $filename,
            $userfile_name,
            $destpath,
            $timestamp,
            $useUrl,
            $useUrlDownloadDirectory,
            $resize_target_width,
            $resize_target_height,
            $resize_type,
            $resize_bgcolor,
            $field_name
        );
    }

    public function exifImageType($filename)
    {
        return $this->uploadRuntime()->imageType((string) $filename);
    }

    public function resizeFile($path, $width, $height, $bgcolor = '#ffffff', $type = '')
    {
        $this->uploadRuntime()->resizeFile(
            (string) $path,
            (int) $width,
            (int) $height,
            $bgcolor === null ? null : (string) $bgcolor,
            (string) $type
        );
    }

    public function resize_image($source_image, $destination_width, $destination_height, $type = 0, $bgcolor = [0, 0, 0])
    {
        return $this->uploadRuntime()->resizeImage(
            $source_image,
            (int) $destination_width,
            (int) $destination_height,
            (int) $type,
            (array) $bgcolor
        );
    }

    public function returnBytes($val)
    {
        return $this->uploadRuntime()->parseByteSize((string) $val);
    }

    public function findQuickModeElement(array $dataObject, $needle)
    {
        return $this->uploadRuntime()->findQuickModeElement($dataObject, (string) $needle);
    }

    public function measureTime()
    {
        return $this->submissionEngine()->measureTime();
    }

    private function uploadRuntime(): UploadRuntime
    {
        return $this->uploadRuntimeService ??= new UploadRuntime($this->app->getInput());
    }

    function __construct(
        CMSApplication $application,
        DatabaseInterface $database,
        MailerFactoryInterface $mailerFactory,
        CacheControllerFactoryInterface $cacheControllerFactory,
        $runmode, // _FF_RUNMODE_FRONTEND, ..._BACKEND, ..._PREVIEW
        $inframe, // run in iframe
        $form, // form id
        $page = 1, // page #
        $border = 0, // show border
        $align = 1, // align code
        $top = 0, // top margin
        $target = '', // target form name
        $suffix = '', // class name suffix
        $editable = 0,
        $editable_override = 0
    ) {
        global $ff_config, $ff_mossite, $ff_mospath, $ff_processor;
        $ff_processor = $this;
        $this->database = $database;
        $this->mailerFactory = $mailerFactory;
        $this->cacheControllerFactory = $cacheControllerFactory;
        $this->dying = false;
        $this->runmode = $runmode;
        $this->inframe = $inframe;
        $this->form = $form;
        $this->page = $page;
        $this->border = $border;
        $this->align = $align;
        $this->top = $top;
        $this->target = $target;
        $this->suffix = trim($suffix);
        $this->editable = $editable;
        $this->editable_override = $editable_override;
        $this->app = $application;

        $this->formrow = new FormTable($this->database);
        $this->formrow->load($form);

        if ($this->formrow->published) {
            $formId = (int) $this->form;
            $this->rows = (new FormElementLoader($this->database))->loadPublished($formId);
            $this->rowcount = count($this->rows);
        } // if
        $this->mospath = $ff_mospath;
        $this->mossite = $ff_mossite;
        $runtimeContext = (new ProcessorRuntimeContextInitializer(
            new RequestMetadataResolver(Browser::getInstance()),
            new FormDisplayContextResolver(),
            new FormPathResolver(),
            new SubmissionTimestampFactory()
        ))->initialize(
            $this->app,
            $ff_config,
            $this->formrow,
            (int) $runmode,
            (bool) $this->inframe,
            (int) $form,
            (int) $this->page,
            (string) $this->mospath,
            (string) $this->mossite
        );
        $this->ip = $runtimeContext->requestMetadata->ip;
        $this->agent = $runtimeContext->requestMetadata->agent;
        $this->browser = $runtimeContext->requestMetadata->browser;
        $this->opsys = $runtimeContext->requestMetadata->platform;
        $this->provider = $runtimeContext->requestMetadata->provider;
        $this->submitted = $runtimeContext->submitted;
        $this->inline = $runtimeContext->display->inline;
        $this->template = $runtimeContext->display->template;
        $this->form_id = $runtimeContext->display->formId;
        $this->homepage = $runtimeContext->display->homepage;
        $this->findtags = $runtimeContext->paths->tokens;
        $this->replacetags = $runtimeContext->paths->values;
        $this->images = $runtimeContext->paths->images;
        $this->uploads = $runtimeContext->paths->uploads;
        $this->showgrid = $runtimeContext->display->showGrid;
        $this->okrun = $runtimeContext->display->canRun;
        $this->traceMode = _FF_TRACEMODE_FIRST;
        $this->traceStack = array();
        $this->traceBuffer = null;
    }

    //  HTML_facileFormsProcessor

}
