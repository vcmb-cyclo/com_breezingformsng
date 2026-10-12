<?php
/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Administrator\View\Records;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryServiceInterface;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Toolbar\Button\DropdownButton;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;
use Vcmb\Component\BreezingformsNG\Administrator\Model\RecordModel;
use Vcmb\Component\BreezingformsNG\Administrator\Model\RecordsModel;
use Vcmb\Component\BreezingformsNG\Administrator\Service\RecordState;
use Vcmb\Component\BreezingformsNG\Administrator\View\BreezingformsNG\HtmlView as BaseHtmlView;

class HtmlView extends BaseHtmlView
{
    // list
    /** @var object[] */
    public array $items = [];
    public ?Pagination $pagination = null;
    public ?Registry $state = null;
    public ?Form $filterForm = null;
    public array $activeFilters = [];
    public int $formSelection = 0;

    // edit
    public ?\stdClass $record = null;
    public array $recordRows = [];
    public ?int $prevRecordId = null;
    public ?int $nextRecordId = null;

    private string $layout = 'default';

    private bool $canEditState = false;
    private bool $canDelete = false;

    public function display($tpl = null)
    {
        /** @var CMSApplication $app */
        $app = Factory::getApplication();
        $input = $app->getInput();
        $layout = $input->getCmd('layout', 'default');
        $this->layout = $layout;

        $app->getDocument()->getWebAssetManager()->useScript('keepalive');

        $user = $app->getIdentity();
        $this->canEditState = $user->authorise('core.edit.state', 'com_breezingformsng');
        $this->canDelete = $user->authorise('core.delete', 'com_breezingformsng');

        if ($layout === 'edit') {
            $this->prepareEditData($input);
            $this->prepareEditToolbar();
            HTMLHelper::_('bootstrap.tooltip', '.hasTooltip');

            $document = $app->getDocument();
            $document->getWebAssetManager()->useStyle('com_breezingformsng.admin-style');
            $document->getWebAssetManager()->registerAndUseScript(
                'com_breezingformsng.record-flags',
                'media/com_breezingformsng/js/admin/record-flags.js',
                ['version' => 'auto'],
                ['defer' => true],
                ['core']
            );
            $document->addScriptOptions(
                'com_breezingformsng.record-flags',
                ['csrfToken' => \Joomla\CMS\Session\Session::getFormToken()]
            );
            Text::script('COM_BREEZINGFORMSNG_AJAX_STATE_ERROR');
            Text::script('JYES');
            Text::script('JNO');
        } elseif ($layout === 'csvimport') {
            $this->formSelection = (int) $this->getRecordsModel()->getState('filter.form');
            $this->prepareImportToolbar();
        } else {
            $this->prepareListData();
            $this->prepareListToolbar();

            $document = $app->getDocument();
            $document->getWebAssetManager()->useScript('table.columns')->useScript('multiselect');
            $document->getWebAssetManager()->registerAndUseScript(
                'com_breezingformsng.record-flags',
                'media/com_breezingformsng/js/admin/record-flags.js',
                ['version' => 'auto'],
                ['defer' => true],
                ['core']
            );
            $document->addScriptOptions(
                'com_breezingformsng.record-flags',
                ['csrfToken' => \Joomla\CMS\Session\Session::getFormToken()]
            );
            $document->getWebAssetManager()->registerAndUseScript(
                'com_breezingformsng.records-list',
                'media/com_breezingformsng/js/admin/records-list.js',
                ['version' => 'auto'],
                ['defer' => true],
                ['core', 'com_breezingformsng.record-flags']
            );
            Text::script('COM_BREEZINGFORMSNG_AJAX_STATE_ERROR');
        }

        parent::display($tpl);
    }

    private function getRecordsModel(): RecordsModel
    {
        $model = $this->getModel('Records');

        if (!$model instanceof RecordsModel) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }

        return $model;
    }

    private function prepareListData(): void
    {
        $model = $this->getRecordsModel();

        $this->items = $model->getItems() ?: [];
        $this->pagination = $model->getPagination();
        $this->state = $model->getState();
        $this->filterForm = $model->getFilterForm();
        $this->activeFilters = $model->getActiveFilters();
    }

    private function prepareEditData(\Joomla\Input\Input $input): void
    {
        $recordId = $input->getInt('record_id', 0);
        /** @var CMSApplication $app */
        $app = Factory::getApplication();

        if ($recordId < 1) {
            $app->redirect('index.php?option=com_breezingformsng&view=records');
            return;
        }

        $component = $app->bootComponent('com_breezingformsng');

        if (!$component instanceof MVCFactoryServiceInterface) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }

        /** @var RecordModel $model */
        $model = $component->getMVCFactory()->createModel('Record', 'Administrator');

        $this->record = $model->getRecord($recordId);
        if (!$this->record) {
            $app->redirect('index.php?option=com_breezingformsng&view=records');
            return;
        }

        $this->recordRows = $model->getEditableRows(
            $recordId,
            (int) $this->record->form,
            (string) $this->record->name
        );

        $recordsModel = $this->getRecordsModel();
        $this->prevRecordId = $recordsModel->getAdjacentRecordId($recordId, 'prev');
        $this->nextRecordId = $recordsModel->getAdjacentRecordId($recordId, 'next');
    }

    private function prepareListToolbar(): void
    {
        /** @var HtmlDocument $document */
        $document = $this->getDocument();
        $toolbar = $document->getToolbar();

        $exportDropdown = $toolbar->dropdownButton('export-options');

        if (!$exportDropdown instanceof DropdownButton) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }

        $exportDropdown->toggleSplit(false);
        $exportDropdown
            ->text(Text::_('COM_BREEZINGFORMSNG_EXPORT_DOWNLOAD'))
            ->icon('icon-download')
            ->buttonClass('btn btn-action');
        $child = $exportDropdown->getChildToolbar();
        $child->standardButton('exportPdf')->text(Text::_('COM_BREEZINGFORMSNG_PDF'))->task('records.exportPdf')->icon('icon-download')->listCheck(false);
        $child->standardButton('exportCsv')->text(Text::_('COM_BREEZINGFORMSNG_CSV'))->task('records.exportCsv')->icon('icon-download')->listCheck(false);
        $child->standardButton('exportXlsx')->text(Text::_('COM_BREEZINGFORMSNG_XLSX'))->task('records.exportXlsx')->icon('icon-download')->listCheck(false);
        $child->standardButton('exportXml')->text(Text::_('COM_BREEZINGFORMSNG_XML'))->task('records.exportXml')->icon('icon-download')->listCheck(false);

        ToolbarHelper::custom('records.csvImport', 'upload', 'upload', Text::_('COM_BREEZINGFORMSNG_BTN_IMPORT_CSV'), false);

        $markDropdown = $toolbar->dropdownButton('mark-options');

        if (!$markDropdown instanceof DropdownButton) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }

        $markDropdown->toggleSplit(false);
        $markDropdown
            ->text(Text::_('COM_BREEZINGFORMSNG_TOOLBAR_MARK'))
            ->icon('icon-check')
            ->buttonClass('btn btn-action')
            ->listCheck(true);
        $markChild = $markDropdown->getChildToolbar();
        $markChild->standardButton('markViewed')
            ->text(Text::_('COM_BREEZINGFORMSNG_TOOLBAR_MARK_VIEWED'))
            ->task('records.viewed')
            ->icon('icon-eye-open')
            ->attributes(['title' => Text::_('COM_BREEZINGFORMSNG_TOOLBAR_MARK_VIEWED_DESC')])
            ->listCheck(true);
        $markChild->standardButton('unmarkViewed')
            ->text(Text::_('COM_BREEZINGFORMSNG_TOOLBAR_UNMARK_VIEWED'))
            ->task('records.unviewed')
            ->icon('icon-eye-close')
            ->attributes(['title' => Text::_('COM_BREEZINGFORMSNG_TOOLBAR_UNMARK_VIEWED_DESC')])
            ->listCheck(true);
        $markChild->standardButton('markExported')
            ->text(Text::_('COM_BREEZINGFORMSNG_TOOLBAR_MARK_EXPORTED'))
            ->task('records.exported')
            ->icon('icon-share')
            ->attributes(['title' => Text::_('COM_BREEZINGFORMSNG_TOOLBAR_MARK_EXPORTED_DESC')])
            ->listCheck(true);
        $markChild->standardButton('unmarkExported')
            ->text(Text::_('COM_BREEZINGFORMSNG_TOOLBAR_UNMARK_EXPORTED'))
            ->task('records.unexported')
            ->icon('icon-cancel-circle')
            ->attributes(['title' => Text::_('COM_BREEZINGFORMSNG_TOOLBAR_UNMARK_EXPORTED_DESC')])
            ->listCheck(true);

        $stateFilter = (string) $this->state->get('filter.state');
        $trashed = (string) RecordState::TRASHED;
        $archived = (string) RecordState::ARCHIVED;

        if ($this->canEditState) {
            $statusDropdown = $toolbar->dropdownButton('status-group');

            if (!$statusDropdown instanceof DropdownButton) {
                throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
            }

            $statusDropdown->toggleSplit(false);
            $statusDropdown
                ->text('JTOOLBAR_CHANGE_STATUS')
                ->icon('icon-ellipsis-h')
                ->buttonClass('btn btn-action')
                ->listCheck(true);
            $statusChild = $statusDropdown->getChildToolbar();

            if ($stateFilter === $archived) {
                $statusChild->unarchive('records.publish')->listCheck(true);
            } elseif ($stateFilter !== '') {
                $statusChild->publish('records.publish')->listCheck(true);
            }

            if ($stateFilter !== $archived) {
                $statusChild->archive('records.archive')->listCheck(true);
            }

            if ($stateFilter !== $trashed) {
                $statusChild->trash('records.trash')->listCheck(true);
            }
        }

        if ($stateFilter === $trashed && $this->canDelete) {
            $toolbar->delete('records.delete', 'JTOOLBAR_EMPTY_TRASH')
                ->message('COM_BREEZINGFORMSNG_CONFIRM_DELETE_RECORDS')
                ->listCheck(true);
        }

        $user = Factory::getApplication()->getIdentity();

        if ($user->authorise('core.admin', 'com_breezingformsng') || $user->authorise('core.options', 'com_breezingformsng')) {
            ToolbarHelper::preferences('com_breezingformsng');
        }
        $toolbar
            ->popupButton('help', 'JHELP')
            ->popupType('iframe')
            ->url(Uri::base() . 'index.php?option=com_breezingformsng&view=help&section=records&tmpl=component')
            ->icon('icon-question-sign')
            ->iframeWidth(900)
            ->iframeHeight(700)
            ->attributes(['title' => Text::_('COM_BREEZINGFORMSNG_HELP_RECORDS_TITLE')]);
    }

    private function prepareEditToolbar(): void
    {
        /** @var HtmlDocument $document */
        $document = $this->getDocument();
        $toolbar = $document->getToolbar();

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

        $state = (int) ($this->record->state ?? RecordState::PUBLISHED);

        if ($this->canEditState) {
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

        if ($state === RecordState::TRASHED && $this->canDelete) {
            $toolbar->delete('records.delete', 'JTOOLBAR_EMPTY_TRASH')
                ->message('COM_BREEZINGFORMSNG_CONFIRM_DELETE_RECORDS')
                ->listCheck(false);
        }

        ToolbarHelper::custom('records.save', 'save', 'save', Text::_('COM_BREEZINGFORMSNG_TOOLBAR_SAVE'), false);
        ToolbarHelper::cancel('records.cancel', 'JTOOLBAR_CLOSE');

        /** @var CMSApplication $app */
        $app      = Factory::getApplication();
        $document = $app->getDocument();
        $wa       = $document->getWebAssetManager();
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
        $document->addScriptOptions('com_breezingformsng.admin-form', [
            'cancelTask' => 'records.cancel',
            'saveTask'   => 'records.save',
            'validateTask' => 'records.save',
            'exportTasks' => ['records.exportPdf', 'records.exportCsv', 'records.exportXlsx', 'records.exportXml'],
            'discardTasks' => [
                'records.viewed', 'records.unviewed', 'records.exported',
                'records.unexported', 'records.publish', 'records.archive',
                'records.trash', 'records.delete',
            ],
        ]);
        Text::script('COM_BREEZINGFORMSNG_TEST_NO_CHANGES');
        Text::script('COM_BREEZINGFORMSNG_CONFIRM_DISCARD_CHANGES');
        Text::script('COM_BREEZINGFORMSNG_EXPORT_SAVE_FIRST');
    }

    private function prepareImportToolbar(): void
    {
        ToolbarHelper::cancel('records.display', Text::_('COM_BREEZINGFORMSNG_TOOLBAR_CANCEL'));
    }

    protected function getDetailLabel(): ?string
    {
        if ($this->layout === 'edit') {
            $recordId = Factory::getApplication()->getInput()->getInt('record_id', 0);

            return $recordId > 0 ? (string) $recordId : null;
        }

        if ($this->layout === 'csvimport') {
            return Text::_('COM_BREEZINGFORMSNG_BTN_IMPORT_CSV');
        }

        return null;
    }
}
