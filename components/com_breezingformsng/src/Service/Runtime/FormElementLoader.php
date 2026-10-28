<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Site\Service\Runtime;

\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/** Loads the published elements belonging to a form. */
final class FormElementLoader
{
    public function __construct(private readonly DatabaseInterface $database)
    {
    }

    /**
     * @return list<\stdClass>
     */
    public function loadPublished(int $formId): array
    {
        $query = $this->database->createQuery()
            ->select('*')
            ->from($this->database->quoteName('#__facileforms_elements'))
            ->where($this->database->quoteName('form') . ' = :formId')
            ->where($this->database->quoteName('published') . ' = 1')
            ->order([
                $this->database->quoteName('page'),
                $this->database->quoteName('ordering'),
            ])
            ->bind(':formId', $formId, ParameterType::INTEGER);

        $this->database->setQuery($query);

        return $this->database->loadObjectList();
    }
}
