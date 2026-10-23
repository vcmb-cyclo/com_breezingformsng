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
    public ?int $prevRecordId = null;
    public ?int $nextRecordId = null;
    public ?int $recordPosition = null;
    public int $recordTotal = 0;

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

        $recordsModel = $this->getRecordsModel($app);
        $navigation = $recordsModel->getNavigation((int) $item->id);
        $this->prevRecordId = $navigation['prev'];
        $this->nextRecordId = $navigation['next'];
        $this->recordPosition = $navigation['position'];
        $this->recordTotal = $navigation['total'];

        $app->getInput()->set('hidemainmenu', true);
        $this->addToolbar($app);
        HTMLHelper::_('bootstrap.tooltip', '.hasTooltip');

        parent::display($tpl);
    }

    /**
     * The list model, whose filters and ordering drive previous and next.
     */
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

        $markDropdown = $toolbar->dropdownButton('mark-options');

        if (!$markDropdown instanceof DropdownButton) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }

        $markDropdown->toggleSplit(false);
        $markDropdown
            ->text(Text::_('COM_BREEZINGFORMSNG_TOOLBAR_MARK'))
            ->icon('icon-check')
            ->buttonClass('btn btn-action');
        $markChild = $markDropdown->getChildToolbar();
        $markChild->standardButton('markViewed')->text(Text::_('COM_BREEZINGFORMSNG_TOOLBAR_MARK_VIEWED'))->task('records.viewed')->icon('icon-eye-open')->listCheck(false);
        $markChild->standardButton('unmarkViewed')->text(Text::_('COM_BREEZINGFORMSNG_TOOLBAR_UNMARK_VIEWED'))->task('records.unviewed')->icon('icon-eye-close')->listCheck(false);
        $markChild->standardButton('markExported')->text(Text::_('COM_BREEZINGFORMSNG_TOOLBAR_MARK_EXPORTED'))->task('records.exported')->icon('icon-share')->listCheck(false);
        $markChild->standardButton('unmarkExported')->text(Text::_('COM_BREEZINGFORMSNG_TOOLBAR_UNMARK_EXPORTED'))->task('records.unexported')->icon('icon-cancel-circle')->listCheck(false);

        $state = (int) ($this->item->state ?? RecordState::PUBLISHED);

        if ($user->authorise('core.edit.state', 'com_breezingformsng')) {
            if ($state === RecordState::ARCHIVED) {
                $toolbar->unarchive('records.publish')->listCheck(false);
            } elseif ($state === RecordState::TRASHED) {
                $toolbar->publish('records.publish')->listCheck(false);
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
            ['com_breezingformsng.admin-form']
        );
        $document->addScriptOptions('com_breezingformsng.record-flags', ['csrfToken' => Session::getFormToken()]);
        $document->addScriptOptions('com_breezingformsng.admin-form', [
            'cancelTask' => 'record.cancel',
            'saveTask'   => 'record.apply',
            'exportTasks' => ['records.exportPdf', 'records.exportCsv', 'records.exportXlsx', 'records.exportXml'],
            'discardTasks' => [
                'records.viewed', 'records.unviewed', 'records.exported',
                'records.unexported', 'records.publish', 'records.archive',
                'records.trash', 'records.delete',
            ],
        ]);
        Text::script('COM_BREEZINGFORMSNG_AJAX_STATE_ERROR');
        Text::script('JYES');
        Text::script('JNO');
        Text::script('COM_BREEZINGFORMSNG_TEST_NO_CHANGES');
        Text::script('COM_BREEZINGFORMSNG_CONFIRM_DISCARD_CHANGES');
        Text::script('COM_BREEZINGFORMSNG_EXPORT_SAVE_FIRST');
    }

    protected function getDetailLabel(): ?string
    {
        return $this->item !== null ? (string) $this->item->title : null;
    }
}
