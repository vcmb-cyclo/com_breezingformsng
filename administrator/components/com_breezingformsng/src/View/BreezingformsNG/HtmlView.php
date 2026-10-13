<?php
/**
 * @package BreezingFormsNG
 * @author      Markus Bopp
 * @author      XDA+GIL
 * @link        https://breezingforms-ng.vcmb.fr
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license     GNU/GPL
 */

namespace Vcmb\Component\BreezingformsNG\Administrator\View\BreezingformsNG;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Uri\Uri;
use Vcmb\Component\BreezingformsNG\Administrator\Helper\BreadcrumbHelper;

class HtmlView extends BaseHtmlView
{
    protected $modules = null;

    public function display($tpl = null)
    {
        /** @var CMSApplication $app */
        $app = Factory::getApplication();
        $doc = $app->getDocument();
        $doc->setTitle($this->getPageTitle());
        $doc->getWebAssetManager()->getRegistry()->addExtensionRegistryFile('com_breezingformsng');
        $doc->getWebAssetManager()->addInlineStyle(
            '.icon-logo_left{
                background-image:url(' . Uri::root(true) . '/media/com_breezingformsng/images/logo_left.png);
                background-size:contain;
                background-repeat:no-repeat;
                background-position:center;
                display:inline-block;
                width:48px;
                height:48px;
                vertical-align:middle;
            }'
        );

        ToolbarHelper::title($this->getToolbarTitle(), 'logo_left');

        parent::display($tpl);
    }

    private function getPageTitle(): string
    {
        return trim(strip_tags($this->getToolbarTitle()));
    }

    private function getToolbarTitle(): string
    {
        return BreadcrumbHelper::render($this->getBreadcrumbSegments());
    }

    /**
     * @return array<int, array{label: string, url?: ?string}>
     */
    private function getBreadcrumbSegments(): array
    {
        $segments = [
            ['label' => Text::_('COM_BREEZINGFORMSNG'), 'url' => 'index.php?option=com_breezingformsng'],
        ];

        $section = $this->getSectionSegment();

        if ($section === null) {
            return $segments;
        }

        $segments[] = $section;

        $detail = $this->getDetailLabel();

        if ($detail !== null) {
            $segments[] = ['label' => $detail, 'url' => null];
        }

        return $segments;
    }

    /**
     * The current (deepest) breadcrumb segment, e.g. a record id or item name.
     * Overridden by subclasses that render an edit-style layout. Returning
     * null keeps the section segment above as the final (non-clickable) level.
     */
    protected function getDetailLabel(): ?string
    {
        return null;
    }

    /**
     * @return array{label: string, url?: ?string}|null
     */
    private function getSectionSegment(): ?array
    {
        $input = Factory::getApplication()->getInput();
        $view = $input->getCmd('view', '');
        $act = $input->getCmd('act', '');

        return match (true) {
            $view === 'scripts', $act === 'managescripts' => [
                'label' => Text::_('COM_BREEZINGFORMSNG_MANAGESCRIPTS'),
                'url' => 'index.php?option=com_breezingformsng&view=scripts',
            ],
            $view === 'pieces', $act === 'managepieces' => [
                'label' => Text::_('COM_BREEZINGFORMSNG_MANAGEPIECES'),
                'url' => 'index.php?option=com_breezingformsng&view=pieces',
            ],
            $view === 'records', $view === 'record', $act === 'managerecs', $act === 'recordmanagement' => [
                'label' => Text::_('COM_BREEZINGFORMSNG_RECORDS_SECTION_TITLE'),
                'url' => 'index.php?option=com_breezingformsng&view=records',
            ],
            $view === 'forms', $act === 'manageforms', $act === 'quickmode' => [
                'label' => Text::_('COM_BREEZINGFORMSNG_MANAGEFORMS'),
                'url' => 'index.php?option=com_breezingformsng&view=forms',
            ],
            $view === 'menus', $view === 'menu', $act === 'managemenus' => [
                'label' => Text::_('COM_BREEZINGFORMSNG_MANAGEMENUS'),
                'url' => 'index.php?option=com_breezingformsng&view=menus',
            ],
            $view === 'integrator', $act === 'integrate' => [
                'label' => Text::_('COM_BREEZINGFORMSNG_INTEGRATOR'),
                'url' => 'index.php?option=com_breezingformsng&view=integrator',
            ],
            $act === 'configuration' => ['label' => Text::_('COM_BREEZINGFORMSNG_CONFIG'), 'url' => null],
            $view === 'about' => ['label' => Text::_('COM_BREEZINGFORMSNG_ABOUT'), 'url' => null],
            default => null,
        };
    }
}
