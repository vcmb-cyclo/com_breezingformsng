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

namespace Vcmb\Component\BreezingformsNG\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use Vcmb\Component\BreezingformsNG\Administrator\Model\PieceModel;
use Vcmb\Component\BreezingformsNG\Administrator\Service\ActionLogger;
use Vcmb\Component\BreezingformsNG\Administrator\Service\PieceTestRunner;
use Vcmb\Component\BreezingformsNG\Administrator\View\Piece\HtmlView;

class PieceController extends PackageItemController
{
    /**
     * A piece is PHP run on the server: its test page is not read-only.
     */
    protected function readOnlyTasks(): array
    {
        return ['edit', 'cancel', 'previous', 'next'];
    }

    protected function logType(): string
    {
        return ActionLogger::PIECE;
    }

    /**
     * Runs the saved piece with the values of the test page, and shows the
     * page again with the outcome.
     */
    public function runTest(): void
    {
        $this->checkToken();

        $model = $this->getModel();

        if (!$model instanceof PieceModel) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }

        $pieceId = $this->input->getInt('id', 0);
        $piece = $model->getItem($pieceId);

        if ($pieceId < 1 || $piece === false) {
            throw new \Exception(Text::_('JLIB_APPLICATION_ERROR_NOT_EXIST'), 404);
        }

        $post = $this->input->post;
        $database = Factory::getContainer()->get(DatabaseInterface::class);
        $functionName = $post->getString('test_function', '');
        $paramNames = (array) $post->get('test_param_names', [], 'array');
        $paramValues = (array) $post->get('test_param_values', [], 'array');
        $testMode = $this->input->getCmd('test_mode', '');

        $execution = ['result' => null, 'output' => '', 'error' => '', 'errorDetails' => []];

        if ($testMode !== 'unit') {
            $execution = PieceTestRunner::executePieceCode(
                $piece,
                $functionName,
                PieceTestRunner::parseArguments($paramNames, $paramValues),
                $database
            );
        }

        $unitTestResult = [];

        if ($testMode === 'unit' || trim((string) $piece->unit_tests) !== '') {
            $unitTestResult = PieceTestRunner::runUnitTests($piece, $functionName, $database);
        }

        $document = $this->app->getDocument();
        $view = $this->getView('piece', $document->getType(), '', ['base_path' => $this->basePath, 'layout' => 'test']);

        if (!$view instanceof HtmlView) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }

        $model->setState($model->getName() . '.id', $pieceId);
        $view->setModel($model, true);
        $view->setDocument($document);
        $view->setTestOutcome(
            $functionName,
            $paramNames,
            (array) $post->get('test_param_defaults', [], 'array'),
            $paramValues,
            $execution,
            $unitTestResult,
            $this->input->getInt('auto_open_tests', 0) === 1
        );
        $view->display();
    }

    /**
     * Runs the unit tests of the code being edited, before it is saved.
     */
    public function runTestsAjax(): void
    {
        $this->checkToken();

        $post = $this->input->post;
        $piece = (object) [
            'id' => $post->getInt('id', 0),
            'code' => $post->get('code', '', 'raw'),
            'unit_tests' => $post->get('unit_tests', '', 'raw'),
        ];
        $result = PieceTestRunner::runUnitTests(
            $piece,
            $post->getString('test_function', ''),
            Factory::getContainer()->get(DatabaseInterface::class)
        );

        $this->app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
        echo json_encode($result, JSON_THROW_ON_ERROR);
        $this->app->close();
    }
}
