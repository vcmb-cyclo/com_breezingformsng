var __bfOpts = Joomla.getOptions('com_breezingformsng.scripts-edit') || {};

function checkIdentifier(value, name)
						{
							var invalidChars = /\W/;
							var error = '';
							if (value == '')
								error += Joomla.Text._('COM_BREEZINGFORMSNG_SCRIPTS_ENTERNAME') + "\n";
							else
			if (invalidChars.test(value))
				error += Joomla.Text._('COM_BREEZINGFORMSNG_SCRIPTS_ENTERIDENT') + "\n";
			return error;
						} // checkIdentifier

			function submitbutton(pressbutton) {
				var form = document.adminForm;
				var error = '';
				var action = pressbutton.indexOf('.') === -1 ? pressbutton : pressbutton.split('.').pop();
				if (action == 'test' && isEditTestBlocked()) {
					alert(Joomla.Text._('COM_BREEZINGFORMSNG_TEST_SAVE_SCRIPT_BEFORE_CONTINUE'));
					return;
				}
				if (action == 'apply' || action == 'save') {
					error += checkIdentifier(getFieldValue('name'), 'name');
					if (getFieldValue('title') == '') error += Joomla.Text._('COM_BREEZINGFORMSNG_SCRIPTS_ENTTITLE') + "\n";
				} // if
				if (error != '') {
					alert(error);
					return;
				}
				Joomla.submitform('script.' + action, form);
			} // submitbutton
			Joomla.submitbutton = submitbutton;
			window.submitbutton = submitbutton;

			onload = function () {
				var titleField = getFormField('title');
				if (titleField) titleField.focus();
			} // onload

			function findToolbarButtons(tasks) {
				var selector = tasks.map(function (task) {
					return 'joomla-toolbar-button[task="script.' + task + '"] button';
				}).join(', ');
				return Array.prototype.slice.call(document.querySelectorAll(selector));
			}

			function getEditTestToolbarButtons() {
				return findToolbarButtons(['test']);
			}

			function getEditSaveToolbarButtons() {
				return findToolbarButtons(['apply', 'save']);
			}

			function getEditPaginationLinks() {
				return Array.prototype.slice.call(document.querySelectorAll('.bf-item-pagination a.page-link:not([aria-current])'));
			}

			function normalizeUnitTestsValue(value) {
				return String(value || '').replace(/\r\n/g, '\n').replace(/\r/g, '\n');
			}

			function getFormField(name) {
				return document.getElementById('jform_' + name);
			}

			function getFieldValue(name) {
				var fieldId = 'jform_' + name;
				var named = document.adminForm && document.adminForm.elements ? document.adminForm.elements['jform[' + name + ']'] : null;
				if (window.Joomla && Joomla.editors && Joomla.editors.instances && Joomla.editors.instances[fieldId] && typeof Joomla.editors.instances[fieldId].getValue === 'function') {
					return Joomla.editors.instances[fieldId].getValue();
				}
				if (typeof RadioNodeList !== 'undefined' && named instanceof RadioNodeList) {
					return named.value;
				}
				var field = getFormField(name) || named;
				if (!field || typeof field.value === 'undefined') {
					return '';
				}
				return field.value;
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
				var initialState = __bfOpts.initialState || {};
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
				var isDirty = isEditDirty();
				getEditSaveToolbarButtons().forEach(function (button) {
					button.classList.toggle('disabled', !isDirty);
					button.setAttribute('aria-disabled', isDirty ? 'false' : 'true');
					button.style.pointerEvents = isDirty ? '' : 'none';
					button.style.opacity = isDirty ? '' : '0.5';
					button.disabled = !isDirty;
					if (!isDirty) {
						button.setAttribute('tabindex', '-1');
						button.title = Joomla.Text._('COM_BREEZINGFORMSNG_TEST_NO_CHANGES');
					} else {
						button.removeAttribute('tabindex');
						button.title = '';
					}
				});
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
						button.title = Joomla.Text._('COM_BREEZINGFORMSNG_TEST_SAVE_SCRIPT_BEFORE_TESTS');
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
				getEditPaginationLinks().forEach(function (link) {
					syncEditNavigationToolbarButton(link, Joomla.Text._('COM_BREEZINGFORMSNG_TEST_SAVE_SCRIPT_BEFORE_NAVIGATION'));
				});
			}

			function syncEditUnitTestsButton() {
				var button = document.getElementById('bf-edit-unit-tests-button');
				var field = getFormField('unit_tests');
				if (!button || !field) {
					return;
				}

				var persistedValue = __bfOpts.persistedUnitTests;
				var currentValue = String(field.value || '');
				var hasTests = currentValue.trim() !== '';
				var isDirty = isEditTestBlocked();
				var enabled = hasTests && __bfOpts.hasSavedRecord && !isDirty;
				button.disabled = !enabled;
				button.classList.toggle('disabled', !enabled);
				button.setAttribute('aria-disabled', enabled ? 'false' : 'true');
				if (!__bfOpts.hasSavedRecord) {
					button.title = Joomla.Text._('COM_BREEZINGFORMSNG_TEST_SAVE_FIRST_SCRIPT');
				} else if (isDirty) {
					button.title = Joomla.Text._('COM_BREEZINGFORMSNG_TEST_SAVE_SCRIPT_BEFORE_UNIT_TESTS');
				} else {
					button.title = enabled ? '' : Joomla.Text._('COM_BREEZINGFORMSNG_TEST_UNIT_TESTS_NONE');
				}
			}

			function runUnitTestsFromEdit() {
				var button = document.getElementById('bf-edit-unit-tests-button');
				if (button && button.disabled) {
					return false;
				}

				var codeField = getFormField('code');
				var unitTestsField = getFormField('unit_tests');
				var resultBox = document.getElementById('bf-edit-script-unit-tests-status');
				var summary = document.getElementById('bf-edit-script-unit-tests-summary');
				var detailsWrap = document.getElementById('bf-edit-script-unit-tests-details-wrap');
				var details = document.getElementById('bf-edit-script-unit-tests-details');
				var functionField = getFormField('name');

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
					summary.textContent = Joomla.Text._('COM_BREEZINGFORMSNG_TEST_ENTER_FUNCTION_NAME');
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
					summary.textContent = Joomla.Text._('COM_BREEZINGFORMSNG_TEST_NO_UNIT_TEST_DEFINED');
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
						'"use strict";\n' + String(getFieldValue('code') || '') + '\nif (typeof ' + functionName + ' !== "function") { throw new Error("Fonction introuvable: ' + functionName + '"); }\nreturn ' + functionName + ';'
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
				summary.textContent = passedCount + '/' + tests.length + ' ' + Joomla.Text._('COM_BREEZINGFORMSNG_TEST_PASSED_SHORT');
				var detailParts = failures.slice();
				if (consoleLines.length) detailParts.push(Joomla.Text._('COM_BREEZINGFORMSNG_TEST_OUTPUT') + ':\n' + consoleLines.join('\n'));
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
