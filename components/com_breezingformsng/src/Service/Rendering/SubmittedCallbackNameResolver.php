<?php

/**
 * BreezingForms NG - A Joomla Forms Application
 *
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 **/

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Site\Service\Rendering;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/** Resolves the configured form-level submitted callback name. */
final class SubmittedCallbackNameResolver
{
    public function __construct(private readonly mixed $database)
    {
    }

    /** @param \stdClass $form */
    public function resolve(object $form): string
    {
        switch ($form->script2cond) {
            case 1:
                /** @var DatabaseInterface $database */
                $database = $this->database;
                $scriptId = (int) $form->script2id;
                $query = $database->createQuery()
                    ->select('name')
                    ->from($database->quoteName('#__facileforms_scripts'))
                    ->where($database->quoteName('id') . ' = :script2id')
                    ->where($database->quoteName('published') . ' = 1')
                    ->bind(':script2id', $scriptId, ParameterType::INTEGER);
                $database->setQuery($query);

                return (string) $database->loadResult();
            case 2:
                return 'ff_' . $form->name . '_submitted';
            default:
                return '';
        }
    }
}
