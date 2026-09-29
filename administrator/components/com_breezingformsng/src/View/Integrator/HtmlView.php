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

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryServiceInterface;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Uri\Uri;
use Vcmb\Component\BreezingformsNG\Administrator\Model\IntegratorModel;

class HtmlView extends \Vcmb\Component\BreezingformsNG\Administrator\View\BreezingformsNG\HtmlView
{
    public array $rules        = [];
    public ?\stdClass $rule    = null;
    public array $items        = [];
    public array $tableNames   = [];
    public array $tableColumns = [];
    public array $forms        = [];
    public array $formElements = [];
    public array $criteria         = [];
    public array $criteriaJoomla   = [];
    public array $criteriaFixed    = [];
    public string $formFilter  = 'all';
    public string $listOrder = 'rules.name';
    public string $listDirn = 'asc';

    public function display($tpl = null): void
    {
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
            $this->prepareList($model, $input);
        }

        parent::display($tpl);
    }

    private function prepareList(IntegratorModel $model, \Joomla\Input\Input $input): void
    {
        $allowedSorts = [
            'rules.name', 'rules.type', 'forms.name',
            'rules.reference_table', 'rules.published',
        ];
        $requestedOrder = $input->getCmd('filter_order', 'rules.name');
        $this->listOrder = in_array($requestedOrder, $allowedSorts, true) ? $requestedOrder : 'rules.name';
        $this->listDirn = strtolower($input->getCmd('filter_order_Dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $this->rules = $model->getRules($this->listOrder, $this->listDirn);
        ToolbarHelper::addNew('integrator.edit');
        ToolbarHelper::deleteList('', 'integrator.remove');
        ToolbarHelper::preferences('com_breezingformsng');
        ToolbarHelper::help(
            'COM_BREEZINGFORMSNG_HELP_INTEGRATOR_TITLE',
            false,
            Uri::base() . 'index.php?option=com_breezingformsng&view=integrator&layout=help&tmpl=component'
        );

        /** @var CMSApplication $app */
        $app      = Factory::getApplication();
        $document = $app->getDocument();
        $wa       = $document->getWebAssetManager();
        $wa->useScript('com_breezingformsng.admin-sort');
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
