<?php
/**
 * BreezingForms - A Joomla Forms Application
 * @version 1.9
 * @package BreezingForms
 * @copyright (C) 2008-2020 by Markus Bopp
 * @copyright (C) 2024 by XDA+GIL
 * @license Released under the terms of the GNU General Public License
 **/

defined('_JEXEC') or die('Direct Access to this location is not allowed.');

use Joomla\CMS\Factory;
use Joomla\CMS\Editor\Editor;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Toolbar\ToolbarHelper;

class HTML_facileFormsScript
{
	static function edit($option, $pkg, &$row, &$typelist)
	{
		global $ff_mossite, $ff_admsite, $ff_config;
		$action = $row->id ? BFText::_('COM_BREEZINGFORMS_SCRIPTS_EDITSCRIPT') : BFText::_('COM_BREEZINGFORMS_SCRIPTS_ADDSCRIPT');
		HTMLHelper::_('bootstrap.tooltip', '.hasTooltip');
		ToolBarHelper::custom('save', 'save.png', 'save_f2.png', BFText::_('COM_BREEZINGFORMS_TOOLBAR_SAVE'), false);
		ToolBarHelper::custom('cancel', 'cancel.png', 'cancel_f2.png', BFText::_('COM_BREEZINGFORMS_TOOLBAR_QUICKMODE_CLOSE'), false);
		?>
		<script type="text/javascript" src="<?php echo $ff_admsite; ?>/admin/areautils.js"></script>
		<script type="text/javascript">
						<!--
						function checkIdentifier(value, name)
						{
							var invalidChars = /\W/;
							var error = '';
							if (value == '')
								error += "<?php echo BFText::_('COM_BREEZINGFORMS_SCRIPTS_ENTERNAME'); ?>\n";
							else
			if (invalidChars.test(value))
				error += "<?php echo BFText::_('COM_BREEZINGFORMS_SCRIPTS_ENTERIDENT'); ?>\n";
			return error;
						} // checkIdentifier

			function submitbutton(pressbutton) {
				var form = document.adminForm;
				var error = '';
				if (pressbutton != 'cancel') {
					error += checkIdentifier(form.name.value, 'name');
					if (form.title.value == '') error += "<?php echo BFText::_('COM_BREEZINGFORMS_SCRIPTS_ENTTITLE'); ?>\n";
				} // if
				if (error != '')
					alert(error);
				else
					Joomla.submitform(pressbutton);
			} // submitbutton

			<?php
			Factory::getApplication()->getDocument()->getWebAssetManager()->addInlineScript('
                    Joomla.submitbutton = submitbutton;  
                ');
			?>

			function createCode() {
				form = document.adminForm;
				name = form.name.value;
				if (name == '') {
					alert("<?php echo BFText::_('COM_BREEZINGFORMS_SCRIPTS_ENTNAMEFIRST'); ?>");
					return;
				} // if
				stype = form.type.value;
				code = '';
				switch (stype) {

					case 'Element Action':
						if (!confirm("<?php echo BFText::_('COM_BREEZINGFORMS_SCRIPTS_CREATEACTCODE'); ?>\n<?php echo BFText::_('COM_BREEZINGFORMS_SCRIPTS_EXISTAPP'); ?>")) return;
						code =
							"function " + name + "(element, action)\n" +
							"{\n" +
							"    switch (action) {\n" +
							"        case 'click':\n" +
							"            break;\n" +
							"        case 'blur':\n" +
							"            break;\n" +
							"        case 'change':\n" +
							"            break;\n" +
							"        case 'focus':\n" +
							"            break;\n" +
							"        case 'select':\n" +
							"            break;\n" +
							"        default:;\n" +
							"    } // switch\n" +
							"} // " + name + "\n";
						break;

					case 'Element Init':
						if (!confirm("<?php echo BFText::_('COM_BREEZINGFORMS_SCRIPTS_CREATEINICODE'); ?>\n<?php echo BFText::_('COM_BREEZINGFORMS_SCRIPTS_EXISTAPP'); ?>")) return;
						code =
							"function " + name + "(element, condition)\n" +
							"{\n" +
							"} // " + name + "\n";
						break;

					case 'Element Validation':
						if (!confirm("<?php echo BFText::_('COM_BREEZINGFORMS_SCRIPTS_CREATEVALCODE'); ?>\n<?php echo BFText::_('COM_BREEZINGFORMS_SCRIPTS_EXISTAPP'); ?>")) return;
						code =
							"function " + name + "(element, message)\n" +
							"{\n" +
							"    if (element_fails_my_test) {\n" +
							"        if (message=='') message = element.name+\" faild in my test.\\n\"\n" +
							"        ff_validationFocus(element.name);\n" +
							"        return message;\n" +
							"    } // if\n" +
							"    return '';\n" +
							"} // " + name + "\n";
						break;

					case 'Form Init':
						if (!confirm("<?php echo BFText::_('COM_BREEZINGFORMS_SCRIPTS_CREATEFINICODE'); ?>\n<?php echo BFText::_('COM_BREEZINGFORMS_SCRIPTS_EXISTAPP'); ?>")) return;
						code =
							"function " + name + "()\n" +
							"{\n" +
							"} // " + name + "\n";
						break;

					case 'Form Submitted':
						if (!confirm("<?php echo BFText::_('COM_BREEZINGFORMS_SCRIPTS_CREATESUBCODE'); ?>\n<?php echo BFText::_('COM_BREEZINGFORMS_SCRIPTS_EXISTAPP'); ?>")) return;
						code =
							"function " + name + "(status, message)\n" +
							"{\n" +
							"    switch (status) {\n" +
							"        case FF_STATUS_OK:\n" +
							"           // do whatever desired on success\n" +
							"           break;\n" +
							"        case FF_STATUS_UNPUBLISHED:\n" +
							"        case FF_STATUS_SAVERECORD_FAILED:\n" +
							"        case FF_STATUS_SAVESUBRECORD_FAILED:\n" +
							"        case FF_STATUS_UPLOAD_FAILED:\n" +
							"        case FF_STATUS_ATTACHMENT_FAILED:\n" +
							"        case FF_STATUS_SENDMAIL_FAILED:\n" +
							"        default:\n" +
							"           alert(message);\n" +
							"    } // switch\n" +
							"} // " + name + "\n";
						break;

					case 'Untyped':
						if (!confirm("<?php echo BFText::_('COM_BREEZINGFORMS_SCRIPTS_CREATEUNTCODE'); ?>\n<?php echo BFText::_('COM_BREEZINGFORMS_SCRIPTS_EXISTAPP'); ?>")) return;
						code =
							"function " + name + "()\n" +
							"{\n" +
							"} // " + name + "\n";
						break;

					default:
						alert("<?php echo BFText::_('COM_BREEZINGFORMS_SCRIPTS_UNKNOWNTYPE'); ?> " + stype);

				} // switch
				oldcode = form.code.value;
				if (oldcode != '')
					form.code.value =
						code +
						"\n// -------------- <?php echo BFText::_('COM_BREEZINGFORMS_SCRIPTS_OLDBELOW'); ?> --------------\n\n" +
						oldcode;
				else
					form.code.value = code;
			} // createCode

			onload = function () {
				document.adminForm.title.focus();
			} // onload
			//-->
		</script>
		<form action="index.php" method="post" name="adminForm" id="adminForm" class="adminForm">
			<table cellpadding="4" cellspacing="1" border="0" class="adminform" style="width:100%;">
				<tr>
					<td></td>
					<td nowrap>
						<?php echo BFText::_('COM_BREEZINGFORMS_SCRIPTS_TITLE'); ?>:
					</td>
					<td nowrap>
						<input type="text" size="50" maxlength="50" name="title" value="<?php echo $row->title; ?>"
							class="inputbox" />
						<?php
						echo '<span><span title="' . bf_ToolTipText(BFText::_('COM_BREEZINGFORMS_SCRIPTS_TIPTITLE')) . '" class="icon-question-circle hasTooltip" aria-hidden="true"></span></span>';
						?>
					</td>
					<td></td>
				</tr>
				<tr>
					<td></td>
					<td nowrap>
						<?php echo BFText::_('COM_BREEZINGFORMS_SCRIPTS_PUBLISHED'); ?>:
					</td>
					<td nowrap>
						<?php echo HTMLHelper::_('select.booleanlist', "published", "", $row->published); ?>
					</td>
					<td></td>
				</tr>
				<tr>
					<td></td>
					<td nowrap>
						<?php echo BFText::_('COM_BREEZINGFORMS_SCRIPTS_PACKAGE'); ?>:
					</td>
					<td nowrap>
						<input type="text" size="30" maxlength="30" id="package" name="package"
							value="<?php echo $row->package; ?>" class="inputbox" />
					</td>
					<td></td>
				</tr>
				<tr>
					<td></td>
					<td nowrap>
						<?php echo BFText::_('COM_BREEZINGFORMS_SCRIPTS_NAME'); ?>:
					</td>
					<td nowrap>
						<input type="text" size="30" maxlength="30" id="name" name="name" value="<?php echo $row->name; ?>"
							class="inputbox" />
						<?php
						echo '<span><span title="' . bf_ToolTipText(BFText::_('COM_BREEZINGFORMS_SCRIPTS_TIPNAME')) . '" class="icon-question-circle hasTooltip" aria-hidden="true"></span></span>';
						?>
					</td>
					<td></td>
				</tr>
				<tr>
					<td></td>
					<td nowrap>
						<?php echo BFText::_('COM_BREEZINGFORMS_SCRIPTS_TYPE'); ?>:
					</td>
					<td nowrap>
						<select id="type" name="type" class="inputbox" size="1">
							<?php
							for ($t = 0; $t < count($typelist); $t++) {
								$tl = $typelist[$t];
								$selected = '';
								if ($tl[0] == $row->type)
									$selected = ' selected';
								echo '<option value="' . $tl[0] . '"' . $selected . '>' . $tl[1] . '</option>';
							} // for
							?>
						</select>
					</td>
					<td></td>
				</tr>
				<tr>
					<td></td>
					<td nowrap colspan="2">
						<?php echo BFText::_('COM_BREEZINGFORMS_SCRIPTS_DESCRIPTION'); ?>:
						<a href="javascript:void(0);"
							onClick="textAreaResize('description',<?php echo $ff_config->areasmall; ?>);">[
							<?php echo $ff_config->areasmall; ?>]
						</a>
						<a href="javascript:void(0);"
							onClick="textAreaResize('description',<?php echo $ff_config->areamedium; ?>);">[
							<?php echo $ff_config->areamedium; ?>]
						</a>
						<a href="javascript:void(0);"
							onClick="textAreaResize('description',<?php echo $ff_config->arealarge; ?>);">[
							<?php echo $ff_config->arealarge; ?>]
						</a>
						<br />
						<textarea wrap="off" name="description" style="width:100%;" rows="<?php echo $ff_config->areasmall; ?>"
							class="inputbox"><?php echo $row->description; ?></textarea>
					</td>
					<td></td>
				</tr>

				<tr>
					<td></td>
					<td nowrap colspan="2">
						<?php echo BFText::_('COM_BREEZINGFORMS_SCRIPTS_CODE'); ?>:
						<br />

						<?php
						$params = array('syntax' => 'javascript');
						$editor = Editor::getInstance('codemirror');
						echo $editor->display('code', $row->code, '100%', 300, 40, 20, false, 'code', null, null, $params);
						?>

					</td>
					<td></td>
				</tr>
			</table>
			<input type="hidden" name="pkg" value="<?php echo $pkg; ?>" />
			<input type="hidden" name="id" value="<?php echo $row->id; ?>" />
			<input type="hidden" name="option" value="<?php echo $option; ?>" />
			<input type="hidden" name="task" value="" />
			<input type="hidden" name="act" value="managescripts" />
		</form>
		<?php
	} // edit

	static function typeName($type)
	{
		switch ($type) {
			case 'Untyped':
				return BFText::_('COM_BREEZINGFORMS_SCRIPTS_UNTYPED');
			case 'Element Init':
				return BFText::_('COM_BREEZINGFORMS_SCRIPTS_ELEMENTINIT');
			case 'Element Action':
				return BFText::_('COM_BREEZINGFORMS_SCRIPTS_ELEMENTACTION');
			case 'Element Validation':
				return BFText::_('COM_BREEZINGFORMS_SCRIPTS_ELEMENTVALID');
			case 'Form Init':
				return BFText::_('COM_BREEZINGFORMS_SCRIPTS_FORMINIT');
			case 'Form Submitted':
				return BFText::_('COM_BREEZINGFORMS_SCRIPTS_FORMSUBMIT');
			default:
				;
		} // switch
		return '???';
	} // typeName

	static function listitems($option, &$rows, &$pkglist, $pkg)
	{
		global $ff_config, $ff_version;
		$sort = BFRequest::getCmd('sort', 'name');
		$dir = strtoupper(BFRequest::getCmd('dir', 'ASC'));
		$dir = $dir === 'DESC' ? 'DESC' : 'ASC';
		$baseQuery = 'index.php?option=' . $option . '&act=managescripts&pkg=' . urlencode($pkg);
		$toggleDir = function ($column) use ($sort, $dir) {
			if ($sort === $column) {
				return $dir === 'ASC' ? 'DESC' : 'ASC';
			}
			return 'ASC';
		};
		?>
		<script type="text/javascript">
							<!--
							function submitbutton(pressbutton)
							{
								var form = document.adminForm;
								switch (pressbutton) {
									case 'copy':
									case 'publish':
									case 'unpublish':
									case 'remove':
									case 'test':
										if (form.boxchecked.value==0) {
											alert("<?php echo BFText::_('COM_BREEZINGFORMS_SCRIPTS_SELSCRIPTSFIRST'); ?>");
			return;
										} // if
			break;
									default:
			break;
								} // switch
			if (pressbutton == 'remove')
				if (!confirm("<?php echo BFText::_('COM_BREEZINGFORMS_SCRIPTS_ASKDELETE'); ?>")) return;
			if (pressbutton == '' && form.pkgsel.value == '')
				form.pkg.value = '- blank -';
			else
				form.pkg.value = form.pkgsel.value;
			Joomla.submitform(pressbutton);
							} // submitbutton

			<?php
			Factory::getApplication()->getDocument()->getWebAssetManager()->addInlineScript('
            
                Joomla.submitbutton = submitbutton;
            ');

			ToolBarHelper::custom('new', 'new.png', 'new_f2.png', BFText::_('COM_BREEZINGFORMS_TOOLBAR_NEW'), false);
			ToolBarHelper::custom('copy', 'copy.png', 'copy_f2.png', BFText::_('COM_BREEZINGFORMS_TOOLBAR_COPY'), false);
			ToolBarHelper::custom('publish', 'publish.png', 'publish_f2.png', BFText::_('COM_BREEZINGFORMS_TOOLBAR_PUBLISH'), false);
			ToolBarHelper::custom('unpublish', 'unpublish.png', 'unpublish_f2.png', BFText::_('COM_BREEZINGFORMS_TOOLBAR_UNPUBLISH'), false);
			ToolBarHelper::custom('test', 'eye', '', 'Test', false);
			ToolBarHelper::custom('remove', 'delete.png', 'delete_f2.png', BFText::_('COM_BREEZINGFORMS_TOOLBAR_DELETE'), false);
			?>

			function listItemTask(id, task) {
				var f = document.adminForm;
				cb = eval('f.' + id);
				if (cb) {
					for (i = 0; true; i++) {
						cbx = eval('f.cb' + i);
						if (!cbx) break;
						cbx.checked = false;
					} // for
					cb.checked = true;
					f.boxchecked.value = 1;
					Joomla.submitbutton(task);
				}
				return false;
			} // listItemTask
			//-->
		</script>
		<form action="index.php" method="post" name="adminForm" id="adminForm">

			<label class="bfPackageSelector">

				<?php echo BFText::_('COM_BREEZINGFORMS_SCRIPTS_PACKAGE'); ?>
				<select id="pkgsel" name="pkgsel" class="inputbox" size="1" onchange="submitbutton('');">
					<?php
					if (count($pkglist))
						foreach ($pkglist as $pkg) {
							$selected = '';
							if ($pkg[0])
								$selected = ' selected';
							echo '<option value="' . $pkg[1] . '"' . $selected . '>' . $pkg[1] . '&nbsp;</option>';
						} // foreach
					?>
				</select>

			</label>

			<table cellpadding="4" cellspacing="0" border="0" width="100%" class="adminlist table table-striped">
				<tr>
					<th style="width: 25px;" nowrap align="right">
						<a href="<?php echo $baseQuery . '&sort=id&dir=' . $toggleDir('id'); ?>">ID</a>
					</th>
					<th style="width: 25px;" nowrap align="center"><input type="checkbox" name="toggle" value=""
							onclick="Joomla.checkAll(this);" /></th>
					<th align="left">
						<a href="<?php echo $baseQuery . '&sort=title&dir=' . $toggleDir('title'); ?>">
							<?php echo BFText::_('COM_BREEZINGFORMS_SCRIPTS_TITLE'); ?>
						</a>
					</th>
					<th align="left">
						<a href="<?php echo $baseQuery . '&sort=name&dir=' . $toggleDir('name'); ?>">
							<?php echo BFText::_('COM_BREEZINGFORMS_SCRIPTS_NAME'); ?>
						</a>
					</th>
					<th align="left">
						<a href="<?php echo $baseQuery . '&sort=type&dir=' . $toggleDir('type'); ?>">
							<?php echo BFText::_('COM_BREEZINGFORMS_SCRIPTS_TYPE'); ?>
						</a>
					</th>
					<th align="left">
						<a href="<?php echo $baseQuery . '&sort=description&dir=' . $toggleDir('description'); ?>">
							<?php echo BFText::_('COM_BREEZINGFORMS_SCRIPTS_DESCRIPTION'); ?>
						</a>
					</th>
					<th align="left">
						<a href="<?php echo $baseQuery . '&sort=modified&dir=' . $toggleDir('modified'); ?>">
							<?php echo BFText::_('JGLOBAL_MODIFIED'); ?>
						</a>
					</th>
					<th align="center">
						<a href="<?php echo $baseQuery . '&sort=published&dir=' . $toggleDir('published'); ?>">
							<?php echo BFText::_('COM_BREEZINGFORMS_SCRIPTS_PUBLISHED'); ?>
						</a>
					</th>
					<th align="center">Test</th>
				</tr>
				<?php
				$k = 0;
				for ($i = 0; $i < count($rows); $i++) {
					$row = $rows[$i];
					$desc = $row->description;
					if (strlen($desc) > $ff_config->limitdesc)
						$desc = substr($desc, 0, $ff_config->limitdesc) . '...';
					?>
					<tr class="row<?php echo $k; ?>">
						<td nowrap valign="top" align="right">
							<?php echo $row->id; ?>
						</td>
						<td nowrap valign="top" align="center"><input type="checkbox" id="cb<?php echo $i; ?>" name="ids[]"
								value="<?php echo $row->id; ?>" onclick="Joomla.isChecked(this.checked);" /></td>
						<td valign="top" align="left"><a href="#edit" onclick="return listItemTask('cb<?php echo $i; ?>','edit')">
								<?php echo $row->title; ?>
							</a></td>
						<td valign="top" align="left">
							<?php echo $row->name; ?>
						</td>
						<td valign="top" align="left">
							<?php echo HTML_facileFormsScript::typeName($row->type); ?>
						</td>
						<td valign="top" align="left">
							<?php echo htmlspecialchars($desc, ENT_QUOTES); ?>
						</td>
						<td valign="top" align="left">
							<?php
							$lastModified = $row->modified ?: $row->created;
							echo $lastModified ? HTMLHelper::date($lastModified, 'Y-m-d H:i') : '-';
							?>
						</td>
						<td valign="top" align="center">
							<?php
							if ($row->published == "1") {
								?><a class="tbody-icon active" href="javascript:void(0);"
									onClick="return listItemTask('cb<?php echo $i; ?>','unpublish')"><span class="icon-publish"
										aria-hidden="true"></span></a>
								<?php
							} else {
								?><a class="tbody-icon" href="javascript:void(0);"
									onClick="return listItemTask('cb<?php echo $i; ?>','publish')"><span class="icon-unpublish"
										aria-hidden="true"></span></a>
								<?php
							} // if
							?>
						</td>
						<td valign="top" align="center">
							<a class="tbody-icon" href="javascript:void(0);"
								onClick="return listItemTask('cb<?php echo $i; ?>','test')"><span class="icon-eye"
									aria-hidden="true"></span></a>
						</td>
					</tr>
					<?php
					$k = 1 - $k;
				} // for
				?>
			</table>
			<input type="hidden" name="boxchecked" value="0" />
			<input type="hidden" name="option" value="<?php echo $option; ?>" />
			<input type="hidden" name="act" value="managescripts" />
			<input type="hidden" name="task" value="" />
			<input type="hidden" name="pkg" value="" />
		</form>
		<?php
	} // listitems

	static function test($option, $pkg, &$row, $functionName, $paramNames, $paramDefaults)
	{
		ToolBarHelper::custom('edit', 'cancel.png', 'cancel_f2.png', 'Retour', false);
		ToolBarHelper::custom('prev', 'arrow-left', '', 'Precedent', false);
		ToolBarHelper::custom('next', 'arrow-right', '', 'Suivant', false);
		$safeCode = json_encode((string) $row->code);
		$safeFunction = json_encode((string) $functionName);
		?>
		<script type="text/javascript">
			(function () {
				var testCode = <?php echo $safeCode; ?>;
				var defaultFunctionName = <?php echo $safeFunction; ?>;

				function parseValue(raw) {
					var value = String(raw || '').trim();
					if (value === '') {
						return '';
					}

					var lower = value.toLowerCase();
					if (lower === 'null') return null;
					if (lower === 'true') return true;
					if (lower === 'false') return false;

					if (/^-?\d+(\.\d+)?$/.test(value)) {
						return value.indexOf('.') !== -1 ? parseFloat(value) : parseInt(value, 10);
					}

					var startsLikeJson = (value.charAt(0) === '{' && value.charAt(value.length - 1) === '}') ||
						(value.charAt(0) === '[' && value.charAt(value.length - 1) === ']');
					if (startsLikeJson) {
						try {
							return JSON.parse(value);
						} catch (e) {
							return value;
						}
					}

					var quoted = (value.charAt(0) === '"' && value.charAt(value.length - 1) === '"') ||
						(value.charAt(0) === "'" && value.charAt(value.length - 1) === "'");
					if (quoted && value.length >= 2) {
						return value.slice(1, -1);
					}

					return value;
				}

				function formatValue(value) {
					if (typeof value === 'undefined') {
						return 'undefined';
					}
					if (typeof value === 'string') {
						return value;
					}
					try {
						return JSON.stringify(value, null, 2);
					} catch (e) {
						return String(value);
					}
				}

				window.submitbutton = function (pressbutton) {
					Joomla.submitform(pressbutton, document.getElementById('adminForm'));
				};

				window.bfRunScriptTest = function () {
					var fnField = document.getElementById('bf-script-function');
					var output = document.getElementById('bf-script-test-output');
					var result = document.getElementById('bf-script-test-result');
					var error = document.getElementById('bf-script-test-error');
					var logs = document.getElementById('bf-script-test-logs');

					if (!fnField || !output || !result || !error || !logs) {
						return false;
					}

					var functionName = String(fnField.value || '').trim();
					if (!functionName) {
						output.style.display = 'block';
						result.style.display = 'none';
						logs.style.display = 'none';
						error.style.display = 'block';
						error.textContent = 'Veuillez renseigner le nom de la fonction a tester.';
						return false;
					}
					if (!/^[A-Za-z_$][A-Za-z0-9_$]*$/.test(functionName)) {
						output.style.display = 'block';
						result.style.display = 'none';
						logs.style.display = 'none';
						error.style.display = 'block';
						error.textContent = 'Nom de fonction invalide.';
						return false;
					}

					var argFields = document.querySelectorAll('.bf-test-arg');
					var args = [];
					var labels = [];
					for (var i = 0; i < argFields.length; i++) {
						var field = argFields[i];
						labels.push(field.getAttribute('data-param') || ('arg' + i));
						args.push(parseValue(field.value));
					}

					var consoleLines = [];
					var consoleProxy = {
						log: function () {
							consoleLines.push('log: ' + Array.prototype.slice.call(arguments).join(' '));
						},
						warn: function () {
							consoleLines.push('warn: ' + Array.prototype.slice.call(arguments).join(' '));
						},
						error: function () {
							consoleLines.push('error: ' + Array.prototype.slice.call(arguments).join(' '));
						}
					};

					output.style.display = 'block';
					result.style.display = 'none';
					error.style.display = 'none';
					logs.style.display = 'none';
					result.textContent = '';
					error.textContent = '';
					logs.textContent = '';

					try {
						var runner = new Function('scriptCode', 'fnName', 'args', 'consoleProxy',
							'var FF_STATUS_OK = 0;\n' +
							'var FF_STATUS_UNPUBLISHED = 1;\n' +
							'var FF_STATUS_SAVERECORD_FAILED = 2;\n' +
							'var FF_STATUS_SAVESUBRECORD_FAILED = 3;\n' +
							'var FF_STATUS_UPLOAD_FAILED = 4;\n' +
							'var FF_STATUS_ATTACHMENT_FAILED = 5;\n' +
							'var FF_STATUS_SENDMAIL_FAILED = 6;\n' +
							'function ff_validationFocus(){ return true; }\n' +
							'var console = consoleProxy || window.console;\n' +
							'eval(scriptCode);\n' +
							'var target = null;\n' +
							'try { target = eval(fnName); } catch (e) { target = null; }\n' +
							'if (typeof target !== "function") {\n' +
							'  throw new Error("Function \'" + fnName + "\' not found in script code.");\n' +
							'}\n' +
							'return target.apply(window, args);'
						);

						var executed = runner(testCode, functionName, args, consoleProxy);
						result.style.display = 'block';
						result.textContent =
							'Result:\n' + formatValue(executed) +
							'\n\nParameters:\n' + formatValue(labels.map(function (name, idx) { return [name, args[idx]]; }));
					} catch (e) {
						error.style.display = 'block';
						error.textContent =
							'Error: ' + (e && e.message ? e.message : e) +
							'\n\nParameters:\n' + formatValue(labels.map(function (name, idx) { return [name, args[idx]]; }));
					}

					if (consoleLines.length) {
						logs.style.display = 'block';
						logs.textContent = consoleLines.join('\n');
					}

					return false;
				};

				window.addEventListener('load', function () {
					var field = document.getElementById('bf-script-function');
					if (field && !field.value) {
						field.value = defaultFunctionName || '';
					}
				});
				if (window.Joomla) {
					window.Joomla.submitbutton = window.submitbutton;
				}
			})();
		</script>
		<form action="index.php" method="post" name="adminForm" id="adminForm" class="adminForm">
			<div class="d-flex justify-content-between align-items-center mb-3">
				<h2 class="m-0">Test Script</h2>
				<button type="button" class="btn btn-primary" onclick="return bfRunScriptTest();">
					<span class="icon-eye" aria-hidden="true"></span>
					Lancer
				</button>
			</div>
			<h3><?php echo htmlspecialchars((string) $row->title, ENT_QUOTES); ?></h3>
			<div class="card mb-3 bg-light">
				<div class="card-body">
					<div class="row">
						<div class="col-sm-6 col-md-3">
							<strong>Script ID:</strong> <?php echo (int) $row->id; ?>
						</div>
						<div class="col-sm-6 col-md-3">
							<strong>Package:</strong> <?php echo htmlspecialchars((string) $row->package, ENT_QUOTES); ?>
						</div>
						<div class="col-sm-6 col-md-3">
							<strong>Name:</strong> <?php echo htmlspecialchars((string) $row->name, ENT_QUOTES); ?>
						</div>
						<div class="col-sm-6 col-md-3">
							<strong>Type:</strong> <?php echo htmlspecialchars(self::typeName((string) $row->type), ENT_QUOTES); ?>
						</div>
					</div>
				</div>
			</div>
			<?php if (!empty($row->description)) { ?>
				<div class="card mb-3">
					<div class="card-header">Description</div>
					<div class="card-body">
						<div class="form-control bg-light" style="white-space: pre-wrap;">
							<?php echo HTMLHelper::_('content.prepare', $row->description); ?>
						</div>
					</div>
				</div>
			<?php } ?>
			<div class="card mb-3 bg-light">
				<div class="card-body">
					<label for="bf-script-function"><strong>Function</strong></label>
					<input type="text" id="bf-script-function" class="form-control" value="<?php echo htmlspecialchars((string) $functionName, ENT_QUOTES); ?>" />
					<small class="text-muted">Valeurs: null, true, false, nombres, JSON ({}/[]), ou texte.</small>
				</div>
			</div>
			<div class="card mb-3">
				<div class="card-header">Arguments</div>
				<div class="card-body">
					<table cellpadding="4" cellspacing="0" border="0" class="adminlist table table-striped">
						<tr>
							<th>Parametre</th>
							<th>Valeur</th>
						</tr>
						<?php if (!count($paramNames)) { ?>
							<tr>
								<td colspan="2">Aucun parametre detecte.</td>
							</tr>
						<?php } else { ?>
							<?php for ($i = 0; $i < count($paramNames); $i++) { ?>
								<?php
								$name = $paramNames[$i];
								$default = isset($paramDefaults[$i]) ? $paramDefaults[$i] : '';
								?>
								<tr>
									<td><?php echo htmlspecialchars($name, ENT_QUOTES); ?></td>
									<td>
										<input
											type="text"
											class="inputbox bf-test-arg"
											data-param="<?php echo htmlspecialchars($name, ENT_QUOTES); ?>"
											value="<?php echo htmlspecialchars($default, ENT_QUOTES); ?>" />
									</td>
								</tr>
							<?php } ?>
						<?php } ?>
					</table>
				</div>
			</div>
			<div id="bf-script-test-output" class="card mb-3" style="display:none;">
				<div class="card-header">Resultat</div>
				<div class="card-body">
					<pre id="bf-script-test-result" class="alert alert-success" style="display:none;"></pre>
					<pre id="bf-script-test-error" class="alert alert-danger" style="display:none;"></pre>
					<pre id="bf-script-test-logs" class="alert alert-secondary" style="display:none;"></pre>
				</div>
			</div>
			<div class="accordion" id="bfScriptCodeAccordion">
				<div class="accordion-item bg-light">
					<h2 class="accordion-header" id="bfScriptCodeHeading">
						<button class="accordion-button collapsed bg-light" type="button" data-bs-toggle="collapse"
							data-bs-target="#bfScriptCodeCollapse" aria-expanded="false" aria-controls="bfScriptCodeCollapse">
							Script code
						</button>
					</h2>
					<div id="bfScriptCodeCollapse" class="accordion-collapse collapse" aria-labelledby="bfScriptCodeHeading"
						data-bs-parent="#bfScriptCodeAccordion">
						<div class="accordion-body bg-light">
							<pre><?php echo htmlspecialchars((string) $row->code, ENT_QUOTES); ?></pre>
						</div>
					</div>
				</div>
			</div>

			<input type="hidden" name="option" value="<?php echo $option; ?>" />
			<input type="hidden" name="task" value="test" />
			<input type="hidden" name="act" value="managescripts" />
			<input type="hidden" name="pkg" value="<?php echo htmlspecialchars((string) $pkg, ENT_QUOTES); ?>" />
			<input type="hidden" name="ids[]" value="<?php echo (int) $row->id; ?>" />
			<input type="hidden" name="test_context" value="1" />
		</form>
		<?php
	} // test

} // class HTML_facileFormsScript
?>
