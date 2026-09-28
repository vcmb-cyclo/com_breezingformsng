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

/** Builds the OnePage AJAX submit callback. */
final class QuickModeAjaxSubmitScriptBuilder
{
    public static function build(
        int $formId,
        bool $hasLastPage,
        string $errorMessage,
        string $successMessage,
        string $redirectUrl,
        string $newline,
    ): string {
        return '                        function bf_ajax_submit(){' . $newline .
            '                            var url = JQuery("#' . $formId . '").attr("action"); // the script where '
            . 'you handle the form input.'
            . $newline .
            '                            var posting = JQuery.post( url, JQuery("#' . $formId . '").serialize() );'
            . $newline .
            $newline .
            '                            JQuery(".bfPage").css("pointer-events","none");' . $newline .
            '                            JQuery(".bfPage").css("opacity","0.4");' . $newline .
            '                            JQuery("#remodal-thankyou-msg").html("");' . $newline .
            $newline .
            '                            posting.fail(function(jqXHR, textStatus, errorThrown) {' . $newline .
            '                                ladda_button.ladda("stop");' . $newline .
            '                                var err = ' . $errorMessage . ';' . $newline .
            '                                alert( err + ": " + textStatus );' . $newline .
            '                                console.log(errorThrown);' . $newline .
            '                                console.log(jqXHR);' . $newline .
            '                                if(typeof crbc_cart_url != "undefined"){' . $newline .
            '                                    location.href = crbc_cart_url;' . $newline .
            '                                }else{' . $newline .
            '                                    location.href = ' . $redirectUrl . ';' . $newline .
            '                                }' . $newline .
            '                            });' . $newline .
            $newline .
            '                            posting.done(function( data ) {' . $newline .
            '                                JQuery("#bfSubmitMessage").css("visibility","hidden");' . $newline .
            '                                JQuery("#bfSubmitMessage").css("display","none");' . $newline .
            '                                JQuery("#bfSubmitMessage").css("z-index","999999");' . $newline .
            '                                if(' . ($hasLastPage ? 'true' : 'false') . '){' . $newline .
            '                                    ladda_button.ladda("stop");' . $newline .
            '                                    var cloned = JQuery(".bfPage").last().clone();' . $newline .
            '                                    JQuery("#remodal-thankyou-msg").html(JQuery(".bfPage").last().clone().'
            . 'html());'
            . $newline .
            '                                    JQuery(cloned).remove();' . $newline .
            '                                    ff_currentpage = JQuery(".bfPage").length + 1;' . $newline .
            '                                    var inst = JQuery("[data-remodal-id=modal]").remodal();' . $newline .
            '                                    inst.open();' . $newline .
            $newline .
            '                                }else{' . $newline .
            '                                    alert(' . $successMessage . ');' . $newline .
            '                                    JQuery(".bfPage").css("pointer-events","auto");' . $newline .
            '                                    JQuery(".bfPage").css("opacity","1.0");' . $newline .
            '                                    ff_currentpage = JQuery(".bfPage").length + 1;' . $newline .
            '                                    ladda_button.ladda("stop");' . $newline .
            '                                    if(typeof crbc_cart_url != "undefined"){' . $newline .
            '                                        location.href = crbc_cart_url;' . $newline .
            '                                    }else{' . $newline .
            '                                        location.href = ' . $redirectUrl . ';' . $newline .
            '                                    }' . $newline .
            '                                }' . $newline .
            '                            });' . $newline .
            '                        }' . $newline;
    }
}
