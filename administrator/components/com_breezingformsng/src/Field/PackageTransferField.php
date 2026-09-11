<?php
/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Administrator\Field;

\defined('_JEXEC') or die;

use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

final class PackageTransferField extends FormField
{
    protected $type = 'PackageTransfer';

    protected function getInput(): string
    {
        $url = Route::_('index.php?option=com_breezingformsng&view=packages');
        $label = Text::_('COM_BREEZINGFORMSNG_IMPORT_EXPORT');

        return '<a class="btn btn-primary" href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">'
            . htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
            . '</a>';
    }
}
