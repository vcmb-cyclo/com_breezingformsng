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

namespace Vcmb\Component\BreezingformsNG\Administrator\View\Menu;

\defined('_JEXEC') or die;

use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Vcmb\Component\BreezingformsNG\Administrator\Model\MenuModel;
use Vcmb\Component\BreezingformsNG\Administrator\View\BreezingformsNG\HtmlView as BaseHtmlView;

class HtmlView extends BaseHtmlView
{
    public ?Form $form = null;
    public ?\stdClass $item = null;

    public function display($tpl = null)
    {
        $model = $this->getModel();

        if (!$model instanceof MenuModel) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }

        $item = $model->getItem();

        if ($item === false) {
            throw new \Exception(Text::_('JLIB_APPLICATION_ERROR_NOT_EXIST'), 404);
        }

        $form = $model->getForm();

        if (!$form instanceof Form) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }

        $this->item = $item;
        $this->form = $form;

        Factory::getApplication()->getInput()->set('hidemainmenu', true);

        /** @var HtmlDocument $document */
        $document = $this->getDocument();
        $toolbar = $document->getToolbar();
        $toolbar->apply('menu.apply');
        $toolbar->save('menu.save');
        $toolbar->cancel('menu.cancel', (int) $item->id > 0 ? 'JTOOLBAR_CLOSE' : 'JTOOLBAR_CANCEL');
        $document->getWebAssetManager()->useScript('keepalive')->useScript('form.validate');

        parent::display($tpl);
    }

    protected function getDetailLabel(): ?string
    {
        if ($this->item === null) {
            return null;
        }

        $title = trim((string) $this->item->title);

        return $title !== '' ? $title : null;
    }
}
