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

namespace Vcmb\Component\BreezingformsNG\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Router\Route;
use Joomla\Utilities\ArrayHelper;
use Vcmb\Component\BreezingformsNG\Administrator\Model\MenuModel;

/**
 * @property CMSApplication $app
 */
class MenusController extends AdminController
{
    protected $text_prefix = 'COM_BREEZINGFORMSNG';

    /**
     * @param array<string, mixed> $config
     */
    public function getModel($name = 'Menu', $prefix = 'Administrator', $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }

    public function copy(): void
    {
        $this->checkToken();

        $ids = ArrayHelper::toInteger((array) $this->input->get('cid', [], 'array'));

        if ($ids === []) {
            $this->setMessage(Text::_($this->text_prefix . '_NO_ITEM_SELECTED'), 'warning');
        } else {
            $this->getMenuModel()->copy($ids);
            $this->setMessage(Text::plural('COM_BREEZINGFORMSNG_MENUS_N_COPIED', count($ids)));
        }

        $this->setRedirect(Route::_('index.php?option=com_breezingformsng&view=menus', false));
    }

    private function getMenuModel(): MenuModel
    {
        $model = $this->getModel();

        if (!$model instanceof MenuModel) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }

        return $model;
    }
}
