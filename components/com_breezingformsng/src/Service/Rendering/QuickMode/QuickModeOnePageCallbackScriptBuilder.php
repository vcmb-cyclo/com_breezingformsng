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

/** Builds the two callbacks shared by the OnePage runtime script. */
final class QuickModeOnePageCallbackScriptBuilder
{
    public function restoreSubmitButton(string $newline): string
    {
        return '                        function bf_restore_submitbutton(){' . $newline
            . '                            var cloned_submit = JQuery(orig_submit_button).clone(true);' . $newline
            . '                            var old_submit = JQuery( "#bfSubmitButton" )'
            . '.replaceWith( JQuery(cloned_submit) );' . $newline
            . '                            JQuery(old_submit).remove();' . $newline
            . '                            JQuery(cloned_submit).attr("id","bfSubmitButton");' . $newline
            . $newline
            . '                            bfSubmitSpinner.bind("#bfSubmitButton");' . $newline
            . '                        }' . $newline;
    }

    public function closeRemodal(string $redirectUrl, string $newline): string
    {
        return '                   function bf_remodal_close(){' . $newline
            . '                        if(typeof crbc_cart_url != "undefined"){' . $newline
            . '                            location.href = crbc_cart_url;' . $newline
            . '                        }else{' . $newline
            . '                            location.href = ' . $redirectUrl . ';' . $newline
            . '                        }' . $newline
            . '                   }' . $newline;
    }
}
