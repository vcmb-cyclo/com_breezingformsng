<?php
/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 *
 * Source history: admin/integrator.html.php (git mv — Phase 3).
 */

namespace Vcmb\Component\BreezingformsNG\Administrator\View\Integrator;

\defined('_JEXEC') or die;

use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryServiceInterface;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Pagination\Pagination;
use Joomla\Registry\Registry;
use Vcmb\Component\BreezingformsNG\Administrator\Model\IntegratorModel;
use Vcmb\Component\BreezingformsNG\Administrator\Model\IntegratorRulesModel;
use Vcmb\Component\BreezingformsNG\Administrator\Service\AreaAccess;

class HtmlView extends \Vcmb\Component\BreezingformsNG\Administrator\View\BreezingformsNG\HtmlView
{
    /** @var list<\stdClass> */
    public array $rules = [];
    public Pagination $pagination;
    public Registry $state;
    public ?Form $filterForm = null;
    /** @var array<string, mixed> */
    public array $activeFilters = [];
    public ?\stdClass $rule    = null;
    /** @var list<\stdClass> */
    public array $items        = [];
    /** @var string[] */
    public array $tableNames   = [];
    /** @var array<string, mixed> */
    public array $tableColumns = [];
    /** @var list<\stdClass> */
    public array $forms        = [];
    /** @var list<\stdClass> */
    public array $formElements = [];
    /** @var list<\stdClass> */
    public array $criteria         = [];
    /** @var list<\stdClass> */
    public array $criteriaJoomla   = [];
    /** @var list<\stdClass> */
    public array $criteriaFixed    = [];
    public string $formFilter  = 'all';
    public function display($tpl = null): void
    {
        AreaAccess::assert($this->getCurrentUser(), AreaAccess::CODE);

        $input  = Factory::getApplication()->getInput();
        $layout = $input->getCmd('layout', 'default');
        $model  = $this->getIntegratorModel();

        if ($layout === 'edit') {

            $input->set('hidemainmenu', 1);

        }


        if ($layout === 'edit') {
            $this->prepareEdit($model, $input);
        } elseif ($layout === 'help') {
            // The help layout is self-contained.
        } else {
            $this->prepareList();
        }

        parent::display($tpl);
    }

    private function prepareList(): void
    {
        $component = Factory::getApplication()->bootComponent('com_breezingformsng');

        if (!$component instanceof MVCFactoryServiceInterface) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }

        $list = $component->getMVCFactory()->createModel('IntegratorRules', 'Administrator');

        if (!$list instanceof IntegratorRulesModel) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }

        $this->rules = $list->getItems() ?: [];
        $this->pagination = $list->getPagination();
        $this->state = $list->getState();
        $this->filterForm = $list->getFilterForm();
        $this->activeFilters = $list->getActiveFilters();

        ToolbarHelper::addNew('integrator.edit');
        ToolbarHelper::deleteList('', 'integrator.remove');
        $user = $this->getCurrentUser();

        if ($user->authorise('core.admin', 'com_breezingformsng') || $user->authorise('core.options', 'com_breezingformsng')) {
            ToolbarHelper::preferences('com_breezingformsng');
        }
        ToolbarHelper::help(
            'COM_BREEZINGFORMSNG_HELP_INTEGRATOR_TITLE',
            false,
            Uri::base() . 'index.php?option=com_breezingformsng&view=integrator&layout=help&tmpl=component'
        );

        $document = $this->getDocument();
        $wa       = $document->getWebAssetManager();
        $wa->useScript('table.columns')->useScript('multiselect');
        $wa->registerAndUseScript(
            'com_breezingformsng.admin-form',
            'media/com_breezingformsng/js/admin/admin-form.js',
            ['version' => 'auto'],
            ['defer' => true],
            ['core']
        );
        $document->addScriptOptions('com_breezingformsng.admin-form', ['confirmDeleteTask' => 'integrator.remove']);
        Text::script('JGLOBAL_CONFIRM_DELETE');

        $wa->registerAndUseScript(
            'com_breezingformsng.admin-toggle-published',
            'media/com_breezingformsng/js/admin/admin-toggle-published.js',
            ['version' => 'auto'],
            ['defer' => true],
            ['core']
        );
        $document->addScriptOptions('com_breezingformsng.admin-toggle-published', ['csrfToken' => Session::getFormToken()]);
        Text::script('JPUBLISHED');
        Text::script('JUNPUBLISHED');
    }

    private function prepareEdit(IntegratorModel $model, \Joomla\Input\Input $input): void
    {
        $id         = $input->getInt('id', 0);
        $this->rule = $id > 0 ? $model->getRule($id) : null;

        $this->formFilter  = $input->getCmd('formfilter', 'all');
        $this->forms       = $model->getForms($this->formFilter);
        $this->tableNames  = $model->getTableNames();

        if ($this->rule !== null) {
            $this->items          = $model->getItems($id);
            $this->tableColumns   = $model->getTableColumns($this->rule->reference_table);
            $this->formElements   = $model->getFormElements((int) $this->rule->form_id);
            $this->criteria       = $model->getCriteria($id);
            $this->criteriaJoomla = $model->getCriteriaJoomla($id);
            $this->criteriaFixed  = $model->getCriteriaFixed($id);
        }

        if ($id === 0) {
            ToolbarHelper::save('integrator.save');
        }
        ToolbarHelper::cancel('integrator.display', 'JTOOLBAR_CLOSE');

        if ($this->rule !== null) {
            /** @var HtmlDocument $document */
            $document = $this->getDocument();
            $toolbar = $document->getToolbar() ?? throw new \RuntimeException('The toolbar is not available.');
            $this->addItemPagination($toolbar, 'IntegratorRules', $id, 'integrator.navigate');
        }
    }

    protected function getDetailLabel(): ?string
    {
        if ($this->rule === null) {
            return null;
        }

        $name = trim((string) $this->rule->name);

        return $name !== '' ? $name : Text::_('COM_BREEZINGFORMSNG_INSTALLER_UNKNOWN');
    }

    private function getIntegratorModel(): IntegratorModel
    {
        $component = Factory::getApplication()->bootComponent('com_breezingformsng');

        if (!$component instanceof MVCFactoryServiceInterface) {
            throw new \RuntimeException('IntegratorModel not found');
        }

        $model = $component->getMVCFactory()
            ->createModel('Integrator', 'Administrator');

        if (!$model instanceof IntegratorModel) {
            throw new \RuntimeException('IntegratorModel not found');
        }

        return $model;
    }
}
