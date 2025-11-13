<?php
/**
 * BreezingForms - A Joomla Forms Application
 * @version 1.9
 * @package BreezingForms
 * @copyright (C) 2008-2020 by Markus Bopp
 * @copyright (C) 2024 by XDA+GIL
 * @license Released under the terms of the GNU General Public License
 **/
defined('_JEXEC') or die('Direct Access to this location is not allowed.');

use Joomla\CMS\Toolbar\ToolbarHelper;

require_once($ff_admpath . '/admin/integrator.class.php');
require_once($ff_admpath . '/admin/integrator.html.php');

$integrator = new BFIntegrator();

switch ($task) {

    case 'add':
    case 'edit':
    case 'save':
    case 'saveCode':
    case 'addItem':
    case 'addCriteria':
    case 'removeCriteria':
    case 'addCriteriaJoomla':
    case 'removeCriteriaJoomla':
    case 'addCriteriaFixed':
    case 'removeCriteriaFixed':
    case 'removeItem':
    case 'saveFinalizeCode':
    case 'pub':
    case 'showPublished':
    case 'showUnpublished':

        if ($task == 'save') {
            $id = $integrator->saveRule();
            BFRequest::setVar('id', $id);
        } else if ($task == 'saveFinalizeCode') {
            $integrator->saveFinalizeCode();
        } else if ($task == 'addItem') {
            $integrator->addItem();
        } else if ($task == 'saveCode') {
            $integrator->saveCode();
        } else if ($task == 'removeItem') {
            $integrator->removeItem();
        } else if ($task == 'addCriteria') {
            $integrator->addCriteria();
        } else if ($task == 'removeCriteria') {
            $integrator->removeCriteria();
        } else if ($task == 'addCriteriaJoomla') {
            $integrator->addCriteriaJoomla();
        } else if ($task == 'removeCriteriaJoomla') {
            $integrator->removeCriteriaJoomla();
        } else if ($task == 'addCriteriaFixed') {
            $integrator->addCriteriaFixed();
        } else if ($task == 'removeCriteriaFixed') {
            $integrator->removeCriteriaFixed();
        } else if ($task == 'pub') {
            if (BFRequest::getVar('pub') == 'publish') {
                $integrator->publishItem();
            } else if (BFRequest::getVar('pub') == 'unpublish') {
                $integrator->unpublishItem();
            }
        }

        $rule = $integrator->getRule(BFRequest::getInt('id', -1));

        if ($rule == null) {
            ToolBarHelper::save('save', 'COM_BREEZINGFORMS_TOOLBAR_SAVE');
        }

        ToolBarHelper::custom('cancel', 'cancel.png', 'cancel_f2.png', BFText::_('COM_BREEZINGFORMS_TOOLBAR_QUICKMODE_CLOSE'), false);


        // CUSTOM
        if ($task == 'showPublished') {
            $formsToLoad = $integrator->getPublishedForms();
            $showType = 'published';
        } else if ($task == 'showUnpublished') {
            $formsToLoad = $integrator->getUnpublishedForms();
            $showType = 'unpublished';
        } else {
            $formsToLoad = $integrator->getForms();
            $showType = 'all';
        }
        // END


        echo BFIntegratorHtml::edit(
            $rule,
            $integrator->getItems(BFRequest::getInt('id', -1)),
            $integrator->getTables(),
            $formsToLoad,
            $integrator->getFormElements($rule != null ? $rule->form_id : -1),
            $integrator->getCriteria(BFRequest::getInt('id', -1)),
            $integrator->getCriteriaJoomla(BFRequest::getInt('id', -1)),
            $integrator->getCriteriaFixed(BFRequest::getInt('id', -1)),
            $showType
        );
        break;

    default:

        if ($task == 'unpublish') {
            $integrator->unpublishRule();
        } else if ($task == 'publish') {
            $integrator->publishRule();
        } else if ($task == 'remove') {
            $integrator->removeRules();
        }

        ToolBarHelper::addNew();
        ToolBarHelper::deleteList();
        echo BFIntegratorHtml::listRules($integrator->getRules());
        break;

}
