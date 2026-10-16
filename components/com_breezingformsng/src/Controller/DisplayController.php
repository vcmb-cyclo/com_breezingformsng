<?php
/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Site\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Session\Session;

class DisplayController extends BaseController
{
    protected $default_view = 'form';

    /**
     * @param array<string, string> $urlparams
     */
    public function display($cachable = false, $urlparams = [])
    {
        $input = $this->input;

        if ($input->getCmd('ff_task') === 'submit' && !Session::checkToken('post')) {
            throw new \Exception(Text::_('JINVALID_TOKEN_NOTICE'), 403);
        }

        return parent::display($cachable, $urlparams);
    }
}
