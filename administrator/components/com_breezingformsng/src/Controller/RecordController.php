<?php

/**
 * BreezingForms NG - A Joomla Forms Application
 *
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 **/

namespace Vcmb\Component\BreezingformsNG\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\FormController;

class RecordController extends FormController
{
    /**
     * Records come from the submitted forms; none is created here.
     *
     * @param  array<string, mixed>  $data
     */
    protected function allowAdd($data = [])
    {
        return false;
    }
}
