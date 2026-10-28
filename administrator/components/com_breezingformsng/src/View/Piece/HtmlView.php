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

namespace Vcmb\Component\BreezingformsNG\Administrator\View\Piece;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Vcmb\Component\BreezingformsNG\Administrator\Model\PieceModel;
use Vcmb\Component\BreezingformsNG\Administrator\Service\AreaAccess;
use Vcmb\Component\BreezingformsNG\Administrator\Service\FormVersioning;
use Vcmb\Component\BreezingformsNG\Administrator\Service\PieceTestRunner;
use Vcmb\Component\BreezingformsNG\Administrator\View\BreezingformsNG\HtmlView as BaseHtmlView;

class HtmlView extends BaseHtmlView
{
    private const EDIT_LABELS = [
        'COM_BREEZINGFORMSNG_PIECES_ENTERIDENT',
        'COM_BREEZINGFORMSNG_PIECES_ENTERNAME',
        'COM_BREEZINGFORMSNG_PIECES_ENTTITLE',
        'COM_BREEZINGFORMSNG_TEST_INVALID_SERVER_RESPONSE',
        'COM_BREEZINGFORMSNG_TEST_NO_CHANGES',
        'COM_BREEZINGFORMSNG_TEST_PASSED_SHORT',
        'COM_BREEZINGFORMSNG_TEST_RUNNING',
        'COM_BREEZINGFORMSNG_TEST_SAVE_FIRST_PIECE',
        'COM_BREEZINGFORMSNG_TEST_SAVE_PIECE_BEFORE_CONTINUE',
        'COM_BREEZINGFORMSNG_TEST_SAVE_PIECE_BEFORE_NAVIGATION',
        'COM_BREEZINGFORMSNG_TEST_SAVE_PIECE_BEFORE_TESTS',
        'COM_BREEZINGFORMSNG_TEST_SAVE_PIECE_BEFORE_UNIT_TESTS',
        'COM_BREEZINGFORMSNG_TEST_UNIT_TESTS_NONE',
    ];

    public ?Form $form = null;
    public ?\stdClass $item = null;
    public bool $canEdit = false;

    // test layout
    public string $functionName = '';
    /** @var array<int, mixed> */
    public array $paramNames = [];
    /** @var array<int, mixed> */
    public array $paramDefaults = [];
    /** @var array<int, mixed> */
    public array $paramValues = [];
    public mixed $result = null;
    public string $output = '';
    public string $error = '';
    /** @var array<string, mixed> */
    public array $errorDetails = [];
    /** @var array<string, mixed> */
    public array $unitTestResult = [];
    public string $testMode = '';
    public bool $autoOpened = false;
    private bool $testRun = false;

    /**
     * Hands over what a run of the piece produced, for the test layout.
     *
     * @param array<int, mixed> $paramNames
     * @param array<int, mixed> $paramDefaults
     * @param array<int, mixed> $paramValues
     * @param array{result: mixed, output: string, error: string, errorDetails: array<string, mixed>} $execution
     * @param array<string, mixed> $unitTestResult
     */
    public function setTestOutcome(
        string $functionName,
        array $paramNames,
        array $paramDefaults,
        array $paramValues,
        array $execution,
        array $unitTestResult,
        bool $autoOpened
    ): void {
        $this->functionName = $functionName;
        $this->paramNames = $paramNames;
        $this->paramDefaults = $paramDefaults;
        $this->paramValues = $paramValues;
        $this->result = $execution['result'];
        $this->output = $execution['output'];
        $this->error = $execution['error'];
        $this->errorDetails = $execution['errorDetails'];
        $this->unitTestResult = $unitTestResult;
        $this->autoOpened = $autoOpened;
        $this->testRun = true;
    }

    public function display($tpl = null)
    {
        /** @var CMSApplication $app */
        $app = Factory::getApplication();
        $model = $this->getModel();

        if (!$model instanceof PieceModel) {
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
            // The test page runs PHP on the server.
            if (!$this->canEdit) {
                throw new \Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
            }

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
            $toolbar->apply('piece.apply');
            $toolbar->save('piece.save');
        }

        if ($saved && $this->canEdit) {
            $toolbar->standardButton('test', 'COM_BREEZINGFORMSNG_TEST', 'piece.test')->icon('icon-eye');
        }

        if ($saved && $this->canEdit && FormVersioning::isEnabled()) {
            $toolbar->versions('com_breezingformsng.piece', (int) $item->id);
        }

        $toolbar->cancel('piece.cancel', $saved ? 'JTOOLBAR_CLOSE' : 'JTOOLBAR_CANCEL');

        if ($saved) {
            $this->addItemPagination($toolbar, 'Pieces', (int) $item->id, 'piece.navigate');
        }

        $wa = $document->getWebAssetManager();
        $wa->useScript('keepalive');
        $wa->registerAndUseScript(
            'com_breezingformsng.pieces-edit',
            'media/com_breezingformsng/js/admin/pieces-edit.js',
            ['version' => 'auto'],
            [],
            ['core']
        );
        $document->addScriptOptions('com_breezingformsng.pieces-edit', [
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
            'hasSavedRecord' => $saved,
            'csrfToken' => Session::getFormToken(),
        ]);

        foreach (self::EDIT_LABELS as $key) {
            Text::script($key);
        }
    }

    private function prepareTest(CMSApplication $app): void
    {
        $item = $this->item;
        $this->testMode = $app->getInput()->getCmd('test_mode', '');
        $hasUnitTests = trim((string) $item->unit_tests) !== '';
        $autoRun = false;

        if (!$this->testRun) {
            [$this->functionName, $this->paramNames, $this->paramDefaults] = PieceTestRunner::parseSignature((string) $item->code);

            // A piece runs on opening when every parameter has a default value.
            $autoRun = true;

            foreach (array_keys($this->paramNames) as $index) {
                if (trim((string) ($this->paramDefaults[$index] ?? '')) === '') {
                    $autoRun = false;
                    break;
                }
            }
        }

        $unitTestsOnly = !$autoRun && !$this->testRun && $hasUnitTests && $this->testMode !== 'unit';

        /** @var HtmlDocument $document */
        $document = $this->getDocument();
        $toolbar = $document->getToolbar();
        $toolbar->linkButton('edit', 'COM_BREEZINGFORMSNG_TEST_BACK')
            ->url(Route::_('index.php?option=com_breezingformsng&task=piece.edit&id=' . (int) $item->id))
            ->icon('icon-undo');
        $this->addItemPagination(
            $toolbar,
            'Pieces',
            (int) $item->id,
            'piece.navigate',
            ['test_context' => 1, ...($this->testMode !== '' ? ['test_mode' => $this->testMode] : [])]
        );

        $document->getWebAssetManager()->registerAndUseScript(
            'com_breezingformsng.pieces-test',
            'media/com_breezingformsng/js/admin/pieces-test.js',
            ['version' => 'auto'],
            [],
            ['core']
        );
        $document->addScriptOptions('com_breezingformsng.pieces-test', [
            'autoSubmit' => !$this->testRun && ($autoRun || $this->testMode === 'unit' || $unitTestsOnly),
            'forceUnitTestMode' => $unitTestsOnly,
        ]);
    }

    protected function getDetailLabel(): ?string
    {
        if ($this->item === null) {
            return null;
        }

        $name = trim((string) $this->item->name);

        return $name !== '' ? $name : Text::_('COM_BREEZINGFORMSNG_PIECES_ADDPIECE');
    }
}
