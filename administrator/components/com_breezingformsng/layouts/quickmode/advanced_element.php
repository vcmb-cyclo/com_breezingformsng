<?php
/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/** @var string $active_language_code */
use Joomla\CMS\Language\Text;
?>
                                    <div class="bfAdvanced" id="bfSectionAdvanced" style="display:none">
                                        <div class="bfPropertyWrap bfng-field">
                                            <label class="bfPropertyLabel bfng-field__label"
                                                title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_SECTION_TURNOFF')); ?>"
                                                for="bfSectionAdvancedTurnOff">
                                                <?php echo Text::_('COM_BREEZINGFORMSNG_TURN_OFF_INITIALLY'); ?>
                                            </label>
                                            <input type="checkbox" value="" id="bfSectionAdvancedTurnOff" />
                                        </div>
                                    </div>
                                    <div class="bfAdvanced" id="bfElementAdvanced" style="display:none">
                                        <br />
                                        <fieldset>
                                            <legend>
                                                <?php echo Text::_('COM_BREEZINGFORMSNG_ADVANCED_ELEMENT_OPTIONS'); ?>
                                            </legend>
                                            <div></div>
                                            <!-- HIDDEN BEGIN -->
                                            <div class="bfElementTypeClass" id="bfElementTypeHiddenAdvanced"
                                                style="display:none">
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_ORDER_NUMBER')); ?>"
                                                        for="bfElementHiddenAdvancedOrderNumber">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ORDER_NUMBER'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementHiddenAdvancedOrderNumber" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_LOGGING')); ?>"
                                                        for="bfElementHiddenAdvancedLogging">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_LOGGING'); ?>
                                                    </label>
                                                    <input type="checkbox" value="" id="bfElementHiddenAdvancedLogging" />
                                                </div>
                                            </div>
                                            <!-- HIDDEN END -->

                                            <!-- NUMBER INPUT BEGIN -->
                                            <div class="bfElementTypeClass" id="bfElementTypeNumberInputAdvanced"
                                                style="display:none">
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_HIDE_LABEL')); ?>"
                                                        for="bfElementNumberInputAdvancedHideLabel">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_HIDE_LABEL'); ?>
                                                    </label>
                                                    <input checked="checked" type="checkbox" value=""
                                                        id="bfElementNumberInputAdvancedHideLabel" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_LOGGING')); ?>"
                                                        for="bfElementNumberInputAdvancedLogging">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_LOGGING'); ?>
                                                    </label>
                                                    <input checked="checked" type="checkbox" value=""
                                                        id="bfElementNumberInputAdvancedLogging" />
                                                </div>
                                            <!-- Begin option Range -->
                                                <div class="bfPropertyWrap bfng-field"> 
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_RANGE')); ?>"
                                                        for="bfElementNumberInputAdvancedRange">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_RANGE'); ?>
                                                    </label>
                                                    <input type="checkbox" value=""
                                                        id="bfElementNumberInputAdvancedRange" />
                                                </div>
                                            <!-- End option Range -->
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_NUMBER_INPUT_STEP_TOOLTIP')); ?>"
                                                        for="bfElementNumberInputAdvancedStep">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_QM_NUMBER_INPUT_STEP'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementNumberInputAdvancedStep" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_NUMBER_INPUT_MIN_TOOLTIP')); ?>"
                                                        for="bfElementNumberInputAdvancedMin">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_QM_NUMBER_INPUT_MIN'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementNumberInputAdvancedMin" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_NUMBER_INPUT_MAX_TOOLTIP')); ?>"
                                                        for="bfElementNumberInputAdvancedMax">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_QM_NUMBER_INPUT_MAX'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementNumberInputAdvancedMax" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_ORDER_NUMBER')); ?>"
                                                        for="bfElementNumberInputOrderNumber">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ORDER_NUMBER'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementNumberInputOrderNumber" />
                                                </div>
                                            </div>
                                            <!-- NUMBER INPUT END -->
                                            <!--  SUMMARIZE BEGIN -->
                                            <div class="bfElementTypeClass" id="bfElementTypeSummarizeAdvanced"
                                                style="display:none">
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_SUMMARIZE_CALC')); ?>"
                                                        for="bfElementAdvancedSummarizeCalc">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_CALC'); ?>
                                                    </label>
                                                    <textarea id="bfElementAdvancedSummarizeCalc"></textarea>
                                                </div>
                                            </div>
                                            <!--  SUMMARIZE END -->

                                            <!-- TEXTFIELD BEGIN -->
                                            <div class="bfElementTypeClass" id="bfElementTypeTextAdvanced" style="display:none">
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_TEXTFIELD_PASSWORD')); ?>"
                                                        for="bfElementAdvancedPassword">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_PASSWORD'); ?>
                                                    </label>
                                                    <input type="checkbox" value="" id="bfElementAdvancedPassword" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_TEXTFIELD_READONLY')); ?>"
                                                        for="bfElementAdvancedReadOnly">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_READONLY'); ?>
                                                    </label>
                                                    <input type="checkbox" value="" id="bfElementAdvancedReadOnly" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_TEXTFIELD_MAILBACK')); ?>"
                                                        for="bfElementAdvancedMailback">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_MAILBACK'); ?>
                                                    </label>
                                                    <input type="checkbox" value="" id="bfElementAdvancedMailback" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_TEXTFIELD_MAILBACK_AS_SENDER')); ?>"
                                                        for="bfElementAdvancedMailbackAsSender">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_MAILBACK_AS_SENDER'); ?>
                                                    </label>
                                                    <input type="checkbox" value="" id="bfElementAdvancedMailbackAsSender" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_TEXTFIELD_MAILBACK_FILE')); ?>"
                                                        for="bfElementAdvancedMailbackfile">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_MAILBACKFILE'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementAdvancedMailbackfile" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_HIDE_LABEL')); ?>"
                                                        for="bfElementAdvancedHideLabel">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_HIDE_LABEL'); ?>
                                                    </label>
                                                    <input checked="checked" type="checkbox" value=""
                                                        id="bfElementAdvancedHideLabel" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_LOGGING')); ?>"
                                                        for="bfElementAdvancedLogging">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_LOGGING'); ?>
                                                    </label>
                                                    <input checked="checked" type="checkbox" value=""
                                                        id="bfElementAdvancedLogging" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_ORDER_NUMBER')); ?>"
                                                        for="bfElementOrderNumber">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ORDER_NUMBER'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementOrderNumber" />
                                                </div>
                                            </div>
                                            <!-- TEXTFIELD END -->
                                            <!-- TEXTAREA BEGIN -->
                                            <div class="bfElementTypeClass" id="bfElementTypeTextareaAdvanced"
                                                style="display:none">
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_HIDE_LABEL')); ?>"
                                                        for="bfElementTextareaAdvancedHideLabel">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_HIDE_LABEL'); ?>
                                                    </label>
                                                    <input checked="checked" type="checkbox" value=""
                                                        id="bfElementTextareaAdvancedHideLabel" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_LOGGING')); ?>"
                                                        for="bfElementTextareaAdvancedLogging">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_LOGGING'); ?>
                                                    </label>
                                                    <input checked="checked" type="checkbox" value=""
                                                        id="bfElementTextareaAdvancedLogging" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_ORDER_NUMBER')); ?>"
                                                        for="bfElementTextareaAdvancedOrderNumber">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ORDER_NUMBER'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementTextareaAdvancedOrderNumber" />
                                                </div>
                                            </div>
                                            <!-- TEXTAREA END -->

                                            <!-- RADIOGROUP BEGIN -->
                                            <div class="bfElementTypeClass" id="bfElementTypeRadioGroupAdvanced"
                                                style="display:none">
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_HIDE_LABEL')); ?>"
                                                        for="bfElementRadioGroupAdvancedHideLabel">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_HIDE_LABEL'); ?>
                                                    </label>
                                                    <input checked="checked" type="checkbox" value=""
                                                        id="bfElementRadioGroupAdvancedHideLabel" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_LOGGING')); ?>"
                                                        for="bfElementRadioGroupAdvancedLogging">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_LOGGING'); ?>
                                                    </label>
                                                    <input checked="checked" type="checkbox" value=""
                                                        id="bfElementRadioGroupAdvancedLogging" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_ORDER_NUMBER')); ?>"
                                                        for="bfElementRadioGroupAdvancedOrderNumber">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ORDER_NUMBER'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementRadioGroupAdvancedOrderNumber" />
                                                </div>
                                            </div>
                                            <!-- RADIOGROUP END -->
                                            <!-- SUBMITBUTTON BEGIN -->
                                            <div class="bfElementTypeClass" id="bfElementTypeSubmitButtonAdvanced"
                                                style="display:none">
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_HIDE_LABEL')); ?>"
                                                        for="bfElementSubmitButtonAdvancedHideLabel">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_HIDE_LABEL'); ?>
                                                    </label>
                                                    <input checked="checked" type="checkbox" value=""
                                                        id="bfElementSubmitButtonAdvancedHideLabel" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_SUBMIT_BUTTON_SOURCE')); ?>"
                                                        for="bfElementSubmitButtonAdvancedSrc">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_SOURCE'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementSubmitButtonAdvancedSrc" />
                                                </div>

                                                <div class="bfPropertyWrap bfTrans bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_SUBMIT_BUTTON_SOURCE')); ?>"
                                                        for="bfElementSubmitButtonAdvancedSrcTrans">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_SOURCE'); ?>
                                                        <br /><em>(
                                                            <?php echo $active_language_code ?>)
                                                        </em>
                                                    </label>
                                                    <input type="text" value="" id="bfElementSubmitButtonAdvancedSrcTrans" />
                                                </div>
                                            </div>
                                            <!-- SUBMITBUTTON END -->


                                            <!-- STRIPE BEGIN -->
                                            <div class="bfElementTypeClass" id="bfElementTypeStripeAdvanced"
                                                style="display:none">


                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_STRIPE_IMAGE')); ?>"
                                                        for="bfElementStripeAdvancedImage">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_IMAGE'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementStripeAdvancedImage" />
                                                </div>

                                                <div class="bfPropertyWrap bfTrans bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_STRIPE_IMAGE')); ?>"
                                                        for="bfElementStripeAdvancedImageTrans">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_IMAGE'); ?>
                                                        <br /><em>(
                                                            <?php echo $active_language_code ?>)
                                                        </em>
                                                    </label>
                                                    <input type="text" value="" id="bfElementStripeAdvancedImageTrans" />
                                                </div>

                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_HIDE_LABEL')); ?>"
                                                        for="bfElementStripeAdvancedHideLabel">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_HIDE_LABEL'); ?>
                                                    </label>
                                                    <input checked="checked" type="checkbox" value=""
                                                        id="bfElementStripeAdvancedHideLabel" />
                                                </div>

                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_STRIPE_FILE')); ?>"
                                                        for="bfElementStripeAdvancedDownloadableFile">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_DOWNLOADABLE_FILE'); ?>
                                                    </label>
                                                    <input checked="checked" type="checkbox" value=""
                                                        id="bfElementStripeAdvancedDownloadableFile" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_STRIPE_FILEPATH')); ?>"
                                                        for="bfElementStripeAdvancedFilepath">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_FILEPATH'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementStripeAdvancedFilepath" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_STRIPE_TRIES')); ?>"
                                                        for="bfElementStripeAdvancedDownloadTries">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_DOWNLOAD_TRIES'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementStripeAdvancedDownloadTries" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltiptext(Text::_('COM_BREEZINGFORMSNG_QM_STRIPE_EMAIL')); ?>"
                                                        for="bfElementStripeAdvancedEmailField">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_STRIPE_EMAIL'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementStripeAdvancedEmailField" />
                                                </div>
                                            </div>
                                            <!-- STRIPE END -->



                                            <!-- PAYPAL BEGIN -->
                                            <div class="bfElementTypeClass" id="bfElementTypePayPalAdvanced"
                                                style="display:none">
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_PAYPAL_IPN')); ?>"
                                                        for="bfElementPayPalAdvancedUseIpn">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_USE_IPN'); ?>
                                                    </label>
                                                    <input type="checkbox" value="" id="bfElementPayPalAdvancedUseIpn" />
                                                    <?php echo Text::_('COM_BREEZINGFORMSNG_USE_IPN_DESCRIPTION'); ?>
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_HIDE_LABEL')); ?>"
                                                        for="bfElementPayPalAdvancedHideLabel">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_HIDE_LABEL'); ?>
                                                    </label>
                                                    <input checked="checked" type="checkbox" value=""
                                                        id="bfElementPayPalAdvancedHideLabel" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_PAYPAL_IMAGE')); ?>"
                                                        for="bfElementPayPalAdvancedImage">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_IMAGE'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementPayPalAdvancedImage" />
                                                </div>

                                                <div class="bfPropertyWrap bfTrans bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_PAYPAL_IMAGE')); ?>"
                                                        for="bfElementPayPalAdvancedImageTrans">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_IMAGE'); ?>
                                                        <br /><em>(
                                                            <?php echo $active_language_code ?>)
                                                        </em>
                                                    </label>
                                                    <input type="text" value="" id="bfElementPayPalAdvancedImageTrans" />
                                                </div>

                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_PAYPAL_TESTACCOUNT')); ?>"
                                                        for="bfElementPayPalAdvancedTestaccount">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_TESTACCOUNT'); ?>
                                                    </label>
                                                    <input checked="checked" type="checkbox" value=""
                                                        id="bfElementPayPalAdvancedTestaccount" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_PAYPAL_TESTBUSINESS')); ?>"
                                                        for="bfElementPayPalAdvancedTestBusiness">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_TESTBUSINESS'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementPayPalAdvancedTestBusiness" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_PAYPAL_TESTTOKEN')); ?>"
                                                        for="bfElementPayPalAdvancedTestToken">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_TESTTOKEN'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementPayPalAdvancedTestToken" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_PAYPAL_FILE')); ?>"
                                                        for="bfElementPayPalAdvancedDownloadableFile">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_DOWNLOADABLE_FILE'); ?>
                                                    </label>
                                                    <input checked="checked" type="checkbox" value=""
                                                        id="bfElementPayPalAdvancedDownloadableFile" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_PAYPAL_FILEPATH')); ?>"
                                                        for="bfElementPayPalAdvancedFilepath">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_FILEPATH'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementPayPalAdvancedFilepath" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_PAYPAL_TRIES')); ?>"
                                                        for="bfElementPayPalAdvancedDownloadTries">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_DOWNLOAD_TRIES'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementPayPalAdvancedDownloadTries" />
                                                </div>
                                            </div>
                                            <!-- PAYPAL END -->
                                            <!-- CAPTCHA BEGIN -->
                                            <div class="bfElementTypeClass" id="bfElementTypeCaptchaAdvanced"
                                                style="display:none">
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_HIDE_LABEL')); ?>"
                                                        for="bfElementCaptchaAdvancedHideLabel">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_HIDE_LABEL'); ?>
                                                    </label>
                                                    <input checked="checked" type="checkbox" value=""
                                                        id="bfElementCaptchaAdvancedHideLabel" />
                                                </div>
                                            </div>
                                            <!-- CAPTCHA END -->
                                            <!-- RECAPTCHA BEGIN -->
                                            <div class="bfElementTypeClass" id="bfElementTypeReCaptchaAdvanced"
                                                style="display:none">
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_HIDE_LABEL')); ?>"
                                                        for="bfElementReCaptchaAdvancedHideLabel">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_HIDE_LABEL'); ?>
                                                    </label>
                                                    <input checked="checked" type="checkbox" value=""
                                                        id="bfElementReCaptchaAdvancedHideLabel" />
                                                </div>
                                            </div>
                                            <!-- RECAPTCHA END -->
                                            <!-- CALENDAR RESPONSIVE BEGIN -->
                                            <div class="bfElementTypeClass" id="bfElementTypeCalendarResponsiveAdvanced"
                                                style="display:none">
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_HIDE_LABEL')); ?>"
                                                        for="bfElementCalendarResponsiveAdvancedHideLabel">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_HIDE_LABEL'); ?>
                                                    </label>
                                                    <input checked="checked" type="checkbox" value=""
                                                        id="bfElementCalendarResponsiveAdvancedHideLabel" />
                                                </div>
                                            </div>
                                            <!-- CALENDAR RESPONSIVE END -->
                                            <!-- CALENDAR BEGIN -->
                                            <div class="bfElementTypeClass" id="bfElementTypeCalendarAdvanced"
                                                style="display:none">
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_HIDE_LABEL')); ?>"
                                                        for="bfElementCalendarAdvancedHideLabel">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_HIDE_LABEL'); ?>
                                                    </label>
                                                    <input checked="checked" type="checkbox" value=""
                                                        id="bfElementCalendarAdvancedHideLabel" />
                                                </div>
                                                    <div class="bfPropertyWrap bfng-field">
                                                        <label class="bfPropertyLabel bfng-field__label"
                                                            title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_CALENDAR_SHOW_TIME_TOOLTIP')); ?>"
                                                            for="bfElementCalendarAdvancedShowTime">
                                                            <?php echo Text::_('COM_BREEZINGFORMSNG_QM_CALENDAR_SHOW_TIME'); ?>
                                                        </label>
                                                        <input checked="checked" type="checkbox" value=""
                                                            id="bfElementCalendarAdvancedShowTime" />
                                                    </div>
                                                    <div class="bfPropertyWrap bfng-field" style="display:none;">
                                                        <label class="bfPropertyLabel bfng-field__label"
                                                            title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_CALENDAR_FIRST_DAY_TOOLTIP')); ?>"
                                                            for="bfElementCalendarAdvancedFirstDay">
                                                            <?php echo Text::_('COM_BREEZINGFORMSNG_QM_CALENDAR_FIRST_DAY'); ?>
                                                        </label>
                                                        <input type="text" value="" id="bfElementCalendarAdvancedFirstDay" />
                                                    </div>
                                                    <div class="bfPropertyWrap bfng-field">
                                                        <label class="bfPropertyLabel bfng-field__label"
                                                            title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_CALENDAR_24H_TIME_TOOLTIP')); ?>"
                                                            for="bfElementCalendarAdvancedTimeFormat">
                                                            <?php echo Text::_('COM_BREEZINGFORMSNG_QM_CALENDAR_24H_TIME'); ?>
                                                        </label>
                                                        <input checked="checked" type="checkbox" value=""
                                                            id="bfElementCalendarAdvancedTimeFormat" />
                                                    </div>
                                                    <div class="bfPropertyWrap bfng-field">
                                                        <label class="bfPropertyLabel bfng-field__label"
                                                            title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_CALENDAR_SINGLE_HEADER_TOOLTIP')); ?>"
                                                            for="bfElementCalendarAdvancedSingleHeader">
                                                            <?php echo Text::_('COM_BREEZINGFORMSNG_QM_CALENDAR_SINGLE_HEADER'); ?>
                                                        </label>
                                                        <input checked="checked" type="checkbox" value=""
                                                            id="bfElementCalendarAdvancedSingleHeader" />
                                                    </div>
                                                    <div class="bfPropertyWrap bfng-field">
                                                        <label class="bfPropertyLabel bfng-field__label"
                                                            title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_CALENDAR_TODAY_BTN_TOOLTIP')); ?>"
                                                            for="bfElementCalendarAdvancedTodayButton">
                                                            <?php echo Text::_('COM_BREEZINGFORMSNG_QM_CALENDAR_TODAY_BTN'); ?>
                                                        </label>
                                                        <input checked="checked" type="checkbox" value=""
                                                            id="bfElementCalendarAdvancedTodayButton" />
                                                    </div>
                                                    <div class="bfPropertyWrap bfng-field">
                                                        <label class="bfPropertyLabel bfng-field__label"
                                                            title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_CALENDAR_WEEK_NMBR_TOOLTIP')); ?>"
                                                            for="bfElementCalendarAdvancedWeekNumbers">
                                                            <?php echo Text::_('COM_BREEZINGFORMSNG_QM_CALENDAR_WEEK_NMBR'); ?>
                                                        </label>
                                                        <input checked="checked" type="checkbox" value=""
                                                            id="bfElementCalendarAdvancedWeekNumbers" />
                                                    </div>
                                                    <div class="bfPropertyWrap bfng-field">
                                                        <label class="bfPropertyLabel bfng-field__label"
                                                            title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_CALENDAR_MIN_YEAR_TOOLTIP')); ?>"
                                                            for="bfElementCalendarAdvancedMinYear">
                                                            <?php echo Text::_('COM_BREEZINGFORMSNG_QM_CALENDAR_MIN_YEAR'); ?>
                                                        </label>
                                                        <input type="text" value="" id="bfElementCalendarAdvancedMinYear" />
                                                    </div>
                                                    <div class="bfPropertyWrap bfng-field">
                                                        <label class="bfPropertyLabel bfng-field__label"
                                                            title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_CALENDAR_MAX_YEAR_TOOLTIP')); ?>"
                                                            for="bfElementCalendarAdvancedShowTime">
                                                            <?php echo Text::_('COM_BREEZINGFORMSNG_QM_CALENDAR_MAX_YEAR'); ?>
                                                        </label>
                                                        <input type="text" value="" id="bfElementCalendarAdvancedMaxYear" />
                                                    </div>
                                            </div>
                                            <!-- CALENDAR END -->
                                            <!-- SIGNATURE BEGIN -->
                                            <div class="bfElementTypeClass" id="bfElementTypeSignatureAdvanced"
                                                style="display:none">
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_HIDE_LABEL')); ?>"
                                                        for="bfElementSignatureAdvancedHideLabel">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_HIDE_LABEL'); ?>
                                                    </label>
                                                    <input checked="checked" type="checkbox" value=""
                                                        id="bfElementSignatureAdvancedHideLabel" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_LOGGING')); ?>"
                                                        for="bfElementSignatureAdvancedLogging">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_LOGGING'); ?>
                                                    </label>
                                                    <input checked="checked" type="checkbox" value=""
                                                        id="bfElementSignatureAdvancedLogging" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_ORDER_NUMBER')); ?>"
                                                        for="bfElementSignatureAdvancedOrderNumber">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ORDER_NUMBER'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementSignatureAdvancedOrderNumber" />
                                                </div>
                                            </div>
                                            <!-- SIGNATURE END -->
                                            <!-- CHECKBOXGROUP BEGIN -->
                                            <div class="bfElementTypeClass" id="bfElementTypeCheckboxGroupAdvanced"
                                                style="display:none">
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_HIDE_LABEL')); ?>"
                                                        for="bfElementCheckboxGroupAdvancedHideLabel">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_HIDE_LABEL'); ?>
                                                    </label>
                                                    <input checked="checked" type="checkbox" value=""
                                                        id="bfElementCheckboxGroupAdvancedHideLabel" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_LOGGING')); ?>"
                                                        for="bfElementCheckboxGroupAdvancedLogging">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_LOGGING'); ?>
                                                    </label>
                                                    <input checked="checked" type="checkbox" value=""
                                                        id="bfElementCheckboxGroupAdvancedLogging" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_ORDER_NUMBER')); ?>"
                                                        for="bfElementCheckboxGroupAdvancedOrderNumber">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ORDER_NUMBER'); ?>
                                                    </label>
                                                    <input type="text" value=""
                                                        id="bfElementCheckboxGroupAdvancedOrderNumber" />
                                                </div>
                                            </div>
                                            <!-- CHECKBOXGROUP END -->
                                            <!-- CHECKBOX BEGIN -->
                                            <div class="bfElementTypeClass" id="bfElementTypeCheckboxAdvanced"
                                                style="display:none">
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_MAILBACK_ACCEPT')); ?>"
                                                        for="bfElementCheckboxAdvancedMailbackAccept">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_MAILBACK_ACCEPT'); ?>
                                                    </label>
                                                    <input checked="checked" type="checkbox" value=""
                                                        id="bfElementCheckboxAdvancedMailbackAccept" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_MAILBACK_CONNECTWITH')); ?>"
                                                        for="bfElementCheckboxAdvancedMailbackConnectWith">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_MAILBACK_CONNECT_WITH'); ?>
                                                    </label>
                                                    <input type="text" value=""
                                                        id="bfElementCheckboxAdvancedMailbackConnectWith" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_HIDE_LABEL')); ?>"
                                                        for="bfElementCheckboxAdvancedHideLabel">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_HIDE_LABEL'); ?>
                                                    </label>
                                                    <input checked="checked" type="checkbox" value=""
                                                        id="bfElementCheckboxAdvancedHideLabel" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_LOGGING')); ?>"
                                                        for="bfElementCheckboxAdvancedLogging">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_LOGGING'); ?>
                                                    </label>
                                                    <input checked="checked" type="checkbox" value=""
                                                        id="bfElementCheckboxAdvancedLogging" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_ORDER_NUMBER')); ?>"
                                                        for="bfElementCheckboxAdvancedOrderNumber">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ORDER_NUMBER'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementCheckboxAdvancedOrderNumber" />
                                                </div>
                                            </div>
                                            <!-- CHECKBOX END -->
                                            <!-- CHECKBOXGROUP BEGIN -->
                                            <div class="bfElementTypeClass" id="bfElementTypeSelectAdvanced"
                                                style="display:none">
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_HIDE_LABEL')); ?>"
                                                        for="bfElementSelectAdvancedHideLabel">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_HIDE_LABEL'); ?>
                                                    </label>
                                                    <input checked="checked" type="checkbox" value=""
                                                        id="bfElementSelectAdvancedHideLabel" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_SELECT_MAILBACK')); ?>"
                                                        for="bfElementSelectAdvancedMailback">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_MAILBACK'); ?>
                                                    </label>
                                                    <input checked="checked" type="checkbox" value=""
                                                        id="bfElementSelectAdvancedMailback" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_LOGGING')); ?>"
                                                        for="bfElementSelectAdvancedLogging">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_LOGGING'); ?>
                                                    </label>
                                                    <input checked="checked" type="checkbox" value=""
                                                        id="bfElementSelectAdvancedLogging" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_ORDER_NUMBER')); ?>"
                                                        for="bfElementSelectAdvancedOrderNumber">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ORDER_NUMBER'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementSelectAdvancedOrderNumber" />
                                                </div>
                                            </div>
                                            <!-- CHECKBOXGROUP END -->
                                            <!-- FILE BEGIN -->
                                            <div class="bfElementTypeClass" id="bfElementTypeFileAdvanced" style="display:none">
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_FILE_HTML5')); ?>"
                                                        for="bfElementFileAdvancedHtml5Uploader">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_HTML5_UPLOADER'); ?>
                                                    </label>
                                                    <input type="checkbox" value="" id="bfElementFileAdvancedHtml5Uploader" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_FILE_FLASH')); ?>"
                                                        for="bfElementFileAdvancedFlashUploader">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_FLASH_UPLOADER'); ?>
                                                    </label>
                                                    <input type="checkbox" value="" id="bfElementFileAdvancedFlashUploader" />
                                                    <br />
                                                    <br />
                                                    <i>(
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_FLASH_UPLOADER_HINT'); ?>
                                                        )
                                                    </i>
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_FILE_MULTI')); ?>"
                                                        for="bfElementFileAdvancedFlashUploaderMulti">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_FLASH_UPLOADER_MULTI'); ?>
                                                    </label>
                                                    <input type="checkbox" value=""
                                                        id="bfElementFileAdvancedFlashUploaderMulti" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_FILE_BYTES')); ?>"
                                                        for="bfElementFileAdvancedFlashUploaderBytes">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_FLASH_UPLOADER_BYTES'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementFileAdvancedFlashUploaderBytes" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_FILE_WIDTH')); ?>"
                                                        for="bfElementFileAdvancedFlashUploaderWidth">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_FLASH_UPLOADER_WIDTH'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementFileAdvancedFlashUploaderWidth" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_FILE_HEIGHT')); ?>"
                                                        for="bfElementFileAdvancedFlashUploaderHeight">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_FLASH_UPLOADER_HEIGHT'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementFileAdvancedFlashUploaderHeight" />
                                                </div>
                                                <div class="bfPropertyWrap bfTrans bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_FILE_TRANSPARENT')); ?>"
                                                        for="bfElementFileAdvancedFlashUploaderTransparent">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_FLASH_UPLOADER_TRANSPARENT'); ?>
                                                    </label>
                                                    <input type="checkbox" value=""
                                                        id="bfElementFileAdvancedFlashUploaderTransparent" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_FILE_UPLOAD_DIRECTORY')); ?>"
                                                        for="bfElementFileAdvancedUploadDirectory">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_UPLOAD_DIRECTORY'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementFileAdvancedUploadDirectory" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_FILE_RESIZE_WIDTH')); ?>"
                                                        for="bfElementFileAdvancedResizeTargetWidth">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_RESIZE_TARGET_WIDTH'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementFileAdvancedResizeTargetWidth"
                                                        value="0" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_FILE_RESIZE_HEIGHT')); ?>"
                                                        for="bfElementFileAdvancedResizeTargetHeight">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_RESIZE_TARGET_HEIGHT'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementFileAdvancedResizeTargetHeight"
                                                        value="0" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_FILE_RESIZE_TYPE')); ?>"
                                                        for="bfElementFileAdvancedResizeType">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_RESIZE_TYPE'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementFileAdvancedResizeType" value="" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_FILE_RESIZE_BGCOLOR')); ?>"
                                                        for="bfElementFileAdvancedResizeBgcolor">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_RESIZE_BGCOLOR'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementFileAdvancedResizeBgcolor"
                                                        value="#ffffff" />
                                                    <br />
                                                    <br />
                                                    <i>(
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_RESIZE_HINT'); ?>)
                                                    </i>
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_FILE_TIMESTAMP')); ?>"
                                                        for="bfElementFileAdvancedTimestamp">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_TIMESTAMP'); ?>
                                                    </label>
                                                    <input checked="checked" type="checkbox" value=""
                                                        id="bfElementFileAdvancedTimestamp" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_FILE_EXTENSIONS')); ?>"
                                                        for="bfElementFileAdvancedAllowedFileExtensions">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ALLOWED_FILE_EXTENSIONS'); ?>
                                                    </label>
                                                    <input type="text" value=""
                                                        id="bfElementFileAdvancedAllowedFileExtensions" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_FILE_ATTACHUSERMAIL')); ?>"
                                                        for="bfElementFileAdvancedAttachToUserMail">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ATTACH_TO_USERMAIL'); ?>
                                                    </label>
                                                    <input checked="checked" type="checkbox" value=""
                                                        id="bfElementFileAdvancedAttachToUserMail" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_FILE_ATTACHADMINMAIL')); ?>"
                                                        for="bfElementFileAdvancedAttachToAdminMail">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ATTACH_TO_ADMINMAIL'); ?>
                                                    </label>
                                                    <input checked="checked" type="checkbox" value=""
                                                        id="bfElementFileAdvancedAttachToAdminMail" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_FILE_USEURL')); ?>"
                                                        for="bfElementFileAdvancedUseUrl">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_USE_URL'); ?>
                                                    </label>
                                                    <input checked="checked" type="checkbox" value=""
                                                        id="bfElementFileAdvancedUseUrl" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field" style="display:none;">
                                                    <!-- legacy and hidden as not required any longer -->
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_FILE_USEURL_DIR')); ?>"
                                                        for="bfElementFileAdvancedUseUrlDownloadDirectory">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_USE_URL_DOWNLOAD_DIRECTORY'); ?>
                                                    </label>
                                                    <input type="text" value=""
                                                        id="bfElementFileAdvancedUseUrlDownloadDirectory" />
                                                    <?php echo Text::_('COM_BREEZINGFORMSNG_USE_URL_DOWNLOAD_DIRECTORY_SET_SYNCH'); ?>
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_HIDE_LABEL')); ?>"
                                                        for="bfElementFileAdvancedHideLabel">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_HIDE_LABEL'); ?>
                                                    </label>
                                                    <input checked="checked" type="checkbox" value=""
                                                        id="bfElementFileAdvancedHideLabel" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_LOGGING')); ?>"
                                                        for="bfElementFileAdvancedLogging">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_LOGGING'); ?>
                                                    </label>
                                                    <input checked="checked" type="checkbox" value=""
                                                        id="bfElementFileAdvancedLogging" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_ORDER_NUMBER')); ?>"
                                                        for="bfElementFileAdvancedOrderNumber">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ORDER_NUMBER'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementFileAdvancedOrderNumber" />
                                                </div>
                                            </div>
                                            <!-- FILE END -->
                                            <div id="bfHideInMailback">
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_MAILBACK_HIDE')); ?>"
                                                        for="bfElementAdvancedHideInMailback">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_HIDE_IN_MAILBACK'); ?>
                                                    </label>
                                                    <input type="checkbox" value="" id="bfElementAdvancedHideInMailback" />
                                                </div>
                                            </div>

                                            <div id="bfAdvancedLeaf">
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_TABINDEX')); ?>"
                                                        id="bfElementAdvancedTabIndexLabel" for="bfElementAdvancedTabIndex">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_TAB_INDEX'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementAdvancedTabIndex" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_TURNOFF')); ?>"
                                                        for="bfElementAdvancedTurnOff">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_TURN_OFF_INITIALLY'); ?>
                                                    </label>
                                                    <input type="checkbox" value="" id="bfElementAdvancedTurnOff" />
                                                </div>
                                                <div id="bfLabelPositionToggle">
                                                    <div class="bfPropertyWrap bfng-field">
                                                        <label class="bfPropertyLabel bfng-field__label"
                                                            title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_LABELPOS')); ?>"
                                                            id="bfElementAdvancedLabelPositionLabel"
                                                            for="bfElementAdvancedLabelPosition">
                                                            <?php echo Text::_('COM_BREEZINGFORMSNG_LABEL_POSITION'); ?>
                                                        </label>
                                                        <select id="bfElementAdvancedLabelPosition">
                                                            <option value="left">
                                                                <?php echo Text::_('COM_BREEZINGFORMSNG_LEFT'); ?>
                                                            </option>
                                                            <option value="top">
                                                                <?php echo Text::_('COM_BREEZINGFORMSNG_TOP'); ?>
                                                            </option>
                                                            <option value="right">
                                                                <?php echo Text::_('COM_BREEZINGFORMSNG_RIGHT'); ?>
                                                            </option>
                                                            <option value="bottom">
                                                                <?php echo Text::_('COM_BREEZINGFORMSNG_BOTTOM'); ?>
                                                            </option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </fieldset>

                                        <fieldset id="bfInitScript" style="display:none">
                                            <br />
                                            <legend>
                                                <?php echo Text::_('COM_BREEZINGFORMSNG_ADVANCED_ELEMENT_INITSCRIPT'); ?>
                                            </legend>
                                            <div class="bfPropertyWrap bfng-field">
                                                <span
                                                    title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_INITSCRIPT')); ?>">
                                                    <?php echo Text::_('COM_BREEZINGFORMSNG_TYPE') ?>
                                                    :
                                                </span>
                                                <input
                                                    onclick="JQuery('#bfInitScriptFlags').css('display', 'none');
                                                                                            JQuery('#bfInitScriptLibrary').css('display', 'none');
                                                                                            JQuery('#bfInitScriptCustom').css('display', 'none');"
                                                    type="radio" name="initType" id="bfInitTypeNone" class="bfInitType"
                                                    value="0" />
                                                <?php echo Text::_('COM_BREEZINGFORMSNG_NONE') ?>
                                                <input
                                                    onclick="JQuery('#bfInitScriptFlags').css('display', '');JQuery('#bfInitScriptLibrary').css('display', '');
                                                                                            JQuery('#bfInitScriptCustom').css('display', 'none');"
                                                    type="radio" name="initType" id="bfInitTypeLibrary" class="bfInitType"
                                                    value="1" />
                                                <?php echo Text::_('COM_BREEZINGFORMSNG_LIBRARY') ?>
                                                <input
                                                    onclick="JQuery('#bfInitScriptFlags').css('display', '');
                                                                                            JQuery('#bfInitScriptLibrary').css('display', 'none');JQuery('#bfInitScriptCustom').css('display', '');"
                                                    type="radio" name="initType" id="bfInitTypeCustom" class="bfInitType"
                                                    value="2" />
                                                <?php echo Text::_('COM_BREEZINGFORMSNG_CUSTOM') ?>
                                            </div>
                                            <div id="bfInitScriptFlags" style="display:none">
                                                <hr />
                                                <div class="bfPropertyWrap bfng-field">
                                                    <input type="checkbox" id="bfInitFormEntry" class="bfInitFormEntry"
                                                        name="bfInitFormEntry" value="1" /><label for="bfInitFormEntry"
                                                        class="bfInitFormEntryLabel">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENTS_FORMENTRY'); ?>
                                                    </label>
                                                    <input type="checkbox" id="bfInitPageEntry" class="bfInitPageEntry"
                                                        name="bfInitPageEntry" value="1" /><label for="bfInitPageEntry"
                                                        class="bfInitFormEntryLabel">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENTS_PAGEENTRY'); ?>
                                                    </label>
                                                </div>
                                            </div>

                                            <div id="bfInitScriptLibrary" style="display:none">
                                                <hr />
                                                <div class="bfPropertyWrap bfng-field">
                                                    <?php echo Text::_('COM_BREEZINGFORMSNG_SCRIPT') ?>:<br /> <select
                                                        id="bfInitScriptSelection"></select>
                                                </div>
                                                <br />
                                                <div id="bfInitSelectionDescription" style="white-space: pre-line;"></div>
                                            </div>

                                            <div id="bfInitScriptCustom" style="display:none">
                                                <hr />
                                                <div class="bfPropertyWrap bfng-field">
                                                    <div class="btn btn-secondary"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_INITSCRIPT_CODEFRAMEWORK')); ?>"
                                                        style="cursor: pointer;" onclick="createInitCode()">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_CREATE_CODE_FRAMEWORK') ?>
                                                    </div>

                                                    <?php
                                                    echo \Vcmb\Component\BreezingformsNG\Administrator\Helper\QuickmodeHtml::renderCodeMirror('bfInitCode');
                                                    ?>
                                                </div>
                                            </div>
                                        </fieldset>

                                        <fieldset id="bfActionScript" style="display:none">
                                            <br />
                                            <legend>
                                                <?php echo Text::_('COM_BREEZINGFORMSNG_ADVANCED_ELEMENT_ACTIONSCRIPT'); ?>
                                            </legend>
                                            <div class="bfPropertyWrap bfng-field">
                                                <span
                                                    title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ACTIONSCRIPT')); ?>">
                                                    <?php echo Text::_('COM_BREEZINGFORMSNG_TYPE') ?>
                                                    :
                                                </span>
                                                <input
                                                    onclick="JQuery('#bfActionScriptFlags').css('display', 'none');
                                                                                            JQuery('#bfActionScriptLibrary').css('display', 'none');
                                                                                            JQuery('#bfActionScriptCustom').css('display', 'none');"
                                                    type="radio" name="actionType" name="actionType" id="bfActionTypeNone"
                                                    class="bfActionType" value="0" />
                                                <?php echo Text::_('COM_BREEZINGFORMSNG_NONE') ?>
                                                <input
                                                    onclick="JQuery('#bfActionScriptFlags').css('display', '');
                                                                                            JQuery('#bfActionScriptLibrary').css('display', '');
                                                                                            JQuery('#bfActionScriptCustom').css('display', 'none');"
                                                    type="radio" name="actionType" id="bfActionTypeLibrary" class="bfActionType"
                                                    value="1" />
                                                <?php echo Text::_('COM_BREEZINGFORMSNG_LIBRARY') ?>
                                                <input
                                                    onclick="JQuery('#bfActionScriptFlags').css('display', '');
                                                                                            JQuery('#bfActionScriptLibrary').css('display', 'none');
                                                                                            JQuery('#bfActionScriptCustom').css('display', '');"
                                                    type="radio" name="actionType" id="bfActionTypeCustom" class="bfActionType"
                                                    value="2" />
                                                <?php echo Text::_('COM_BREEZINGFORMSNG_CUSTOM') ?>
                                            </div>
                                            <div id="bfActionScriptFlags" style="display:none">
                                                <hr />
                                                <div class="bfPropertyWrap bfng-field">
                                                    <?php echo Text::_('COM_BREEZINGFORMSNG_ACTIONS') ?>:
                                                    <input style="display:none" type="checkbox" class="bfAction"
                                                        id="bfActionClick" name="bfActionClick" value="1" /><label
                                                        style="display:none" class="bfActionLabel" id="bfActionClickLabel">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENTS_CLICK'); ?>
                                                    </label>
                                                    <input style="display:none" type="checkbox" class="bfAction"
                                                        id="bfActionBlur" name="bfActionBlur" value="1" /><label
                                                        style="display:none" class="bfActionLabel" id="bfActionBlurLabel">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENTS_BLUR'); ?>
                                                    </label>
                                                    <input style="display:none" type="checkbox" class="bfAction"
                                                        id="bfActionChange" name="bfActionChange" value="1" /><label
                                                        style="display:none" class="bfActionLabel" id="bfActionChangeLabel">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENTS_CHANGE'); ?>
                                                    </label>
                                                    <input style="display:none" type="checkbox" class="bfAction"
                                                        id="bfActionFocus" name="bfActionFocus" value="1" /><label
                                                        style="display:none" class="bfActionLabel" id="bfActionFocusLabel">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENTS_FOCUS'); ?>
                                                    </label>
                                                    <input style="display:none" type="checkbox" class="bfAction"
                                                        id="bfActionSelect" name="bfActionSelect" value="1" /><label
                                                        style="display:none" class="bfActionLabel" id="bfActionSelectLabel">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENTS_SELECTION'); ?>
                                                    </label>
                                                </div>
                                            </div>

                                            <div id="bfActionScriptLibrary" style="display:none">
                                                <hr />
                                                <div class="bfPropertyWrap bfng-field">
                                                    <?php echo Text::_('COM_BREEZINGFORMSNG_SCRIPT') ?>:<br /><select
                                                        id="bfActionsScriptSelection"></select>
                                                </div>
                                                <br />
                                                <div id="bfActionsScriptSelectionDescription" style="white-space: pre-line;"></div>
                                            </div>

                                            <div id="bfActionScriptCustom" style="display:none">
                                                <hr />
                                                <div class="bfPropertyWrap bfng-field">
                                                    <div class="btn btn-secondary"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ACTIONSCRIPT_CODEFRAMEWORK')); ?>"
                                                        style="cursor: pointer;" onclick="createActionCode()">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_CREATE_CODE_FRAMEWORK') ?>
                                                    </div>
                                                    <?php
                                                    echo \Vcmb\Component\BreezingformsNG\Administrator\Helper\QuickmodeHtml::renderCodeMirror('bfActionCode');
                                                    ?>
                                                </div>
                                            </div>

                                        </fieldset>

                                    </div>
