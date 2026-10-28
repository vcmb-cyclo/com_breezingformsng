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

/** Registers form and element callbacks while preserving their historical order. */
final class CallbackRegistrationService
{
    public function __construct(private readonly HTML_facileFormsProcessor $processor)
    {
    }

    /**
     * @param array<int, array<int, mixed>> $library
     * @param array<int, string> $linked
     * @param \stdClass $form
     */
    public function registerForm(object $form, array &$library, array &$linked, int $formId): bool
    {
        $this->processor->addFunction(
            $form->script1cond,
            $form->script1id,
            'ff_' . $form->name . '_init',
            $form->script1code,
            $library,
            $linked,
            'f',
            $formId,
            1
        );
        if ($this->processor->bury()) {
            return true;
        }

        $this->processor->addFunction(
            $form->script2cond,
            $form->script2id,
            'ff_' . $form->name . '_submitted',
            $form->script2code,
            $library,
            $linked,
            'f',
            $formId,
            1
        );

        return $this->processor->bury();
    }

    /**
     * @param array<int, array<int, mixed>> $library
     * @param array<int, string> $linked
     * @param \stdClass $element
     */
    public function registerElement(object $element, array &$library, array &$linked): bool
    {
        $callbacks = [
            ['script1cond', 'script1id', 'init', 'script1code'],
            ['script2cond', 'script2id', 'action', 'script2code'],
            ['script3cond', 'script3id', 'validate', 'script3code'],
        ];

        foreach ($callbacks as $index => [$condition, $id, $suffix, $code]) {
            $this->processor->addFunction(
                $element->{$condition},
                $element->{$id},
                'ff_' . $element->name . '_' . $suffix,
                $element->{$code},
                $library,
                $linked,
                'e',
                $element->id,
                1
            );
            if ($this->processor->bury()) {
                if ($index === 2) {
                    ob_end_clean();
                }

                return true;
            }
        }

        return false;
    }

    /**
     * @param array<int, array<int, mixed>> $library
     * @param array<int, string> $linked
     */
    public function registerIconBorders(array &$library, array &$linked, string $newline): bool
    {
        $this->processor->linkcode(
            'ff_hideIconBorder',
            $library,
            $linked,
            'function ff_hideIconBorder(element)' . $newline .
            '{' . $newline .
            '    element.style.border = "none";' . $newline .
            '} // ff_hideIconBorder'
        );

        if ($this->processor->bury()) {
            return true;
        }

        $this->processor->linkcode(
            'ff_dispIconBorder',
            $library,
            $linked,
            'function ff_dispIconBorder(element)' . $newline .
            '{' . $newline .
            '    element.style.border = "1px outset";' . $newline .
            '} // ff_dispIconBorder'
        );

        return $this->processor->bury();
    }

    /**
     * @param array<int, array<int, mixed>> $library
     * @param array<int, string> $linked
     * @param \stdClass $element
     */
    public function registerStaticTextScan(object $element, array &$library, array &$linked): void
    {
        if ($element->type === 'Static Text/HTML') {
            $this->processor->linkcode('#scanonly', $library, $linked, $element->data1);
        }
    }
}
