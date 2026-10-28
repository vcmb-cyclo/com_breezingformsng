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

use HTML_facileFormsProcessor;
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\QuickMode\BootstrapRenderer;
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\QuickMode\ClassicRenderer;
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\QuickMode\OnePageRenderer;

/** Creates the QuickMode renderer selected by template metadata. */
final class QuickModeRendererFactory
{
    /**
     * @param array<string, mixed> $metadata
     *
     * @return ClassicRenderer|BootstrapRenderer|OnePageRenderer
     */
    public function create(HTML_facileFormsProcessor $processor, array $metadata): object
    {
        if (($metadata['themebootstrapThemeEngine'] ?? '') === 'bootstrap') {
            if (!empty($metadata['themebootstrapMode'])) {
                return new OnePageRenderer($processor);
            }

            return new BootstrapRenderer($processor);
        }

        return new ClassicRenderer($processor);
    }
}
