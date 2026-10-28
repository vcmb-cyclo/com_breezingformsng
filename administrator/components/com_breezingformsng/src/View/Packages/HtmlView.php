<?php
/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Administrator\View\Packages;

\defined('_JEXEC') or die;

use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryServiceInterface;
use Joomla\CMS\Router\Route;
use Vcmb\Component\BreezingformsNG\Administrator\Model\PackageTransferModel;
use Vcmb\Component\BreezingformsNG\Administrator\Service\AreaAccess;
use Vcmb\Component\BreezingformsNG\Administrator\View\BreezingformsNG\HtmlView as BaseHtmlView;

/**
 * Configuration screen: the default layout only holds the toolbar, each
 * action (create a package, install one) opens its own layout.
 */
final class HtmlView extends BaseHtmlView
{
    /** @var list<string> */
    public array $packages = [];
    /** @var array<string, list<array<string, mixed>>> */
    public array $choices = [];
    /** @var array<array<string, mixed>> */
    public array $profiles = [];
    /** Package preselected in the list, after a save. */
    public string $selectedPackage = '';
    /** Time zone the audit dates are shown in. */
    public ?\DateTimeZone $timezone = null;

    public function display($tpl = null)
    {
        AreaAccess::assert(Factory::getApplication()->getIdentity(), AreaAccess::CONFIGURATION);

        if ($this->getLayout() === 'export') {
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
            $this->selectedPackage = $app->getInput()->getString('package', '');
            $this->timezone = $app->getIdentity()->getTimezone();
        }

        $this->addToolbar();

        parent::display($tpl);
    }

    protected function getDetailLabel(): ?string
    {
        return match ($this->getLayout()) {
            'export' => Text::_('COM_BREEZINGFORMSNG_INSTALLER_CREATEPKG'),
            'import' => Text::_('COM_BREEZINGFORMSNG_INSTALLER'),
            default => null,
        };
    }

    private function addToolbar(): void
    {
        /** @var HtmlDocument $document */
        $document = $this->getDocument();
        $toolbar = $document->getToolbar();
        $user = Factory::getApplication()->getIdentity();
        $home = Route::_('index.php?option=com_breezingformsng&view=packages');

        // The validating toolbar buttons call document.formvalidator.
        if (in_array($this->getLayout(), ['export', 'import'], true)) {
            $document->getWebAssetManager()->useScript('form.validate');
        }

        // Unsaved changes guard of the Create Package layout: joomla.dialog confirm.
        if ($this->getLayout() === 'export') {
            $document->getWebAssetManager()->useScript('joomla.dialog');
            Text::script('JYES');
            Text::script('JNO');
            Text::script('COM_BREEZINGFORMSNG_CONFIRM_DISCARD_CHANGES');
        }

        switch ($this->getLayout()) {
            case 'export':
                $toolbar->apply('packages.save')
                    ->form('bf-package-export')
                    ->formValidation(true);
                $toolbar->standardButton('download', 'COM_BREEZINGFORMSNG_INSTALLER_CREATEPKG', 'packages.export')
                    ->form('bf-package-export')
                    ->formValidation(true);
                $toolbar->linkButton('close', 'JTOOLBAR_CLOSE')->url($home)->icon('icon-times');
                break;

            case 'import':
                $toolbar->standardButton('upload', 'COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_IMPORT', 'packages.import')
                    ->form('bf-package-import')
                    ->formValidation(true);
                $toolbar->linkButton('close', 'JTOOLBAR_CLOSE')->url($home)->icon('icon-times');
                break;

            default:
                $toolbar->linkButton('installer', 'COM_BREEZINGFORMSNG_INSTALLER')
                    ->url(Route::_('index.php?option=com_breezingformsng&view=packages&layout=import'))
                    ->icon('icon-upload');
                $toolbar->linkButton('createpkg', 'COM_BREEZINGFORMSNG_INSTALLER_CREATEPKG')
                    ->url(Route::_('index.php?option=com_breezingformsng&view=packages&layout=export'))
                    ->icon('icon-plus');

                // Last: Joomla right-aligns Options and every button after it.
                if ($user->authorise('core.admin', 'com_breezingformsng') || $user->authorise('core.options', 'com_breezingformsng')) {
                    $toolbar->preferences('com_breezingformsng');
                }
        }
    }
}
