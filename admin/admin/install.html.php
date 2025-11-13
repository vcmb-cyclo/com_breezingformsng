<?php
/**
* BreezingForms - A Joomla Forms Application
* @version 1.4.4
* @package BreezingForms
* @copyright (C) 2004 by Peter Koch
* @license Released under the terms of the GNU General Public License
**/
defined('_JEXEC') or die('Direct Access to this location is not allowed.');

class HTML_facileFormsInstaller
{
	static function step2($option, $release)
	{
?>
		<script language="javascript" type="text/javascript">
		function submitbutton(pressbutton)
		{
			var form = document.adminForm;
			var error = '';
			var checked = false;
			for (var i = 0; true; i++) {
				opt = eval('form.opt'+i);
				if (!opt) break;
				if (opt.checked) {
					checked = true;
					break;
				} // if
			} // for
			if (!checked)
				alert("<?php echo BFText::_('COM_BREEZINGFORMS_INSTALL_SELECTMODE'); ?>");
			else{
                Joomla.submitform(pressbutton);
			}
		} // submitbutton

		</script>

        <form action="index.php" method="post" name="adminForm" id="adminForm" class="adminForm">

		<table cellpadding="4" cellspacing="1" border="0" class="adminform">

			<tr><th colspan="3" class="title"><h2><?php echo BFText::_('COM_BREEZINGFORMS_INSTALL_STEP2'); ?></h2></th></tr>
			<tr>
				<td></td>
				<td><?php echo BFText::_('COM_BREEZINGFORMS_INSTALL_STEP2MSG'); ?></td>
				<td></td>
			</tr>
			<tr>
				<td></td>
				<td nowrap>
					<fieldset><legend><?php echo BFText::_('COM_BREEZINGFORMS_INSTALL_SELECTDBMODE'); ?></legend>
					<table cellpadding="4" cellspacing="1" border="0">
						<tr>
							<td nowrap>
								<input type="radio" id="opt0" name="ff_installmode" value="0"<?php if ($release=='') echo ' checked="checked"'; ?>/>
								<label for="opt0"> <?php echo BFText::_('COM_BREEZINGFORMS_INSTALL_NEWINSTALL'); ?></label>
							</td>
						</tr>
                                                <?php
                                                if ($release!=''){
                                                ?>
						<tr>
							<td nowrap>
								<input type="radio" id="opt1" name="ff_installmode" value="1"<?php if ($release=='1.4') echo ' checked="checked"'; ?>/>
								<label for="opt1"> <?php echo BFText::_('COM_BREEZINGFORMS_INSTALL_REINSTALL').' ('.BFText::_('COM_BREEZINGFORMS_INSTALL_UPTODATE').')'; ?></label>
							</td>
						</tr>
                                                <?php
                                                }
                                                ?>
					</table>
					</fieldset>
				</td>
				<td>
                                    <input id="smp2" type="hidden" name="ff_instqmsamples" value="0" />
                                    <input id="smp2" type="hidden" name="ff_instsamples" value="0" />
                                </td>
			</tr>
			<tr>
				<td></td>
				<td nowrap style="text-align:right">
					<input class="btn btn-primary" onclick="submitbutton('step3');" type="submit" value="<?php echo BFText::_('COM_BREEZINGFORMS_TOOLBAR_CONTINUE'); ?>"/>
				</td>
				<td></td>
			</tr>
		</table>
		<input type="hidden" name="option" value="<?php echo $option; ?>" />
		<input type="hidden" name="act" value="installation" />
		<input type="hidden" name="task" value="" />
		</form>
<?php
	} // step2


	static function step3($option, &$errors)
	{
?>
        <form action="index.php" method="post" name="adminForm" id="adminForm" class="adminForm">
		<table cellpadding="4" cellspacing="1" border="0" class="adminform">

			<tr><th colspan="3" class="title"><h2><?php echo BFText::_('COM_BREEZINGFORMS_INSTALL_COMPLETE'); ?></h2></th></tr>
			<tr>
				<td></td>
				<td><?php echo BFText::_('COM_BREEZINGFORMS_INSTALL_COMPLETEMSG'); ?>
					<hr/><br/>
<?php
					if (count($errors)==0)
						echo BFText::_('COM_BREEZINGFORMS_INSTALL_NOMESSAGES');
					else
						for ($i = 0; $i < count($errors); $i++) echo $errors[$i]."<br/>";
?>
				</td>
				<td></td>
			</tr>
			<tr>
				<td></td>
				<td nowrap style="text-align:right">
                                        <input class="btn btn-primary" onclick="Joomla.submitbutton('step4');" type="submit" value="<?php echo BFText::_('COM_BREEZINGFORMS_TOOLBAR_CONTINUE'); ?>"/>
				</td>
				<td></td>
			</tr>
		</table>
		<input type="hidden" name="option" value="<?php echo $option; ?>" />
		<input type="hidden" name="act" value="installation" />
		<input type="hidden" name="task" value="" />
		</form>
<?php
	} // step3

} // class HTML_facileFormsInstaller
?>