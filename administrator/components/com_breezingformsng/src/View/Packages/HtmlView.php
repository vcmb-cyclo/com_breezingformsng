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
use Vcmb\Component\BreezingformsNG\Administrator\Service\AreaAccess;

final class HtmlView extends BaseHtmlView
{
    /** @var list<string> */
    public array $packages = [];
    /** @var array<string, list<array<string, mixed>>> */
    public array $choices = [];
    /** @var array<array<string, mixed>> */
    public array $profiles = [];

    public function display($tpl = null)
    {
        AreaAccess::assert(Factory::getApplication()->getIdentity(), AreaAccess::CONFIGURATION);

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
        $user = Factory::getApplication()->getIdentity();

        if ($user->authorise('core.admin', 'com_breezingformsng') || $user->authorise('core.options', 'com_breezingformsng')) {
            ToolbarHelper::preferences('com_breezingformsng');
        }
        parent::display($tpl);
    }
}
