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

namespace Vcmb\Component\BreezingformsNG\Administrator\View\Record;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryServiceInterface;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Toolbar\Button\DropdownButton;
use Vcmb\Component\BreezingformsNG\Administrator\Model\RecordModel;
use Vcmb\Component\BreezingformsNG\Administrator\Model\RecordsModel;
use Vcmb\Component\BreezingformsNG\Administrator\Service\RecordState;
use Vcmb\Component\BreezingformsNG\Administrator\View\BreezingformsNG\HtmlView as BaseHtmlView;

class HtmlView extends BaseHtmlView
{
    public ?Form $form = null;
    public ?\stdClass $item = null;
    public ?int $recordPosition = null;
    public ?Pagination $pagination = null;
    public int $recordTotal = 0;
    public bool $canEditState = false;
    public ?\DateTimeZone $timezone = null;

    public function display($tpl = null)
    {
        /** @var CMSApplication $app */
        $app = Factory::getApplication();
        $model = $this->getModel();

        if (!$model instanceof RecordModel) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }

        $item = $model->getItem();

        if ($item === false) {
            throw new \Exception(Text::_('JLIB_APPLICATION_ERROR_NOT_EXIST'), 404);
        }

        $form = $model->getForm();

        if (!$form instanceof Form) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }

        $this->item = $item;
        $this->form = $form;
        $user = $app->getIdentity();
        $this->canEditState = $user->authorise('core.edit.state', 'com_breezingformsng');
        // Dates are stored in UTC and shown in the time zone of the user.
        $this->timezone = $user->getTimezone();

        $recordsModel = $this->getRecordsModel($app);
        $navigation = $recordsModel->getNavigation((int) $item->id);
        $this->recordPosition = $navigation['position'];
        $this->recordTotal = $navigation['total'];
        $this->pagination = $this->getPagination($app, (int) $item->id);

        $app->getInput()->set('hidemainmenu', true);
        $this->addToolbar($app);
        HTMLHelper::_('bootstrap.tooltip', '.hasTooltip');
        HTMLHelper::_('bootstrap.collapse');

        parent::display($tpl);
    }

    /**
     * The list model, whose filters and ordering drive previous and next.
     */
    /**
     * Joomla pagination over the records list, one record per page: each page
     * opens the record at that position through record.navigate.
     */
    private function getPagination(CMSApplication $app, int $recordId): ?Pagination
    {
        if ($this->recordPosition === null) {
            return null;
        }

        $pagination = new Pagination($this->recordTotal, $this->recordPosition - 1, 1, 'record', $app);
        foreach (['view' => null, 'layout' => null, 'option' => 'com_breezingformsng', 'task' => 'record.navigate', 'id' => $recordId, Session::getFormToken() => 1] as $key => $value) {
            $pagination->setAdditionalUrlParam($key, $value);
        }

        return $pagination;
    }

    private function getRecordsModel(CMSApplication $app): RecordsModel
    {
        $component = $app->bootComponent('com_breezingformsng');

        if (!$component instanceof MVCFactoryServiceInterface) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }

        $model = $component->getMVCFactory()->createModel('Records', 'Administrator');

        if (!$model instanceof RecordsModel) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }

        return $model;
    }

    private function addToolbar(CMSApplication $app): void
    {
        /** @var HtmlDocument $document */
        $document = $this->getDocument();
        $toolbar = $document->getToolbar();
        $user = $app->getIdentity();

        $toolbar->apply('record.apply');
        $toolbar->save('record.save');

        $exportDropdown = $toolbar->dropdownButton('export-options');

        if (!$exportDropdown instanceof DropdownButton) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }

        $exportDropdown->toggleSplit(false);
        $exportDropdown
            ->text(Text::_('COM_BREEZINGFORMSNG_EXPORT_DOWNLOAD'))
            ->icon('icon-download')
            ->buttonClass('btn btn-action');
        $exportChild = $exportDropdown->getChildToolbar();
        $exportChild->standardButton('exportPdf')->text(Text::_('COM_BREEZINGFORMSNG_PDF'))->task('records.exportPdf')->icon('icon-download')->listCheck(false);
        $exportChild->standardButton('exportCsv')->text(Text::_('COM_BREEZINGFORMSNG_CSV'))->task('records.exportCsv')->icon('icon-download')->listCheck(false);
        $exportChild->standardButton('exportXlsx')->text(Text::_('COM_BREEZINGFORMSNG_XLSX'))->task('records.exportXlsx')->icon('icon-download')->listCheck(false);
        $exportChild->standardButton('exportXml')->text(Text::_('COM_BREEZINGFORMSNG_XML'))->task('records.exportXml')->icon('icon-download')->listCheck(false);

        $state = (int) ($this->item->state ?? RecordState::PUBLISHED);

        if ($user->authorise('core.edit.state', 'com_breezingformsng')) {
            if ($state === RecordState::ARCHIVED) {
                $toolbar->unarchive('records.publish')->listCheck(false);
            } elseif ($state === RecordState::TRASHED) {
                $toolbar->publish('records.publish', 'COM_BREEZINGFORMSNG_RECORDS_TOOLBAR_RESTORE')->listCheck(false);
            } else {
                $toolbar->archive('records.archive')->listCheck(false);
            }

            if ($state !== RecordState::TRASHED) {
                $toolbar->trash('records.trash')->listCheck(false);
            }
        }

        if ($state === RecordState::TRASHED && $user->authorise('core.delete', 'com_breezingformsng')) {
            $toolbar->delete('records.delete', 'JTOOLBAR_EMPTY_TRASH')
                ->message('COM_BREEZINGFORMSNG_CONFIRM_DELETE_RECORDS')
                ->listCheck(false);
        }

        $toolbar->cancel('record.cancel', 'JTOOLBAR_CLOSE');

        $wa = $document->getWebAssetManager();
        $wa->useScript('keepalive')->useStyle('com_breezingformsng.admin-style');
        $wa->registerAndUseScript(
            'com_breezingformsng.record-flags',
            'media/com_breezingformsng/js/admin/record-flags.js',
            ['version' => 'auto'],
            ['defer' => true],
            ['core']
        );
        $wa->registerAndUseScript(
            'com_breezingformsng.admin-form',
            'media/com_breezingformsng/js/admin/admin-form.js',
            ['version' => 'auto'],
            ['defer' => true],
            ['core']
        );
        $wa->registerAndUseScript(
            'com_breezingformsng.admin-form-dirty',
            'media/com_breezingformsng/js/admin/admin-form-dirty.js',
            ['version' => 'auto'],
            ['defer' => true],
            ['com_breezingformsng.admin-form', 'joomla.dialog']
        );
        $document->addScriptOptions('com_breezingformsng.record-flags', ['csrfToken' => Session::getFormToken()]);
        $document->addScriptOptions('com_breezingformsng.admin-form', [
            'cancelTask' => 'record.cancel',
            'saveTask'   => 'record.apply',
            'exportTasks' => ['records.exportPdf', 'records.exportCsv', 'records.exportXlsx', 'records.exportXml'],
            'discardTasks' => [
                'records.publish', 'records.archive', 'records.trash', 'records.delete',
            ],
        ]);
        Text::script('COM_BREEZINGFORMSNG_AJAX_STATE_ERROR');
        // Buttons and title of the joomla.dialog confirm and alert.
        Text::script('JYES');
        Text::script('JNO');
        Text::script('JOK');
        Text::script('INFO');
        Text::script('COM_BREEZINGFORMSNG_TEST_NO_CHANGES');
        Text::script('COM_BREEZINGFORMSNG_CONFIRM_DISCARD_CHANGES');
        Text::script('COM_BREEZINGFORMSNG_EXPORT_SAVE_FIRST');
    }

    protected function getDetailLabel(): ?string
    {
        return $this->item !== null ? (string) $this->item->title : null;
    }
}
