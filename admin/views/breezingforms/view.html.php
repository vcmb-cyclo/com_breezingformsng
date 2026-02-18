<?php
/**
 * @package     BreezingForms
 * @author      Markus Bopp
 * @link        http://www.crosstec.de
 * @copyright   (C) 2024 by XDA+GIL
 * @license     GNU/GPL
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\HTML\Helpers\Sidebar;
use Joomla\CMS\HTML\HTMLHelper;

class BreezingformsViewBreezingforms extends HtmlView
{
    protected $modules = null;

    public function display($tpl = null)
    {
        ToolbarHelper::title('BreezingForms NG', 'logo_left');
        $doc = Factory::getApplication()->getDocument();
        $doc->setTitle("BreezingForms NG");

        require_once(JPATH_SITE . '/administrator/components/com_breezingforms/libraries/crosstec/classes/BFRequest.php');
        require_once(JPATH_SITE . '/administrator/components/com_breezingforms/libraries/crosstec/classes/BFText.php');
        // $doc->addScript( URI::root().'media/system/js/core.js' )
        // Add Joomla core JavaScript framework
 //       HTMLHelper::_('bootstrap.framework');
 //       $doc->addScript('media/system/js/core.js');

        Sidebar::addEntry(
            '<i class="fa fa-folder-open" aria-hidden="true"></i> ' . '<m>' .
            BFText::_('COM_BREEZINGFORMS_MANAGERECS') . '</m>',
            'index.php?option=com_breezingforms&act=managerecs',
            BFRequest::getVar('act', '') == 'managerecs' || BFRequest::getVar('act', '') == 'recordmanagement' || BFRequest::getVar('act', '') == ''
        );

        Sidebar::addEntry(
            '<i class="fa fa-pencil-square-o" aria-hidden="true"></i> ' . '<m>' .
            BFText::_('COM_BREEZINGFORMS_MANAGEFORMS') . '</m>',
            'index.php?option=com_breezingforms&act=manageforms',
            BFRequest::getVar('act', '') == 'manageforms' || BFRequest::getVar('act', '') == 'easymode' || BFRequest::getVar('act', '') == 'quickmode'
        );

        Sidebar::addEntry(
            '<i class="fa fa-code" aria-hidden="true"></i> ' . '<m>' .
            BFText::_('COM_BREEZINGFORMS_MANAGESCRIPTS') . '</m>',
            'index.php?option=com_breezingforms&act=managescripts',
            BFRequest::getVar('act', '') == 'managescripts'
        );

        Sidebar::addEntry(
            '<i class="fa fa-puzzle-piece" aria-hidden="true"></i> ' . '<m>' .
            BFText::_('COM_BREEZINGFORMS_MANAGEPIECES') . '</m>',
            'index.php?option=com_breezingforms&act=managepieces',
            BFRequest::getVar('act', '') == 'managepieces'
        );

        Sidebar::addEntry(
            '<i class="fa fa-link" aria-hidden="true"></i> ' . '<m>' .
            BFText::_('COM_BREEZINGFORMS_INTEGRATOR') . '</m>',
            'index.php?option=com_breezingforms&act=integrate',
            BFRequest::getVar('act', '') == 'integrate'
        );

        /*
        Sidebar::addEntry('<i class="fa fa-bars" aria-hidden="true"></i> '  .'<m>'.
            BFText::_('COM_BREEZINGFORMS_MANAGEMENUS') .'</m>',
            'index.php?option=com_breezingforms&act=managemenus', BFRequest::getVar('act','') == 'managemenus');*/

        Sidebar::addEntry(
            '<i class="fa fa-cog" aria-hidden="true"></i> ' . '<m>' .
            BFText::_('COM_BREEZINGFORMS_CONFIG') . '</m>',
            'index.php?option=com_breezingforms&act=configuration',
            BFRequest::getVar('act', '') == 'configuration'
        );

        Sidebar::addEntry(
            '<i class="fa fa-info-circle" aria-hidden="true"></i> ' . '<m>' .
            BFText::_('COM_BREEZINGFORMS_ABOUT') . '</m>',
            'index.php?option=com_breezingforms&act=about',
            BFRequest::getVar('act', '') == 'about'
        );

        $this->sidebar = '<div id="bf-sidebar">' . Sidebar::render() . '</div>';


        Factory::getApplication()->getDocument()->getWebAssetManager()->addInlineScript('
            jQuery(document).ready(function(){
                jQuery("#bf-sidebar").appendTo("#wrapper");
            });
            ');

        parent::display($tpl);
    }
}
