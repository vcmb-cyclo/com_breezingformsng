<?php

/**
 * BreezingForms NG - A Joomla Forms Application
 *
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 **/

namespace Vcmb\Component\BreezingformsNG\Administrator\View\Script;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Vcmb\Component\BreezingformsNG\Administrator\Model\ScriptModel;
use Vcmb\Component\BreezingformsNG\Administrator\Service\AreaAccess;
use Vcmb\Component\BreezingformsNG\Administrator\Service\ScriptSignatureParser;
use Vcmb\Component\BreezingformsNG\Administrator\View\BreezingformsNG\HtmlView as BaseHtmlView;

class HtmlView extends BaseHtmlView
{
    private const EDIT_LABELS = [
        'COM_BREEZINGFORMSNG_SCRIPTS_ENTERIDENT',
        'COM_BREEZINGFORMSNG_SCRIPTS_ENTERNAME',
        'COM_BREEZINGFORMSNG_SCRIPTS_ENTTITLE',
        'COM_BREEZINGFORMSNG_TEST_ENTER_FUNCTION_NAME',
        'COM_BREEZINGFORMSNG_TEST_NO_CHANGES',
        'COM_BREEZINGFORMSNG_TEST_NO_UNIT_TEST_DEFINED',
        'COM_BREEZINGFORMSNG_TEST_OUTPUT',
        'COM_BREEZINGFORMSNG_TEST_PASSED_SHORT',
        'COM_BREEZINGFORMSNG_TEST_SAVE_FIRST_SCRIPT',
        'COM_BREEZINGFORMSNG_TEST_SAVE_SCRIPT_BEFORE_CONTINUE',
        'COM_BREEZINGFORMSNG_TEST_SAVE_SCRIPT_BEFORE_NAVIGATION',
        'COM_BREEZINGFORMSNG_TEST_SAVE_SCRIPT_BEFORE_TESTS',
        'COM_BREEZINGFORMSNG_TEST_SAVE_SCRIPT_BEFORE_UNIT_TESTS',
        'COM_BREEZINGFORMSNG_TEST_UNIT_TESTS_NONE',
    ];

    private const TEST_LABELS = [
        'COM_BREEZINGFORMSNG_TEST_ENTER_FUNCTION_NAME_TO_TEST',
        'COM_BREEZINGFORMSNG_TEST_INVALID_FUNCTION_NAME',
        'COM_BREEZINGFORMSNG_TEST_NO_UNIT_TEST_DEFINED',
        'COM_BREEZINGFORMSNG_TEST_OUTPUT',
        'COM_BREEZINGFORMSNG_TEST_PASSED_SHORT',
        'COM_BREEZINGFORMSNG_TEST_UNIT_FAILURES_ON_OPEN',
        'COM_BREEZINGFORMSNG_TEST_UNIT_FAILURES_1',
        'COM_BREEZINGFORMSNG_TEST_UNIT_FAILURES_MORE',
        'COM_BREEZINGFORMSNG_TEST_UNIT_TESTS_NONE',
    ];

    public ?Form $form = null;
    public ?\stdClass $item = null;
    public bool $canEdit = false;

    // test layout
    public string $functionName = '';
    /** @var string[] */
    public array $paramNames = [];
    /** @var string[] */
    public array $paramDefaults = [];
    public string $testMode = '';

    public function display($tpl = null)
    {
        /** @var CMSApplication $app */
        $app = Factory::getApplication();
        $model = $this->getModel();

        if (!$model instanceof ScriptModel) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }

        $item = $model->getItem();

        if ($item === false) {
            throw new \Exception(Text::_('JLIB_APPLICATION_ERROR_NOT_EXIST'), 404);
        }

        $this->item = $item;
        $this->canEdit = AreaAccess::isAllowed($app->getIdentity(), AreaAccess::CODE);
        $app->getInput()->set('hidemainmenu', true);

        if ($this->getLayout() === 'test') {
            if ((int) $item->id < 1) {
                throw new \Exception(Text::_('JLIB_APPLICATION_ERROR_NOT_EXIST'), 404);
            }

            $this->prepareTest($app);
        } else {
            $form = $model->getForm();

            if (!$form instanceof Form) {
                throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
            }

            $this->form = $form;
            $this->prepareEdit();
        }

        parent::display($tpl);
    }

    private function prepareEdit(): void
    {
        /** @var HtmlDocument $document */
        $document = $this->getDocument();
        $toolbar = $document->getToolbar();
        $item = $this->item;
        $saved = (int) $item->id > 0;

        if ($this->canEdit) {
            $toolbar->apply('script.apply');
            $toolbar->save('script.save');
        }

        if ($saved) {
            $toolbar->standardButton('previous', 'COM_BREEZINGFORMSNG_PROCESS_PAGEPREV', 'script.previous')->icon('icon-arrow-left');
            $toolbar->standardButton('next', 'COM_BREEZINGFORMSNG_PROCESS_PAGENEXT', 'script.next')->icon('icon-arrow-right');
            $toolbar->standardButton('test', 'COM_BREEZINGFORMSNG_TEST', 'script.test')->icon('icon-eye');
        }

        $toolbar->cancel('script.cancel', $saved ? 'JTOOLBAR_CLOSE' : 'JTOOLBAR_CANCEL');

        $wa = $document->getWebAssetManager();
        $wa->useScript('keepalive');
        $wa->registerAndUseScript(
            'com_breezingformsng.scripts-edit',
            'media/com_breezingformsng/js/admin/scripts-edit.js',
            ['version' => 'auto'],
            [],
            ['core']
        );
        $document->addScriptOptions('com_breezingformsng.scripts-edit', [
            'initialState' => [
                'title' => (string) $item->title,
                'type' => (string) ($item->type ?: 'Untyped'),
                'package' => (string) $item->package,
                'name' => (string) $item->name,
                'published' => (string) (int) ($item->published ?? 1),
                'description' => (string) $item->description,
                'code' => (string) $item->code,
                'unit_tests' => (string) $item->unit_tests,
            ],
            'persistedUnitTests' => (string) $item->unit_tests,
            'hasSavedRecord' => $saved,
        ]);

        foreach (self::EDIT_LABELS as $key) {
            Text::script($key);
        }
    }

    private function prepareTest(CMSApplication $app): void
    {
        $item = $this->item;
        [$this->functionName, $this->paramNames, $this->paramDefaults] = (new ScriptSignatureParser())->parse(
            (string) $item->code,
            (string) $item->name
        );
        $this->testMode = $app->getInput()->getCmd('test_mode', '');

        // A script runs on opening when every parameter has a default value.
        $autoRun = true;

        foreach (array_keys($this->paramNames) as $index) {
            if (trim((string) ($this->paramDefaults[$index] ?? '')) === '') {
                $autoRun = false;
                break;
            }
        }

        /** @var HtmlDocument $document */
        $document = $this->getDocument();
        $toolbar = $document->getToolbar();
        $toolbar->linkButton('edit', 'COM_BREEZINGFORMSNG_TEST_BACK')
            ->url(Route::_('index.php?option=com_breezingformsng&task=script.edit&id=' . (int) $item->id))
            ->icon('icon-undo');
        $toolbar->standardButton('previous', 'COM_BREEZINGFORMSNG_PROCESS_PAGEPREV', 'script.previous')->icon('icon-arrow-left');
        $toolbar->standardButton('next', 'COM_BREEZINGFORMSNG_PROCESS_PAGENEXT', 'script.next')->icon('icon-arrow-right');

        $document->getWebAssetManager()->registerAndUseScript(
            'com_breezingformsng.scripts-test',
            'media/com_breezingformsng/js/admin/scripts-test.js',
            ['version' => 'auto'],
            [],
            ['core']
        );
        $document->addScriptOptions('com_breezingformsng.scripts-test', [
            'code' => (string) $item->code,
            'functionName' => $this->functionName,
            'unitTests' => (string) $item->unit_tests,
            'testMode' => $this->testMode,
            'autoRun' => $autoRun,
            'hasUnitTests' => trim((string) $item->unit_tests) !== '',
        ]);

        foreach (self::TEST_LABELS as $key) {
            Text::script($key);
        }
    }

    protected function getDetailLabel(): ?string
    {
        if ($this->item === null) {
            return null;
        }

        $name = trim((string) $this->item->name);

        return $name !== '' ? $name : Text::_('COM_BREEZINGFORMSNG_SCRIPTS_ADDSCRIPT');
    }
}
