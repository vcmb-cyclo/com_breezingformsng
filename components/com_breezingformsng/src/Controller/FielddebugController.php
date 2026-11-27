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

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Site\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSWebApplicationInterface;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\MVC\Factory\MVCFactoryServiceInterface;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;
use Vcmb\Component\BreezingformsNG\Administrator\Model\FormModel;
use Vcmb\Component\BreezingformsNG\Administrator\Model\FormOptionsModel;
use Vcmb\Component\BreezingformsNG\Administrator\Service\ContentBuilderFormSync;
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\Debug\FieldEdit;
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\Debug\FieldOrder;
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\Debug\FieldWriter;

/** Changes to a form made from its debug bar on the site. */
class FielddebugController extends BaseController
{
    private const MESSAGES = [
        FieldWriter::INVALID => 'COM_BREEZINGFORMSNG_DEBUG_BAR_SAVE_FAILED',
        FieldWriter::DENIED => 'JERROR_ALERTNOAUTHOR',
        FieldWriter::CHANGED => 'COM_BREEZINGFORMSNG_DEBUG_BAR_MOVE_CHANGED',
        FieldWriter::LOCKED => 'COM_BREEZINGFORMSNG_DEBUG_BAR_MOVE_LOCKED',
    ];

    /** Saves the moves and the settings of fields changed on the page, as one version. */
    public function save(): void
    {
        $app = $this->app;

        if (!$app instanceof CMSWebApplicationInterface) {
            return;
        }

        $this->input->getMethod() === 'POST' && Session::checkToken('post')
            ? $this->answer($app, ...$this->write())
            : $this->answer($app, FieldWriter::DENIED);
    }

    /** @return array{int, string} Status, then the field whose changes were refused. */
    private function write(): array
    {
        $user = $this->app->getIdentity();

        if ($user === null || !$user->authorise('core.admin')) {
            return [FieldWriter::DENIED, ''];
        }

        $post = $this->input->post;
        // Names only compared with those of the form; texts of a Super User, kept with
        // their markup as the editor does.
        $moves = FieldOrder::moves((string) $post->get('moves', '[]', 'raw'));
        $edits = FieldEdit::changes((string) $post->get('edits', '[]', 'raw'));

        if ($moves === null || $edits === null) {
            return [FieldWriter::INVALID, ''];
        }

        $language = $this->app->getLanguage();
        $default = ComponentHelper::getParams('com_languages')->get('site');
        $tag = $language === null || $language->getTag() === $default
            ? FieldEdit::DEFAULT_LANGUAGE
            : $language->getTag();

        // Written for the language of the page: another one would change other texts.
        if ($edits !== [] && $post->getCmd('language', '') !== $tag) {
            return [FieldWriter::CHANGED, ''];
        }

        $formId = $post->getInt('formId', 0);

        try {
            $component = $this->app->bootComponent('com_breezingformsng');

            if (!$component instanceof MVCFactoryServiceInterface) {
                throw new \RuntimeException('The form models are not available.');
            }

            $factory = $component->getMVCFactory();
            $options = $factory->createModel('FormOptions', 'Administrator', ['ignore_request' => true]);
            $forms = $factory->createModel('Form', 'Administrator', ['ignore_request' => true]);

            if (!$options instanceof FormOptionsModel || !$forms instanceof FormModel) {
                throw new \RuntimeException('The form models are not available.');
            }

            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $writer = new FieldWriter($db, $options, $forms);
            $status = $writer->write(
                $formId,
                $moves,
                $edits,
                $post->getString('note', ''),
                $tag,
                $post->getAlnum('revision', ''),
                $user
            );

            if ($status === FieldWriter::SAVED) {
                (new ContentBuilderFormSync($this->app, $db))->sync($formId);
            }

            return [$status, $writer->refusedField()];
        } catch (\Throwable $e) {
            Log::add($e->getMessage(), Log::WARNING, 'com_breezingformsng');

            return [FieldWriter::INVALID, ''];
        }
    }

    private function answer(CMSWebApplicationInterface $app, int $status, string $field = ''): void
    {
        $message = isset(self::MESSAGES[$status]) ? Text::_(self::MESSAGES[$status]) : '';

        if ($field !== '') {
            $message = Text::sprintf('COM_BREEZINGFORMSNG_DEBUG_BAR_SAVE_FAILED_FIELD', $field);
        }

        $app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
        $app->setHeader('Status', (string) $status, true);
        $app->sendHeaders();
        echo new JsonResponse(null, $message, $status !== FieldWriter::SAVED);
        $app->close();
    }
}
