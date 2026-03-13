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
		$hasPersistedUnitTests = $row->id && trim((string) $row->unit_tests) !== '';
		$safePersistedUnitTests = json_encode((string) $row->unit_tests);
		$initialState = array(
			'title' => (string) $row->title,
			'type' => (string) $row->type,
			'package' => (string) $row->package,
			'name' => (string) $row->name,
			'published' => (string) $row->published,
			'description' => (string) $row->description,
			'code' => (string) $row->code,
			'unit_tests' => (string) $row->unit_tests
		);
		$safeInitialState = json_encode($initialState);
		$unitTestsHelp = "Une ligne par test au format entree -> resultat attendu.\n" .
			"Exemples :\n" .
			"'12/ 02/2023 ' -> '12/02/2023'\n" .
			"' abc ' -> 'abc'\n" .
			"'' -> ''\n\n" .
			"Valeurs acceptees : texte entre guillemets simples ou doubles, null, true, false, nombres, JSON ({}/[]).\n" .
			"Si votre fonction attend plusieurs arguments, utilisez un tableau JSON a gauche : [\"abc\", 12] -> \"ok\"";
		HTMLHelper::_('bootstrap.tooltip', '.hasTooltip');
		if ($row->id) {
			ToolBarHelper::custom('prev', 'arrow-left', '', BFText::_('COM_BREEZINGFORMS_PROCESS_PAGEPREV'), false);
			ToolBarHelper::custom('next', 'arrow-right', '', BFText::_('COM_BREEZINGFORMS_PROCESS_PAGENEXT'), false);
			ToolBarHelper::custom('test', 'eye', '', 'Test', false);
		}
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
				if ((pressbutton == 'test' || pressbutton == 'prev' || pressbutton == 'next') && isEditTestBlocked()) {
					alert('Enregistrez le script avant de poursuivre.');
					return;
				}
				if (pressbutton != 'cancel' && pressbutton != 'prev' && pressbutton != 'next' && pressbutton != 'test') {
					error += checkIdentifier(form.name.value, 'name');
					if (form.title.value == '') error += "<?php echo BFText::_('COM_BREEZINGFORMS_SCRIPTS_ENTTITLE'); ?>\n";
				} // if
				if (error != '')
					alert(error);
				else
					Joomla.submitform(pressbutton);
			} // submitbutton
			Joomla.submitbutton = submitbutton;
			window.submitbutton = submitbutton;

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

			function getEditTestToolbarButton() {
				return findToolbarButton('test', ['test']);
			}

			function getEditTestToolbarButtons() {
				return findToolbarButtons('test', ['test']);
			}

			function getEditSaveToolbarButton() {
				return findToolbarButton('save', ['save', 'enregistrer']);
			}

			function getEditPrevToolbarButton() {
				return findToolbarButton('prev', ['prev', 'precedent']);
			}

			function getEditNextToolbarButton() {
				return findToolbarButton('next', ['next', 'suivant']);
			}

			function findToolbarButton(taskName, textHints) {
				var buttons = findToolbarButtons(taskName, textHints);
				return buttons.length ? buttons[0] : null;
			}

			function findToolbarButtons(taskName, textHints) {
				var toolbarRoots = document.querySelectorAll('#toolbar, .toolbar, .subhead, .btn-toolbar, joomla-toolbar, .joomla-toolbar-button');
				var matches = [];
				var seen = [];
				function pushMatch(node) {
					if (!node || node.id === 'bf-edit-unit-tests-button') {
						return;
					}
					var target = node.closest ? (node.closest('button, a, [role="button"], li, .btn, .toolbar-item') || node) : node;
					if (seen.indexOf(target) === -1) {
						seen.push(target);
						matches.push(target);
					}
				}
				var selectors = [
					'#toolbar-' + taskName,
					'.toolbar-' + taskName,
					'.button-' + taskName,
					'[data-task="' + taskName + '"]',
					"[onclick*='" + taskName + "']",
					"[href*='" + taskName + "']"
				];
				for (var r = 0; r < toolbarRoots.length; r++) {
					for (var s = 0; s < selectors.length; s++) {
						var scopedMatches = toolbarRoots[r].querySelectorAll(selectors[s]);
						for (var m = 0; m < scopedMatches.length; m++) {
							pushMatch(scopedMatches[m]);
						}
					}
				}
				for (var s = 0; s < selectors.length; s++) {
					var directMatches = document.querySelectorAll(selectors[s]);
					for (var d = 0; d < directMatches.length; d++) {
						pushMatch(directMatches[d]);
					}
				}
				var candidates = document.querySelectorAll('#toolbar button, #toolbar a, .toolbar button, .toolbar a, .subhead button, .subhead a, .btn-toolbar button, .btn-toolbar a, joomla-toolbar button, joomla-toolbar a, .joomla-toolbar-button button, .joomla-toolbar-button a');
				if (!candidates.length) {
					candidates = document.querySelectorAll('button, a, [role="button"]');
				}
				for (var i = 0; i < candidates.length; i++) {
					var candidate = candidates[i];
					var haystack = [
						candidate.id || '',
						candidate.className || '',
						candidate.getAttribute('data-task') || '',
						candidate.getAttribute('onclick') || '',
						candidate.getAttribute('href') || '',
						candidate.textContent || '',
						candidate.title || '',
						candidate.getAttribute('aria-label') || ''
					].join(' ').toLowerCase();
					if (haystack.indexOf(taskName.toLowerCase()) !== -1) {
						pushMatch(candidate);
					}
					for (var h = 0; h < textHints.length; h++) {
						if (haystack.indexOf(String(textHints[h]).toLowerCase()) !== -1) {
							pushMatch(candidate);
						}
					}
				}
				return matches;
			}

			function normalizeUnitTestsValue(value) {
				return String(value || '').replace(/\r\n/g, '\n').replace(/\r/g, '\n');
			}

			function getCodeMirrorInstance(field) {
				if (!field || !field.parentNode) {
					return null;
				}
				var sibling = field.nextElementSibling;
				while (sibling) {
					if (sibling.CodeMirror) {
						return sibling.CodeMirror;
					}
					sibling = sibling.nextElementSibling;
				}
				var wrappers = field.parentNode.querySelectorAll('.CodeMirror');
				for (var i = 0; i < wrappers.length; i++) {
					if (field.compareDocumentPosition(wrappers[i]) & Node.DOCUMENT_POSITION_FOLLOWING) {
						return wrappers[i].CodeMirror || null;
					}
				}
				return null;
			}

			function getFieldValue(fieldId) {
				var field = document.getElementById(fieldId);
				if (!field && document.adminForm && document.adminForm.elements && document.adminForm.elements[fieldId]) {
					field = document.adminForm.elements[fieldId];
				}
				if (!field) {
					return '';
				}
				if (typeof RadioNodeList !== 'undefined' && field instanceof RadioNodeList) {
					return field.value;
				}
				if (field.length && typeof field.value !== 'undefined' && !field.tagName) {
					return field.value;
				}
				if (window.Joomla && Joomla.editors && Joomla.editors.instances && Joomla.editors.instances[fieldId] && typeof Joomla.editors.instances[fieldId].getValue === 'function') {
					return Joomla.editors.instances[fieldId].getValue();
				}
				if (typeof field.value !== 'undefined') {
					var codeMirrorInstance = getCodeMirrorInstance(field);
					if (codeMirrorInstance) {
						return codeMirrorInstance.getValue();
					}
					return field.value;
				}
				return '';
			}

			function getCurrentEditState() {
				return {
					title: getFieldValue('title'),
					type: getFieldValue('type'),
					package: getFieldValue('package'),
					name: getFieldValue('name'),
					published: getFieldValue('published'),
					description: getFieldValue('description'),
					code: getFieldValue('code'),
					unit_tests: getFieldValue('unit_tests')
				};
			}

			function isEditDirty() {
				var initialState = <?php echo $safeInitialState; ?> || {};
				var currentState = getCurrentEditState();
				var keys = Object.keys(initialState);
				for (var i = 0; i < keys.length; i++) {
					var key = keys[i];
					if (normalizeUnitTestsValue(currentState[key]) !== normalizeUnitTestsValue(initialState[key])) {
						return true;
					}
				}
				return false;
			}

			function isEditTestBlocked() {
				return isEditDirty();
			}

			function syncEditSaveToolbarButton() {
				var button = getEditSaveToolbarButton();
				if (!button) {
					return;
				}
				var isDirty = isEditDirty();
				button.classList.toggle('disabled', !isDirty);
				button.setAttribute('aria-disabled', isDirty ? 'false' : 'true');
				button.style.pointerEvents = isDirty ? '' : 'none';
				button.style.opacity = isDirty ? '' : '0.5';
				if (button.tagName === 'BUTTON') {
					button.disabled = !isDirty;
				}
				if (!isDirty) {
					button.setAttribute('tabindex', '-1');
					button.title = 'Aucune modification a enregistrer.';
				} else {
					button.removeAttribute('tabindex');
					button.title = '';
				}
			}

			function syncEditTestToolbarButton() {
				var buttons = getEditTestToolbarButtons();
				if (!buttons.length) {
					return;
				}
				var isBlocked = isEditTestBlocked();
				for (var i = 0; i < buttons.length; i++) {
					var button = buttons[i];
					button.classList.toggle('disabled', isBlocked);
					button.setAttribute('aria-disabled', isBlocked ? 'true' : 'false');
					button.style.pointerEvents = isBlocked ? 'none' : '';
					button.style.opacity = isBlocked ? '0.5' : '';
					if (button.tagName === 'BUTTON') {
						button.disabled = isBlocked;
					}
					if (isBlocked) {
						button.setAttribute('tabindex', '-1');
						button.title = 'Enregistrez le script avant de lancer les tests.';
					} else {
						button.removeAttribute('tabindex');
						button.title = '';
					}
				}
			}

			function syncEditNavigationToolbarButton(button, title) {
				if (!button) {
					return;
				}
				var isBlocked = isEditDirty();
				button.classList.toggle('disabled', isBlocked);
				button.setAttribute('aria-disabled', isBlocked ? 'true' : 'false');
				button.style.pointerEvents = isBlocked ? 'none' : '';
				button.style.opacity = isBlocked ? '0.5' : '';
				if (button.tagName === 'BUTTON') {
					button.disabled = isBlocked;
				}
				if (isBlocked) {
					button.setAttribute('tabindex', '-1');
					button.title = title;
				} else {
					button.removeAttribute('tabindex');
					button.title = '';
				}
			}

			function syncEditPrevNextToolbarButtons() {
				syncEditNavigationToolbarButton(getEditPrevToolbarButton(), 'Enregistrez le script avant de changer d\'element.');
				syncEditNavigationToolbarButton(getEditNextToolbarButton(), 'Enregistrez le script avant de changer d\'element.');
			}

			function syncEditUnitTestsButton() {
				var button = document.getElementById('bf-edit-unit-tests-button');
				var field = document.getElementById('unit_tests');
				if (!button || !field) {
					return;
				}

				var persistedValue = <?php echo $safePersistedUnitTests; ?>;
				var currentValue = String(field.value || '');
				var hasTests = currentValue.trim() !== '';
				var isDirty = isEditTestBlocked();
				var enabled = hasTests && <?php echo $row->id ? 'true' : 'false'; ?> && !isDirty;
				button.disabled = !enabled;
				button.classList.toggle('disabled', !enabled);
				button.setAttribute('aria-disabled', enabled ? 'false' : 'true');
				if (!<?php echo $row->id ? 'true' : 'false'; ?>) {
					button.title = 'Enregistrez d\'abord le script pour lancer les tests unitaires.';
				} else if (isDirty) {
					button.title = 'Enregistrez le script avant de lancer les tests unitaires.';
				} else {
					button.title = enabled ? '' : 'Aucun test unitaire renseigne';
				}
			}

			function runUnitTestsFromEdit() {
				var button = document.getElementById('bf-edit-unit-tests-button');
				if (button && button.disabled) {
					return false;
				}

				var codeField = document.getElementById('code');
				var unitTestsField = document.getElementById('unit_tests');
				var resultBox = document.getElementById('bf-edit-script-unit-tests-status');
				var summary = document.getElementById('bf-edit-script-unit-tests-summary');
				var detailsWrap = document.getElementById('bf-edit-script-unit-tests-details-wrap');
				var details = document.getElementById('bf-edit-script-unit-tests-details');
				var functionField = document.getElementById('name');

				if (!codeField || !unitTestsField || !resultBox || !summary || !detailsWrap || !details) {
					return false;
				}

				function parseValue(raw) {
					var value = String(raw || '').trim();
					if (value === '') return '';
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
						} catch (e) {}
					}
					var quoted = (value.charAt(0) === '"' && value.charAt(value.length - 1) === '"') ||
						(value.charAt(0) === "'" && value.charAt(value.length - 1) === "'");
					if (quoted && value.length >= 2) {
						return value.slice(1, -1);
					}
					return value;
				}

				function formatValue(value) {
					if (typeof value === 'undefined') return 'undefined';
					if (typeof value === 'string') return value;
					try {
						return JSON.stringify(value, null, 2);
					} catch (e) {
						return String(value);
					}
				}

				function valuesEqual(actual, expected) {
					if (actual === expected) return true;
					try {
						return JSON.stringify(actual) === JSON.stringify(expected);
					} catch (e) {
						return false;
					}
				}

				function parseUnitTestLine(line, lineNumber) {
					var trimmedLine = String(line || '').trim();
					if (!trimmedLine || trimmedLine.indexOf('//') === 0 || trimmedLine.indexOf('#') === 0) {
						return null;
					}
					var arrowIndex = trimmedLine.indexOf('->');
					if (arrowIndex === -1) {
						throw new Error('Ligne ' + lineNumber + ' invalide: separateur -> manquant.');
					}
					var inputText = trimmedLine.slice(0, arrowIndex).trim();
					var expectedText = trimmedLine.slice(arrowIndex + 2).trim();
					if (inputText === '' || expectedText === '') {
						throw new Error('Ligne ' + lineNumber + ' invalide: entree ou resultat attendu manquant.');
					}
					var inputValue = parseValue(inputText);
					return {
						lineNumber: lineNumber,
						inputText: inputText,
						args: Array.isArray(inputValue) ? inputValue.slice() : [inputValue],
						expectedValue: parseValue(expectedText)
					};
				}

				var functionName = String((functionField && functionField.value) || '').trim();
				if (!functionName) {
					resultBox.style.display = 'block';
					resultBox.className = 'alert alert-danger';
					summary.textContent = 'Veuillez renseigner le nom de la fonction.';
					detailsWrap.style.display = 'none';
					details.textContent = '';
					return false;
				}

				var lines = String(unitTestsField.value || '').split(/\r?\n/);
				var tests = [];
				try {
					for (var i = 0; i < lines.length; i++) {
						var parsed = parseUnitTestLine(lines[i], i + 1);
						if (parsed) tests.push(parsed);
					}
				} catch (e) {
					resultBox.style.display = 'block';
					resultBox.className = 'alert alert-danger';
					summary.textContent = e && e.message ? e.message : String(e);
					detailsWrap.style.display = 'none';
					details.textContent = '';
					return false;
				}

				if (!tests.length) {
					resultBox.style.display = 'block';
					resultBox.className = 'alert alert-warning';
					summary.textContent = 'Aucun test unitaire defini.';
					detailsWrap.style.display = 'none';
					details.textContent = '';
					return false;
				}

				var consoleLines = [];
				var fakeConsole = {
					log: function () { consoleLines.push(Array.prototype.slice.call(arguments).map(formatValue).join(' ')); },
					info: function () { consoleLines.push(Array.prototype.slice.call(arguments).map(formatValue).join(' ')); },
					warn: function () { consoleLines.push(Array.prototype.slice.call(arguments).map(formatValue).join(' ')); },
					error: function () { consoleLines.push(Array.prototype.slice.call(arguments).map(formatValue).join(' ')); }
				};
				var failures = [];
				var passedCount = 0;

				try {
					var runner = new Function(
						'console',
						'"use strict";\n' + String(codeField.value || '') + '\nif (typeof ' + functionName + ' !== "function") { throw new Error("Fonction introuvable: ' + functionName + '"); }\nreturn ' + functionName + ';'
					);
					var fn = runner(fakeConsole);
					for (var t = 0; t < tests.length; t++) {
						var test = tests[t];
						try {
							var actualValue = fn.apply(window, test.args);
							if (valuesEqual(actualValue, test.expectedValue)) {
								passedCount++;
							} else {
								failures.push('Ligne ' + test.lineNumber + ' | entree: ' + test.inputText + ' | attendu: ' + formatValue(test.expectedValue) + ' | obtenu: ' + formatValue(actualValue));
							}
						} catch (testError) {
							failures.push('Ligne ' + test.lineNumber + ' | entree: ' + test.inputText + ' | erreur: ' + (testError && testError.message ? testError.message : String(testError)));
						}
					}
				} catch (e) {
					resultBox.style.display = 'block';
					resultBox.className = 'alert alert-danger';
					summary.textContent = e && e.message ? e.message : String(e);
					detailsWrap.style.display = consoleLines.length ? 'block' : 'none';
					details.textContent = consoleLines.join('\n');
					return false;
				}

				resultBox.style.display = 'block';
				resultBox.className = failures.length ? 'alert alert-warning' : 'alert alert-success';
				summary.textContent = passedCount + '/' + tests.length + ' réussis';
				var detailParts = failures.slice();
				if (consoleLines.length) detailParts.push('Output:\n' + consoleLines.join('\n'));
				if (detailParts.length) {
					detailsWrap.style.display = 'block';
					details.textContent = detailParts.join('\n\n');
				} else {
					detailsWrap.style.display = 'none';
					details.textContent = '';
				}
				return false;
			}

			window.addEventListener('load', function () {
				var form = document.getElementById('adminForm');
				syncEditUnitTestsButton();
				syncEditSaveToolbarButton();
				syncEditTestToolbarButton();
				syncEditPrevNextToolbarButtons();
				if (form) {
					['input', 'change'].forEach(function (eventName) {
						form.addEventListener(eventName, function () {
							syncEditUnitTestsButton();
							syncEditSaveToolbarButton();
							syncEditTestToolbarButton();
							syncEditPrevNextToolbarButtons();
						});
					});
					window.setInterval(function () {
						syncEditUnitTestsButton();
						syncEditSaveToolbarButton();
						syncEditTestToolbarButton();
						syncEditPrevNextToolbarButtons();
					}, 500);
				}
			});
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
						<input type="text" size="70" maxlength="50" id="title" name="title" value="<?php echo $row->title; ?>"
							class="inputbox" />
						<?php
						echo '<span><span title="' . bf_ToolTipText(BFText::_('COM_BREEZINGFORMS_SCRIPTS_TIPTITLE')) . '" class="icon-question-circle hasTooltip" aria-hidden="true"></span></span>';
						?>
					</td>
					<td nowrap>
						<?php echo BFText::_('COM_BREEZINGFORMS_SCRIPTS_TYPE'); ?>:
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
					<td nowrap>
						<?php echo BFText::_('COM_BREEZINGFORMS_SCRIPTS_PUBLISHED'); ?>:
						<?php echo HTMLHelper::_('select.booleanlist', "published", "", $row->published); ?>
					</td>
				</tr>
				<tr>
					<td></td>
					<td nowrap colspan="3">
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
						<textarea wrap="off" name="description" id="description" style="width:100%;" rows="12"
							class="inputbox"><?php echo $row->description; ?></textarea>
					</td>
				</tr>

				<tr>
					<td></td>
					<td nowrap colspan="3">
						<?php echo BFText::_('COM_BREEZINGFORMS_SCRIPTS_CODE'); ?>:
						<br />

						<?php
						$params = array('syntax' => 'javascript');
						$editor = Editor::getInstance('codemirror');
						echo $editor->display('code', $row->code, '100%', 300, 40, 20, false, 'code', null, null, $params);
						?>

					</td>
				</tr>
				<tr>
					<td></td>
					<td nowrap colspan="3">
						Tests unitaires:
						<?php
						echo '<span><span title="' . htmlspecialchars($unitTestsHelp, ENT_QUOTES) . '" class="icon-question-circle hasTooltip" aria-hidden="true"></span></span>';
						?>
						<a href="javascript:void(0);"
							onClick="textAreaResize('unit_tests',<?php echo $ff_config->areasmall; ?>);">[
							<?php echo $ff_config->areasmall; ?>]
						</a>
						<a href="javascript:void(0);"
							onClick="textAreaResize('unit_tests',<?php echo $ff_config->areamedium; ?>);">[
							<?php echo $ff_config->areamedium; ?>]
						</a>
						<a href="javascript:void(0);"
							onClick="textAreaResize('unit_tests',<?php echo $ff_config->arealarge; ?>);">[
							<?php echo $ff_config->arealarge; ?>]
						</a>
						<br />
						<textarea wrap="off" name="unit_tests" id="unit_tests" style="width:100%;" rows="8"
							class="inputbox"><?php echo htmlspecialchars((string) $row->unit_tests, ENT_QUOTES); ?></textarea>
						<div class="mt-2 text-muted">
							Une ligne par test, format simple :
							<code>'12/ 02/2023 ' -> '12/02/2023'</code><br />
							<code>' abc ' -> 'abc'</code><br />
							<code>'' -> ''</code><br />
							Types acceptes : texte entre quotes, `null`, `true`, `false`, nombres, JSON.
						</div>
						<div class="mt-3">
							<button
								type="button"
								id="bf-edit-unit-tests-button"
								class="btn btn-secondary"
								onclick="return runUnitTestsFromEdit();"
								<?php echo $hasPersistedUnitTests ? '' : 'disabled="disabled" aria-disabled="true" title="' . ($row->id ? 'Aucun test unitaire renseigne' : 'Enregistrez d&#039;abord le script pour lancer les tests unitaires.') . '"'; ?>>
								<span class="icon-play" aria-hidden="true"></span>
								Tests unitaires
							</button>
						</div>
						<div id="bf-edit-script-unit-tests-status" class="alert mt-3" style="display:none;">
							<strong>Tests unitaires:</strong>
							<div id="bf-edit-script-unit-tests-summary"></div>
							<div id="bf-edit-script-unit-tests-details-wrap" style="display:none;">
								<div><strong>Detail:</strong></div>
								<pre id="bf-edit-script-unit-tests-details"></pre>
							</div>
						</div>
					</td>
				</tr>
			</table>
			<input type="hidden" name="pkg" value="<?php echo $pkg; ?>" />
			<input type="hidden" name="id" value="<?php echo $row->id; ?>" />
			<input type="hidden" name="option" value="<?php echo $option; ?>" />
			<input type="hidden" name="task" value="" />
			<input type="hidden" name="test_mode" value="" />
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

	static function listitems($option, &$rows, &$pkglist, $pkg, $search, $total, $limit, $limitstart, $pageSizes)
	{
		global $ff_config, $ff_version;
		$sort = BFRequest::getCmd('sort', 'name');
		$dir = strtoupper(BFRequest::getCmd('dir', 'ASC'));
		$dir = $dir === 'DESC' ? 'DESC' : 'ASC';
		$baseQuery = 'index.php?option=' . $option .
			'&act=managescripts' .
			'&pkg=' . urlencode($pkg) .
			'&search=' . urlencode($search) .
			'&limit=' . (int) $limit .
			'&limitstart=' . (int) $limitstart;
		$toggleDir = function ($column) use ($sort, $dir) {
			if ($sort === $column) {
				return $dir === 'ASC' ? 'DESC' : 'ASC';
			}
			return 'ASC';
		};
		$pageCount = $limit > 0 ? (int) ceil($total / $limit) : 1;
		$pageCount = max(1, $pageCount);
		$currentPage = $limit > 0 ? ((int) floor($limitstart / $limit) + 1) : 1;
		$currentPage = min(max($currentPage, 1), $pageCount);
		$startNo = $total > 0 ? $limitstart + 1 : 0;
		$endNo = $total > 0 ? min($limitstart + $limit, $total) : 0;
		$shownPageNumbers = array();
		if ($pageCount <= 4) {
			for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
				$shownPageNumbers[] = $pageNo;
			}
		} else {
			$shownPageNumbers = array(1, 2, $pageCount - 1, $pageCount, max(1, $currentPage - 1), $currentPage, min($pageCount, $currentPage + 1));
			$shownPageNumbers = array_values(array_unique($shownPageNumbers));
			sort($shownPageNumbers);
		}
		$gotoLabel = rtrim(BFText::_('COM_BREEZINGFORMS_GO_TO_PAGE'), '.');
		?>
			<script type="text/javascript">
								<!--
								var bfScriptsPageCount = <?php echo (int) $pageCount; ?>;
								function bfScriptsSyncPackage(form)
								{
									if (!form) {
										return;
									}
									if (form.pkgsel && form.pkg) {
										form.pkg.value = form.pkgsel.value === '' ? '- blank -' : form.pkgsel.value;
									}
								}

								function bfScriptsSubmitList(resetLimitStart)
								{
									var form = document.adminForm;
									if (!form) {
										return false;
									}
									if (resetLimitStart && form.limitstart) {
										form.limitstart.value = 0;
									}
									bfScriptsSyncPackage(form);
									Joomla.submitform('', form);
									return false;
								}

								function submitbutton(pressbutton)
								{
									var form = document.adminForm;
									switch (pressbutton) {
										case 'copy':
										case 'publish':
										case 'unpublish':
										case 'remove':
											if (form.boxchecked.value==0) {
												alert("<?php echo BFText::_('COM_BREEZINGFORMS_SCRIPTS_SELSCRIPTSFIRST'); ?>");
												return;
											} // if
											break;
										default:
											break;
									} // switch
									if (pressbutton == 'remove') {
										if (!confirm("<?php echo BFText::_('COM_BREEZINGFORMS_SCRIPTS_ASKDELETE'); ?>")) {
											return;
										}
									}
									bfScriptsSyncPackage(form);
									Joomla.submitform(pressbutton, form);
								} // submitbutton
								Joomla.submitbutton = submitbutton;

								function bfScriptsGoToPage(pageNo)
								{
									var form = document.adminForm;
									var limit = parseInt(form.limit.value, 10);
									var page = parseInt(pageNo, 10);
									if (isNaN(limit) || limit <= 0) {
										limit = 10;
									}
									if (isNaN(page) || page < 1) {
										page = 1;
									}
									if (page > bfScriptsPageCount) {
										page = bfScriptsPageCount;
									}
									form.limitstart.value = (page - 1) * limit;
									return bfScriptsSubmitList(false);
								}

								function bfScriptsChangePageSize(pageSize)
								{
									var form = document.adminForm;
									var size = parseInt(pageSize, 10);
									if (isNaN(size) || size <= 0) {
										return false;
									}
									form.limit.value = size;
									form.limitstart.value = 0;
									return bfScriptsSubmitList(false);
								}

								function bfScriptsGotoPageFromInput()
								{
									var input = document.getElementById('bfScriptsGotoPage');
									if (!input) {
										return false;
									}
									return bfScriptsGoToPage(input.value);
								}
			<?php
			ToolBarHelper::custom('new', 'new.png', 'new_f2.png', BFText::_('COM_BREEZINGFORMS_TOOLBAR_NEW'), false);
			ToolBarHelper::custom('copy', 'copy.png', 'copy_f2.png', BFText::_('COM_BREEZINGFORMS_TOOLBAR_COPY'), false);
			ToolBarHelper::custom('publish', 'publish.png', 'publish_f2.png', BFText::_('COM_BREEZINGFORMS_TOOLBAR_PUBLISH'), false);
			ToolBarHelper::custom('unpublish', 'unpublish.png', 'unpublish_f2.png', BFText::_('COM_BREEZINGFORMS_TOOLBAR_UNPUBLISH'), false);
			ToolBarHelper::custom('remove', 'delete.png', 'delete_f2.png', BFText::_('COM_BREEZINGFORMS_TOOLBAR_DELETE'), false);
			?>

			function listItemTask(id, task) {
				var f = document.adminForm;
				var cb = document.getElementById(id);
				if (cb && f) {
					var checkboxes = f.querySelectorAll('input[type="checkbox"][id^="cb"]');
					for (var i = 0; i < checkboxes.length; i++) {
						checkboxes[i].checked = false;
					}
					cb.checked = true;
					f.boxchecked.value = 1;
					Joomla.submitbutton(task);
				}
				return false;
			} // listItemTask
			window.listItemTask = listItemTask;

			//-->
		</script>
		<form action="index.php" method="post" name="adminForm" id="adminForm">

			<label class="bfPackageSelector">

				<?php echo BFText::_('COM_BREEZINGFORMS_SCRIPTS_PACKAGE'); ?>
					<select id="pkgsel" name="pkgsel" class="inputbox" size="1" onchange="return bfScriptsSubmitList(true);">
					<?php
					if (count($pkglist))
						foreach ($pkglist as $pkgEntry) {
							$selected = '';
							if ($pkgEntry[0])
								$selected = ' selected';
							$label = $pkgEntry[1] === '' ? BFText::_('COM_BREEZINGFORMS_ALL_FILTER') : $pkgEntry[1];
							echo '<option value="' . $pkgEntry[1] . '"' . $selected . '>' . $label . '&nbsp;</option>';
						} // foreach
					?>
				</select>

			</label>
				<label class="bfPackageSelector bfFilterTools">
					Filtre
					<input type="text" name="search" id="search" class="inputbox"
						value="<?php echo htmlspecialchars($search, ENT_QUOTES); ?>" onchange="return bfScriptsSubmitList(true);"
						onkeydown="if(event.key==='Enter'){event.preventDefault();bfScriptsSubmitList(true);}" />
				</label>
			<div style="clear: both;"></div>

				<div class="jtable-main-container bf-manage-list-pagination-container" id="bfScriptsPaginationContainer">
				<table cellpadding="4" cellspacing="0" border="0" width="100%" class="adminlist table table-striped">
				<tr>
					<th style="width: 25px;" nowrap align="right">
						<a href="<?php echo $baseQuery . '&sort=id&dir=' . $toggleDir('id'); ?>">ID</a>
					</th>
					<th style="width: 25px;" nowrap align="center"><input type="checkbox" name="toggle" value=""
							onclick="Joomla.checkAll(this);" /></th>
					<th align="left">
						<a href="<?php echo $baseQuery . '&sort=package&dir=' . $toggleDir('package'); ?>">
							<?php echo BFText::_('COM_BREEZINGFORMS_SCRIPTS_PACKAGE'); ?>
						</a>
					</th>
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
						<td valign="top" align="left">
							<?php echo htmlspecialchars((string) $row->package, ENT_QUOTES); ?>
						</td>
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
					</tr>
					<?php
					$k = 1 - $k;
				} // for
				?>
				</table>
				<div class="jtable-bottom-panel">
					<div class="jtable-left-area">
						<span class="jtable-page-list">
							<?php
							$firstDisabled = $currentPage <= 1;
							$lastDisabled = $currentPage >= $pageCount;
							?>
							<span class="jtable-page-number-first<?php echo $firstDisabled ? ' jtable-page-number-disabled' : ''; ?>"<?php echo $firstDisabled ? '' : ' onclick="return bfScriptsGoToPage(1);"'; ?>>&lt;&lt;</span>
							<span class="jtable-page-number-previous<?php echo $firstDisabled ? ' jtable-page-number-disabled' : ''; ?>"<?php echo $firstDisabled ? '' : ' onclick="return bfScriptsGoToPage(' . ($currentPage - 1) . ');"'; ?>>&lt;</span>
							<?php
							$previousPageNo = 0;
							foreach ($shownPageNumbers as $pageNo) {
								if (($pageNo - $previousPageNo) > 1) {
									echo '<span class="jtable-page-number-space">...</span>';
								}
								$isActive = $pageNo === $currentPage;
								echo '<span class="jtable-page-number' . ($isActive ? ' jtable-page-number-active jtable-page-number-disabled' : '') . '"' .
									($isActive ? '' : ' onclick="return bfScriptsGoToPage(' . (int) $pageNo . ');"') . '>' . (int) $pageNo . '</span>';
								$previousPageNo = $pageNo;
							}
							?>
							<span class="jtable-page-number-next<?php echo $lastDisabled ? ' jtable-page-number-disabled' : ''; ?>"<?php echo $lastDisabled ? '' : ' onclick="return bfScriptsGoToPage(' . ($currentPage + 1) . ');"'; ?>>&gt;</span>
							<span class="jtable-page-number-last<?php echo $lastDisabled ? ' jtable-page-number-disabled' : ''; ?>"<?php echo $lastDisabled ? '' : ' onclick="return bfScriptsGoToPage(' . $pageCount . ');"'; ?>>&gt;&gt;</span>
						</span>
						<span class="jtable-page-size-change">
							<span>Row count: </span>
							<select name="limit" onchange="return bfScriptsChangePageSize(this.value);">
								<?php foreach ($pageSizes as $pageSize) { ?>
									<option value="<?php echo (int) $pageSize; ?>"<?php echo (int) $pageSize === (int) $limit ? ' selected="selected"' : ''; ?>><?php echo (int) $pageSize; ?></option>
								<?php } ?>
							</select>
						</span>
						<span class="jtable-goto-page">
							<span><?php echo htmlspecialchars($gotoLabel, ENT_QUOTES); ?>: </span>
							<input type="text" id="bfScriptsGotoPage" maxlength="10" value="<?php echo (int) $currentPage; ?>"
								onkeydown="if(event.key==='Enter'){event.preventDefault();bfScriptsGotoPageFromInput();}" />
						</span>
					</div>
					<div class="jtable-right-area">
						<span class="jtable-page-info"><?php echo $total > 0 ? ('Showing ' . (int) $startNo . '-' . (int) $endNo . ' of ' . (int) $total) : ''; ?></span>
					</div>
				</div>
				</div>
				<input type="hidden" name="boxchecked" value="0" />
				<input type="hidden" name="option" value="<?php echo $option; ?>" />
				<input type="hidden" name="act" value="managescripts" />
				<input type="hidden" name="task" value="" />
				<input type="hidden" name="limitstart" value="<?php echo (int) $limitstart; ?>" />
				<input type="hidden" name="pkg" value="<?php echo htmlspecialchars($pkg, ENT_QUOTES); ?>" />
			</form>
		<?php
	} // listitems

	static function test($option, $pkg, &$row, $functionName, $paramNames, $paramDefaults, $autoRun = false, $testMode = '')
	{
		ToolBarHelper::custom('edit', 'cancel.png', 'cancel_f2.png', 'Retour', false);
		ToolBarHelper::custom('prev', 'arrow-left', '', BFText::_('COM_BREEZINGFORMS_PROCESS_PAGEPREV'), false);
		ToolBarHelper::custom('next', 'arrow-right', '', BFText::_('COM_BREEZINGFORMS_PROCESS_PAGENEXT'), false);
		$hasUnitTests = trim((string) $row->unit_tests) !== '';
		$safeCode = json_encode((string) $row->code);
		$safeFunction = json_encode((string) $functionName);
		$safeUnitTests = json_encode((string) $row->unit_tests);
		$safeRequestedTestMode = json_encode((string) $testMode);
		$safeAutoRun = $autoRun ? 'true' : 'false';
		$safeHasUnitTests = $hasUnitTests ? 'true' : 'false';
		?>
		<script type="text/javascript">
			(function () {
				var testCode = <?php echo $safeCode; ?>;
				var defaultFunctionName = <?php echo $safeFunction; ?>;
				var unitTestsDefinition = <?php echo $safeUnitTests; ?>;
				var requestedTestMode = <?php echo $safeRequestedTestMode; ?>;

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

				function valuesEqual(actual, expected) {
					if (actual === expected) {
						return true;
					}
					try {
						return JSON.stringify(actual) === JSON.stringify(expected);
					} catch (e) {
						return false;
					}
				}

				function parseUnitTestLine(line, lineNumber) {
					var trimmedLine = String(line || '').trim();
					if (!trimmedLine || trimmedLine.indexOf('//') === 0 || trimmedLine.indexOf('#') === 0) {
						return null;
					}

					var arrowIndex = trimmedLine.indexOf('->');
					if (arrowIndex === -1) {
						throw new Error('Ligne ' + lineNumber + ' invalide: separateur -> manquant.');
					}

					var inputText = trimmedLine.slice(0, arrowIndex).trim();
					var expectedText = trimmedLine.slice(arrowIndex + 2).trim();
					if (inputText === '' || expectedText === '') {
						throw new Error('Ligne ' + lineNumber + ' invalide: entree ou resultat attendu manquant.');
					}

					var inputValue = parseValue(inputText);
					var expectedValue = parseValue(expectedText);
					var args = Array.isArray(inputValue) ? inputValue.slice() : [inputValue];

					return {
						lineNumber: lineNumber,
						inputText: inputText,
						expectedText: expectedText,
						args: args,
						expectedValue: expectedValue
					};
				}

				function hasUnitTests() {
					var lines = String(unitTestsDefinition || '').split(/\r?\n/);
					for (var i = 0; i < lines.length; i++) {
						var trimmedLine = String(lines[i] || '').trim();
						if (trimmedLine && trimmedLine.indexOf('//') !== 0 && trimmedLine.indexOf('#') !== 0) {
							return true;
						}
					}
					return false;
				}

					function syncUnitTestButtons() {
						var enabled = hasUnitTests();
					var buttons = document.querySelectorAll('.bf-unit-tests-button');
					for (var i = 0; i < buttons.length; i++) {
						buttons[i].disabled = !enabled;
						buttons[i].classList.toggle('disabled', !enabled);
						buttons[i].setAttribute('aria-disabled', enabled ? 'false' : 'true');
						buttons[i].title = enabled ? '' : 'Aucun test unitaire renseigne';
						}
					}

					function showAutoOpenUnitWarning(message) {
						var banner = document.getElementById('bf-script-auto-unit-warning');
						var text = document.getElementById('bf-script-auto-unit-warning-text');
						if (!banner || !text) {
							return;
						}
						text.textContent = message;
						banner.style.display = 'block';
						window.setTimeout(function () {
							banner.style.display = 'none';
						}, 5000);
					}

					function formatAutoOpenUnitWarningMessage(failureCount) {
						var count = parseInt(failureCount, 10) || 0;
						if (count <= 0) {
							return 'Des tests unitaires ont échoué à l\'ouverture.';
						}
						return count + (count > 1 ? ' tests unitaires en échec.' : ' test unitaire en échec.');
					}

				window.submitbutton = function (pressbutton) {
					Joomla.submitform(pressbutton, document.getElementById('adminForm'));
				};

				window.bfRunScriptTest = function () {
					var fnField = document.getElementById('bf-script-function');
					var errorBox = document.getElementById('bf-script-test-error');
					var errorMessage = document.getElementById('bf-script-test-error-message');
					var errorOutputWrap = document.getElementById('bf-script-test-error-output-wrap');
					var errorOutput = document.getElementById('bf-script-test-error-output');
					var errorResultWrap = document.getElementById('bf-script-test-error-result-wrap');
					var errorResult = document.getElementById('bf-script-test-error-result');
					var errorParams = document.getElementById('bf-script-test-error-params');

					var outputWrap = document.getElementById('bf-script-test-output-wrap');
					var output = document.getElementById('bf-script-test-output');

					var statusBox = document.getElementById('bf-script-test-status');
					var statusResult = document.getElementById('bf-script-test-result');
					var statusWarning = document.getElementById('bf-script-test-status-warning');
					var statusSuccess = document.getElementById('bf-script-test-status-success');
					var statusInvalid = document.getElementById('bf-script-test-status-invalid');
					var statusParamsWrap = document.getElementById('bf-script-test-status-params-wrap');
					var statusParams = document.getElementById('bf-script-test-status-params');

					if (!fnField || !errorBox || !statusBox) {
						return false;
					}

					function resetUi() {
						errorBox.style.display = 'none';
						errorMessage.textContent = '';
						errorOutputWrap.style.display = 'none';
						errorOutput.textContent = '';
						errorResultWrap.style.display = 'none';
						errorResult.textContent = '';
						errorParams.textContent = '';

						outputWrap.style.display = 'none';
						output.textContent = '';

						statusBox.style.display = 'none';
						statusBox.className = 'alert';
						statusResult.textContent = '';
						statusWarning.style.display = 'none';
						statusSuccess.style.display = 'none';
						statusInvalid.style.display = 'none';
						statusParamsWrap.style.display = 'none';
						statusParams.textContent = '';
					}

					var functionName = String(fnField.value || '').trim();
					if (!functionName) {
						resetUi();
						errorBox.style.display = 'block';
						errorMessage.textContent = 'Veuillez renseigner le nom de la fonction a tester.';
						return false;
					}
					if (!/^[A-Za-z_$][A-Za-z0-9_$]*$/.test(functionName)) {
						resetUi();
						errorBox.style.display = 'block';
						errorMessage.textContent = 'Nom de fonction invalide.';
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

					resetUi();

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
						var paramsMap = {};
						for (var p = 0; p < labels.length; p++) {
							paramsMap[labels[p]] = args[p];
						}
						var paramsText = formatValue(paramsMap);
						var outputText = consoleLines.length ? consoleLines.join('\n') : '';

						if (outputText) {
							outputWrap.style.display = 'block';
							output.textContent = outputText;
						}

						var isEmptyResult = executed === '';
						var isSuccess = executed !== false && executed !== null && typeof executed !== 'undefined' && !isEmptyResult;
						statusBox.style.display = 'block';
						statusBox.className = 'alert ' + (isEmptyResult ? 'alert-warning' : (isSuccess ? 'alert-success' : 'alert-danger'));
						statusResult.textContent = formatValue(executed);
						statusWarning.style.display = isEmptyResult ? 'block' : 'none';
						statusSuccess.style.display = isSuccess ? 'block' : 'none';
						statusInvalid.style.display = (!isSuccess && !isEmptyResult) ? 'block' : 'none';
						if (!isSuccess && !isEmptyResult) {
							statusParamsWrap.style.display = 'block';
							statusParams.textContent = paramsText;
						}
					} catch (e) {
						var paramsMap = {};
						for (var p = 0; p < labels.length; p++) {
							paramsMap[labels[p]] = args[p];
						}
						var paramsText = formatValue(paramsMap);
						var outputText = consoleLines.length ? consoleLines.join('\n') : '';

						errorBox.style.display = 'block';
						errorMessage.textContent = e && e.message ? e.message : String(e);
						if (outputText) {
							errorOutputWrap.style.display = 'block';
							errorOutput.textContent = outputText;
						}
						errorParams.textContent = paramsText;
					}

					return false;
				};

					window.bfRunScriptUnitTests = function (fromAutoOpen) {
					if (!hasUnitTests()) {
						syncUnitTestButtons();
						return false;
					}

					var fnField = document.getElementById('bf-script-function');
					var unitTestsBox = document.getElementById('bf-script-unit-tests-status');
					var unitTestsSummary = document.getElementById('bf-script-unit-tests-summary');
					var unitTestsDetailsWrap = document.getElementById('bf-script-unit-tests-details-wrap');
					var unitTestsDetails = document.getElementById('bf-script-unit-tests-details');

					if (!fnField || !unitTestsBox || !unitTestsSummary || !unitTestsDetailsWrap || !unitTestsDetails) {
						return false;
					}

					var functionName = String(fnField.value || '').trim();
						if (!functionName) {
						unitTestsBox.style.display = 'block';
						unitTestsBox.className = 'alert alert-danger';
						unitTestsSummary.textContent = 'Veuillez renseigner le nom de la fonction a tester.';
						unitTestsDetailsWrap.style.display = 'none';
						unitTestsDetails.textContent = '';
						return false;
					}
						if (!/^[A-Za-z_$][A-Za-z0-9_$]*$/.test(functionName)) {
						unitTestsBox.style.display = 'block';
						unitTestsBox.className = 'alert alert-danger';
						unitTestsSummary.textContent = 'Nom de fonction invalide.';
						unitTestsDetailsWrap.style.display = 'none';
						unitTestsDetails.textContent = '';
						return false;
					}

					var lines = String(unitTestsDefinition || '').split(/\r?\n/);
					var tests = [];
					try {
						for (var i = 0; i < lines.length; i++) {
							var parsed = parseUnitTestLine(lines[i], i + 1);
							if (parsed) {
								tests.push(parsed);
							}
						}
					} catch (e) {
						unitTestsBox.style.display = 'block';
						unitTestsBox.className = 'alert alert-danger';
						unitTestsSummary.textContent = e && e.message ? e.message : String(e);
						unitTestsDetailsWrap.style.display = 'none';
						unitTestsDetails.textContent = '';
						return false;
					}

					if (!tests.length) {
						unitTestsBox.style.display = 'block';
						unitTestsBox.className = 'alert alert-warning';
						unitTestsSummary.textContent = 'Aucun test unitaire defini.';
						unitTestsDetailsWrap.style.display = 'none';
						unitTestsDetails.textContent = '';
						return false;
					}

					var consoleLines = [];
					var originalConsole = window.console;
					var fakeConsole = {
						log: function () {
							consoleLines.push(Array.prototype.slice.call(arguments).map(formatValue).join(' '));
						},
						info: function () {
							consoleLines.push(Array.prototype.slice.call(arguments).map(formatValue).join(' '));
						},
						warn: function () {
							consoleLines.push(Array.prototype.slice.call(arguments).map(formatValue).join(' '));
						},
						error: function () {
							consoleLines.push(Array.prototype.slice.call(arguments).map(formatValue).join(' '));
						}
					};

					var passedCount = 0;
					var failures = [];

					try {
						var runner = new Function(
							'console',
							'"use strict";\n' + testCode + '\nif (typeof ' + functionName + ' !== "function") { throw new Error("Fonction introuvable: ' + functionName + '"); }\nreturn ' + functionName + ';'
						);
						var fn = runner(fakeConsole);

						for (var t = 0; t < tests.length; t++) {
							var test = tests[t];
							try {
								var actualValue = fn.apply(window, test.args);
								if (valuesEqual(actualValue, test.expectedValue)) {
									passedCount++;
								} else {
									failures.push(
										'Ligne ' + test.lineNumber +
										' | entree: ' + test.inputText +
										' | attendu: ' + formatValue(test.expectedValue) +
										' | obtenu: ' + formatValue(actualValue)
									);
								}
							} catch (testError) {
								failures.push(
									'Ligne ' + test.lineNumber +
									' | entree: ' + test.inputText +
									' | erreur: ' + (testError && testError.message ? testError.message : String(testError))
								);
							}
						}
						} catch (e) {
							unitTestsBox.style.display = 'block';
							unitTestsBox.className = 'alert alert-danger';
							unitTestsSummary.textContent = e && e.message ? e.message : String(e);
							unitTestsDetailsWrap.style.display = consoleLines.length ? 'block' : 'none';
							unitTestsDetails.textContent = consoleLines.join('\n');
							if (fromAutoOpen) {
								showAutoOpenUnitWarning('Des tests unitaires ont échoué à l\'ouverture.');
							}
							return false;
						} finally {
						window.console = originalConsole;
					}

						unitTestsBox.style.display = 'block';
						unitTestsBox.className = failures.length ? 'alert alert-warning' : 'alert alert-success';
						unitTestsSummary.textContent = passedCount + '/' + tests.length + ' réussis';
						if (fromAutoOpen && failures.length) {
							showAutoOpenUnitWarning(formatAutoOpenUnitWarningMessage(failures.length));
						}

					var details = failures.slice();
					if (consoleLines.length) {
						details.push('Output:\n' + consoleLines.join('\n'));
					}

					if (details.length) {
						unitTestsDetailsWrap.style.display = 'block';
						unitTestsDetails.textContent = details.join('\n\n');
					} else {
						unitTestsDetailsWrap.style.display = 'none';
						unitTestsDetails.textContent = '';
					}

					return false;
				};

					window.bfRunAllScriptTests = function () {
						window.bfRunScriptTest();
						if (hasUnitTests()) {
							window.bfRunScriptUnitTests();
					}
					return false;
				};

				window.addEventListener('load', function () {
					var field = document.getElementById('bf-script-function');
					if (field && !field.value) {
						field.value = defaultFunctionName || '';
					}
						syncUnitTestButtons();
						if (requestedTestMode === 'unit' && hasUnitTests()) {
							window.bfRunScriptUnitTests(true);
						} else if (<?php echo $safeAutoRun; ?>) {
							window.bfRunAllScriptTests();
						} else if (<?php echo $safeHasUnitTests; ?>) {
							window.bfRunScriptUnitTests(true);
						}
					});
				if (window.Joomla) {
					window.Joomla.submitbutton = window.submitbutton;
				}
			})();
		</script>
		<form action="index.php" method="post" name="adminForm" id="adminForm" class="adminForm">
			<div id="bf-script-auto-unit-warning" class="alert alert-warning" style="display:none;">
				<span class="icon-warning text-warning" aria-hidden="true"></span>
				<span id="bf-script-auto-unit-warning-text"></span>
			</div>
			<div class="d-flex justify-content-between align-items-center mb-3">
				<h2 class="m-0">Test Script</h2>
				<button type="button" class="btn btn-primary" onclick="return bfRunAllScriptTests();">
					<span class="icon-play" aria-hidden="true"></span>
					Lancer
				</button>
			</div>
			<h3><?php echo htmlspecialchars((string) $row->title, ENT_QUOTES); ?></h3>
			<div class="card mb-3 bg-light">
				<div class="card-body">
					<div class="row">
						<div class="col-sm-6 col-md-3">
							<strong>Script ID :</strong> <?php echo (int) $row->id; ?>
						</div>
						<div class="col-sm-6 col-md-3">
							<strong>Package :</strong> <?php echo htmlspecialchars((string) $row->package, ENT_QUOTES); ?>
						</div>
						<div class="col-sm-6 col-md-3">
							<strong>Name :</strong> <?php echo htmlspecialchars((string) $row->name, ENT_QUOTES); ?>
						</div>
						<div class="col-sm-6 col-md-3">
							<strong>Type :</strong> <?php echo htmlspecialchars(self::typeName((string) $row->type), ENT_QUOTES); ?>
						</div>
					</div>
				</div>
			</div>
			<div class="card mb-3 bg-light">
				<div class="card-body">
					<div class="row">
						<div class="col-sm-6 col-md-3">
							<strong>Created :</strong> <?php echo $row->created ? HTMLHelper::date($row->created, 'Y-m-d H:i', true) : '-'; ?>
						</div>
						<div class="col-sm-6 col-md-3">
							<strong>Created by :</strong> <?php echo htmlspecialchars((string) $row->created_by, ENT_QUOTES); ?>
						</div>
						<div class="col-sm-6 col-md-3">
							<strong>Modified :</strong> <?php echo $row->modified ? HTMLHelper::date($row->modified, 'Y-m-d H:i', true) : '-'; ?>
						</div>
						<div class="col-sm-6 col-md-3">
							<strong>Modified by :</strong> <?php echo htmlspecialchars((string) $row->modified_by, ENT_QUOTES); ?>
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
			<?php if (trim((string) $row->unit_tests) !== '') { ?>
				<div class="accordion mt-3" id="bfScriptUnitTestsAccordion">
					<div class="accordion-item bg-light">
						<h2 class="accordion-header" id="bfScriptUnitTestsHeading">
							<button class="accordion-button collapsed bg-light" type="button" data-bs-toggle="collapse"
								data-bs-target="#bfScriptUnitTestsCollapse" aria-expanded="false" aria-controls="bfScriptUnitTestsCollapse">
								Tests unitaires
							</button>
						</h2>
						<div id="bfScriptUnitTestsCollapse" class="accordion-collapse collapse" aria-labelledby="bfScriptUnitTestsHeading"
							data-bs-parent="#bfScriptUnitTestsAccordion">
							<div class="accordion-body bg-light">
								<pre><?php echo htmlspecialchars((string) $row->unit_tests, ENT_QUOTES); ?></pre>
							</div>
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
							<th></th>
						</tr>
						<?php if (!count($paramNames)) { ?>
							<tr>
								<td colspan="3">Aucun parametre detecte.</td>
							</tr>
						<?php } else { ?>
							<?php $lastParamIndex = count($paramNames) - 1; ?>
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
									<td>
										<?php if ($i === $lastParamIndex) { ?>
											<button type="button" class="btn btn-primary" onclick="return bfRunAllScriptTests();">
												<span class="icon-play" aria-hidden="true"></span>
												Lancer
											</button>
										<?php } ?>
									</td>
								</tr>
							<?php } ?>
						<?php } ?>
					</table>
				</div>
			</div>
			<div id="bf-script-test-error" class="alert alert-danger bf-piece-test-alert" style="display:none;">
				<span class="icon-times text-danger" aria-hidden="true"></span>
				Invalide: <span id="bf-script-test-error-message"></span>
				<div id="bf-script-test-error-output-wrap" style="display:none;">
					<div><strong>Output:</strong></div>
					<pre id="bf-script-test-error-output"></pre>
				</div>
				<div id="bf-script-test-error-result-wrap" style="display:none;">
					<div><strong>Result:</strong></div>
					<pre id="bf-script-test-error-result"></pre>
				</div>
				<div><strong>Parameters:</strong></div>
				<pre id="bf-script-test-error-params"></pre>
			</div>
			<div id="bf-script-test-output-wrap" style="display:none;">
				<p><strong>Output:</strong></p>
				<pre id="bf-script-test-output"></pre>
			</div>
			<div id="bf-script-test-status" class="alert" style="display:none;">
				<strong>Result:</strong>
				<pre id="bf-script-test-result"></pre>
				<div id="bf-script-test-status-warning" style="display:none;">
					<span class="icon-warning text-warning" aria-hidden="true"></span>
					Warning: resultat vide
				</div>
				<div id="bf-script-test-status-success" style="display:none;">
					<span class="icon-check text-success" aria-hidden="true"></span>
					Execut&eacute;
				</div>
				<div id="bf-script-test-status-invalid" style="display:none;">
					<span class="icon-times text-danger" aria-hidden="true"></span>
					Invalide: resultat faux
				</div>
				<div id="bf-script-test-status-params-wrap" style="display:none;">
					<div><strong>Parameters:</strong></div>
					<pre id="bf-script-test-status-params"></pre>
				</div>
			</div>
			<div id="bf-script-unit-tests-status" class="alert" style="display:none;">
				<strong>Tests unitaires:</strong>
				<div id="bf-script-unit-tests-summary"></div>
				<div id="bf-script-unit-tests-details-wrap" style="display:none;">
					<div><strong>Detail:</strong></div>
					<pre id="bf-script-unit-tests-details"></pre>
				</div>
			</div>
			<input type="hidden" name="option" value="<?php echo $option; ?>" />
			<input type="hidden" name="task" value="test" />
			<input type="hidden" name="act" value="managescripts" />
			<input type="hidden" name="pkg" value="<?php echo htmlspecialchars((string) $pkg, ENT_QUOTES); ?>" />
			<input type="hidden" name="ids[]" value="<?php echo (int) $row->id; ?>" />
			<input type="hidden" name="test_context" value="1" />
			<input type="hidden" name="test_mode" value="<?php echo htmlspecialchars((string) $testMode, ENT_QUOTES); ?>" />
		</form>
		<?php
	} // test

} // class HTML_facileFormsScript
?>
