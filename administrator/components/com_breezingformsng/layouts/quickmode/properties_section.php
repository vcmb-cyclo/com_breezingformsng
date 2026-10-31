<?php
/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/** @var string $active_language_code */
use Joomla\CMS\HTML\Helpers\Bootstrap;
use Joomla\CMS\Language\Text;
?>
                                    <div class="bfProperties" id="bfSectionProperties" style="display:none">
                                        <br />
                                        <fieldset>
                                            <legend>
                                                <?php echo Text::_('COM_BREEZINGFORMSNG_SECTION_PROPERTIES'); ?>
                                            </legend>
                                            <div class="bfPropertyWrap bfng-field">
                                                <label class="bfPropertyLabel bfng-field__label"
                                                    title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_SECTION_TYPE')); ?>"
                                                    for="bfSectionType">
                                                    <?php echo Text::_('COM_BREEZINGFORMSNG_SECTION_TYPE'); ?>
                                                </label>
                                                <select id="bfSectionType">
                                                    <option value="normal">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_NORMAL'); ?>
                                                    </option>
                                                    <option value="section">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_FIELDSET'); ?>
                                                    </option>
                                                </select>
                                            </div>
                                            <div class="bfPropertyWrap bfng-field">
                                                <label class="bfPropertyLabel bfng-field__label"
                                                    title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_SECTION_DISPLAY_TYPE')); ?>"
                                                    for="bfSectionDisplayType">
                                                    <?php echo Text::_('COM_BREEZINGFORMSNG_SECTION_DISPLAY_TYPE'); ?>
                                                </label>
                                                <select id="bfSectionDisplayType">
                                                    <option value="inline">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_INLINE'); ?>
                                                    </option>
                                                    <option value="breaks">
                                                        <?php echo Text::_('COM_BREEZINGFORMSNG_BREAKS'); ?>
                                                    </option>
                                                </select>
                                            </div>
                                            <div class="bfPropertyWrap bfng-field">
                                                <label class="bfPropertyLabel bfng-field__label"
                                                    title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_SECTION_TITLE')); ?>"
                                                    for="bfSectionTitle">
                                                    <?php echo Text::_('COM_BREEZINGFORMSNG_SECTION_TITLE'); ?>
                                                </label>
                                                <input type="text" value="" id="bfSectionTitle" />
                                            </div>

                                            <div class="bfPropertyWrap bfTrans bfng-field">
                                                <label class="bfPropertyLabel bfng-field__label"
                                                    title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_SECTION_TITLE')); ?>"
                                                    for="bfSectionTitleTrans">
                                                    <?php echo Text::_('COM_BREEZINGFORMSNG_SECTION_TITLE'); ?>
                                                    <br /><em>(
                                                        <?php echo $active_language_code ?>)
                                                    </em>
                                                </label>
                                                <input type="text" value="" id="bfSectionTitleTrans" />
                                            </div>

                                            <div class="bfPropertyWrap bfng-field">
                                                <label class="bfPropertyLabel bfng-field__label"
                                                    title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_SECTION_NAME')); ?>"
                                                    for="bfSectionName">
                                                    <?php echo Text::_('COM_BREEZINGFORMSNG_SECTION_NAME'); ?>
                                                </label>
                                                <input type="text" value="" id="bfSectionName" />
                                            </div>
                                            <div class="bfPropertyWrap bfng-field">
                                                <label class="bfPropertyLabel bfng-field__label"
                                                    title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_SECTION_DESCRIPTION')); ?>"
                                                    for="bfSectionDescription">
                                                    <?php echo Text::_('COM_BREEZINGFORMSNG_SECTION_DESCRIPTION'); ?>
                                                </label>


                                                <?php
                                                echo Bootstrap::renderModal(
                                                    'bfPropertiesDescriptionModal',
                                                    array(
                                                        'url' => "index.php?option=com_breezingformsng&tmpl=component&task=quickmode.editor",
                                                        'width' => '1024px',
                                                        'height' => '500px'
                                                    )
                                                );
                                                ?>

                                                <a href="#bfPropertiesDescriptionModal" data-bs-toggle="modal">
                                                    <?php echo Text::_('COM_BREEZINGFORMSNG_EDIT_DESCRIPTION'); ?>
                                                </a>

                                            </div>

                                            <div class="bfPropertyWrap bfTrans bfng-field">
                                                <label class="bfPropertyLabel bfng-field__label"
                                                    title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_SECTION_DESCRIPTION')); ?>"
                                                    for="bfSectionDescriptionTrans">
                                                    <?php echo Text::_('COM_BREEZINGFORMSNG_SECTION_DESCRIPTION'); ?>
                                                    <br /><em>(
                                                        <?php echo $active_language_code ?>)
                                                    </em>
                                                </label>

                                                <?php
                                                echo Bootstrap::renderModal(
                                                    'bfPropertiesDescriptionModal2',
                                                    array(
                                                        'url' => "index.php?option=com_breezingformsng&tmpl=component&task=quickmode.editor&active_language_code=" . $active_language_code,
                                                        'width' => '1024px',
                                                        'height' => '500px'
                                                    )
                                                );
                                                ?>

                                                <a href="#bfPropertiesDescriptionModal2" data-bs-toggle="modal">
                                                    <?php echo Text::_('COM_BREEZINGFORMSNG_EDIT_DESCRIPTION'); ?>
                                                </a>


                                            </div>
                                        </fieldset>
                                    </div>
                                    <!-- SECTION PROPERTIES END -->

                                    <!-- ELEMENT PROPERTIES BEGIN -->
