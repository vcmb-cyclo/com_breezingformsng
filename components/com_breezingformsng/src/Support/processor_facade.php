<?php

/**
 * BreezingForms NG - A Joomla Forms Application
 *
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

/**
 * @param mixed $msg
 * @return void
 */
function ff_trace($msg = null)
{
    global $ff_processor;
    if (!$ff_processor instanceof HTML_facileFormsProcessor) {
        return;
    }

    (new TraceRuntime($ff_processor))->trace($msg);
}

// ff_trace

/**
 * @param int $line
 * @param mixed $msg
 * @return void
 */
function _ff_trace($line, $msg = null)
{
    global $ff_processor;
    if (!$ff_processor instanceof HTML_facileFormsProcessor) {
        return;
    }

    (new TraceRuntime($ff_processor))->traceLine($line, $msg);
}

// _ff_trace

/**
 * @param int|null $newmode
 * @param string $name
 * @return int|null
 */
function _ff_getMode(&$newmode, &$name)
{
    global $ff_processor;
    if (!$ff_processor instanceof HTML_facileFormsProcessor) {
        return null;
    }

    return (new TraceRuntime($ff_processor))->getMode($newmode, $name);
}

// _ff_getmode

/**
 * @param int|null $newmode
 * @param string $name
 * @param int $line
 * @param string|null $type
 * @param int|string|null $id
 * @param int|null $pane
 * @return void
 */
function _ff_tracePiece($newmode, $name, $line, $type, $id, $pane)
{
    global $ff_processor;
    if (!$ff_processor instanceof HTML_facileFormsProcessor) {
        return;
    }

    (new TraceRuntime($ff_processor))->tracePiece($newmode, $name, $line, $type, $id, $pane);
}

// _ff_tracePiece

/**
 * @param int|null $newmode
 * @param string $name
 * @param int $line
 * @param string|null $type
 * @param int|string|null $id
 * @param int|null $pane
 * @param array<int, mixed>|null $args
 * @return void
 */
function _ff_traceFunction($newmode, $name, $line, $type, $id, $pane, &$args)
{
    global $ff_processor;
    if (!$ff_processor instanceof HTML_facileFormsProcessor) {
        return;
    }

    (new TraceRuntime($ff_processor))->traceFunction($newmode, $name, $line, $type, $id, $pane, $args);
}

// _ff_traceFunction

/**
 * @param int $line
 * @param mixed $retval
 * @return mixed
 */
function _ff_traceExit($line, $retval = null)
{
    global $ff_processor;
    if (!$ff_processor instanceof HTML_facileFormsProcessor) {
        return $retval;
    }

    return (new TraceRuntime($ff_processor))->traceExit($line, $retval);
}

// _ff_traceExit

/**
 * @param int $errno
 * @param string $errstr
 * @param string $errfile
 * @param int $errline
 * @return bool Always true: the error is handled here, as it was when the
 *              handler returned nothing.
 */
function _ff_errorHandler($errno, $errstr, $errfile, $errline)
{
    global $ff_processor, $ff_mossite;
    if (!$ff_processor instanceof HTML_facileFormsProcessor) {
        return true;
    }

    (new ErrorHandlerRuntime($ff_processor, (string) $ff_mossite))->handle(
        (int) $errno,
        (string) $errstr,
        (string) $errfile,
        (int) $errline
    );

    return true;
}

// _ff_errorHandler

class HTML_facileFormsProcessor
{
    /** @var bool|null */
    public $okrun = null;     // running is allowed
    /** @var string|null */
    public $ip = null;     // visitor ip
    /** @var string|null */
    public $agent = null;     // visitor agent
    /** @var string|null */
    public $browser = null;     // visitors browser
    /** @var string|null */
    public $opsys = null;     // visitors operating system
    /** @var string|null */
    public $provider = null;     // visitors provider
    /** @var string|null */
    public $submitted = null;     // submit date/time
    /** @var FormTable Loaded first thing in the constructor. */
    public $formrow;     // form row
    /** @var int|string|null */
    public $form = null;     // form #
    /** @var string|null */
    public $form_id = null;     // html form id
    /** @var int|null */
    public $page = null;     // page id
    /** @var int|string|null */
    public $target = null;     // target form name
    /** @var list<\stdClass>|null */
    public $rows = null;     // element rows
    /** @var int|null */
    public $rowcount = null;     // # of element rows
    /** @var int|string|null */
    public $runmode = null;     // current run mode _FF_RUNMODE_...
    /** @var int|null */
    public $inline = null;     // inline preview
    /** @var int|null */
    public $inframe = null;     // running in a frame
    /** @var int|null */
    public $template = null;     // 0-frontend 1-backend
    /** @var string|null */
    public $homepage = null;     // home page
    /** @var string|null */
    public $mospath = null;     // mos absolute path
    /** @var string|null */
    public $mossite = null;     // mos site
    /** @var string|null */
    public $images = null;     // ff_images path
    /** @var string|null */
    public $uploads = null;     // ff_uploads path
    /** @var int|null */
    public $border = null;     // show border
    /** @var int|null */
    public $align = null;     // form alignment
    /** @var int|null */
    public $top = null;     // top margin
    /** @var string|null */
    public $suffix = null;     // class name suffix
    /** @var int|string|null */
    public $status = null;     // submit return status
    /** @var string|null */
    public $message = null;     // submit return message
    /** @var int|string|null */
    public $record_id = null;     // id of saved record
    /** @var list<array<int, mixed>>|null */
    public $submitdata = null;     // submitted data
    /** @var array<int, array<int, mixed>>|null */
    public $savedata = null;     // data for db save
    /** @var array<int, array<int, mixed>>|null */
    public $maildata = null;     // data for mail notification
    /** @var array<int, array<int, mixed>>|null */
    public $sfdata = null;
    /** @var array<int, array<int, mixed>>|null */
    public $xmldata = null;     // data for xml attachment
    /** @var array<int, array<int, mixed>>|null */
    public $mb_xmldata = null;     // data for mailback attachments
    /** @var array<mixed>|null */
    public $queryCols = null;     // query column definitions
    /** @var array<mixed>|null */
    public $queryRows = null;     // query rows
    /** @var bool|null */
    public $showgrid = null;     // show grid in preview
    /** @var list<string>|null */
    public $findtags = null;     // tags to be replaced
    /** @var list<string>|null */
    public $replacetags = null;     // tag replacements
    /** @var bool|null */
    public $dying = null;     // form is dying
    /** @var int|null */
    public $errrep = null;     // remember old error reporting
    /** @var int|null */
    public $traceMode = null;     // trace mode
    /** @var array<int, array<int, mixed>>|null */
    public $traceStack = null;     // trace stack
    /** @var string|null */
    public $traceBuffer = null;     // trace buffer
    /** @var mixed */
    public $user_id = null;
    /** @var mixed */
    public $username = null;
    /** @var mixed */
    public $user_full_name = null;
    /** @var array<mixed> */
    public $mailbackRecipients = array();
    /** @var int|null */
    public $editable = null;
    /** @var int|null */
    public $editable_override = null;
    /** @var bool */
    public $sendNotificationAfterPayment = false;
    /** @var string */
    public $opt_token = "9562384751";
    /** @var array<int, string> */
    public $draggableDivIds = array();
    /** @var object|null */
    public $quickmode = null;
    /** @var CMSApplication */
    public $app;
    /** @var DatabaseInterface */
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

    /**
     * @return string
     */
    public function header()
    {
        return $this->renderingEngine()->header();
    }

    /**
     * @param string $path
     * @param list<\stdClass> $rows
     * @param string $fieldName
     * @return string
     */
    public function cbCreatePathByTokens($path, array $rows, $fieldName)
    {
        return $this->renderingEngine()->cbCreatePathByTokens($path, $rows, $fieldName);
    }

    /**
     * @param string $path
     * @return string
     */
    public function makeSafeFolder($path)
    {
        return $this->renderingEngine()->makeSafeFolder($path);
    }

    /**
     * @phpstan-impure
     * @return array<string, mixed>
     */
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

    /**
     * @phpstan-impure
     * @param array<string, mixed>|null $cbResult
     * @return string|null
     */
    public function collectSubmitdata($cbResult = null)
    {
        return $this->submissionEngine()->collectSubmitdata($cbResult);
    }

    /**
     * @return null
     */
    public function submit()
    {
        return $this->submissionEngine()->submit();
    }

    /**
     * @param mixed $value
     * @return string
     */
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

    /**
     * @return null
     */
    public function sendEmailNotification()
    {
        return $this->notificationEngine()->sendEmailNotification();
    }

    /**
     * @return string|null
     */
    public function getFormTitleTranslated()
    {
        return $this->notificationEngine()->getFormTitleTranslated();
    }

    /**
     * @param string $field
     * @param mixed $name
     * @param mixed $res
     * @param mixed $dataObject
     * @param int $childrenLength
     * @return null
     */
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

    /**
     * @return null
     */
    public function sendMailbackNotification()
    {
        return $this->notificationEngine()->sendMailbackNotification();
    }

    /**
     * @return null
     */
    public function sendSalesforceNotification()
    {
        return $this->notificationEngine()->sendSalesforceNotification();
    }

    /**
     * @return null
     */
    public function sendMailChimpNotification()
    {
        return $this->notificationEngine()->sendMailChimpNotification();
    }

    private function notificationEngine(): NotificationEngine
    {
        return $this->notificationEngineService ??= new NotificationEngine($this, $this->exportEngine());
    }

    /**
     * @phpstan-impure
     * @param array<string, mixed>|null $cbResult
     * @return mixed
     */
    public function logToDatabase($cbResult = null)
    {
        return $this->exportEngine()->logToDatabase($cbResult);
    }

    public function random_str(
        int $length,
        string $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'
    ): string {
        return $this->exportEngine()->randomString($length, $keyspace);
    }

    /**
     * @param mixed $from
     * @param mixed $fromname
     * @param mixed $recipient
     * @param mixed $subject
     * @param mixed $body
     * @param mixed $attachment
     * @param mixed $html
     * @param mixed $cc
     * @param mixed $bcc
     * @param mixed $alt_sender
     * @return null
     */
    public function sendMail(
        $from,
        $fromname,
        $recipient,
        $subject,
        $body,
        $attachment = null,
        $html = null,
        $cc = null,
        $bcc = null,
        $alt_sender = ''
    ) {
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

    /**
     * @param array<mixed> $filter
     * @param bool $mailback
     * @param bool $translate
     * @return string
     */
    public function exppdf($filter = [], $mailback = false, $translate = true)
    {
        return $this->exportEngine()->exppdf($filter, $mailback, $translate);
    }

    /**
     * @param array<mixed> $filter
     * @param bool $mailback
     * @return string
     */
    public function expcsv($filter = [], $mailback = false)
    {
        return $this->exportEngine()->expcsv($filter, $mailback);
    }

    /**
     * @param array<mixed> $filter
     * @param bool $mailback
     * @param bool $translate
     * @return string
     */
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

    /**
     * @param int|string $id
     * @param mixed $name
     * @return string
     */
    public function getPieceById($id, $name = null)
    {
        return $this->scriptingEngine()->getPieceById($id, $name);
    }

    /**
     * @param string $name
     * @param mixed $id
     * @return string
     */
    public function getPieceByName($name, $id = null)
    {
        return $this->scriptingEngine()->getPieceByName($name, $id);
    }

    /**
     * @phpstan-impure
     * @param string $code
     * @param string $name
     * @param string|null $type
     * @param int|string|null $id
     * @param int|null $pane
     * @return mixed
     */
    public function execPiece($code, $name, $type, $id, $pane)
    {
        return $this->scriptingEngine()->execPiece($code, $name, $type, $id, $pane);
    }

    /**
     * @param int|string $id
     * @return mixed
     */
    public function execPieceById($id)
    {
        return $this->scriptingEngine()->execPieceById($id);
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function execPieceByName($name)
    {
        return $this->scriptingEngine()->execPieceByName($name);
    }

    /**
     * @param string $code
     * @param string $name
     * @param string|null $type
     * @param int|string|null $id
     * @param int|null $pane
     * @return string
     */
    public function replaceCode($code, $name, $type, $id, $pane)
    {
        return $this->scriptingEngine()->replaceCode($code, $name, $type, $id, $pane);
    }

    /**
     * @param \stdClass $elem
     * @param \Vcmb\Component\BreezingformsNG\Site\Table\QueryColumn $coldef
     * @return null
     */
    public function compileQueryCol(&$elem, &$coldef)
    {
        return $this->scriptingEngine()->compileQueryCol($elem, $coldef);
    }

    /**
     * @param string $code
     * @param \stdClass $elem
     * @param object $row
     * @param \Vcmb\Component\BreezingformsNG\Site\Table\QueryColumn $coldef
     * @param mixed $value
     * @return mixed
     */
    public function execQueryValue($code, &$elem, &$row, &$coldef, $value)
    {
        return $this->scriptingEngine()->execQueryValue($code, $elem, $row, $coldef, $value);
    }

    /**
     * @param \stdClass $elem
     * @param array<int, mixed> $valrows
     * @param list<\Vcmb\Component\BreezingformsNG\Site\Table\QueryColumn> $coldefs
     * @return null
     */
    public function execQuery(&$elem, &$valrows, &$coldefs)
    {
        return $this->scriptingEngine()->execQuery($elem, $valrows, $coldefs);
    }

    /**
     * @param \stdClass $row
     * @return string
     */
    public function script2clause(&$row)
    {
        return $this->scriptingEngine()->script2clause($row);
    }

    /**
     * @param array<int, array<int, mixed>> $library
     * @return null
     */
    public function loadBuiltins(&$library)
    {
        return $this->scriptingEngine()->loadBuiltins($library);
    }

    /**
     * @param array<int, array<int, mixed>> $library
     * @return null
     */
    public function loadScripts(&$library)
    {
        return $this->scriptingEngine()->loadScripts($library);
    }

    /**
     * @param mixed $str
     * @return string
     */
    public function compressJavascript($str)
    {
        return $this->scriptingEngine()->compressJavascript($str);
    }

    /**
     * @phpstan-impure
     * @param string $func
     * @param array<int, array<int, mixed>> $library
     * @param array<int, string> $linked
     * @param string $code
     * @param string|null $type
     * @param int|string|null $id
     * @param int|null $pane
     * @return string|null
     */
    public function linkcode($func, &$library, &$linked, $code, $type = null, $id = null, $pane = null)
    {
        return $this->scriptingEngine()->linkcode($func, $library, $linked, $code, $type, $id, $pane);
    }

    /**
     * @param int|string $cond
     * @param int|string|null $id
     * @param string $name
     * @param string $code
     * @param array<int, array<int, mixed>> $library
     * @param array<int, string> $linked
     * @param string|null $type
     * @param int|string|null $rowid
     * @param int|null $pane
     * @return null
     */
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

    /**
     * @param mixed $mode
     * @return string
     */
    public function dispTraceMode($mode)
    {
        return $this->codeToolsRuntime()->dispTraceMode($mode);
    }

    /**
     * @phpstan-impure
     * @param mixed $code
     * @return bool
     */
    public function trim(&$code)
    {
        return $this->codeToolsRuntime()->trim($code);
    }

    /**
     * @phpstan-impure
     * @param mixed $code
     * @return int
     */
    public function nonblank(&$code)
    {
        return $this->codeToolsRuntime()->nonblank($code);
    }

    /**
     * @param mixed $classdef
     * @return string
     */
    public function getClassName($classdef)
    {
        return $this->codeToolsRuntime()->getClassName($classdef);
    }

    /**
     * @param mixed $mixed
     * @param string $indent
     * @return string
     */
    public function expJsValue($mixed, $indent = '')
    {
        return $this->codeToolsRuntime()->expJsValue($mixed, $indent);
    }

    /**
     * @param mixed $name
     * @param mixed $mixed
     * @return string
     */
    public function expJsVar($name, $mixed)
    {
        return $this->codeToolsRuntime()->expJsVar($name, $mixed);
    }

    /**
     * @return void
     */
    public function dumpTrace()
    {
        $this->traceRuntime()->dumpTrace();
    }

    /**
     * @param mixed $name
     * @return void
     */
    public function traceEval($name)
    {
        $this->traceRuntime()->traceEval($name);
    }

    /**
     * @return bool
     */
    public function suicide()
    {
        return $this->traceRuntime()->suicide();
    }

    /**
     * @phpstan-impure
     * @return bool
     */
    public function bury()
    {
        return $this->traceRuntime()->bury();
    }

    /**
     * @param mixed $code
     * @param mixed $spos
     * @param mixed $offs
     * @return string
     */
    public function findToken(&$code, &$spos, &$offs)
    {
        return $this->codeToolsRuntime()->findToken($code, $spos, $offs);
    }

    /**
     * @param mixed $code
     * @param mixed $spos
     * @param mixed $offs
     * @param mixed $line
     * @return string
     */
    public function findRealToken(&$code, &$spos, &$offs, &$line)
    {
        return $this->codeToolsRuntime()->findRealToken($code, $spos, $offs, $line);
    }

    /**
     * @param mixed $mode
     * @param mixed $code
     * @param mixed $name
     * @param mixed $type
     * @param mixed $id
     * @param mixed $pane
     * @return string
     */
    public function patchCode($mode, $code, $name, $type, $id, $pane)
    {
        return $this->codeToolsRuntime()->patchCode($mode, $code, $name, $type, $id, $pane);
    }

    /**
     * @param mixed $code
     * @param mixed $name
     * @param mixed $type
     * @param mixed $id
     * @param mixed $pane
     * @return bool
     */
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
     *
     * @param string $filename
     * @param string $userfile_name
     * @param string $destpath
     * @param mixed $timestamp
     * @param mixed $useUrl
     * @param mixed $useUrlDownloadDirectory
     * @param mixed $resize_target_width
     * @param mixed $resize_target_height
     * @param mixed $resize_type
     * @param mixed $resize_bgcolor
     * @param mixed $field_name
     * @return array{default: string, server: string}|''
     */
    public function saveUpload(
        $filename,
        $userfile_name,
        $destpath,
        $timestamp,
        $useUrl = false,
        $useUrlDownloadDirectory = '',
        $resize_target_width = 0,
        $resize_target_height = 0,
        $resize_type = '',
        $resize_bgcolor = '#ffffff',
        $field_name = ''
    ) {
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

    /**
     * @param mixed $filename
     * @return int|false
     */
    public function exifImageType($filename)
    {
        return $this->uploadRuntime()->imageType((string) $filename);
    }

    /**
     * @param mixed $path
     * @param mixed $width
     * @param mixed $height
     * @param mixed $bgcolor
     * @param mixed $type
     * @return void
     */
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

    /**
     * @param mixed $source_image
     * @param mixed $destination_width
     * @param mixed $destination_height
     * @param mixed $type
     * @param list<int> $bgcolor
     * @return mixed
     */
    public function resize_image(
        $source_image,
        $destination_width,
        $destination_height,
        $type = 0,
        $bgcolor = [0, 0, 0]
    ) {
        return $this->uploadRuntime()->resizeImage(
            $source_image,
            (int) $destination_width,
            (int) $destination_height,
            (int) $type,
            (array) $bgcolor
        );
    }

    /**
     * @param mixed $val
     * @return int|float
     */
    public function returnBytes($val)
    {
        return $this->uploadRuntime()->parseByteSize((string) $val);
    }

    /**
     * @param array<mixed> $dataObject
     * @param mixed $needle
     * @return array<mixed>|null
     */
    public function findQuickModeElement(array $dataObject, $needle)
    {
        return $this->uploadRuntime()->findQuickModeElement($dataObject, (string) $needle);
    }

    /**
     * @return float
     */
    public function measureTime()
    {
        return $this->submissionEngine()->measureTime();
    }

    private function uploadRuntime(): UploadRuntime
    {
        return $this->uploadRuntimeService ??= new UploadRuntime($this->app->getInput());
    }

    /**
     * @param int|string $runmode
     * @param int $inframe
     * @param int|string $form
     * @param int $page
     * @param int $border
     * @param int $align
     * @param int $top
     * @param int|string $target
     * @param string $suffix
     * @param int $editable
     * @param int $editable_override
     */
    public function __construct(
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
