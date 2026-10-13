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

use Joomla\CMS\Router\Route;

/**
 * Builds the historical ReCaptcha validation callback.
 */
final class CaptchaReCaptchaValidationScriptBuilder
{
    public function build(string $captchaError, string $recaptchaEndpoint, int $page): string
    {
        $recaptchaEndpoint = self::escapeJavaScriptStringContent(Route::_($recaptchaEndpoint));

        // phpcs:disable Generic.Files.LineLength.TooLong -- Preserve legacy JavaScript lines verbatim.
        return 'var bfReCaptchaLoaded = true;
                                    function bfCheckCaptcha(){
					if(checkFileExtensions()){
                                                function bfValidateCaptcha()
                                                {
                                                    if(typeof bfInvisibleRecaptcha != "undefined" && bfInvisibleRecaptcha === false){
														if(typeof bfDoFlashUpload != \'undefined\'){
															bfDoFlashUpload();
														} else {
															ff_submitForm2();
														}
														return;
                                                    }
                                                    
                                                    if(typeof onloadBFNewRecaptchaCallback == "undefined"){
                                                        challengeField = JQuery("input#recaptcha_challenge_field").val();
                                                        responseField = JQuery("input#recaptcha_response_field").val();
                                                        var html = JQuery.ajax({
                                                        type: "POST",
                                                        url: "' . $recaptchaEndpoint . '",
                                                        data: "recaptcha_challenge_field=" + challengeField + "&recaptcha_response_field=" + responseField,
                                                        async: false
                                                        }).responseText;

                                                        if (html.replace(/^\s+|\s+$/, "") == "success")
                                                        {
                                                            if(typeof bfDoFlashUpload != \'undefined\'){
                                                                bfDoFlashUpload();
                                                            } else {
                                                                ff_submitForm2();
                                                            }
                                                        }
                                                        else
                                                        {
                                                                if(typeof bfUseErrorAlerts == "undefined"){
                                                                        alert(' . $captchaError . ');
                                                                } else {
                                                                    if(typeof inlineErrorElements != "undefined"){
                                                                        inlineErrorElements.push(["bfReCaptchaEntry",' . $captchaError . ']);
                                                                    }
                                                                    bfShowErrors(' . $captchaError . ');
                                                                }

                                                                if(ff_currentpage != ' . $page . ')ff_switchpage(' . $page . ');
                                                                Recaptcha.focus_response_field();

                                                                Recaptcha.reload();

                                                                if(document.getElementById("bfSubmitButton")){
                                                                    document.getElementById("bfSubmitButton").disabled = false;
                                                                }
                                                                if(typeof JQuery != "undefined"){JQuery(".bfCustomSubmitButton").prop("disabled", false);}
                                                                if(typeof bfSubmitSpinner != "undefined"){
                                                                    bf_restore_submitbutton();
                                                                }
                                                                
                                                        }
                                                    }
                                                    else{
                                                        
                                                        if(typeof bfInvisibleRecaptcha != "undefined"){
                                                        
                                                            grecaptcha.execute();
                                                        }
                                                        
                                                        var gresponse = grecaptcha.getResponse();
                                                        
                                                        if(gresponse == ""){
                                                            
                                                            if(typeof bfInvisibleRecaptcha == "undefined"){
                                                            
	                                                            if(typeof bfUseErrorAlerts == "undefined"){
	                                                                    alert(' . $captchaError . ');
	                                                            } else {
	                                                                if(typeof inlineErrorElements != "undefined"){
	                                                                    inlineErrorElements.push(["bfReCaptchaEntry",' . $captchaError . ']);
	                                                                }
	                                                                bfShowErrors(' . $captchaError . ');
	                                                            }
                                                            
                                                            
                                                                if(ff_currentpage != ' . $page . ')ff_switchpage(' . $page . ');
                                                            }
                                                            if(document.getElementById("bfSubmitButton")){
                                                                document.getElementById("bfSubmitButton").disabled = false;
                                                            }
                                                            if(typeof JQuery != "undefined"){JQuery(".bfCustomSubmitButton").prop("disabled", false);}
                                                            if(typeof bfSubmitSpinner != "undefined"){
                                                                bf_restore_submitbutton();
                                                            }
                                                            
                                                            
                                                        }else{
               
                                                            if(typeof bfDoFlashUpload != \'undefined\'){
                                                                bfDoFlashUpload();
                                                            } else {
                                                                ff_submitForm2();
                                                            }
                                                        }
                                                    }
                                                }

                                                bfValidateCaptcha();

					}
						}';
        // phpcs:enable Generic.Files.LineLength.TooLong
    }

    private static function escapeJavaScriptStringContent(string $value): string
    {
        $json = json_encode(
            $value,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT
        );

        return substr($json, 1, -1);
    }
}
