<?php
/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Administrator\View\Records;

\defined('_JEXEC') or die;

use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\Button\DropdownButton;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;
use Vcmb\Component\BreezingformsNG\Administrator\Model\RecordsModel;
use Vcmb\Component\BreezingformsNG\Administrator\Service\RecordState;
use Vcmb\Component\BreezingformsNG\Administrator\View\BreezingformsNG\HtmlView as BaseHtmlView;

class HtmlView extends BaseHtmlView
{
    /** @var list<\stdClass> */
    public array $items = [];
    public Pagination $pagination;
    public Registry $state;
    public ?Form $filterForm = null;
    /** @var array<string, mixed> */
    public array $activeFilters = [];

    public bool $canOpen = false;

    private bool $canEditState = false;
    private bool $canDelete = false;

    public function display($tpl = null)
    {
        $this->getDocument()->getWebAssetManager()->useScript('keepalive');

        $user = $this->getCurrentUser();
        $this->canEditState = $user->authorise('core.edit.state', 'com_breezingformsng');
        $this->canDelete = $user->authorise('core.delete', 'com_breezingformsng');
        $this->canOpen = $user->authorise('core.edit', 'com_breezingformsng');

        $this->prepareListData();
        $this->prepareListToolbar();

        $document = $this->getDocument();
        // Not deferred: it must run before the table.columns module reads the state.
        $document->getWebAssetManager()->registerAndUseScript(
            'com_breezingformsng.records-columns',
            'media/com_breezingformsng/js/admin/records-columns.js'
        );
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

    private function prepareListToolbar(): void
    {
        /** @var HtmlDocument $document */
        $document = $this->getDocument();
        $toolbar = $document->getToolbar() ?? throw new \RuntimeException('The toolbar is not available.');
        $user = $this->getCurrentUser();

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

        if ($user->authorise('core.create', 'com_breezingformsng')) {
            $toolbar->linkButton('import', 'COM_BREEZINGFORMSNG_BTN_IMPORT_CSV')
                ->url(Route::_('index.php?option=com_breezingformsng&view=recordsimport'))
                ->icon('icon-upload');
        }

        if ($this->canEditState) {
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
        }

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
            } elseif ($stateFilter === $trashed || $stateFilter === RecordState::FILTER_ALL) {
                $statusChild->publish('records.publish', 'COM_BREEZINGFORMSNG_RECORDS_TOOLBAR_RESTORE')->listCheck(true);
            }

            if ($stateFilter !== $archived) {
                $statusChild->archive('records.archive')->listCheck(true);
            }

            if ($stateFilter !== $trashed) {
                $statusChild->trash('records.trash')->listCheck(true);
            }

            $statusChild->checkin('records.checkin')->listCheck(true);
        }

        // As in Joomla, emptying the trash deletes the checked records, which
        // the model accepts only in the trash: with every state listed, the
        // button shows as soon as the page holds a trashed record.
        $listsTrashed = $stateFilter === $trashed || ($stateFilter === RecordState::FILTER_ALL
            && in_array(RecordState::TRASHED, array_map(static fn (\stdClass $item): int => (int) $item->state, $this->items), true));

        if ($listsTrashed && $this->canDelete) {
            $toolbar->delete('records.delete', 'JTOOLBAR_EMPTY_TRASH')
                ->message('COM_BREEZINGFORMSNG_CONFIRM_DELETE_RECORDS')
                ->listCheck(true);
        }

        if ($user->authorise('core.admin', 'com_breezingformsng') || $user->authorise('core.options', 'com_breezingformsng')) {
            $toolbar->preferences('com_breezingformsng');
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
}
