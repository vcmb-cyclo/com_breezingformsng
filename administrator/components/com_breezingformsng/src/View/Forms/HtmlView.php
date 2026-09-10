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
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryServiceInterface;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Session\SessionInterface;
use Vcmb\Component\BreezingformsNG\Administrator\Model\FormModel;
use Vcmb\Component\BreezingformsNG\Administrator\Model\FormsModel;

class HtmlView extends \Vcmb\Component\BreezingformsNG\Administrator\View\BreezingformsNG\HtmlView
{
    public array      $items    = [];
    public array      $packages = [];
    public int        $total    = 0;
    public string     $pkg      = '';
    public string     $search   = '';
    public string     $listOrder= 'ordering';
    public string     $listDirn = 'asc';
    public string     $filterState = '';
    public int        $limit    = 20;
    public int        $limitStart = 0;
    public ?\stdClass $form     = null;
    public array      $initScripts = [];
    public array      $submittedScripts = [];
    public array      $pieceBefore = [];
    public array      $pieceAfter  = [];
    public array      $pieceBeginSubmit = [];
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
        /** @var FormModel $formModel */
        $formModel = $factory->createModel('Form', 'Administrator', ['ignore_request' => true]);

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

            ToolbarHelper::apply('forms.save');
            ToolbarHelper::cancel('forms.cancel', $id > 0 ? 'JTOOLBAR_CLOSE' : 'JTOOLBAR_CANCEL');
        } else {
            $document = $app->getDocument();
            $wa       = $document->getWebAssetManager();
            $wa->registerAndUseScript(
                'com_breezingformsng.admin-sort',
                'media/com_breezingformsng/js/admin/admin-sort.js',
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
            $document->addScriptOptions('com_breezingformsng.admin-form', ['confirmDeleteTask' => 'forms.remove']);
            Text::script('JGLOBAL_CONFIRM_DELETE');

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

            $session = $app->getSession();
            $pkgIn   = $input->getString('pkg', '__unset__');
            $this->pkg   = $this->resolvePackage($pkgIn, $listModel, $session);

            $searchReq = $input->getString('search', '__unset__');
            if ($searchReq === '__unset__') {
                $this->search = (string) $session->get('bf.forms_search', '');
            } else {
                $this->search = trim($searchReq);
                $session->set('bf.forms_search', $this->search);
            }

            $filterStateIn = $input->getString('filter_state', '__unset__');
            if ($filterStateIn !== '__unset__') {
                $this->filterState = in_array(strtoupper($filterStateIn), ['P', 'U'], true)
                    ? strtoupper($filterStateIn) : '';
                $session->set('bf.forms_filter_state', $this->filterState);
            } else {
                $this->filterState = (string) $session->get('bf.forms_filter_state', '');
            }

            $sortIn = $input->getString('filter_order', '__unset__');
            if ($sortIn !== '__unset__') {
                $this->listOrder = $sortIn;
                $session->set('bf.forms_sort', $sortIn);
            } else {
                $this->listOrder = (string) $session->get('bf.forms_sort', 'ordering');
            }

            $dirIn = strtolower($input->getString('filter_order_Dir', '__unset__'));
            if ($dirIn !== '__unset__') {
                $this->listDirn = $dirIn === 'desc' ? 'desc' : 'asc';
                $session->set('bf.forms_dir', $this->listDirn);
            } else {
                $this->listDirn = strtolower((string) $session->get('bf.forms_dir', 'asc'));
            }

            $this->limit      = (int) $app->getUserStateFromRequest(
                'global.list.limit', 'limit', (int) $app->get('list_limit'), 'int'
            );
            $this->limitStart = $input->getInt('limitstart', 0);
            $this->total      = $listModel->getTotal($this->pkg, $this->search, $this->filterState);
            $this->items      = $listModel->getItems($this->pkg, $this->search, $this->filterState, $this->listOrder, $this->listDirn, $this->limit, $this->limitStart);
            $this->packages   = $listModel->getPackages();

            ToolbarHelper::addNew('forms.edit');
            ToolbarHelper::custom('forms.copy', 'copy', '', 'JLIB_HTML_BATCH_COPY', true);
            ToolbarHelper::publish('forms.publish', 'JTOOLBAR_PUBLISH', true);
            ToolbarHelper::unpublish('forms.unpublish', 'JTOOLBAR_UNPUBLISH', true);
            ToolbarHelper::deleteList('JGLOBAL_CONFIRM_DELETE', 'forms.remove');
        }

        parent::display($tpl);
    }

    private function resolvePackage(string $package, FormsModel $model, SessionInterface $session): string
    {
        $packages = $model->getPackages();

        if ($package === '__unset__') {
            $package = (string) $session->get('bf.forms_pkg', '');
        } elseif ($package === '- blank -') {
            $package = '';
        }

        if ($package !== '' && !in_array($package, $packages, true)) {
            $package = $packages[0] ?? '';
        }

        $session->set('bf.forms_pkg', $package);

        return $package;
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
