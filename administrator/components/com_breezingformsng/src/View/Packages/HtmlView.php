<?php
/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Administrator\View\Packages;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryServiceInterface;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Vcmb\Component\BreezingformsNG\Administrator\Model\PackageTransferModel;

final class HtmlView extends BaseHtmlView
{
    public array $packages = [];
    public array $choices = [];
    public array $profiles = [];

    public function display($tpl = null)
    {
        $app = Factory::getApplication();
        $component = $app->bootComponent('com_breezingformsng');
        if (!$component instanceof MVCFactoryServiceInterface) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }
        $model = $component->getMVCFactory()->createModel('PackageTransfer', 'Administrator', ['ignore_request' => true]);
        if (!$model instanceof PackageTransferModel) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
        }
        $this->packages = $model->getPackages();
        $this->choices = $model->getExportChoices();
        $this->profiles = $model->getPackageProfiles();
        ToolbarHelper::title(Text::_("COM_BREEZINGFORMSNG_CONFIGURATION"));
        ToolbarHelper::preferences("com_breezingformsng");
        parent::display($tpl);
    }
}
