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

namespace Vcmb\Component\BreezingformsNG\Administrator\Field;

\defined('_JEXEC') or die;

use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Database\ParameterType;

/**
 * The top level menu items of the package of the edited one.
 */
final class MenuparentField extends ListField
{
    protected $type = 'Menuparent';

    protected function getOptions()
    {
        $options = parent::getOptions();
        $itemId = (int) $this->form->getValue('id');
        $package = (string) $this->form->getValue('package');

        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName(['id', 'title']))
            ->from($db->quoteName('#__facileforms_compmenus'))
            ->where($db->quoteName('parent') . ' = 0')
            ->where($db->quoteName('id') . ' <> :itemId')
            ->order($db->quoteName('ordering') . ' ASC')
            ->bind(':itemId', $itemId, ParameterType::INTEGER);

        if ($package !== '') {
            $query->where($db->quoteName('package') . ' = :package')
                ->bind(':package', $package);
        }

        foreach ($db->setQuery($query)->loadObjectList() as $parent) {
            $options[] = HTMLHelper::_('select.option', (int) $parent->id, (string) $parent->title);
        }

        return $options;
    }
}
