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

namespace Vcmb\Component\BreezingformsNG\Administrator\Service;

\defined('_JEXEC') or die;

use CB\Component\Contentbuilderng\Administrator\Extension\ContentbuilderngComponent;
use CB\Component\Contentbuilderng\Administrator\Helper\FormSourceFactory;
use CB\Component\Contentbuilderng\Administrator\Service\FormSupportService;
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Vcmb\Component\BreezingformsNG\Site\Service\Integration\ContentBuilderInstallation;

/**
 * Brings the ContentBuilder NG views built on a BreezingForms NG form in line
 * with its elements once the form has been saved.
 *
 * The ContentBuilder NG services come from its own container, so that this
 * component does not depend on how they are built.
 */
final class ContentBuilderFormSync
{
    public function __construct(
        private readonly CMSApplicationInterface $application,
        private readonly DatabaseInterface $database
    ) {
    }

    public function sync(int $formId): void
    {
        if (!ContentBuilderInstallation::isInstalled(JPATH_ADMINISTRATOR . '/components/com_contentbuilderng')) {
            return;
        }

        $db = $this->database;
        $sourceType = 'com_breezingformsng';
        $query = $db->createQuery()
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__contentbuilderng_forms'))
            ->where($db->quoteName('type') . ' = :sourceType')
            ->where($db->quoteName('reference_id') . ' = :referenceId')
            ->bind(':sourceType', $sourceType)
            ->bind(':referenceId', $formId, ParameterType::INTEGER);
        $views = $db->setQuery($query)->loadColumn();

        if ($views === []) {
            return;
        }

        $component = $this->application->bootComponent('com_contentbuilderng');

        if (!$component instanceof ContentbuilderngComponent) {
            return;
        }

        $form = FormSourceFactory::getForm('com_breezingformsng', $formId);
        $formSupport = $component->getContainer()->get(FormSupportService::class);

        if (!\is_object($form) || !$formSupport instanceof FormSupportService) {
            return;
        }

        foreach ($views as $viewId) {
            $formSupport->synchElements($viewId, $form);
        }
    }
}
