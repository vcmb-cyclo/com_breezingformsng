<?php
/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Administrator\View\Forms;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\Registry\Registry;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryServiceInterface;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Toolbar\Button\DropdownButton;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Vcmb\Component\BreezingformsNG\Administrator\Helper\FormsAdvancedOptionsHtml;
use Vcmb\Component\BreezingformsNG\Administrator\Model\FormOptionsModel;
use Vcmb\Component\BreezingformsNG\Administrator\Model\FormsModel;
use Vcmb\Component\BreezingformsNG\Administrator\Model\PackageFilter;

class HtmlView extends \Vcmb\Component\BreezingformsNG\Administrator\View\BreezingformsNG\HtmlView
{
    /** @var list<\stdClass> */
    public array $items = [];
    public Pagination $pagination;
    public Registry $state;
    public ?Form $filterForm = null;
    /** @var array<string, mixed> */
    public array $activeFilters = [];
    public string $pkg = '';
    public ?\stdClass $form     = null;
    /** @var list<\stdClass> */
    public array      $initScripts = [];
    /** @var list<\stdClass> */
    public array      $submittedScripts = [];
    /** @var list<\stdClass> */
    public array      $pieceBefore = [];
    /** @var list<\stdClass> */
    public array      $pieceAfter  = [];
    /** @var list<\stdClass> */
    public array      $pieceBeginSubmit = [];
    /** @var list<\stdClass> */
    public array      $pieceEndSubmit   = [];

    public function display($tpl = null): void
    {
        $this->getDocument()->getWebAssetManager()->useStyle('com_breezingformsng.list-filters');
        /** @var CMSApplication $app */
        $app     = Factory::getApplication();
        $input   = $app->getInput();
        $layout  = $input->getCmd('layout', 'default');
        $component = $app->bootComponent('com_breezingformsng');

        if (!$component instanceof MVCFactoryServiceInterface) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }

        $factory = $component->getMVCFactory();

        /** @var FormsModel $listModel */
        $listModel = $factory->createModel('Forms', 'Administrator', ['ignore_request' => true]);
        /** @var FormOptionsModel $formModel */
        $formModel = $factory->createModel('FormOptions', 'Administrator', ['ignore_request' => true]);

        if ($layout === 'edit') {

            $input->set('hidemainmenu', 1);

        }

        if ($layout === 'edit') {
            // edit.php's Init/Submitted script and Before/After/Begin/End
            // Submit piece pickers call bfToggle() inline via onchange to
            // swap which code editor is visible - defined here, not in the
            // list-only branch below.
            $document = $this->getDocument();
            $wa = $document->getWebAssetManager();
            $wa->registerAndUseStyle(
                'com_breezingformsng.forms-edit',
                'media/com_breezingformsng/css/admin/forms-edit.css',
                ['version' => 'auto']
            );
            $wa->registerAndUseScript(
                'com_breezingformsng.admin-form',
                'media/com_breezingformsng/js/admin/admin-form.js',
                ['version' => 'auto'],
                ['defer' => true],
                ['core']
            );
        }

        if ($layout === 'edit') {
            $id         = $input->getInt('id', 0);
            $this->pkg  = $input->getString('pkg', '');
            $this->form = $id > 0 ? $formModel->getForm($id) : $formModel->getDefaultForm($this->pkg);

            if ($this->form === null) {
                $app->enqueueMessage(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'), 'error');
                $app->redirect('index.php?option=com_breezingformsng&view=forms');
                return;
            }

            $this->initScripts       = $listModel->getScripts('Form Init');
            $this->submittedScripts  = $listModel->getScripts('Form Submitted');
            $this->pieceBefore       = $listModel->getPieces('Before Form');
            $this->pieceAfter        = $listModel->getPieces('After Form');
            $this->pieceBeginSubmit  = $listModel->getPieces('Begin Submit');
            $this->pieceEndSubmit    = $listModel->getPieces('End Submit');

            if (FormsAdvancedOptionsHtml::missingPieces($this->form, [
                1 => $this->pieceBefore, 2 => $this->pieceAfter, 3 => $this->pieceBeginSubmit, 4 => $this->pieceEndSubmit,
            ]) !== []) {
                $app->enqueueMessage(Text::_('COM_BREEZINGFORMSNG_FORMS_PIECE_MISSING_MESSAGE'), 'error');
            }

            ToolbarHelper::apply('forms.save');
            ToolbarHelper::cancel('forms.cancel', $id > 0 ? 'JTOOLBAR_CLOSE' : 'JTOOLBAR_CANCEL');
        } else {
            $document = $this->getDocument();
            $wa       = $document->getWebAssetManager();
            // Not deferred: it must run before the table.columns module reads the state.
            $wa->registerAndUseScript(
                'com_breezingformsng.forms-columns',
                'media/com_breezingformsng/js/admin/forms-columns.js'
            );
            $wa->useScript('table.columns')->useScript('multiselect');
            $wa->registerAndUseScript(
                'com_breezingformsng.admin-form',
                'media/com_breezingformsng/js/admin/admin-form.js',
                ['version' => 'auto'],
                ['defer' => true],
                ['core']
            );

            $wa->registerAndUseScript(
                'com_breezingformsng.admin-toggle-published',
                'media/com_breezingformsng/js/admin/admin-toggle-published.js',
                ['version' => 'auto'],
                ['defer' => true],
                ['core']
            );
            $document->addScriptOptions(
                'com_breezingformsng.admin-toggle-published',
                ['csrfToken' => Session::getFormToken()]
            );
            Text::script('JPUBLISHED');
            Text::script('JUNPUBLISHED');
            Text::script('COM_BREEZINGFORMSNG_DEBUG_MODE_ENABLED');
            Text::script('COM_BREEZINGFORMSNG_DEBUG_MODE_DISABLED');
            Text::script('COM_BREEZINGFORMSNG_AJAX_STATE_ERROR');

            $model = $this->getModel();

            if (!$model instanceof FormsModel) {
                throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
            }

            $this->items = $model->getItems() ?: [];
            $this->pagination = $model->getPagination();
            $this->state = $model->getState();
            $this->filterForm = $model->getFilterForm();
            $this->activeFilters = $model->getActiveFilters();
            $filterPackage = (string) $this->state->get('filter.package');
            $this->pkg = PackageFilter::selectedPackage($filterPackage);

            ToolbarHelper::addNew('forms.edit');
            ToolbarHelper::custom('forms.copy', 'copy', '', 'JLIB_HTML_BATCH_COPY', true);
            // Only a form that is not in QuickMode format can be converted.
            $hasLegacyForms = array_filter($this->items, static fn (\stdClass $item): bool => !(int) ($item->is_quickmode ?? 0)) !== [];

            if ($hasLegacyForms
                && $this->getCurrentUser()->authorise('core.create', 'com_breezingformsng')
                && $this->getCurrentUser()->authorise('core.edit', 'com_breezingformsng')) {
                ToolbarHelper::custom('forms.convertLegacy', 'copy', '', 'COM_BREEZINGFORMSNG_FORMS_CONVERT_LEGACY', true);
            }
            $user = $this->getCurrentUser();

            $this->addActionsDropdown(
                $user->authorise('core.edit.state', 'com_breezingformsng'),
                $user->authorise('core.edit', 'com_breezingformsng')
            );
            ToolbarHelper::checkin('forms.checkin');

            if ((string) $this->state->get('filter.published') === '-2') {
                // In the trash, Publish and Unpublish restore.
                if ($user->authorise('core.delete', 'com_breezingformsng')) {
                    ToolbarHelper::deleteList('JGLOBAL_CONFIRM_DELETE', 'forms.delete', 'JTOOLBAR_EMPTY_TRASH');
                }
            } elseif ($user->authorise('core.edit.state', 'com_breezingformsng')) {
                ToolbarHelper::trash('forms.trash');
            }

            if ($user->authorise('core.admin', 'com_breezingformsng') || $user->authorise('core.options', 'com_breezingformsng')) {
                ToolbarHelper::preferences('com_breezingformsng');
            }
        }

        parent::display($tpl);
    }

    /** The "Actions" menu of the checked forms: publication and debug mode. */
    private function addActionsDropdown(bool $canEditState, bool $canEdit): void
    {
        if (!$canEditState && !$canEdit) {
            return;
        }

        /** @var HtmlDocument $document */
        $document = $this->getDocument();
        $toolbar = $document->getToolbar() ?? throw new \RuntimeException('The toolbar is not available.');
        $dropdown = $toolbar->dropdownButton('forms-status-group');

        if (!$dropdown instanceof DropdownButton) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }

        $dropdown->toggleSplit(false);
        $dropdown->text(Text::_('COM_BREEZINGFORMSNG_TOOLBAR_ACTIONS'))
            ->icon('fa-solid fa-ellipsis')
            ->buttonClass('btn btn-action')
            ->listCheck(true);
        $actions = $dropdown->getChildToolbar();

        if ($canEditState) {
            $actions->publish('forms.publish')->icon('fa-solid fa-check text-success')->listCheck(true);
            $actions->unpublish('forms.unpublish')->icon('fa-solid fa-circle-xmark text-danger')->listCheck(true);
        }

        if ($canEdit) {
            $actions->standardButton('debug_on', 'COM_BREEZINGFORMSNG_FORMS_DEBUG_ON', 'forms.debugOn')
                ->icon('fa-solid fa-bug text-success')
                ->listCheck(true);
            $actions->standardButton('debug_off', 'COM_BREEZINGFORMSNG_FORMS_DEBUG_OFF', 'forms.debugOff')
                ->icon('fa-solid fa-bug text-danger')
                ->listCheck(true);
        }
    }

    protected function getDetailLabel(): ?string
    {
        if ($this->form === null) {
            return null;
        }

        $title = trim((string) $this->form->title);

        return $title !== '' ? $title : Text::_('COM_BREEZINGFORMSNG_INSTALLER_UNKNOWN');
    }
}
