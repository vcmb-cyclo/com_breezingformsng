<?php
/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Administrator\View\Forms;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\Registry\Registry;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryServiceInterface;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Vcmb\Component\BreezingformsNG\Administrator\Helper\FormsAdvancedOptionsHtml;
use Vcmb\Component\BreezingformsNG\Administrator\Model\FormOptionsModel;
use Vcmb\Component\BreezingformsNG\Administrator\Model\FormsModel;

class HtmlView extends \Vcmb\Component\BreezingformsNG\Administrator\View\BreezingformsNG\HtmlView
{
    /** @var object[] */
    public array $items = [];
    public ?Pagination $pagination = null;
    public ?Registry $state = null;
    public ?Form $filterForm = null;
    /** @var array<string, mixed> */
    public array $activeFilters = [];
    public string $pkg = '';
    public ?\stdClass $form     = null;
    /** @var list<object> */
    public array      $initScripts = [];
    /** @var list<object> */
    public array      $submittedScripts = [];
    /** @var list<object> */
    public array      $pieceBefore = [];
    /** @var list<object> */
    public array      $pieceAfter  = [];
    /** @var list<object> */
    public array      $pieceBeginSubmit = [];
    /** @var list<object> */
    public array      $pieceEndSubmit   = [];

    public function display($tpl = null): void
    {
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
            $document = $app->getDocument();
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
            $document = $app->getDocument();
            $wa       = $document->getWebAssetManager();
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
            $this->pkg = (string) $this->state->get('filter.package');

            ToolbarHelper::addNew('forms.edit');
            ToolbarHelper::custom('forms.copy', 'copy', '', 'JLIB_HTML_BATCH_COPY', true);
            ToolbarHelper::publish('forms.publish', 'JTOOLBAR_PUBLISH', true);
            ToolbarHelper::unpublish('forms.unpublish', 'JTOOLBAR_UNPUBLISH', true);
            ToolbarHelper::checkin('forms.checkin');
            ToolbarHelper::deleteList('JGLOBAL_CONFIRM_DELETE', 'forms.delete');
            $user = Factory::getApplication()->getIdentity();

            if ($user->authorise('core.admin', 'com_breezingformsng') || $user->authorise('core.options', 'com_breezingformsng')) {
                ToolbarHelper::preferences('com_breezingformsng');
            }
        }

        parent::display($tpl);
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
