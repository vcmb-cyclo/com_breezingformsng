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

namespace Vcmb\Component\BreezingformsNG\Administrator\View\Scripts;

\defined('_JEXEC') or die;

use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;
use Vcmb\Component\BreezingformsNG\Administrator\Model\ScriptsModel;
use Vcmb\Component\BreezingformsNG\Administrator\Service\AreaAccess;
use Vcmb\Component\BreezingformsNG\Administrator\View\BreezingformsNG\HtmlView as BaseHtmlView;

class HtmlView extends BaseHtmlView
{
    /** @var object[] */
    public array $items = [];
    public ?Pagination $pagination = null;
    public ?Registry $state = null;
    public ?Form $filterForm = null;
    public array $activeFilters = [];
    public bool $canEdit = false;

    public function display($tpl = null)
    {
        $model = $this->getModel();

        if (!$model instanceof ScriptsModel) {
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
        $this->canEdit = AreaAccess::isAllowed($user, AreaAccess::CODE);

        if ($this->canEdit) {
            if ($user->authorise('core.create', 'com_breezingformsng')) {
                $toolbar->addNew('script.add');
                $toolbar->standardButton('copy', 'JLIB_HTML_BATCH_COPY', 'scripts.copy')->icon('icon-copy')->listCheck(true);
            }

            if ($user->authorise('core.edit.state', 'com_breezingformsng')) {
                $toolbar->publish('scripts.publish')->listCheck(true);
                $toolbar->unpublish('scripts.unpublish')->listCheck(true);
            } else {
                $this->canEdit = false;
            }

            if ($user->authorise('core.delete', 'com_breezingformsng')) {
                $toolbar->delete('scripts.delete')->message('COM_BREEZINGFORMSNG_SCRIPTS_ASKDELETE')->listCheck(true);
            }
        }

        if ($user->authorise('core.admin', 'com_breezingformsng') || $user->authorise('core.options', 'com_breezingformsng')) {
            $toolbar->preferences('com_breezingformsng');
        }

        ToolbarHelper::help(
            'COM_BREEZINGFORMSNG_HELP_SCRIPTS_TITLE',
            false,
            Uri::base() . 'index.php?option=com_breezingformsng&view=scripts&layout=help&tmpl=component'
        );

        $document->getWebAssetManager()->useScript('table.columns')->useScript('multiselect');
    }
}
