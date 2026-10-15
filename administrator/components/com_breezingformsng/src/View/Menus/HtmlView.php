<?php

/**
 * BreezingForms NG - A Joomla Forms Application
 *
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 **/

namespace Vcmb\Component\BreezingformsNG\Administrator\View\Menus;

\defined('_JEXEC') or die;

use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Pagination\Pagination;
use Joomla\Registry\Registry;
use Vcmb\Component\BreezingformsNG\Administrator\Model\MenusModel;
use Vcmb\Component\BreezingformsNG\Administrator\View\BreezingformsNG\HtmlView as BaseHtmlView;

class HtmlView extends BaseHtmlView
{
    /** @var object[] */
    public array $items = [];
    public ?Pagination $pagination = null;
    public ?Registry $state = null;
    public ?Form $filterForm = null;
    /** @var array<string, mixed> */
    public array $activeFilters = [];
    public bool $canEditState = false;
    public bool $canOpen = false;

    public function display($tpl = null)
    {
        $model = $this->getModel();

        if (!$model instanceof MenusModel) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }

        $this->items = $model->getItems() ?: [];
        $this->pagination = $model->getPagination();
        $this->state = $model->getState();
        $this->filterForm = $model->getFilterForm();
        $this->activeFilters = $model->getActiveFilters();

        $this->addToolbar();

        parent::display($tpl);
    }

    private function addToolbar(): void
    {
        /** @var HtmlDocument $document */
        $document = $this->getDocument();
        $toolbar = $document->getToolbar();
        $user = Factory::getApplication()->getIdentity();
        $this->canEditState = $user->authorise('core.edit.state', 'com_breezingformsng');
        $this->canOpen = $user->authorise('core.edit', 'com_breezingformsng');

        if ($user->authorise('core.create', 'com_breezingformsng')) {
            $toolbar->addNew('menu.add');
            $toolbar->standardButton('copy', 'JLIB_HTML_BATCH_COPY', 'menus.copy')->icon('icon-copy')->listCheck(true);
        }

        if ($this->canEditState) {
            $toolbar->publish('menus.publish')->listCheck(true);
            $toolbar->unpublish('menus.unpublish')->listCheck(true);
        }

        if ($user->authorise('core.delete', 'com_breezingformsng')) {
            $toolbar->delete('menus.delete')->message('JGLOBAL_CONFIRM_DELETE')->listCheck(true);
        }

        $toolbar->standardButton('sync', 'COM_BREEZINGFORMSNG_MENUS_SYNC', 'menus.sync')->icon('icon-refresh')->listCheck(false);

        $document->getWebAssetManager()->useScript('table.columns')->useScript('multiselect');
    }
}
