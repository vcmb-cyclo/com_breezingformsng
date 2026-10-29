<?php
/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;

/** @property CMSApplication $app */
class DisplayController extends BaseController
{
    /**
     * Edit views of the native form MVC, with the list each one returns to.
     */
    private const ITEM_VIEWS = [
        'record' => 'records',
        'menu' => 'menus',
        'script' => 'scripts',
        'piece' => 'pieces',
    ];

    /**
     * @param  bool                  $cachable
     * @param  array<string, string>  $urlparams
     */
    public function display($cachable = false, $urlparams = [])
    {
        $user = $this->app->getIdentity() ?? throw new \RuntimeException('The current user is not loaded.');

        if (!$user->authorise('core.manage', 'com_breezingformsng')) {
            throw new \Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        $input = $this->app->getInput();
        $act = $input->getCmd('act', '');
        $task = $input->getCmd('task', '');
        $view = $input->getCmd('view', '');

        // List screens must keep the main admin menu enabled; only the edit
        // layouts re-enable hidemainmenu themselves (same fix as ContentBuilderNG).
        if (!\in_array($input->getCmd('layout', ''), ['edit'], true)) {
            $input->set('hidemainmenu', 0);
        }

        if ($task === '' && in_array($act, ['managepieces', 'managescripts'], true)) {
            $view = $act === 'managepieces' ? 'pieces' : 'scripts';
            $input->set('view', $view);
        }

        if ($task === '' && $view === 'help') {
            return parent::display($cachable, $urlparams);
        }

        if ($task === '' && $view === 'about') {
            return parent::display($cachable, $urlparams);
        }

        if ($task === '' && $view === 'recordsimport') {
            return parent::display($cachable, $urlparams);
        }

        if ($task === '' && $view === 'packages') {
            return parent::display($cachable, $urlparams);
        }

        if ($task === '' && isset(self::ITEM_VIEWS[$view])) {
            $itemId = $input->getInt('id', 0);

            // An item is opened through its edit task, which holds its id.
            if (!$this->checkEditId('com_breezingformsng.edit.' . $view, $itemId)) {
                $this->setMessage(Text::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $itemId), 'error');
                $this->setRedirect(Route::_('index.php?option=com_breezingformsng&view=' . self::ITEM_VIEWS[$view], false));

                return $this;
            }

            return parent::display($cachable, $urlparams);
        }

        if ($task === '' && (
            $view === 'records'
            || in_array($act, ['managerecs', 'recordmanagement'], true)
            || ($act === '' && $view === '')
        )) {
            $input->set('view', 'records');
            $input->set('act', '');
            return parent::display($cachable, $urlparams);
        }

        if ($task === '' && ($view === 'integrator' || $act === 'integrate')) {
            $input->set('view', 'integrator');
            $input->set('act', '');
            return parent::display($cachable, $urlparams);
        }

        if ($act === 'manageforms' || ($task === '' && $view === 'forms')) {
            $input->set('view', 'forms');
            $input->set('act', '');
            if ($task === '' && $input->getCmd('layout', '') === 'edit' && $input->getInt('id', 0) > 0 && !$input->getBool('advanced', false)) {
                $this->app->redirect(
                    'index.php?option=com_breezingformsng&task=quickmode.display'
                    . '&form=' . $input->getInt('id', 0)
                    . '&pkg=' . rawurlencode($input->getString('pkg', ''))
                );
                return $this;
            }
            if ($task === '' || $task === 'listitems') {
                $input->set('task', '');
                return parent::display($cachable, $urlparams);
            }
            $taskMap = [
                'new'       => 'forms.edit',
                'edit'      => 'forms.edit',
                'save'      => 'forms.save',
                'cancel'    => 'forms.cancel',
                'copy'      => 'forms.copy',
                'remove'    => 'forms.delete',
                'publish'   => 'forms.publish',
                'unpublish' => 'forms.unpublish',
                'orderup'   => 'forms.orderup',
                'orderdown' => 'forms.orderdown',
            ];
            if (isset($taskMap[$task])) {
                $input->set('task', $taskMap[$task]);
            }
            return parent::display($cachable, $urlparams);
        }

        if ($task === '' && ($view === 'menus' || $act === 'managemenus')) {
            $input->set('view', 'menus');
            $input->set('act', '');
            return parent::display($cachable, $urlparams);
        }

        if ($task === '' && in_array($view, ['pieces', 'scripts'], true)) {
            return parent::display($cachable, $urlparams);
        }

        // No valid route matched — return without invoking the removed legacy bridge.
        return $this;
    }
}
