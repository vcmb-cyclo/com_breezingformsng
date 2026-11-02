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
 * Builds the submit callback used by QuickMode navigation buttons.
 */
final class QuickModeSubmitActionBuilder
{
    public function build(bool $onePage, bool $hasFlashUpload): string
    {
        $submitFunction = $onePage ? 'bf_validate_submit' : 'ff_validate_submit';
        $submitCall = $submitFunction . "(this, 'click')";

        if (!$hasFlashUpload) {
            return $submitCall;
        }

        return "if(typeof bfAjaxObject101 == 'undefined' && typeof bfReCaptchaLoaded == 'undefined')"
            . "{bfDoFlashUpload()}else{"
            . $submitCall . '}';
    }

    /**
     * What a payment button does without a click action of its own: the form
     * refuses a plain submit, so the button has to start the validation. An
     * upload field may come after the button, so the page decides on the click.
     */
    public function buildForPaymentButton(bool $onePage): string
    {
        $submitFunction = $onePage ? 'bf_validate_submit' : 'ff_validate_submit';

        return "if(typeof bf_htmltextareainit != 'undefined'){ bf_htmltextareainit() }"
            . "if(typeof bfDoFlashUpload != 'undefined' && typeof bfAjaxObject101 == 'undefined'"
            . " && typeof bfReCaptchaLoaded == 'undefined'){bfDoFlashUpload()}else{"
            . $submitFunction . "(this, 'click')}";
    }
}
