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
use Vcmb\Component\BreezingformsNG\Administrator\Model\PackageFilter;

/**
 * Package selector whose real values cannot collide with its "No package" option.
 *
 * @psalm-suppress PropertyNotSetInConstructor Inherited Joomla form field state, set by setup().
 */
final class PackagefilterField extends ListField
{
    protected $type = 'Packagefilter';

    private const TABLES = [
        'forms' => '#__facileforms_forms',
        'menus' => '#__facileforms_compmenus',
        'pieces' => '#__facileforms_pieces',
        'scripts' => '#__facileforms_scripts',
    ];

    #[\Override]
    protected function getInput()
    {
        // A legacy raw value (bookmark, saved state) must select the option of that package.
        if (is_string($this->value)) {
            $this->value = PackageFilter::toSelectorValue($this->value);
        }

        return parent::getInput();
    }

    #[\Override]
    protected function getOptions()
    {
        $options = parent::getOptions();
        $source = (string) ($this->element['source'] ?? '');
        $table = self::TABLES[$source] ?? null;

        if ($table === null) {
            throw new \UnexpectedValueException('Unknown package filter source: ' . $source);
        }

        $db = $this->getDatabase();
        $query = $db->createQuery()
            ->select('DISTINCT ' . $db->quoteName('package'))
            ->from($db->quoteName($table))
            ->where($db->quoteName('package') . ' <> ' . $db->quote(''))
            ->order($db->quoteName('package') . ' ASC');

        foreach ($db->setQuery($query)->loadColumn() ?: [] as $package) {
            if (is_string($package)) {
                $options[] = HTMLHelper::_('select.option', PackageFilter::encode($package), $package);
            }
        }

        return $options;
    }
}
