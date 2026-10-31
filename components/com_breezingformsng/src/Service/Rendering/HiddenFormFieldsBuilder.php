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

/**
 * Builds the hidden `<input>` fields the rendered form needs to submit
 * correctly - payment method, CSRF token, routing, submission mode and
 * miscellaneous context/extra parameters. Consolidates what were six
 * single-purpose, single-caller classes (PaymentMethodFieldBuilder,
 * FormTokenFieldBuilder, AdditionalHiddenFieldsBuilder,
 * FormRoutingFieldsBuilder, FormSubmissionFieldsBuilder,
 * FormContextFieldsBuilder) - see php-architecture-migration-plan.md,
 * "Consolider les builders de champs cachés triviaux".
 */
final class HiddenFormFieldsBuilder
{
    public function paymentMethod(string $indentation): string
    {
        return $indentation . '<input type="hidden" name="ff_payment_method" id="bfPaymentMethod" value=""/>' . "\n";
    }

    public function token(string $token, string $indentation, string $newline = "\r\n"): string
    {
        return $indentation . $token . $newline;
    }

    /**
     * @param array<int|string, mixed> $parameters
     */
    public function additional(array $parameters, string $indentation, string $newline = "\r\n"): string
    {
        $output = '';

        foreach ($parameters as $name => $value) {
            $output .= $indentation . '<input type="hidden" name="'
                . htmlentities((string) $name, ENT_QUOTES, 'UTF-8') . '" value="'
                . htmlentities(urlencode((string) $value), ENT_QUOTES, 'UTF-8') . '"/>' . $newline;
        }

        return $output;
    }

    public function routing(string $return, string $template, string $newline = "\r\n"): string
    {
        $fields = '';

        if ($return !== '') {
            $fields .= '<input type="hidden" name="return" value="'
                . htmlentities($return, ENT_QUOTES, 'UTF-8') . '"/>' . $newline;
        }

        if ($template === 'component') {
            $fields .= '<input type="hidden" name="tmpl" value="component"/>' . $newline;
        }

        return $fields;
    }

    public function submission(
        int $formId,
        string $indent,
        string $newline
    ): string {
        $fields = $indent . '<input type="hidden" name="ff_form" value="'
            . htmlentities((string) $formId, ENT_QUOTES, 'UTF-8') . '"/>' . $newline;
        $fields .= $indent . '<input type="hidden" name="ff_task" value="submit"/>' . $newline;

        return $fields;
    }

    /**
     * @param array<string, int|string> $fields
     */
    public function context(array $fields, string $indentation, string $newline = "\r\n"): string
    {
        $output = '';

        foreach ($fields as $name => $value) {
            $output .= $indentation . '<input type="hidden" name="'
                . htmlentities($name, ENT_QUOTES, 'UTF-8') . '" value="'
                . htmlentities((string) $value, ENT_QUOTES, 'UTF-8') . '"/>' . $newline;
        }

        return $output;
    }

    public function optionalContext(
        int $target,
        bool $inFrame,
        bool $border,
        int $page,
        int $align,
        int $top,
        string $indentation,
        bool $includeTarget = true,
        bool $includeFrame = true,
        bool $includeBorder = true,
        bool $includeLayoutFields = true,
        string $newline = "\r\n"
    ): string {
        $fields = [];

        if ($includeTarget && $target > 1) {
            $fields['ff_target'] = $target;
        }
        if ($includeFrame && $inFrame) {
            $fields['ff_frame'] = 1;
        }
        if ($includeBorder && $border) {
            $fields['ff_border'] = 1;
        }
        if ($page !== 1) {
            $fields['ff_page'] = $page;
        }
        if ($includeLayoutFields && $align !== 1) {
            $fields['ff_align'] = $align;
        }
        if ($includeLayoutFields && $top !== 0) {
            $fields['ff_top'] = $top;
        }

        return $this->context($fields, $indentation, $newline);
    }

    public function contentBuilderTechnical(string $indentation, int $formId, int $recordId, bool $isNew): string
    {
        if ($formId === 0) {
            return '';
        }

        $fields = '<input type="hidden" name="cb_form_id" value="' . $formId . '"/>' . "\n";
        if ($recordId !== 0) {
            $fields .= '<input type="hidden" name="cb_record_id" value="' . $recordId . '"/>' . "\n";
        }
        if ($isNew) {
            $fields .= '<input type="hidden" name="cbIsNew" value="1"/>' . "\n";
        }

        return $indentation . str_replace("\n", "\n" . $indentation, rtrim($fields, "\n")) . "\n";
    }
}
