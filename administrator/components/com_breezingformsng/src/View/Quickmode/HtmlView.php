<?php
/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Administrator\View\Quickmode;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryServiceInterface;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Editor\Editor;
use Vcmb\Component\BreezingformsNG\Administrator\Helper\BreadcrumbHelper;
use Vcmb\Component\BreezingformsNG\Administrator\Helper\FormsAdvancedOptionsHtml;
use Vcmb\Component\BreezingformsNG\Administrator\Model\FormModel;
use Vcmb\Component\BreezingformsNG\Administrator\Model\FormsModel;
use Vcmb\Component\BreezingformsNG\Administrator\Model\QuickmodeModel;

class HtmlView extends BaseHtmlView
{
    public string $formTitle  = '';
    public string $formName   = '';
    public string $formDesc   = '';
    public int    $formId     = 0;
    public int    $emailntf   = 1;
    public int    $published  = 1;
    public int    $debugMode  = 0;
    public string $emailadr   = '';
    public string $templateCode = '';
    public array  $elementScripts = [];
    public array  $themes         = [];
    public array  $themesBootstrap  = [];

    /**
     * Backing data for the "Options" tab (fragment-3), which reuses the
     * classic forms.edit&advanced=1 settings block. Null when the form has
     * not been saved yet (formId === 0): that screen has no
     * #__facileforms_forms row to read or write until the first QuickMode
     * save creates one.
     */
    public ?\stdClass $advancedOptionsForm = null;
    public $advancedOptionsEditor = null;
    public array $advancedOptionsTabEntryCounts = [];
    public array $advancedOptionsInitScripts = [];
    public array $advancedOptionsSubmittedScripts = [];
    public array $advancedOptionsPieceBefore = [];
    public array $advancedOptionsPieceAfter = [];
    public array $advancedOptionsPieceBeginSubmit = [];
    public array $advancedOptionsPieceEndSubmit = [];

    public function display($tpl = null): void
    {
        /** @var CMSApplication $app */
        $app = Factory::getApplication();
        $app->getInput()->set('hidemainmenu', 1);
        $input  = $app->getInput();
        $layout = $input->getCmd('layout', '');

        if ($layout === 'editor') {
            parent::display($tpl);
            return;
        }

        $model  = $this->getQuickmodeModel();
        $formId = $input->getInt('form', 0);

        $options = $formId > 0 ? $model->getFormOptions($formId) : null;

        if ($options === null) {
            $this->formName  = 'QuickForm' . mt_rand(0, mt_getrandmax());
            $this->formTitle = $this->formName;
            $this->emailntf  = 1;
            $this->emailadr  = '';
            $this->formDesc  = '';
            $this->published = 1;
            $this->debugMode = 0;
        } else {
            $this->formName  = (string) $options->name;
            $this->formTitle = (string) $options->title;
            $this->emailntf  = (int) $options->emailntf;
            $this->emailadr  = (string) $options->emailadr;
            $this->formDesc  = (string) $options->description;
            $this->published = (int) $options->published;
            $this->debugMode = (int) $options->debug_mode;
        }

        $this->formId          = $formId;
        $this->templateCode    = $formId > 0 ? $model->getTemplateCode($formId) : '';
        $this->elementScripts  = $model->getElementScripts();
        $this->themes          = $model->getThemes();
        $this->themesBootstrap = $model->getThemesBootstrap();

        if ($formId > 0) {
            $listModel = $this->getFormsModel();
            $this->advancedOptionsForm = $this->getFormModel()->getForm($formId);

            if ($this->advancedOptionsForm !== null) {
                $this->advancedOptionsEditor           = Editor::getInstance('codemirror');
                $this->advancedOptionsTabEntryCounts    = FormsAdvancedOptionsHtml::countEntries($this->advancedOptionsForm);
                $this->advancedOptionsInitScripts       = $listModel->getScripts('Form Init');
                $this->advancedOptionsSubmittedScripts  = $listModel->getScripts('Form Submitted');
                $this->advancedOptionsPieceBefore       = $listModel->getPieces('Before Form');
                $this->advancedOptionsPieceAfter        = $listModel->getPieces('After Form');
                $this->advancedOptionsPieceBeginSubmit  = $listModel->getPieces('Begin Submit');
                $this->advancedOptionsPieceEndSubmit    = $listModel->getPieces('End Submit');
            }
        }

        // Toolbar
        $pageTitle = BreadcrumbHelper::render([
            ['label' => Text::_('COM_BREEZINGFORMSNG'), 'url' => 'index.php?option=com_breezingformsng'],
            ['label' => Text::_('COM_BREEZINGFORMSNG_MANAGEFORMS'), 'url' => 'index.php?option=com_breezingformsng&view=forms'],
            ['label' => $this->formTitle !== '' ? $this->formTitle : Text::_('COM_BREEZINGFORMSNG_INSTALLER_UNKNOWN')],
        ]);

        $doc = $app->getDocument();
        $doc->setTitle(strip_tags($pageTitle));
        $doc->getWebAssetManager()->addInlineStyle(
            '.icon-logo_left{
                background-image:url(' . Uri::root(true) . '/media/com_breezingformsng/images/logo_left.png);
                background-size:contain;background-repeat:no-repeat;background-position:center;
                display:inline-block;width:48px;height:48px;vertical-align:middle;
            }'
        );
        $wa = $doc->getWebAssetManager();
        $wa->useStyle('com_breezingformsng.tokens');
        $wa->useStyle('com_breezingformsng.bfng-admin');
        $wa->useStyle('com_breezingformsng.quickmode-style');
        $wa->useStyle('com_breezingformsng.jstree3-style');
        $wa->useStyle('com_breezingformsng.admin-style');
        $wa->useStyle('com_breezingformsng.custom-style');
        $wa->useScript('com_breezingformsng.jquery-alias');
        $wa->useScript('com_breezingformsng.jstree3');
        $wa->useScript('bootstrap.tab');
        $wa->useScript('com_breezingformsng.base64');
        $wa->useScript('com_breezingformsng.center');
        $wa->useScript('com_breezingformsng.scroll');
        $wa->useScript('com_breezingformsng.quickmode-elements');
        $wa->useScript('com_breezingformsng.quickmode-app');
        $wa->useScript('com_breezingformsng.quickmode-yesno-switch');
        $wa->useScript('com_breezingformsng.custom-js');
        $wa->registerAndUseScript(
            'com_breezingformsng.quickmode-form-state',
            'media/com_breezingformsng/js/admin/admin-toggle-published.js',
            ['version' => 'auto'],
            ['defer' => true],
            ['core']
        );
        $wa->useScript('com_breezingformsng.jquery-restore');
        $doc->addScriptOptions('com_breezingformsng.admin-toggle-published', [
            'csrfToken' => Session::getFormToken(),
        ]);
        Text::script('JPUBLISHED');
        Text::script('JUNPUBLISHED');
        Text::script('COM_BREEZINGFORMSNG_DEBUG_MODE_ENABLED');
        Text::script('COM_BREEZINGFORMSNG_DEBUG_MODE_DISABLED');
        Text::script('COM_BREEZINGFORMSNG_AJAX_STATE_ERROR');
        ToolbarHelper::title($pageTitle, 'logo_left');

        parent::display($tpl);
    }

    private function getQuickmodeModel(): QuickmodeModel
    {
        $model = $this->getMVCFactory()->createModel('Quickmode', 'Administrator', ['ignore_request' => true]);

        if (!$model instanceof QuickmodeModel) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }

        return $model;
    }

    /**
     * Loads the "Options" tab's backing row - same model the classic
     * forms.edit&advanced=1 screen uses (View\Forms\HtmlView::display()).
     */
    private function getFormModel(): FormModel
    {
        $model = $this->getMVCFactory()->createModel('Form', 'Administrator', ['ignore_request' => true]);

        if (!$model instanceof FormModel) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }

        return $model;
    }

    private function getFormsModel(): FormsModel
    {
        $model = $this->getMVCFactory()->createModel('Forms', 'Administrator', ['ignore_request' => true]);

        if (!$model instanceof FormsModel) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }

        return $model;
    }

    private function getMVCFactory(): \Joomla\CMS\MVC\Factory\MVCFactoryInterface
    {
        $component = Factory::getApplication()->bootComponent('com_breezingformsng');

        if (!$component instanceof MVCFactoryServiceInterface) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }

        return $component->getMVCFactory();
    }
}
