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

namespace Vcmb\Component\BreezingformsNG\Administrator\View\Recordsimport;

\defined('_JEXEC') or die;

use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Vcmb\Component\BreezingformsNG\Administrator\Model\RecordsimportModel;
use Vcmb\Component\BreezingformsNG\Administrator\View\BreezingformsNG\HtmlView as BaseHtmlView;

class HtmlView extends BaseHtmlView
{
    public ?Form $form = null;

    public function display($tpl = null)
    {
        $app = Factory::getApplication();

        if (!$app->getIdentity()->authorise('core.create', 'com_breezingformsng')) {
            throw new \Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        $model = $this->getModel();

        if (!$model instanceof RecordsimportModel) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }

        $form = $model->getForm();

        if (!$form instanceof Form) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }

        $this->form = $form;
        $app->getInput()->set('hidemainmenu', true);

        /** @var HtmlDocument $document */
        $document = $this->getDocument();
        $toolbar = $document->getToolbar();
        $toolbar->standardButton('import', 'COM_BREEZINGFORMSNG_IMPORT', 'recordsimport.import')
            ->icon('icon-upload')
            ->buttonClass('btn btn-success');
        $toolbar->cancel('recordsimport.cancel', 'JTOOLBAR_CANCEL');
        $document->getWebAssetManager()->useScript('keepalive')->useScript('form.validate');

        parent::display($tpl);
    }

    protected function getDetailLabel(): ?string
    {
        return Text::_('COM_BREEZINGFORMSNG_BTN_IMPORT_CSV');
    }
}
