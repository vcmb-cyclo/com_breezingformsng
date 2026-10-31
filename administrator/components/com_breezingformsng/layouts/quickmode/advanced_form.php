<?php
/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/** @var string $dataObjectString */
/** @var array<int, string> $themes */
/** @var array<int, string> $themesbootstrap */
use Joomla\CMS\Language\Text;
?>
                                    <div class="bfAdvanced" id="bfPageAdvanced" style="display:none">
                                    </div>
                                    <div class="bfAdvanced" id="bfFormAdvanced" style="display:none">
                                        <br />
                                        <!--
                                            The "Plus d'options" link that used to live here
                                            (navigating to forms.edit&advanced=1) was repatriated
                                            as the "Options" tab (fragment-3) in QuickmodeHtml -
                                            see docs/maintenance/quickmode-options-tab-plan.md.
                                        -->
                                        <fieldset>
                                            <legend>
                                                <?php echo Text::_('COM_BREEZINGFORMSNG_ADVANCED_FORM_THEMES'); ?>
                                            </legend>
                                            <div class="bfPropertyWrap bfng-field">
                                                <label class="bfPropertyLabel bfng-field__label"
                                                    title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_THEME_ENGINE')); ?>"
                                                    for="bfThemeBootstrapThemeBreezingForms">
                                                    <?php echo Text::_('COM_BREEZINGFORMSNG_CHOOSE_THEME_ENGINE'); ?>
                                                </label>
                                                <input
                                                    onclick="JQuery('#bfThemeBootstrapDiv').css('display', 'none');JQuery('#bfThemeBreezingFormsDiv').css('display', 'block');" type="radio"
                                                    name="bfThemeBootstrapThemeEngine" value=""
                                                    id="bfThemeBootstrapThemeBreezingForms" /> BreezingForms
                                                <input
                                                    onclick="JQuery('#bfThemeBootstrapDiv').css('display', 'block'); JQuery('#bfThemeBreezingFormsDiv').css('display', 'none');" 
                                                    type="radio"
                                                    name="bfThemeBootstrapThemeEngine" value=""
                                                    id="bfThemeBootstrapThemeBootstrap" />
                                                <?php echo Text::_('COM_BREEZINGFORMSNG_THEME_ENGINE_BOOTSTRAP'); ?>
                                            </div>
                                            <div id="bfThemeBreezingFormsDiv" style="display:none;">
                                                <br />
                                                <legend>
                                                    <?php echo Text::_('COM_BREEZINGFORMSNG_ADVANCED_THEME_BREEZINGFORMS_ENGINE'); ?>
                                                </legend>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_THEME_NATIVE')); ?>"
                                                        for="bfTheme">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_THEME'); ?>
                                                    </label>
                                                    <select id="bfTheme">
                                                        <?php
                                                        $tCount = count($themes);
                                                        for ($i = 0; $i < $tCount; $i++) {
                                                            echo '<option value="' . $themes[$i] . '">' . $themes[$i] . '</option>' . "\n";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div id="bfThemeBootstrapDiv" style="display:none;">
                                                <legend>
                                                    <?php echo Text::_('COM_BREEZINGFORMSNG_ADVANCED_THEME_BOOTSTRAP_ENGINE'); ?>
                                                </legend>
                                                <br />
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_THEME_BOOTSTRAP')); ?>"
                                                        for="bfThemeBootstrap">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_THEME_BOOTSTRAP'); ?>
                                                    </label>
                                                    <select id="bfThemeBootstrap">
                                                        <option value="">Default</option>
                                                        >
                                                        <?php
                                                        $tCount = count($themesbootstrap);
                                                        for ($i = 0; $i < $tCount; $i++) {
                                                            echo '<option value="' . $themesbootstrap[$i] . '">' . $themesbootstrap[$i] . '</option>' . "\n";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>


                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_THEME_BOOTSTRAP_MODE_TIP')); ?>"
                                                        for="bfThemeBootstrapModeYes">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_QM_THEME_BOOTSTRAP_MODE'); ?>
                                                    </label>

                                                    <input type="radio" name="bfThemeBootstrapMode" value=""
                                                        id="bfThemeBootstrapModeYes" />
                                                    <?php echo Text::_('COM_BREEZINGFORMSNG_YES'); ?>
                                                    <input checked="checked" type="radio" name="bfThemeBootstrapMode" value=""
                                                        id="bfThemeBootstrapModeNo" />
                                                    <?php echo Text::_('COM_BREEZINGFORMSNG_NO'); ?>
                                                </div>

                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_THEME_BOOTSTRAP_LABEL_TOP')); ?>"
                                                        for="bfThemeBootstrapLabelTopYes">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_THEME_BOOTSTRAP_LABELTOP'); ?>
                                                    </label>

                                                    <input type="radio" name="bfThemeBootstrapLabelTop" value=""
                                                        id="bfThemeBootstrapLabelTopYes" />
                                                    <?php echo Text::_('COM_BREEZINGFORMSNG_YES'); ?>
                                                    <input checked="checked" type="radio" name="bfThemeBootstrapLabelTop"
                                                        value="" id="bfThemeBootstrapLabelTopNo" />
                                                    <?php echo Text::_('COM_BREEZINGFORMSNG_NO'); ?>
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_THEME_BOOTSTRAP_HERO_UNIT')); ?>"
                                                        for="bfThemeBootstrapUseHeroUnitYes">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_THEME_BOOTSTRAP_USE_HERO_UNIT'); ?>
                                                    </label>

                                                    <input type="radio" name="bfThemeBootstrapUseHeroUnit" value=""
                                                        id="bfThemeBootstrapUseHeroUnitYes" />
                                                    <?php echo Text::_('COM_BREEZINGFORMSNG_YES'); ?>
                                                    <input checked="checked" type="radio" name="bfThemeBootstrapUseHeroUnit"
                                                        value="" id="bfThemeBootstrapUseHeroUnitNo" />
                                                    <?php echo Text::_('COM_BREEZINGFORMSNG_NO'); ?>
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_THEME_BOOTSTRAP_WELL')); ?>"
                                                        for="bfThemeBootstrapUseWellYes">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_THEME_BOOTSTRAP_USE_WELL'); ?>
                                                    </label>

                                                    <input type="radio" name="bfThemeBootstrapUseWell" value=""
                                                        id="bfThemeBootstrapUseWellYes" />
                                                    <?php echo Text::_('COM_BREEZINGFORMSNG_YES'); ?>
                                                    <input checked="checked" type="radio" name="bfThemeBootstrapUseWell"
                                                        value="" id="bfThemeBootstrapUseWellNo" />
                                                    <?php echo Text::_('COM_BREEZINGFORMSNG_NO'); ?>
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_THEME_BOOTSTRAP_PROGRESS')); ?>"
                                                        for="bfThemeBootstrapUseProgressYes">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_THEME_BOOTSTRAP_USE_PROGRESS'); ?>
                                                    </label>

                                                    <input type="radio" name="bfThemeBootstrapUseProgress" value=""
                                                        id="bfThemeBootstrapUseProgressYes" />
                                                    <?php echo Text::_('COM_BREEZINGFORMSNG_YES'); ?>
                                                    <input checked="checked" type="radio" name="bfThemeBootstrapUseProgress"
                                                        value="" id="bfThemeBootstrapUseProgressNo" />
                                                    <?php echo Text::_('COM_BREEZINGFORMSNG_NO'); ?>
                                                </div>

                                                <?php
                                                $dbObject = \Vcmb\Component\BreezingformsNG\Administrator\Helper\QuickmodeHtml::decodeJsonArray((string) $dataObjectString);
                                                if (isset($dbObject['properties']['themebootstrap'])) {
                                                    $themeboostrapfolder = $dbObject['properties']['themebootstrap'];
                                                    $folder = 'themes-bootstrap5';
                                                    $themesbootstrap_path = JPATH_SITE . '/media/breezingforms/' . $folder . '/' . $themeboostrapfolder . '/';
                                                    if (is_dir($themesbootstrap_path) && file_exists($themesbootstrap_path . 'vars.txt')) {
                                                        $varsfile = file_get_contents($themesbootstrap_path . 'vars.txt');
                                                        $varscontent = $varsfile !== false ? htmlentities($varsfile, ENT_QUOTES, 'UTF-8') : '';
                                                        if ($varscontent) {
                                                            echo '<br/>
                                                                <div class="bfPropertyWrap bfng-field">
                                                                <label class="bfPropertyLabel bfng-field__label" title="' . bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_THEME_BOOTSTRAP_PROGRESS')) . '" for="bfThemeBootstrapVars">' . Text::_('COM_BREEZINGFORMSNG_THEME_BOOTSTRAP_VARS') . '</label>
                                                                <textarea id="bfThemeBootstrapVars">' . $varscontent . '</textarea>
                                                                </div>
                                                                <input type="hidden" name="bfThemeBootstrapBefore" id="bfThemeBootstrapBefore" value=""/>';
                                                        }
                                                    }
                                                }
                                                ?>
                                            </div>
 
                                            <legend>
                                                <?php echo Text::_('COM_BREEZINGFORMSNG_ADVANCED_FORM_OTHER'); ?>
                                            </legend>
                                            <div class="bfPropertyWrap bfng-field">
                                                <label class="bfPropertyLabel bfng-field__label"
                                                    title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_HINT_JOOMLA')); ?>"
                                                    for="bfElementAdvancedJoomlaHint">
                                                    <?php echo Text::_('COM_BREEZINGFORMSNG_JOOMLA_HINT'); ?>
                                                </label>
                                                <input type="checkbox" value="" id="bfElementAdvancedJoomlaHint" />
                                            </div>
                                            <div class="bfPropertyWrap bfng-field">
                                                <label class="bfPropertyLabel bfng-field__label"
                                                    title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ERROR_ALERTS')); ?>"
                                                    for="bfElementAdvancedUseErrorAlerts">
                                                    <?php echo Text::_('COM_BREEZINGFORMSNG_USE_ERROR_ALERTS'); ?>
                                                </label>
                                                <input type="checkbox" value="" id="bfElementAdvancedUseErrorAlerts" />
                                            </div>
                                            <div class="bfPropertyWrap bfng-field">
                                                <label class="bfPropertyLabel bfng-field__label"
                                                    title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ERROR_DEFAULT')); ?>"
                                                    for="bfElementAdvancedUseDefaultErrors">
                                                    <?php echo Text::_('COM_BREEZINGFORMSNG_IF_NOT_USE_ERROR_ALERTS'); ?>
                                                </label>
                                                <?php echo Text::_('COM_BREEZINGFORMSNG_IF_USE_DEFAULT_ERRORS'); ?>
                                                <input type="checkbox" value="" id="bfElementAdvancedUseDefaultErrors" />
                                                <?php echo Text::_('COM_BREEZINGFORMSNG_IF_USE_BALLOON_ERRORS'); ?>
                                                <input type="checkbox" value="" id="bfElementAdvancedUseBalloonErrors" />
                                            </div>
                                            <div class="bfPropertyWrap bfng-field">
                                                <div id="bfFadingEffectToggle">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_FADE_IN')); ?>"
                                                        for="bfElementAdvancedFadeIn">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_FADE_IN'); ?>
                                                    </label>
                                                    <input type="checkbox" value="" id="bfElementAdvancedFadeIn" />
                                                </div>
                                            </div>
                                            <div id="bfRollOverToggle">
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ROLLOVER')); ?>"
                                                        for="bfElementAdvancedRollover">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ROLLOVER'); ?>
                                                    </label>
                                                    <input type="checkbox" value="" id="bfElementAdvancedRollover" />
                                                </div>
                                                <div class="bfPropertyWrap bfng-field">
                                                    <label class="bfPropertyLabel bfng-field__label"
                                                        title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_ROLLOVER_COLOR')); ?>"
                                                        for="bfElementAdvancedRolloverColor">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_ROLLOVER_COLOR'); ?>
                                                    </label>
                                                    <input type="text" value="" id="bfElementAdvancedRolloverColor" />
                                                </div>

                                            </div>
                                            <div class="bfPropertyWrap bfng-field">
                                                <label class="bfPropertyLabel bfng-field__label"
                                                    title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_VISIBILITY_RULES')); ?>"
                                                    for="bfElementAdvancedToggleFields">
                                                    <?php echo Text::_('COM_BREEZINGFORMSNG_FORM_TOGGLEFIELDS'); ?>
                                                </label>
                                                <textarea id="bfElementAdvancedToggleFields"></textarea>
                                            </div>
                                        </fieldset>
                                    </div>
