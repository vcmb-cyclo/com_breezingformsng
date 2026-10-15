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
 * Builds the historical AJAX CAPTCHA validation callback.
 */
final class CaptchaLegacyValidationScriptBuilder
{
    public function build(string $captchaError, string $imageEndpoint, string $checkEndpoint, int $page): string
    {
        $imageEndpoint = self::escapeJavaScriptStringContent($imageEndpoint);
        $checkEndpoint = self::escapeJavaScriptStringContent($checkEndpoint);

        // phpcs:disable Generic.Files.LineLength.TooLong -- Preserve legacy JavaScript lines verbatim.
        return strtr(
            <<<'JS'

				function bfAjaxObject101() {
					this.createRequestObject = function() {
						try {
							var ro = new XMLHttpRequest();
						}
						catch (e) {
							var ro = new ActiveXObject("Microsoft.XMLHTTP");
						}
						return ro;
					}
					this.sndReq = function(action, url, data) {
					
						if (action.toUpperCase() == "POST") {
							this.http.open(action,url,true);
							this.http.setRequestHeader("Content-Type","application/x-www-form-urlencoded");
							this.http.onreadystatechange = this.handleResponse;
							this.http.send(data);
						}
						else {
							this.http.open(action,url + "?" + data,true);
							this.http.onreadystatechange = this.handleResponse;
							this.http.send(null);
						}
					}
					this.handleResponse = function() {
						if ( me.http.readyState == 4) {
							if (typeof me.funcDone == "function") { me.funcDone();}
							var rawdata = me.http.responseText.split("|");
							for ( var i = 0; i < rawdata.length; i++ ) {
								var item = (rawdata[i]).split("=>");
								if (item[0] != "") {
									if (item[1].substr(0,3) == "%V%" ) {
										document.getElementById(item[0]).value = item[1].substring(3);
									}
									else {
										if(item[1] == "true"){
                                                                                    if(typeof bfDoFlashUpload != 'undefined'){
                                                                                        bfDoFlashUpload();
                                                                                    } else {
									   		ff_submitForm2();
                                                                                    }
									   } else {
                                                                                if(typeof JQuery != "undefined" && JQuery("#bfSubmitMessage"))
									        {
                                                                                    JQuery("#bfSubmitMessage").css("visibility","hidden");
                                                                                    JQuery("#bfSubmitMessage").css("display","none");
									        }
                                                                                if(typeof bfUseErrorAlerts == "undefined"){
                                                                                    alert(__CAPTCHA_ERROR__);
									        } else {
                                                                                   if(typeof inlineErrorElements != "undefined"){
                                                                                     inlineErrorElements.push(["bfCaptchaEntry",__CAPTCHA_ERROR__]);
                                                                                   }
									           bfShowErrors(__CAPTCHA_ERROR__);
									        }
                                                                                if(typeof bfSubmitSpinner != "undefined"){
                                                                                    
                                                                                    bf_restore_submitbutton();
                                                                                }
                                                                                
                                                                                        document.getElementById('ff_capimgValue').src = '__IMAGE_ENDPOINT__' + Math.random();
                                                                                        document.getElementById('bfCaptchaEntry').value = "";
                                                                                        if(ff_currentpage != __PAGE__)ff_switchpage(__PAGE__);
                                                                                        document.getElementById('bfCaptchaEntry').focus();
                                                                                        if(document.getElementById("bfSubmitButton")){
                                                                                            document.getElementById("bfSubmitButton").disabled = false;
                                                                                        }
                                                                                        if(typeof JQuery != "undefined"){JQuery(".bfCustomSubmitButton").prop("disabled", false);}
										}
                                                                                
									}
								}
							}
						}
						if ((me.http.readyState == 1) && (typeof me.funcWait == "function")) { me.funcWait(); }
					}
					var me = this;
					this.http = this.createRequestObject();

					var funcWait = null;
					var funcDone = null;
				}

                                function bfCheckCaptcha(){
                                        if(checkFileExtensions()){
                                               var ao = new bfAjaxObject101();
                                               ao.sndReq("get","__CHECK_ENDPOINT__"+document.getElementById("bfCaptchaEntry").value,"");
                                        }
                                }
JS,
            [
                '__CAPTCHA_ERROR__' => $captchaError,
                '__IMAGE_ENDPOINT__' => $imageEndpoint,
                '__PAGE__' => (string) $page,
                '__CHECK_ENDPOINT__' => $checkEndpoint,
            ]
        );
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
