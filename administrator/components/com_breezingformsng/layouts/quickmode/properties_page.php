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
                                    <div class="bfProperties" id="bfPageProperties" style="display:none">
                                        <br />
                                        <fieldset>
                                            <legend>
                                                <?php echo Text::_('COM_BREEZINGFORMSNG_PAGE_PROPERTIES'); ?>
                                            </legend>
                                            <div class="bfPropertyWrap bfng-field">
                                                <label class="bfPropertyLabel bfng-field__label"
                                                    title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_PAGE_INTRO')); ?>"
                                                    for="bfPageIntro">
                                                    <?php echo Text::_('COM_BREEZINGFORMSNG_PAGE_INTRO'); ?>
                                                </label>

                                                <?php
                                                echo Bootstrap::renderModal(
                                                    'bfPropertiesIntroModal',
                                                    array(
                                                        'url' => "index.php?option=com_breezingformsng&tmpl=component&task=quickmode.editor",
                                                        'width' => '1024px',
                                                        'height' => '500px'
                                                    )
                                                );
                                                ?>

                                                <a href="#bfPropertiesIntroModal" data-bs-toggle="modal">
                                                    <?php echo Text::_('COM_BREEZINGFORMSNG_EDIT_INTRO'); ?>
                                                </a>

                                            </div>

                                            <div class="bfPropertyWrap bfTrans bfng-field">
                                                <label class="bfPropertyLabel bfng-field__label"
                                                    title="<?php echo bf_tooltipText(Text::_('COM_BREEZINGFORMSNG_QM_PAGE_INTRO')); ?>"
                                                    for="bfPageIntroTrans">
                                                    <?php echo Text::_('COM_BREEZINGFORMSNG_PAGE_INTRO'); ?>
                                                    <br /><em>(
                                                        <?php echo $active_language_code ?>)
                                                    </em>
                                                </label>


                                                <?php
                                                echo Bootstrap::renderModal(
                                                    'bfPropertiesIntroModal2',
                                                    array(
                                                        'url' => "index.php?option=com_breezingformsng&tmpl=component&task=quickmode.editor&active_language_code=" . $active_language_code,
                                                        'width' => '1024px',
                                                        'height' => '500px'
                                                    )
                                                );
                                                ?>

                                                <a href="#bfPropertiesIntroModal2" data-bs-toggle="modal">
                                                    <?php echo Text::_('COM_BREEZINGFORMSNG_EDIT_INTRO'); ?>
                                                </a>

                                            </div>
                                        </fieldset>
                                    </div>
                                    <!-- PAGE PROPERTIES END -->

                                    <!-- SECTION PROPERTIES BEGIN -->
