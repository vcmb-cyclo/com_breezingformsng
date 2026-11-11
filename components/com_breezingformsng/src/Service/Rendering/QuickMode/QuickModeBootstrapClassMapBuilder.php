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

namespace Vcmb\Component\BreezingformsNG\Site\Service\Rendering\QuickMode;

/**
 * Provides the Bootstrap 5 class mapping used by Bootstrap-based renderers.
 */
final class QuickModeBootstrapClassMapBuilder
{
    /**
     * @return array<string, string>
     */
    public static function build(): array
    {
        return [
            'bar' => 'progress-bar',
            'progress' => 'progress',
            'span1' => 'col-md-1',
            'span2' => 'col-md-2',
            'span3' => 'col-md-3',
            'span4' => 'col-md-4',
            'span5' => 'col-md-5',
            'span6' => 'col-md-6',
            'span7' => 'col-md-7',
            'span8' => 'col-md-8',
            'span9' => 'col-md-9',
            'span10' => 'col-md-10',
            'span11' => 'col-md-11',
            'span12' => 'col-md-12',
            'control-group' => 'mb-3',
            'control-label' => 'form-label',
            'row-fluid' => 'row',
            'icon-asterisk' => 'fas fa-asterisk',
            'icon-question-sign' => 'fas fa-circle-question',
            'form-actions' => 'mt-3',
            'form-actions-buttons' => 'd-flex flex-wrap gap-2',
            'btn' => 'btn',
            'btn-primary' => 'btn-primary',
            'btn-secondary' => 'btn-secondary',
            'alert' => 'alert',
            'alert-error' => 'alert-danger',
            'controls' => '',
            'form-inline' => 'bf-form-inline',
            'form-group' => 'bf-form-group mb-3',
            'well' => 'card',
            'well-small' => 'card-body',
            'hero-unit' => 'bf-hero-unit',
            'float-start' => 'float-start',
            'float-end' => 'float-end',
            'radio' => 'form-check-label',
            'checkbox' => 'form-check-label',
            'inline' => 'form-check-inline',
            'radio-form-group' => 'radio-form-group',
            'checkbox-form-group' => 'checkbox-form-group',
            'input-append' => 'input-group',
            'input-group-btn' => '',
            'form-control' => 'form-control',
            'icon-calendar' => 'fas fa-calendar',
            'icon-refresh' => 'fas fa-arrows-rotate',
            'icon-play' => 'fas fa-play',
            'icon-picture' => 'fas fa-image',
            'img-thumbnail' => 'img-thumbnail',
            'icon-upload' => 'fas fa-upload',
            'nonform-control' => 'nonform-control',
            'other-form-group' => 'other-form-group',
            'custom-form-control' => 'custom-form-control',
            'input-group-text' => 'input-group-text',
            'row' => 'row',
            'form-select' => 'form-select',
        ];
    }
}
