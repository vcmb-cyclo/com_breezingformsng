<?php
/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/** @var string $active_language_code */
/** @var bool $isAzureBootstrapTheme */
use Joomla\CMS\Language\Text;
?>
                                    <div class="bfProperties" id="bfElementProperties" style="display:none">
                                        <br />
                                        <fieldset>
                                            <div class="bfPropertyWrap bfng-field">
                                                <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                    title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_TYPE')); ?>"
                                                    for="bfElementType">
                                                    <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_TYPE'); ?>
                                                </label>
                                                <select id="bfElementType">
                                                    <option value="">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_CHOOSE_ONE'); ?>
                                                    </option>
                                                    <?php
                                                    $elementTypeOptions = array(
                                                        array('value' => 'bfElementTypeCalendar', 'label' => Text::_('COM_BREEZINGFORMSNG_CALENDAR'), 'icon' => '📅'),
                                                        array('value' => 'bfElementTypeCalendarResponsive', 'label' => Text::_('COM_BREEZINGFORMSNG_CALENDAR_RESPONSIVE'), 'icon' => '📆'),
                                                        array('value' => 'bfElementTypeCaptcha', 'label' => Text::_('COM_BREEZINGFORMSNG_CAPTCHA'), 'icon' => '🔐'),
                                                        array('value' => 'bfElementTypeCheckbox', 'label' => Text::_('COM_BREEZINGFORMSNG_CHECKBOX'), 'icon' => '☑️'),
                                                        array('value' => 'bfElementTypeCheckboxGroup', 'label' => Text::_('COM_BREEZINGFORMSNG_CHECKBOX_GROUP'), 'icon' => '✅'),
                                                        array('value' => 'bfElementTypeFile', 'label' => Text::_('COM_BREEZINGFORMSNG_FILE'), 'icon' => '📎'),
                                                        array('value' => 'bfElementTypeHidden', 'label' => Text::_('COM_BREEZINGFORMSNG_HIDDEN'), 'icon' => '🙈'),
                                                        array('value' => 'bfElementTypeNumberInput', 'label' => Text::_('COM_BREEZINGFORMSNG_NUMBER_INPUT'), 'icon' => '🔢'),
                                                        array('value' => 'bfElementTypePayPal', 'label' => Text::_('COM_BREEZINGFORMSNG_PAYPAL'), 'icon' => '💳'),
                                                        array('value' => 'bfElementTypeRadioGroup', 'label' => Text::_('COM_BREEZINGFORMSNG_RADIO_GROUP'), 'icon' => '🔘'),
                                                        array('value' => 'bfElementTypeReCaptcha', 'label' => Text::_('COM_BREEZINGFORMSNG_ReCaptcha'), 'icon' => '🛡️'),
                                                        array('value' => 'bfElementTypeSelect', 'label' => Text::_('COM_BREEZINGFORMSNG_SELECT'), 'icon' => '📋'),
                                                        array('value' => 'bfElementTypeSignature', 'label' => Text::_('COM_BREEZINGFORMSNG_SIGNATURE'), 'icon' => '✍️'),
                                                        array('value' => 'bfElementTypeSlider', 'label' => 'Slider', 'icon' => '🎚️'),
                                                        array('value' => 'bfElementTypeStripe', 'label' => Text::_('COM_BREEZINGFORMSNG_STRIPE'), 'icon' => '💳'),
                                                        array('value' => 'bfElementTypeSubmitButton', 'label' => Text::_('COM_BREEZINGFORMSNG_SUBMIT_BUTTON'), 'icon' => '🚀'),
                                                        array('value' => 'bfElementTypeSummarize', 'label' => Text::_('COM_BREEZINGFORMSNG_SUMMARIZE'), 'icon' => '🧮'),
                                                        array('value' => 'bfElementTypeTextarea', 'label' => Text::_('COM_BREEZINGFORMSNG_TEXTAREA'), 'icon' => '📝'),
                                                        array('value' => 'bfElementTypeText', 'label' => Text::_('COM_BREEZINGFORMSNG_TEXTFIELD'), 'icon' => '🔤')
                                                    );

                                                    usort($elementTypeOptions, function ($a, $b) {
                                                        return strcasecmp($a['label'], $b['label']);
                                                    });

                                                    foreach ($elementTypeOptions as $elementTypeOption) :
                                                        $optionText = $elementTypeOption['icon'] . ' ' . $elementTypeOption['label'];
                                                    ?>
                                                        <option value="<?php echo $elementTypeOption['value']; ?>">
                                                            <?php echo htmlspecialchars($optionText, ENT_QUOTES, 'UTF-8'); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <legend>
                                                <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_PROPERTIES'); ?>
                                            </legend>
                                            <div class="bfPropertyWrap bfng-field">
                                                <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                    title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_LABEL')); ?>"
                                                    for="bfElementLabel">
                                                    <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_LABEL'); ?>
                                                </label>
                                                <input type="text" value="" id="bfElementLabel" />
                                            </div>

                                            <div class="bfPropertyWrap bfTrans bfng-field">
                                                <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                    title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_LABEL')); ?>"
                                                    for="bfElementLabelTrans">
                                                    <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_LABEL'); ?>
                                                    <br /><em>(
                                                        <?php echo $active_language_code ?>)
                                                    </em>
                                                </label>
                                                <input type="text" value="" id="bfElementLabelTrans" />
                                            </div>

                                            <div class="bfPropertyWrap bfng-field">
                                                <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                    title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_NAME')); ?>"
                                                    for="bfElementName">
                                                    <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_NAME'); ?>
                                                </label>
                                                <input type="text" value="" id="bfElementName" />
                                            </div>

                                            <!-- NUMBER INPUT BEGIN -->
                                            <div class="bfElementTypeClass" id="bfElementTypeNumberInput" style="display:none">
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label" title="<?php echo 'value'; ?>"
                                                        for="bfElementTypeNumberInputValue">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_VALUE'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementTypeNumberInputValue" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo 'placeholder'; ?>"
                                                        for="bfElementTypeNumberInputPlaceholder">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_TEXT_PLACEHOLDER'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementTypeNumberInputPlaceholder" />
                                                </div>
                                                <div class="bfPropertyWrap bfTrans bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo 'translated placholder'; ?>"
                                                        for="bfElementTypeNumberInputPlaceholderTrans">
                                                        <?php echo 'translated placeholder'; ?>
                                                        <br /><em>(
                                                            <?php echo $active_language_code ?>)
                                                        </em>
                                                    </label>
                                                    <input type="text" value="" id="bfElementTypeNumberInputPlaceholderTrans" />
                                                </div>
                                                <?php
                                                // Icon for Textfield
                                                // This code is only for NEW THEME

                                                if ($isAzureBootstrapTheme) {
                                                    ?>

                                                    <div class="bfPropertyWrap bfng-field">
                                                        <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                            title="<?php echo 'icon element'; ?>"
                                                            for="bfElementTypeNumberInputIcon">
                                                            <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_ICON'); ?>
                                                        </label>
                                                        <input type="text" value="" id="bfElementTypeNumberInputIcon" />
                                                    </div>
                                                <?php } ?>

                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_TEXTFIELD_SIZE')); ?>"
                                                        for="bfElementTypeNumberInputSize">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_SIZE'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementTypeNumberInputSize" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_TEXTFIELD_MAXLENGTH')); ?>"
                                                        for="bfElementTypeNumberInputMaxLength">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_MAX_LENGTH'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementTypeNumberInputMaxLength" />
                                                </div>

                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_TEXTFIELD_HINT')); ?>"
                                                        for="bfElementTypeNumberInputHint">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_HINT'); ?>
                                                    </label>
                                                    <textarea id="bfElementTypeNumberInputHint"></textarea>
                                                </div>

                                                <div class="bfPropertyWrap bfTrans bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_TEXTFIELD_HINT')); ?>"
                                                        for="bfElementTypeNumberInputHintTrans">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_HINT'); ?>
                                                        <br /><em>(
                                                            <?php echo $active_language_code ?>)
                                                        </em>
                                                    </label>
                                                    <textarea id="bfElementTypeNumberInputHintTrans"></textarea>
                                                </div>
                                            </div>
                                            <!-- NUMBER INPUT END -->

                                            <!-- HIDDEN BEGIN -->
                                            <div class="bfElementTypeClass" id="bfElementTypeHidden" style="display:none">
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_HIDDEN_VALUE')); ?>"
                                                        for="bfElementTypeHiddenValue">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_VALUE'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementTypeHiddenValue" />
                                                </div>
                                            </div>
                                            <!-- HIDDEN END -->
                                            <!-- SUMMARIZE BEGIN -->
                                            <div class="bfElementTypeClass" id="bfElementTypeSummarize" style="display:none">
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_SUMMARIZE_CONNECTWITH')); ?>"
                                                        for="bfElementTypeSummarizeConnectWith">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_SUMMARIZE_CONNECT_WITH'); ?>
                                                    </label>
                                                    <select id="bfElementTypeSummarizeConnectWith">
                                                        <option value="">
                                                            <?php echo Text::_('COM_BREEZINGFORMSNG_CHOOSE_ONE'); ?>
                                                        </option>
                                                    </select>
                                                </div>

                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_SUMMARIZE_EMPTY_MESSAGE')); ?>"
                                                        for="bfElementTypeSummarizeEmptyMessage">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_EMPTY_MESSAGE'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementTypeSummarizeEmptyMessage" />
                                                </div>

                                                <div class="bfPropertyWrap bfTrans bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_SUMMARIZE_EMPTY_MESSAGE')); ?>"
                                                        for="bfElementTypeSummarizeEmptyMessageTrans">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_EMPTY_MESSAGE'); ?>
                                                        <br /><em>(
                                                            <?php echo $active_language_code ?>)
                                                        </em>
                                                    </label>
                                                    <input type="text" value="" id="bfElementTypeSummarizeEmptyMessageTrans" />
                                                </div>

                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_SUMMARIZE_EMPTY_HIDE')); ?>"
                                                        for="bfElementTypeSummarizeHideIfEmpty">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_HIDE_EMPTY'); ?>
                                                    </label>
                                                    <input type="checkbox" value="" id="bfElementTypeSummarizeHideIfEmpty" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_SUMMARIZE_USE_LABEL')); ?>"
                                                        for="bfElementTypeSummarizeUseElementLabel">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_USE_LABEL'); ?>
                                                    </label>
                                                    <input type="checkbox" value=""
                                                        id="bfElementTypeSummarizeUseElementLabel" />
                                                </div>
                                            </div>
                                            <!-- SUMMARIZE END -->
                                            <!-- TEXTFIELD BEGIN -->
                                            <div class="bfElementTypeClass" id="bfElementTypeText" style="display:none">
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_TEXTFIELD_VALUE')); ?>"
                                                        for="bfElementTypeTextValue">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_VALUE'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementTypeTextValue" />
                                                </div>

                                                <div class="bfPropertyWrap bfTrans bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_TEXTFIELD_VALUE')); ?>"
                                                        for="bfElementTypeTextValueTrans">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_VALUE'); ?>
                                                        <br /><em>(
                                                            <?php echo $active_language_code ?>)
                                                        </em>
                                                    </label>
                                                    <input type="text" value="" id="bfElementTypeTextValueTrans" />
                                                </div>

                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_TEXTFIELD_PLACEHOLDER')); ?>"
                                                        for="bfElementTypeTextPlaceholder">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_TEXT_PLACEHOLDER'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementTypeTextPlaceholder" />
                                                </div>

                                                <div class="bfPropertyWrap bfTrans bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_TEXTFIELD_PLACEHOLDER')); ?>"
                                                        for="bfElementTypeTextPlaceholderTrans">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_TEXT_PLACEHOLDER'); ?>
                                                        <br /><em>(
                                                            <?php echo $active_language_code ?>)
                                                        </em>
                                                    </label>
                                                    <input type="text" value="" id="bfElementTypeTextPlaceholderTrans" />
                                                </div>
                                                <?php
                                                // Icon for Textfield
                                                // This code is only for NEW THEME

                                                if ($isAzureBootstrapTheme) {
                                                    ?>

                                                    <div class="bfPropertyWrap bfng-field">
                                                        <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                            title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_ICON')); ?>"
                                                            for="bfElementTypeTextIcon">
                                                            <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_ICON'); ?>
                                                        </label>
                                                        <input type="text" value="" id="bfElementTypeTextIcon" />
                                                    </div>

                                                <?php } ?>

                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_TEXTFIELD_SIZE')); ?>"
                                                        for="bfElementTypeTextSize">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_SIZE'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementTypeTextSize" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_TEXTFIELD_MAXLENGTH')); ?>"
                                                        for="bfElementTypeTextMaxLength">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_MAX_LENGTH'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementTypeTextMaxLength" />
                                                </div>

                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_TEXTFIELD_HINT')); ?>"
                                                        for="bfElementTypeTextHint">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_HINT'); ?>
                                                    </label>
                                                    <textarea id="bfElementTypeTextHint"></textarea>
                                                </div>

                                                <div class="bfPropertyWrap bfTrans bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_TEXTFIELD_HINT')); ?>"
                                                        for="bfElementTypeTextHintTrans">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_HINT'); ?>
                                                        <br /><em>(
                                                            <?php echo $active_language_code ?>)
                                                        </em>
                                                    </label>
                                                    <textarea id="bfElementTypeTextHintTrans"></textarea>
                                                </div>
                                            </div>
                                            <!-- TEXTFIELD END -->
                                            <!-- TEXTAREA BEGIN -->
                                            <div class="bfElementTypeClass" id="bfElementTypeTextarea" style="display:none">
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_TEXTAREA_VALUE')); ?>"
                                                        for="bfElementTypeTextareaValue">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_VALUE'); ?>
                                                    </label>
                                                    <textarea id="bfElementTypeTextareaValue"></textarea>
                                                </div>

                                                <div class="bfPropertyWrap bfTrans bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_TEXTAREA_VALUE')); ?>"
                                                        for="bfElementTypeTextareaValueTrans">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_VALUE'); ?>
                                                        <br /><em>(
                                                            <?php echo $active_language_code ?>)
                                                        </em>
                                                    </label>
                                                    <textarea id="bfElementTypeTextareaValueTrans"></textarea>
                                                </div>

                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_TEXTAREA_PLACEHOLDER')); ?>"
                                                        for="bfElementTypeTextareaPlaceholder">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_TEXT_PLACEHOLDER'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementTypeTextareaPlaceholder" />
                                                </div>

                                                <div class="bfPropertyWrap bfTrans bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_TEXTAREA_PLACEHOLDER')); ?>"
                                                        for="bfElementTypeTextareaPlaceholderTrans">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_TEXT_PLACEHOLDER'); ?>
                                                        <br /><em>(
                                                            <?php echo $active_language_code ?>)
                                                        </em>
                                                    </label>
                                                    <input type="text" value="" id="bfElementTypeTextareaPlaceholderTrans" />
                                                </div>
                                                <?php
                                                // Icon for Textarea
                                                // This code is only for NEW THEME

                                                if ($isAzureBootstrapTheme) {
                                                    ?>

                                                    <div class="bfPropertyWrap bfng-field">
                                                        <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                            title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_ICON')); ?>"
                                                            for="bfElementTypeTextareaIcon">
                                                            <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_ICON'); ?>
                                                        </label>
                                                        <input type="text" value="" id="bfElementTypeTextareaIcon" />
                                                    </div>

                                                <?php } ?>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_TEXTAREA_WIDTH')); ?>"
                                                        for="bfElementTypeTextareaWidth">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_WIDTH'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementTypeTextareaWidth" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_TEXTAREA_HEIGHT')); ?>"
                                                        for="bfElementTypeTextareaHeight">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_HEIGHT'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementTypeTextareaHeight" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_TEXTAREA_MAXLENGTH')); ?>"
                                                        for="bfElementTypeTextareaMaxLength">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_MAX_LENGTH'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementTypeTextareaMaxLength" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_TEXTAREA_MAXLENGTH_SHOW')); ?>"
                                                        for="bfElementTypeTextareaMaxLengthShow">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_MAX_LENGTH_SHOW'); ?>
                                                    </label>
                                                    <input type="checkbox" value="" id="bfElementTypeTextareaMaxLengthShow" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_TEXTAREA_HTML')); ?>"
                                                        for="bfElementTypeTextareaIsHtml">HTML</label>
                                                    <input type="checkbox" value="" id="bfElementTypeTextareaIsHtml" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_HINT')); ?>"
                                                        for="bfElementTypeTextareaHint">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_HINT'); ?>
                                                    </label>
                                                    <textarea id="bfElementTypeTextareaHint"></textarea>
                                                </div>

                                                <div class="bfPropertyWrap bfTrans bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_HINT')); ?>"
                                                        for="bfElementTypeTextareaHintTrans">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_HINT'); ?>
                                                        <br /><em>(
                                                            <?php echo $active_language_code ?>)
                                                        </em>
                                                    </label>
                                                    <textarea id="bfElementTypeTextareaHintTrans"></textarea>
                                                </div>
                                            </div>
                                            <!-- TEXTAREA END -->

                                            <!-- RADIOGROUP BEGIN -->
                                            <div class="bfElementTypeClass" id="bfElementTypeRadioGroup" style="display:none">

                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_RADIO_GROUP')); ?>"
                                                        for="bfElementTypeRadioGroupGroups">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_GROUP'); ?>
                                                    </label>
                                                    <textarea id="bfElementTypeRadioGroupGroups"></textarea>
                                                </div>

                                                <div class="bfPropertyWrap bfTrans bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_RADIO_GROUP')); ?>"
                                                        for="bfElementTypeRadioGroupGroupsTrans">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_GROUP'); ?>
                                                        <br /><em>(
                                                            <?php echo $active_language_code ?>)
                                                        </em>
                                                    </label>
                                                    <textarea id="bfElementTypeRadioGroupGroupsTrans"></textarea>
                                                </div>

                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_RADIO_GROUP_READONLY')); ?>"
                                                        for="bfElementTypeRadioGroupReadonly">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_READONLY'); ?>
                                                    </label>
                                                    <input type="checkbox" value="" id="bfElementTypeRadioGroupReadonly" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_RADIO_GROUP_WRAP')); ?>"
                                                        for="bfElementTypeRadioGroupWrap">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_WRAP'); ?>
                                                    </label>
                                                    <input type="checkbox" value="" id="bfElementTypeRadioGroupWrap" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_HINT')); ?>"
                                                        for="bfElementTypeRadioGroupHint">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_HINT'); ?>
                                                    </label>
                                                    <textarea id="bfElementTypeRadioGroupHint"></textarea>
                                                </div>

                                                <div class="bfPropertyWrap bfTrans bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_HINT')); ?>"
                                                        for="bfElementTypeRadioGroupHintTrans">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_HINT'); ?>
                                                        <br /><em>(
                                                            <?php echo $active_language_code ?>)
                                                        </em>
                                                    </label>
                                                    <textarea id="bfElementTypeRadioGroupHintTrans"></textarea>
                                                </div>
                                            </div>
                                            <!-- RADIOGROUP END -->
                                            <!-- SUBMITBUTTON BEGIN -->
                                            <div class="bfElementTypeClass" id="bfElementTypeSubmitButton" style="display:none">
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_SUBMIT_VALUE')); ?>"
                                                        for="bfElementTypeSubmitButtonValue">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_VALUE'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementTypeSubmitButtonValue" />
                                                </div>


                                                <div class="bfPropertyWrap bfTrans bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_SUBMIT_VALUE')); ?>"
                                                        for="bfElementTypeSubmitButtonValueTrans">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_VALUE'); ?>
                                                        <br /><em>(
                                                            <?php echo $active_language_code ?>)
                                                        </em>
                                                    </label>
                                                    <input type="text" value="" id="bfElementTypeSubmitButtonValueTrans" />
                                                </div>

                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_HINT')); ?>"
                                                        for="bfElementTypeSubmitButtonHint">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_HINT'); ?>
                                                    </label>
                                                    <textarea id="bfElementTypeSubmitButtonHint"></textarea>
                                                </div>

                                                <div class="bfPropertyWrap bfTrans bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_HINT')); ?>"
                                                        for="bfElementTypeSubmitButtonHintTrans">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_HINT'); ?>
                                                        <br /><em>(
                                                            <?php echo $active_language_code ?>)
                                                        </em>
                                                    </label>
                                                    <textarea id="bfElementTypeSubmitButtonHintTrans"></textarea>
                                                </div>
                                            </div>
                                            <!-- SUBMITBUTTON END -->


                                            <!-- STRIPE BEGIN -->
                                            <div class="bfElementTypeClass" id="bfElementTypeStripe" style="display:none">
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_STRIPE_SECRETKEY')); ?>"
                                                        for="bfElementTypeStripeSecretKey">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_SECRETKEY'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementTypeStripeSecretKey" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_STRIPE_PUBLISHABLEKEY')); ?>"
                                                        for="bfElementTypeStripePublishableKey">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_PUBLISHABLEKEY'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementTypeStripePublishableKey" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_STRIPE_WEBHOOKSECRET')); ?>"
                                                        for="bfElementTypeStripeWebhookSecret">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_STRIPE_WEBHOOKSECRET'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementTypeStripeWebhookSecret" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_STRIPE_WEBHOOKURL')); ?>"
                                                        for="bfElementTypeStripeWebhookUrl">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_STRIPE_WEBHOOKURL'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementTypeStripeWebhookUrl" readonly />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_STRIPE_ITEMNAME')); ?>"
                                                        for="bfElementTypeStripeItemname">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_ITEMNAME'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementTypeStripeItemname" />
                                                </div>

                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_STRIPE_AMOUNT')); ?>"
                                                        for="bfElementTypeStripeAmount">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_AMOUNT'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementTypeStripeAmount" />
                                                </div>

                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_STRIPE_CURRENCY')); ?>"
                                                        for="bfElementTypeStripeCurrencyCode">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_CURRENCY_CODE'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementTypeStripeCurrencyCode" />
                                                </div>

                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_STRIPE_THANKYOUPAGE')); ?>"
                                                        for="bfElementTypeStripeThankYouPage">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_THANKYOU_PAGE'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementTypeStripeThankYouPage" />
                                                </div>

                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_STRIPE_SENDNOTAFPAY')); ?>"
                                                        for="bfElementTypeStripeSendNotificationAfterPayment">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_NOTIFICATION_AFTER_PAYMENT'); ?>
                                                    </label>
                                                    <input type="checkbox" value=""
                                                        id="bfElementTypeStripeSendNotificationAfterPayment" />
                                                </div>

                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_STRIPE_KLARNA')); ?>"
                                                        for="bfElementTypeStripeKlarna">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_STRIPE_KLARNA'); ?>
                                                    </label>
                                                    <input type="checkbox" value=""
                                                        id="bfElementTypeStripeKlarna" />
                                                </div>

                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_STRIPE_IDEAL')); ?>"
                                                        for="bfElementTypeStripeIdeal">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_STRIPE_IDEAL'); ?>
                                                    </label>
                                                    <input type="checkbox" value=""
                                                        id="bfElementTypeStripeIdeal" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_HINT')); ?>"
                                                        for="bfElementTypeStripeHint">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_HINT'); ?>
                                                    </label>
                                                    <textarea id="bfElementTypeStripeHint"></textarea>
                                                </div>

                                                <div class="bfPropertyWrap bfTrans bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_HINT')); ?>"
                                                        for="bfElementTypeStripeHintTrans">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_HINT'); ?>
                                                        <br /><em>(
                                                            <?php echo $active_language_code ?>)
                                                        </em>
                                                    </label>
                                                    <textarea id="bfElementTypeStripeHintTrans"></textarea>
                                                </div>
                                            </div>
                                            <!-- STRIPE END -->


                                            <!-- PAYPAL BEGIN -->
                                            <div class="bfElementTypeClass" id="bfElementTypePayPal" style="display:none">
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_PAYPAL_BUSINESS')); ?>"
                                                        for="bfElementTypePayPalBusiness">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_BUSINESS'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementTypePayPalBusiness" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_PAYPAL_TOKEN')); ?>"
                                                        for="bfElementTypePayPalToken">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_TOKEN'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementTypePayPalToken" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_PAYPAL_ITEMNAME')); ?>"
                                                        for="bfElementTypePayPalItemname">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_ITEMNAME'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementTypePayPalItemname" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_PAYPAL_ITEMNUMBER')); ?>"
                                                        for="bfElementTypePayPalItemnumber">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_ITEMNUMBER'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementTypePayPalItemnumber" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_PAYPAL_AMOUNT')); ?>"
                                                        for="bfElementTypePayPalAmount">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_AMOUNT'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementTypePayPalAmount" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_PAYPAL_TAX')); ?>"
                                                        for="bfElementTypePayPalTax">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_TAX'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementTypePayPalTax" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_PAYPAL_THANKYOUPAGE')); ?>"
                                                        for="bfElementTypePayPalThankYouPage">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_THANKYOU_PAGE'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementTypePayPalThankYouPage" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_PAYPAL_CANCEL')); ?>"
                                                        for="bfElementTypePayPalCancelURL">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_CANCEL_URL'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementTypePayPalCancelURL" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_PAYPAL_LOCALE')); ?>"
                                                        for="bfElementTypePayPalLocale">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_LOCALE'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementTypePayPalLocale" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_PAYPAL_CURRENCY')); ?>"
                                                        for="bfElementTypePayPalCurrencyCode">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_CURRENCY_CODE'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementTypePayPalCurrencyCode" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_PAYPAL_SENDNOTAFPAY')); ?>"
                                                        for="bfElementTypePayPalSendNotificationAfterPayment">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_NOTIFICATION_AFTER_PAYMENT'); ?>
                                                    </label>
                                                    <input type="checkbox" value=""
                                                        id="bfElementTypePayPalSendNotificationAfterPayment" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_HINT')); ?>"
                                                        for="bfElementTypePayPalHint">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_HINT'); ?>
                                                    </label>
                                                    <textarea id="bfElementTypePayPalHint"></textarea>
                                                </div>

                                                <div class="bfPropertyWrap bfTrans bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_HINT')); ?>"
                                                        for="bfElementTypePayPalHintTrans">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_HINT'); ?>
                                                        <br /><em>(
                                                            <?php echo $active_language_code ?>)
                                                        </em>
                                                    </label>
                                                    <textarea id="bfElementTypePayPalHintTrans"></textarea>
                                                </div>
                                            </div>
                                            <!-- PAYPAL END -->
                                            <!-- CAPTCHA BEGIN -->
                                            <div class="bfElementTypeClass" id="bfElementTypeCaptcha" style="display:none">
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_HINT')); ?>"
                                                        for="bfElementTypeCaptchaHint">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_HINT'); ?>
                                                    </label>
                                                    <textarea id="bfElementTypeCaptchaHint"></textarea>
                                                </div>

                                                <div class="bfPropertyWrap bfTrans bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_HINT')); ?>"
                                                        for="bfElementTypeCaptchaHintTrans">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_HINT'); ?>
                                                        <br /><em>(
                                                            <?php echo $active_language_code ?>)
                                                        </em>
                                                    </label>
                                                    <textarea id="bfElementTypeCaptchaHintTrans"></textarea>
                                                </div>

                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_CAPTCHA_WIDTH')); ?>"
                                                        for="bfElementTypeCaptchaWidth">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_CAPTCHA_WIDTH'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementTypeCaptchaWidth" />
                                                </div>
                                            </div>
                                            <!-- CAPTCHA END -->
                                            <!-- RECAPTCHA BEGIN -->
                                            <div class="bfElementTypeClass" id="bfElementTypeReCaptcha" style="display:none">

                                                <div class="bfPropertyWrap bfng-field">
                                                    <label for="bfElementTypeReCaptchaNew" class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_RECAPTCHA_NEW_CAPTCHA_HINT')); ?>">reCaptcha</label>

                                                    <label>
                                                        <input type="radio" name="bfElementTypeReCaptchaNew" value=""
                                                            id="bfElementTypeReCaptchaNew" checked="checked" />
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_QM_RECAPTCHA_NEW_CAPTCHA'); ?>
                                                    </label>
                                                    <label>
                                                        <input type="radio" name="bfElementTypeReCaptchaNew" value=""
                                                            id="bfElementTypeReCaptchaInvisible" />
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_QM_RECAPTCHA_INVISIBLE_CAPTCHA'); ?>
                                                    </label>
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_RECAPTCHA_PUBKEY')); ?>"
                                                        for=bfElementTypeReCaptchaPubkey><?php echo Text::_('COM_BREEZINGFORMSNG_PUBLIC_KEY'); ?></label>
                                                    <input type="text" value="" id="bfElementTypeReCaptchaPubkey" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_RECAPTCHA_PRIVKEY')); ?>"
                                                        for=bfElementTypeReCaptchaPrivkey><?php echo Text::_('COM_BREEZINGFORMSNG_PRIVATE_KEY'); ?></label>
                                                    <input type="text" value="" id="bfElementTypeReCaptchaPrivkey" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_RECAPTCHA_THEME')); ?>"
                                                        for=bfElementTypeReCaptchaTheme><?php echo Text::_('COM_BREEZINGFORMSNG_Theme'); ?></label>
                                                    <input type="text" value="red" id="bfElementTypeReCaptchaTheme" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_RECAPTCHA_SIZE')); ?>"
                                                        for=bfElementTypeReCaptchaSize><?php echo Text::_('COM_BREEZINGFORMSNG_RECAPTCHA_SIZE'); ?></label>
                                                    <input type="text" value="normal" id="bfElementTypeReCaptchaSize" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_HINT')); ?>"
                                                        for="bfElementTypeReCaptchaHint">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_HINT'); ?>
                                                    </label>
                                                    <textarea id="bfElementTypeReCaptchaHint"></textarea>
                                                </div>

                                                <div class="bfPropertyWrap bfTrans bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_HINT')); ?>"
                                                        for="bfElementTypeReCaptchaHintTrans">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_HINT'); ?>
                                                        <br /><em>(
                                                            <?php echo $active_language_code ?>)
                                                        </em>
                                                    </label>
                                                    <textarea id="bfElementTypeReCaptchaHintTrans"></textarea>
                                                </div>
                                            </div>
                                            <!-- RECAPTCHA END -->
                                            <!-- CALENDAR Responsive BEGIN -->
                                            <div class="bfElementTypeClass" id="bfElementTypeCalendarResponsive"
                                                style="display:none">
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_CALENDAR_FORMAT')); ?>"
                                                        for="bfElementTypeCalendarResponsiveFormat">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_FORMAT'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementTypeCalendarResponsiveFormat" />
                                                </div>

                                                <div class="bfPropertyWrap bfTrans bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_CALENDAR_FORMAT')); ?>"
                                                        for="bfElementTypeCalendarResponsiveFormatTrans">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_FORMAT'); ?>
                                                        <br /><em>(
                                                            <?php echo $active_language_code ?>)
                                                        </em>
                                                    </label>
                                                    <input type="text" value=""
                                                        id="bfElementTypeCalendarResponsiveFormatTrans" />
                                                </div>

                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_CALENDAR_VALUE')); ?>"
                                                        for="bfElementTypeCalendarResponsiveValue">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_VALUE'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementTypeCalendarResponsiveValue" />
                                                </div>

                                                <div class="bfPropertyWrap bfTrans bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_CALENDAR_VALUE')); ?>"
                                                        for="bfElementTypeCalendarResponsiveValueTrans">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_VALUE'); ?>
                                                        <br /><em>(
                                                            <?php echo $active_language_code ?>)
                                                        </em>
                                                    </label>
                                                    <input type="text" value=""
                                                        id="bfElementTypeCalendarResponsiveValueTrans" />
                                                </div>
                                                <?php
                                                // Icon for Responsive Calendar
                                                // This code is only for NEW THEME

                                                if ($isAzureBootstrapTheme) {
                                                    ?>

                                                    <div class="bfPropertyWrap bfng-field">
                                                        <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                            title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_ICON')); ?>"
                                                            for="bfElementTypeCalendarResponsiveIcon">
                                                            <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_ICON'); ?>
                                                        </label>
                                                        <input type="text" value="" id="bfElementTypeCalendarResponsiveIcon" />
                                                    </div>

                                                <?php } ?>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_CALENDAR_SIZE')); ?>"
                                                        for="bfElementTypeCalendarResponsiveSize">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_SIZE'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementTypeCalendarResponsiveSize" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_HINT')); ?>"
                                                        for="bfElementTypeCalendarResponsiveHint">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_HINT'); ?>
                                                    </label>
                                                    <textarea id="bfElementTypeCalendarResponsiveHint"></textarea>
                                                </div>

                                                <div class="bfPropertyWrap bfTrans bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_HINT')); ?>"
                                                        for="bfElementTypeCalendarResponsiveHintTrans">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_HINT'); ?>
                                                        <br /><em>(
                                                            <?php echo $active_language_code ?>)
                                                        </em>
                                                    </label>
                                                    <textarea id="bfElementTypeCalendarResponsiveHintTrans"></textarea>
                                                </div>
                                            </div>
                                            <!-- CALENDAR RESPONSIVE END -->
                                            <!-- CALENDAR BEGIN -->
                                            <div class="bfElementTypeClass" id="bfElementTypeCalendar" style="display:none">
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_CALENDAR_FORMAT')); ?>"
                                                        for="bfElementTypeCalendarFormat">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_FORMAT'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementTypeCalendarFormat" />
                                                </div>

                                                <div class="bfPropertyWrap bfTrans bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_CALENDAR_FORMAT')); ?>"
                                                        for="bfElementTypeCalendarFormatTrans">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_FORMAT'); ?>
                                                        <br /><em>(
                                                            <?php echo $active_language_code ?>)
                                                        </em>
                                                    </label>
                                                    <input type="text" value="" id="bfElementTypeCalendarFormatTrans" />
                                                </div>

                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_CALENDAR_VALUE')); ?>"
                                                        for="bfElementTypeCalendarValue">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_VALUE'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementTypeCalendarValue" />
                                                </div>

                                                <div class="bfPropertyWrap bfTrans bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_CALENDAR_VALUE')); ?>"
                                                        for="bfElementTypeCalendarValueTrans">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_VALUE'); ?>
                                                        <br /><em>(
                                                            <?php echo $active_language_code ?>)
                                                        </em>
                                                    </label>
                                                    <input type="text" value="" id="bfElementTypeCalendarValueTrans" />
                                                </div>

                                                <?php
                                                // Icon for Calendar
                                                // This code is only for NEW THEME

                                                if ($isAzureBootstrapTheme) {
                                                    ?>

                                                    <div class="bfPropertyWrap bfng-field">
                                                        <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                            title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_ICON')); ?>"
                                                            for="bfElementTypeCalendarIcon">
                                                            <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_ICON'); ?>
                                                        </label>
                                                        <input type="text" value="" id="bfElementTypeCalendarIcon" />
                                                    </div>

                                                <?php } ?>

                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_CALENDAR_SIZE')); ?>"
                                                        for="bfElementTypeCalendarSize">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_SIZE'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementTypeCalendarSize" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_HINT')); ?>"
                                                        for="bfElementTypeCalendarHint">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_HINT'); ?>
                                                    </label>
                                                    <textarea id="bfElementTypeCalendarHint"></textarea>
                                                </div>

                                                <div class="bfPropertyWrap bfTrans bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_HINT')); ?>"
                                                        for="bfElementTypeCalendarHintTrans">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_HINT'); ?>
                                                        <br /><em>(
                                                            <?php echo $active_language_code ?>)
                                                        </em>
                                                    </label>
                                                    <textarea id="bfElementTypeCalendarHintTrans"></textarea>
                                                </div>
                                            </div>
                                            <!-- CALENDAR END -->
                                            <!-- SIGNATURE BEGIN -->
                                            <div class="bfElementTypeClass" id="bfElementTypeSignature" style="display:none">

                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_HINT')); ?>"
                                                        for="bfElementTypeSignatureHint">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_HINT'); ?>
                                                    </label>
                                                    <textarea id="bfElementTypeSignatureHint"></textarea>
                                                </div>

                                                <div class="bfPropertyWrap bfTrans bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_HINT')); ?>"
                                                        for="bfElementTypeSignatureHintTrans">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_HINT'); ?>
                                                        <br /><em>(
                                                            <?php echo $active_language_code ?>)
                                                        </em>
                                                    </label>
                                                    <textarea id="bfElementTypeSignatureHintTrans"></textarea>
                                                </div>
                                            </div>
                                            <!-- SIGNATURE END -->
                                            <!-- CHECKBOXGROUP BEGIN -->
                                            <div class="bfElementTypeClass" id="bfElementTypeCheckboxGroup"
                                                style="display:none">
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_CHECKBOX_GROUP')); ?>"
                                                        for="bfElementTypeCheckboxGroupGroups">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_GROUP'); ?>
                                                    </label>
                                                    <textarea id="bfElementTypeCheckboxGroupGroups"></textarea>
                                                </div>

                                                <div class="bfPropertyWrap bfTrans bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_CHECKBOX_GROUP')); ?>"
                                                        for="bfElementTypeCheckboxGroupGroupsTrans">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_GROUP'); ?>
                                                        <br /><em>(
                                                            <?php echo $active_language_code ?>)
                                                        </em>
                                                    </label>
                                                    <textarea id="bfElementTypeCheckboxGroupGroupsTrans"></textarea>
                                                </div>

                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_CHECKBOX_GROUP_READONLY')); ?>"
                                                        for="bfElementTypeCheckboxGroupReadonly">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_READONLY'); ?>
                                                    </label>
                                                    <input type="checkbox" value="" id="bfElementTypeCheckboxGroupReadonly" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_CHECKBOX_GROUP_WRAP')); ?>"
                                                        for="bfElementTypeCheckboxGroupWrap">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_WRAP'); ?>
                                                    </label>
                                                    <input type="checkbox" value="" id="bfElementTypeCheckboxGroupWrap" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_HINT')); ?>"
                                                        for="bfElementTypeCheckboxGroupHint">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_HINT'); ?>
                                                    </label>
                                                    <textarea id="bfElementTypeCheckboxGroupHint"></textarea>
                                                </div>

                                                <div class="bfPropertyWrap bfTrans bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_HINT')); ?>"
                                                        for="bfElementTypeCheckboxGroupHintTrans">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_HINT'); ?>
                                                        <br /><em>(
                                                            <?php echo $active_language_code ?>)
                                                        </em>
                                                    </label>
                                                    <textarea id="bfElementTypeCheckboxGroupHintTrans"></textarea>
                                                </div>
                                            </div>
                                            <!-- CHECKBOXGROUP END -->
                                            <!-- CHECKBOX BEGIN -->
                                            <div class="bfElementTypeClass" id="bfElementTypeCheckbox" style="display:none">
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_CHECKBOX_VALUE')); ?>"
                                                        for="bfElementTypeCheckboxValue">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_VALUE'); ?>
                                                    </label>
                                                    <textarea id="bfElementTypeCheckboxValue"></textarea>
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_CHECKBOX_CHECKED')); ?>"
                                                        for="bfElementTypeCheckboxChecked">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_CHECKED'); ?>
                                                    </label>
                                                    <input type="checkbox" value="" id="bfElementTypeCheckboxChecked" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_CHECKBOX_READONLY')); ?>"
                                                        for="bfElementTypeCheckboxReadonly">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_READONLY'); ?>
                                                    </label>
                                                    <input type="checkbox" value="" id="bfElementTypeCheckboxReadonly" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_HINT')); ?>"
                                                        for="bfElementTypeCheckboxHint">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_HINT'); ?>
                                                    </label>
                                                    <textarea id="bfElementTypeCheckboxHint"></textarea>
                                                </div>

                                                <div class="bfPropertyWrap bfTrans bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_HINT')); ?>"
                                                        for="bfElementTypeCheckboxHintTrans">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_HINT'); ?>
                                                        <br /><em>(
                                                            <?php echo $active_language_code ?>)
                                                        </em>
                                                    </label>
                                                    <textarea id="bfElementTypeCheckboxHintTrans"></textarea>
                                                </div>
                                            </div>
                                            <!-- CHECKBOX END -->
                                            <!-- SELECT BEGIN -->
                                            <div class="bfElementTypeClass" id="bfElementTypeSelect" style="display:none">
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_SELECTLIST')); ?>"
                                                        for="bfElementTypeSelectList">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_LIST'); ?>
                                                    </label>
                                                    <textarea id="bfElementTypeSelectList"></textarea>
                                                </div>

                                                <div class="bfPropertyWrap bfTrans bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_SELECTLIST')); ?>"
                                                        for="bfElementTypeSelectListTrans">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_LIST'); ?>
                                                        <br /><em>(
                                                            <?php echo $active_language_code ?>)
                                                        </em>
                                                    </label>
                                                    <textarea id="bfElementTypeSelectListTrans"></textarea>
                                                </div>

                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_SELECTLIST_MULTIPLE')); ?>"
                                                        for="bfElementTypeSelectMultiple">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_MULTIPLE'); ?>
                                                    </label>
                                                    <input type="checkbox" value="" id="bfElementTypeSelectMultiple" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_SELECTLIST_WIDTH')); ?>"
                                                        for="bfElementTypeSelectListWidth">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_WIDTH'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementTypeSelectListWidth" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_SELECTLIST_HEIGHT')); ?>"
                                                        for="bfElementTypeSelectListHeight">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_HEIGHT'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementTypeSelectListHeight" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_SELECTLIST_READONLY')); ?>"
                                                        for="bfElementTypeSelectReadonly">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_READONLY'); ?>
                                                    </label>
                                                    <input type="checkbox" value="" id="bfElementTypeSelectReadonly" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_HINT')); ?>"
                                                        for="bfElementTypeSelectHint">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_HINT'); ?>
                                                    </label>
                                                    <textarea id="bfElementTypeSelectHint"></textarea>
                                                </div>

                                                <div class="bfPropertyWrap bfTrans bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_HINT')); ?>"
                                                        for="bfElementTypeSelectHintTrans">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_HINT'); ?>
                                                        <br /><em>(
                                                            <?php echo $active_language_code ?>)
                                                        </em>
                                                    </label>
                                                    <textarea id="bfElementTypeSelectHintTrans"></textarea>
                                                </div>
                                            </div>
                                            <!-- SELECT END -->
                                            <!-- FILE BEGIN -->
                                            <div class="bfElementTypeClass" id="bfElementTypeFile" style="display:none">
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_FILE_READONLY')); ?>"
                                                        for="bfElementTypeFileReadonly">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_READONLY'); ?>
                                                    </label>
                                                    <input type="checkbox" value="" id="bfElementTypeFileReadonly" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_HINT')); ?>"
                                                        for="bfElementTypeFileHint">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_HINT'); ?>
                                                    </label>
                                                    <textarea id="bfElementTypeFileHint"></textarea>
                                                </div>

                                                <div class="bfPropertyWrap bfTrans bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ELEMENT_HINT')); ?>"
                                                        for="bfElementTypeFileHintTrans">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_HINT'); ?>
                                                        <br /><em>(
                                                            <?php echo $active_language_code ?>)
                                                        </em>
                                                    </label>
                                                    <textarea id="bfElementTypeFileHintTrans"></textarea>
                                                </div>
                                            </div>
                                            <!-- FILE END -->
                                        </fieldset>
                                        <fieldset id="bfValidationScript" style="display:none">
                                            <legend>
                                                <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_VALIDATION'); ?>
                                            </legend>
                                            <span id="bfElementValidationRequiredSet" style="display:none">
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_VALIDATION_REQUIRED')); ?>"
                                                        for="bfElementValidationRequired">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_VALIDATION_REQUIRED'); ?>
                                                    </label>
                                                    <input type="checkbox" value="" id="bfElementValidationRequired" />
                                                </div>
                                            </span>

                                            <div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel hasTooltip bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_VALIDATION_TYPE')); ?>"
                                                        for="bfElementValidation">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ELEMENT_VALIDATION_LABEL'); ?>
                                                    </label>
                                                    <?php echo Text::_('COM_BREEZINGFORMSNG_TYPE') ?>:
                                                    <input
                                                        onclick="JQuery('#bfValidationScriptFlags').css('display', 'none');
                                                                            JQuery('#bfValidationScriptLibrary').css('display', 'none');JQuery('#bfValidationScriptCustom').css('display', 'none');"
                                                        type="radio" name="validationType" id="bfValidationTypeNone"
                                                        class="bfValidationType" value="0" />
                                                    <?php echo Text::_('COM_BREEZINGFORMSNG_NONE') ?>
                                                    <input
                                                        onclick="JQuery('#bfValidationScriptFlags').css('display', '');
                                                                            JQuery('#bfValidationScriptLibrary').css('display', '');JQuery('#bfValidationScriptCustom').css('display', 'none');"
                                                        type="radio" name="validationType" id="bfValidationTypeLibrary"
                                                        class="bfValidationType" value="1" />
                                                    <?php echo Text::_('COM_BREEZINGFORMSNG_LIBRARY') ?>
                                                    <input
                                                        onclick="JQuery('#bfValidationScriptFlags').css('display', '');JQuery('#bfValidationScriptLibrary').css('display', 'none');
                                                                            JQuery('#bfValidationScriptCustom').css('display', '');"
                                                        type="radio" name="validationType" id="bfValidationTypeCustom"
                                                        class="bfValidationType" value="2" />
                                                    <?php echo Text::_('COM_BREEZINGFORMSNG_CUSTOM') ?>
                                                </div>

                                                <div id="bfValidationScriptFlags" style="display:none">
                                                    <hr />
                                                    <div class="bfPropertyWrap bfng-field">
                                                        <span class="hasTooltip"
                                                            title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_VALIDATION_ERROR_MESSAGE')); ?>">
                                                            <?php echo Text::_('COM_BREEZINGFORMSNG_ERROR_MESSAGE') ?>
                                                            :
                                                        </span> <input type="text" style="width:100%" maxlength="255"
                                                            class="bfValidationMessage" id="bfValidationMessage"
                                                            name="bfValidationMessage" value="" class="inputbox" />
                                                    </div>

                                                    <div class="bfPropertyWrap bfTrans bfng-field">
                                                        <span class="hasTooltip"
                                                            title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_VALIDATION_ERROR_MESSAGE')); ?>">
                                                            <?php echo Text::_('COM_BREEZINGFORMSNG_ERROR_MESSAGE') ?>
                                                            <em>(
                                                                <?php echo $active_language_code ?>)
                                                            </em>:
                                                        </span>
                                                        <input type="text" style="width:100%" maxlength="255"
                                                            class="bfValidationMessage" id="bfValidationMessageTrans"
                                                            name="bfValidationMessage" value="" class="inputbox" />
                                                    </div>
                                                </div>

                                                <div id="bfValidationScriptLibrary" style="display:none">
                                                    <hr />
                                                    <div class="bfPropertyWrap bfng-field">
                                                        <span class="hasTooltip"
                                                            title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_VALIDATION_SCRIPTLIBRARY')); ?>">
                                                            <?php echo Text::_('COM_BREEZINGFORMSNG_SCRIPT') ?>
                                                            :
                                                        </span><br /> <select id="bfValidationScriptSelection"></select>
                                                    </div>
                                                    <br />
                                                    <div id="bfValidationScriptSelectionDescription" style="white-space: pre-line;"></div>
                                                </div>

                                                <div id="bfValidationScriptCustom" style="display:none">
                                                    <hr />
                                                    <div class="bfPropertyWrap bfng-field">
                                                        <div class="hasTooltip btn btn-secondary"
                                                            title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_VALIDATION_CODEFRAMEWORK')); ?>"
                                                            style="cursor: pointer;" onclick="createValidationCode()">
                                                            <?php echo Text::_('COM_BREEZINGFORMSNG_CREATE_CODE_FRAMEWORK') ?>
                                                        </div>

                                                        <?php
                                                        echo \Vcmb\Component\BreezingformsNG\Administrator\Helper\QuickmodeHtml::renderCodeMirror('bfValidationCode');
                                                        ?>

                                                    </div>
                                                </div>
                                            </div>
                                        </fieldset>
                                    </div>
                                    <br />
