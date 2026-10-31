<?php
/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/** @var string $active_language_code */
/** @var string $formDesc */
/** @var string $formEmailadr */
/** @var int $formEmailntf */
/** @var string $formName */
/** @var string $formTitle */
use Joomla\CMS\Language\Text;
?>
                                    <!-- FORM PROPERTIES BEGIN -->
                                    <div class="bfProperties" id="bfFormProperties" style="display:none">
                                        <br />
                                        <fieldset>

                                            <legend>
                                                <?php echo Text::_('COM_BREEZINGFORMSNG_FORM_PROPERTIES'); ?>
                                            </legend>
                                            <div class="bfPropertyWrap bfng-field">
                                                <label class="bfPropertyLabel bfng-field__label"
                                                    title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_FORM_TITLE')); ?>"
                                                    for="bfFormTitle">
                                                    <?php echo Text::_('COM_BREEZINGFORMSNG_FORM_TITLE'); ?>
                                                </label>
                                                <input type="text"
                                                    value="<?php echo htmlentities($formTitle, ENT_QUOTES, 'UTF-8') ?>"
                                                    id="bfFormTitle" />
                                            </div>

                                            <div class="bfPropertyWrap bfTrans bfng-field">
                                                <label class="bfPropertyLabel bfng-field__label"
                                                    title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_FORM_TITLE')); ?>"
                                                    for="bfFormTitleTrans">
                                                    <?php echo Text::_('COM_BREEZINGFORMSNG_FORM_TITLE'); ?>
                                                    <br /><em>(
                                                        <?php echo $active_language_code ?>)
                                                    </em>
                                                </label>
                                                <input type="text" value="" id="bfFormTitleTrans" />
                                            </div>

                                            <div class="bfPropertyWrap bfng-field">
                                                <label class="bfPropertyLabel bfng-field__label"
                                                    title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_FORM_NAME')); ?>"
                                                    for="bfFormName">
                                                    <?php echo Text::_('COM_BREEZINGFORMSNG_FORM_NAME'); ?>
                                                </label>
                                                <input type="text"
                                                    value="<?php echo htmlentities($formName, ENT_QUOTES, 'UTF-8') ?>"
                                                    id="bfFormName" />
                                            </div>
                                            <div class="bfPropertyWrap bfng-field">
                                                <label class="bfPropertyLabel bfng-field__label"
                                                    title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_FORM_DESCRIPTION')); ?>"
                                                    for="bfFormDescription">
                                                    <?php echo Text::_('COM_BREEZINGFORMSNG_FORM_DESC'); ?>
                                                </label>
                                                <textarea
                                                    id="bfFormDescription"><?php echo htmlentities($formDesc, ENT_QUOTES, 'UTF-8') ?></textarea>
                                            </div>
                                            <div class="bfPropertyWrap bfng-field">
                                                <label class="bfPropertyLabel bfng-field__label"
                                                    title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_FORM_LASTPAGE')); ?>"
                                                    for="bfFormLastPageThankYou">
                                                    <?php echo Text::_('COM_BREEZINGFORMSNG_LAST_PAGE_THANK_YOU'); ?>
                                                </label>
                                                <input type="checkbox" value="" id="bfFormLastPageThankYou" />
                                            </div>
                                            <div class="bfPropertyWrap bfng-field">
                                                <label class="bfPropertyLabel bfng-field__label"
                                                    title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_FORM_EMAIL_NOTIFICATION')); ?>""
                                                       for=" bfFormMailNotification">
                                                    <?php echo Text::_('COM_BREEZINGFORMSNG_MAIL_NOTIFICATION'); ?>
                                                </label>
                                                <input <?php echo $formEmailntf == 2 || $formEmailntf == 1 ? 'checked="checked"' : '' ?> type="checkbox"
                                                    value="<?php echo htmlentities((string) $formEmailntf, ENT_QUOTES, 'UTF-8') ?>"
                                                    id="bfFormMailNotification" />
                                            </div>
                                            <div class="bfPropertyWrap bfng-field">
                                                <label class="bfPropertyLabel bfng-field__label"
                                                    title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_FORM_EMAIL_NOTIFICATION_ADDRESS')); ?>"
                                                    for="bfFormMailRecipient">
                                                    <?php echo Text::_('COM_BREEZINGFORMSNG_MAIL_RECIPIENT'); ?>
                                                </label>
                                                <input type="text"
                                                    value="<?php echo htmlentities($formEmailadr, ENT_QUOTES, 'UTF-8') ?>"
                                                    id="bfFormMailRecipient" />
                                            </div>
                                            <div class="bfPropertyWrap bfng-field">
                                                <label class="bfPropertyLabel bfng-field__label"
                                                    title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_FORM_SUBMIT_INCLUDE')); ?>"
                                                    for="bfSubmitIncludeYes">
                                                    <?php echo Text::_('COM_BREEZINGFORMSNG_FORM_SUBMIT_INCLUDE'); ?>
                                                </label>
                                                <input checked="checked" type="radio" name="bfSubmitInclude" value=""
                                                    id="bfSubmitIncludeYes" />
                                                <?php echo Text::_('COM_BREEZINGFORMSNG_YES'); ?>
                                                <input type="radio" name="bfSubmitInclude" value="" id="bfSubmitIncludeNo" />
                                                <?php echo Text::_('COM_BREEZINGFORMSNG_NO'); ?>
                                            </div>
                                            <div class="bfPropertyWrap bfng-field">
                                                <label class="bfPropertyLabel bfng-field__label"
                                                    title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_FORM_SUBMIT_LABEL')); ?>"
                                                    for="bfFormSubmitLabel">
                                                    <?php echo Text::_('COM_BREEZINGFORMSNG_FORM_SUBMIT_LABEL'); ?>
                                                </label>
                                                <input type="text" value="save" id="bfFormSubmitLabel" />
                                            </div>

                                            <div class="bfPropertyWrap bfTrans bfng-field">
                                                <label class="bfPropertyLabel bfng-field__label"
                                                    title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_FORM_SUBMIT_LABEL')); ?>"
                                                    for="bfFormSubmitLabelTrans">
                                                    <?php echo Text::_('COM_BREEZINGFORMSNG_FORM_SUBMIT_LABEL'); ?>
                                                    <br /><em>(
                                                        <?php echo $active_language_code ?>)
                                                    </em>
                                                </label>
                                                <input type="text" value="save" id="bfFormSubmitLabelTrans" />
                                            </div>

                                            <div class="bfPropertyWrap bfng-field">
                                                <label class="bfPropertyLabel bfng-field__label"
                                                    title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_FORM_PAGING_INCLUDE')); ?>"
                                                    for="bfPagingIncludeYes">
                                                    <?php echo Text::_('COM_BREEZINGFORMSNG_FORM_PAGING_INCLUDE'); ?>
                                                </label>
                                                <input checked="checked" type="radio" name="bfPagingInclude" value=""
                                                    id="bfPagingIncludeYes" />
                                                <?php echo Text::_('COM_BREEZINGFORMSNG_YES'); ?>
                                                <input type="radio" name="bfPagingInclude" value="" id="bfPagingIncludeNo" />
                                                <?php echo Text::_('COM_BREEZINGFORMSNG_NO'); ?>
                                            </div>
                                            <div class="bfPropertyWrap bfng-field">
                                                <label class="bfPropertyLabel bfng-field__label"
                                                    title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_FORM_PAGING_NEXT_LABEL')); ?>"
                                                    for="bfFormPagingNextLabel">
                                                    <?php echo Text::_('COM_BREEZINGFORMSNG_FORM_PAGING_NEXT_LABEL'); ?>
                                                </label>
                                                <input type="text" value="next" id="bfFormPagingNextLabel" />
                                            </div>

                                            <div class="bfPropertyWrap bfTrans bfng-field">
                                                <label class="bfPropertyLabel bfng-field__label"
                                                    title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_FORM_PAGING_NEXT_LABEL')); ?>"
                                                    for="bfFormPagingNextLabelTrans">
                                                    <?php echo Text::_('COM_BREEZINGFORMSNG_FORM_PAGING_NEXT_LABEL'); ?>
                                                    <br /><em>(
                                                        <?php echo $active_language_code ?>)
                                                    </em>
                                                </label>
                                                <input type="text" value="next" id="bfFormPagingNextLabelTrans" />
                                            </div>

                                            <div class="bfPropertyWrap bfng-field">
                                                <label class="bfPropertyLabel bfng-field__label"
                                                    title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_FORM_PAGING_PREV_LABEL')); ?>"
                                                    for="bfFormPagingPrevLabel">
                                                    <?php echo Text::_('COM_BREEZINGFORMSNG_FORM_PAGING_PREV_LABEL'); ?>
                                                </label>
                                                <input type="text" value="back" id="bfFormPagingPrevLabel" />
                                            </div>

                                            <div class="bfPropertyWrap bfTrans bfng-field">
                                                <label class="bfPropertyLabel bfng-field__label"
                                                    title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_FORM_PAGING_PREV_LABEL')); ?>"
                                                    for="bfFormPagingPrevLabelTrans">
                                                    <?php echo Text::_('COM_BREEZINGFORMSNG_FORM_PAGING_PREV_LABEL'); ?>
                                                    <br /><em>(
                                                        <?php echo $active_language_code ?>)
                                                    </em>
                                                </label>
                                                <input type="text" value="back" id="bfFormPagingPrevLabelTrans" />
                                            </div>

                                            <div class="bfPropertyWrap bfng-field">
                                                <label class="bfPropertyLabel bfng-field__label"
                                                    title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_FORM_CANCEL_INCLUDE')); ?>"
                                                    for="bfCancelIncludeYes">
                                                    <?php echo Text::_('COM_BREEZINGFORMSNG_FORM_CANCEL_INCLUDE'); ?>
                                                </label>
                                                <input checked="checked" type="radio" name="bfCancelInclude" value=""
                                                    id="bfCancelIncludeYes" />
                                                <?php echo Text::_('COM_BREEZINGFORMSNG_YES'); ?>
                                                <input type="radio" name="bfCancelInclude" value="" id="bfCancelIncludeNo" />
                                                <?php echo Text::_('COM_BREEZINGFORMSNG_NO'); ?>
                                            </div>
                                            <div class="bfPropertyWrap bfng-field">
                                                <label class="bfPropertyLabel bfng-field__label"
                                                    title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_FORM_CANCEL_LABEL')); ?>"
                                                    for="bfFormCancelLabel">
                                                    <?php echo Text::_('COM_BREEZINGFORMSNG_FORM_CANCEL_LABEL'); ?>
                                                </label>
                                                <input type="text" value="reset" id="bfFormCancelLabel" />
                                            </div>

                                            <div class="bfPropertyWrap bfTrans bfng-field">
                                                <label class="bfPropertyLabel bfng-field__label"
                                                    title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_FORM_CANCEL_LABEL')); ?>"
                                                    for="bfFormCancelLabelTrans">
                                                    <?php echo Text::_('COM_BREEZINGFORMSNG_FORM_CANCEL_LABEL'); ?>
                                                    <br /><em>(
                                                        <?php echo $active_language_code ?>)
                                                    </em>
                                                </label>
                                                <input type="text" value="reset" id="bfFormCancelLabelTrans" />
                                            </div>

                                        </fieldset>
                                    </div>
                                    <!-- FORM PROPERTIES END -->

                                    <!-- PAGE PROPERTIES BEGIN -->
