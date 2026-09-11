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
use Joomla\CMS\MVC\Factory\MVCFactoryServiceInterface;
use Vcmb\Component\BreezingformsNG\Administrator\Model\PackageModel;
use Vcmb\Component\BreezingformsNG\Administrator\Model\PieceModel;
use Vcmb\Component\BreezingformsNG\Administrator\Model\ScriptModel;

/** @property CMSApplication $app */
class DisplayController extends BaseController
{
    public function display($cachable = false, $urlparams = [])
    {
        if (!$this->app->getIdentity()->authorise('core.manage', 'com_breezingformsng')) {
            throw new \Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        $input = $this->app->getInput();
        $act = $input->getCmd('act', '');
        $task = $input->getCmd('task', '');
        $view = $input->getCmd('view', '');

        // List screens must keep the main admin menu enabled; only the edit
        // layouts re-enable hidemainmenu themselves (same fix as ContentBuilderNG).
        if (!\in_array($input->getCmd('layout', ''), ['edit', 'csvimport'], true)) {
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

        if ($task === '' && $view === 'packages') {
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
                'remove'    => 'forms.remove',
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
            $this->prepareListPackage($view);

            return parent::display($cachable, $urlparams);
        }

        // No valid route matched — return without invoking the removed legacy bridge.
        return $this;
    }

    private function prepareListPackage(string $view): void
    {
        $input      = $this->app->getInput();
        $session    = $this->app->getSession();
        $sessionKey = $view === 'pieces' ? 'bf.piecepkg' : 'bf.scriptpkg';

        $package = $input->get('pkg', null, 'STRING');

        if ($package === null) {
            $package = (string) ($session->get($sessionKey) ?? '');
        } elseif ($package === '- blank -') {
            $package = '';
        }

        if ($package !== '') {
            $component = $this->app->bootComponent('com_breezingformsng');

            if (!$component instanceof MVCFactoryServiceInterface) {
                throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
            }

            $model = $component->getMVCFactory()
                ->createModel($view === 'pieces' ? 'Piece' : 'Script', 'Administrator', ['ignore_request' => true]);

            if (!$model instanceof PackageModel) {
                throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
            }

            if (
                ($view === 'pieces' && !$model instanceof PieceModel)
                || ($view === 'scripts' && !$model instanceof ScriptModel)
            ) {
                throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
            }

            if (!$model->packageExists((string) $package)) {
                $package = '';
            }
        }

        $session->set($sessionKey, $package);
        $input->set('pkg', $package);
    }

}
