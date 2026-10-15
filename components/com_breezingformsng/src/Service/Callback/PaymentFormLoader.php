<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Site\Service\Callback;

\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/** Loads a payment form and decodes its serialized template areas. */
final class PaymentFormLoader
{
    public function __construct(private readonly DatabaseInterface $database)
    {
    }

    public function load(int $formId): ?object
    {
        $query = $this->database->createQuery()
            ->select('*')
            ->from($this->database->quoteName('#__facileforms_forms'))
            ->where($this->database->quoteName('id') . ' = :formId')
            ->bind(':formId', $formId, ParameterType::INTEGER);
        $this->database->setQuery($query);
        $forms = $this->database->loadObjectList();

        return $forms[0] ?? null;
    }

    /** @return array<mixed>|null */
    public function decodeAreas(object $form): ?array
    {
        $areas = json_decode((string) ($form->template_areas ?? ''), true);

        return is_array($areas) ? $areas : null;
    }
}
