<?php
/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\MVC\Factory\MVCFactoryServiceInterface;
use Vcmb\Component\BreezingformsNG\Administrator\Model\IntegratorModel;
use Vcmb\Component\BreezingformsNG\Administrator\Service\AreaAccess;

/** @property CMSApplication $app */
class IntegratorController extends BaseController
{
    public function execute($task)
    {
        AreaAccess::assert($this->app->getIdentity(), AreaAccess::CODE);

        return parent::execute($task);
    }

    /**
     * @param array<string, string> $urlparams
     */
    public function display($cachable = false, $urlparams = [])
    {
        $input = $this->app->getInput();
        $input->set('view', 'integrator');
        $input->set('layout', 'default');
        return parent::display($cachable, $urlparams);
    }

    public function edit(): void
    {
        $input = $this->app->getInput();
        $input->set('view', 'integrator');
        $input->set('layout', 'edit');
        parent::display();
    }

    public function save(): void
    {
        $this->checkToken();
        $input  = $this->app->getInput();
        $model  = $this->getIntegratorModel();
        $name   = $input->getString('rule_name', '');
        $formId = $input->getInt('form_id', 0);
        $table  = $input->getString('reference_table', '');
        $type   = $input->getString('type', 'insert');

        $id = $model->saveRule($name, $formId, $table, $type);
        $this->setRedirect($this->editUrl($id));
    }

    public function remove(): void
    {
        $this->checkToken();
        $ids = (array) $this->app->getInput()->get('cid', [], 'ARRAY');
        $this->getIntegratorModel()->deleteRules($ids);
        $this->setRedirect($this->listUrl());
    }

    public function setPublished(): void
    {
        $this->checkToken();

        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        $input = $this->app->getInput();
        $id    = $input->getInt('id', 0);
        $state = $input->getInt('state', 0);
        if ($id > 0) {
            $this->getIntegratorModel()->publishRule($id, $state);
        }
        $this->app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
        $this->app->setBody(json_encode(['Result' => 'OK'], JSON_THROW_ON_ERROR));
        $this->app->close();
    }

    public function publish(): void
    {
        $this->checkToken();
        $id = $this->app->getInput()->getInt('publish_id', 0);
        $this->getIntegratorModel()->publishRule($id, 1);
        $this->setRedirect($this->listUrl());
    }

    public function unpublish(): void
    {
        $this->checkToken();
        $id = $this->app->getInput()->getInt('publish_id', 0);
        $this->getIntegratorModel()->publishRule($id, 0);
        $this->setRedirect($this->listUrl());
    }

    public function addItem(): void
    {
        $this->checkToken();
        $input  = $this->app->getInput();
        $ruleId = $input->getInt('id', 0);
        $model  = $this->getIntegratorModel();
        $model->addItem(
            $ruleId,
            $input->getInt('element_id', 0),
            $input->getString('reference_column', '')
        );
        $this->setRedirect($this->editUrl($ruleId));
    }

    public function removeItem(): void
    {
        $this->checkToken();

        $input  = $this->app->getInput();
        $ruleId = $input->getInt('id', 0);
        $this->getIntegratorModel()->removeItem($input->getInt('itemId', 0));
        $this->setRedirect($this->editUrl($ruleId));
    }

    public function saveCode(): void
    {
        $this->checkToken();
        $input  = $this->app->getInput();
        $ruleId = $input->getInt('id', 0);
        $this->getIntegratorModel()->saveCode(
            $input->getInt('itemId', 0),
            $ruleId,
            $input->post->getRaw('code') ?? ''
        );
        $this->setRedirect($this->editUrl($ruleId));
    }

    public function saveFinalizeCode(): void
    {
        $this->checkToken();
        $input  = $this->app->getInput();
        $ruleId = $input->getInt('id', 0);
        $this->getIntegratorModel()->saveFinalizeCode(
            $ruleId,
            $input->post->getRaw('finalizeCode') ?? ''
        );
        $this->setRedirect($this->editUrl($ruleId));
    }

    public function addCriteria(): void
    {
        $this->checkToken();
        $input  = $this->app->getInput();
        $ruleId = $input->getInt('id', 0);
        $this->getIntegratorModel()->addCriteria(
            $ruleId,
            $input->getString('operator', '='),
            $input->getString('reference_column', ''),
            $input->getInt('element_id', 0),
            $input->getString('andor', 'AND')
        );
        $this->setRedirect($this->editUrl($ruleId));
    }

    public function removeCriteria(): void
    {
        $this->checkToken();

        $input  = $this->app->getInput();
        $ruleId = $input->getInt('id', 0);
        $this->getIntegratorModel()->removeCriteria($input->getInt('criteriaId', 0));
        $this->setRedirect($this->editUrl($ruleId));
    }

    public function addCriteriaJoomla(): void
    {
        $this->checkToken();
        $input  = $this->app->getInput();
        $ruleId = $input->getInt('id', 0);
        $this->getIntegratorModel()->addCriteriaJoomla(
            $ruleId,
            $input->getString('operator', '='),
            $input->getString('reference_column', ''),
            $input->getString('joomla_object', 'Userid'),
            $input->getString('andor', 'AND')
        );
        $this->setRedirect($this->editUrl($ruleId));
    }

    public function removeCriteriaJoomla(): void
    {
        $this->checkToken();

        $input  = $this->app->getInput();
        $ruleId = $input->getInt('id', 0);
        $this->getIntegratorModel()->removeCriteriaJoomla($input->getInt('criteriaId', 0));
        $this->setRedirect($this->editUrl($ruleId));
    }

    public function addCriteriaFixed(): void
    {
        $this->checkToken();
        $input  = $this->app->getInput();
        $ruleId = $input->getInt('id', 0);
        $this->getIntegratorModel()->addCriteriaFixed(
            $ruleId,
            $input->getString('operator', '='),
            $input->getString('reference_column', ''),
            $input->getString('fixed_value', ''),
            $input->getString('andor', 'AND')
        );
        $this->setRedirect($this->editUrl($ruleId));
    }

    public function removeCriteriaFixed(): void
    {
        $this->checkToken();

        $input  = $this->app->getInput();
        $ruleId = $input->getInt('id', 0);
        $this->getIntegratorModel()->removeCriteriaFixed($input->getInt('criteriaId', 0));
        $this->setRedirect($this->editUrl($ruleId));
    }

    public function publishItem(): void
    {
        $this->checkToken();
        $input  = $this->app->getInput();
        $ruleId = $input->getInt('id', 0);
        $this->getIntegratorModel()->publishItem($input->getInt('publish_id', 0), 1);
        $this->setRedirect($this->editUrl($ruleId));
    }

    public function unpublishItem(): void
    {
        $this->checkToken();
        $input  = $this->app->getInput();
        $ruleId = $input->getInt('id', 0);
        $this->getIntegratorModel()->publishItem($input->getInt('publish_id', 0), 0);
        $this->setRedirect($this->editUrl($ruleId));
    }

    private function editUrl(int $id): string
    {
        return 'index.php?option=com_breezingformsng&view=integrator&layout=edit&id=' . $id;
    }

    private function listUrl(): string
    {
        return 'index.php?option=com_breezingformsng&view=integrator';
    }

    private function getIntegratorModel(): IntegratorModel
    {
        $component = $this->app->bootComponent('com_breezingformsng');

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
