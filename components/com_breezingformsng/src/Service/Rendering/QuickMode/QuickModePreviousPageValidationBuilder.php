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

/** Builds the OnePage previous-page validation callback. */
final class QuickModePreviousPageValidationBuilder
{
    public function build(string $newline): string
    {
        return '                        function bf_validate_prevpage(page)' . $newline .
            '                        {' . $newline .
            $newline .
            '                            if(typeof bfUseErrorAlerts != "undefined"){' . $newline .
            '                             JQuery(".bfErrorMessage").html("");' . $newline .
            '                             JQuery(".bfErrorMessage").css("display","none");' . $newline .
            '                            }' . $newline .
            $newline .
            '                            error = ff_validation(ff_currentpage);' . $newline .
            '                            if (error != "") {' . $newline .
            $newline .
            '                               if(typeof bfUseErrorAlerts == "undefined"){' . $newline .
            '                                   alert(error);' . $newline .
            '                                } else {' . $newline .
            '                                   bfShowErrors(error);' . $newline .
            '                                }' . $newline .
            '                                ff_validationFocus("");' . $newline .
            '                                /* need to test this */' . $newline .
            '                                /*' . $newline .
            '                                JQuery(".bfErrorMessage").css("display","none");' . $newline .
            '                                ff_currentpage = ff_getPageByName(ff_validationFocusName);' . $newline .
            '                                JQuery("#bfPage"+ff_currentpage+" .bfErrorMessage")'
            . '.css("display","block");' . $newline .
            '                                bfSubmitSpinner.stop();' . $newline .
            '                                */' . $newline .
            $newline .
            '                            } else{' . $newline .
            $newline .
            '                                if(page > 0){' . $newline .
            '                                 JQuery("#bfPage"+(page)).ScrollTo({offsetTop: 50});' . $newline .
            '                                 ff_currentpage = page;' . $newline .
            '                                 ff_initialize("pageentry");' . $newline .
            '                                }' . $newline .
            '                            }' . $newline .
            '                        } // ff_validate_prevpage' . $newline;
    }
}
