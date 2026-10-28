var __bfOpts = Joomla.getOptions('com_breezingformsng.pieces-edit') || {};

function checkIdentifier(value)
						{
							var invalidChars = /\W/;
							var error = '';
							if (value == '')
								error += Joomla.Text._('COM_BREEZINGFORMSNG_PIECES_ENTERNAME') + "\n";
							else
			if (invalidChars.test(value))
				error += Joomla.Text._('COM_BREEZINGFORMSNG_PIECES_ENTERIDENT') + "\n";
			return error;
						} // checkIdentifier

			function submitbutton(pressbutton) {
				var form = document.adminForm;
				var error = '';
				var action = pressbutton.indexOf('.') === -1 ? pressbutton : pressbutton.split('.').pop();
				if (action == 'test' && isEditTestBlocked()) {
					alert(Joomla.Text._('COM_BREEZINGFORMSNG_TEST_SAVE_PIECE_BEFORE_CONTINUE'));
					return;
				}
				if (action == 'apply' || action == 'save') {
					error += checkIdentifier(getFieldValue('name'), 'name');
					if (getFieldValue('title') == '') error += Joomla.Text._('COM_BREEZINGFORMSNG_PIECES_ENTTITLE') + "\n";
				} // if
				if (error != '') {
					alert(error);
					return;
				}
				Joomla.submitform('piece.' + action, form);
			} // submitbutton
			Joomla.submitbutton = submitbutton;
			window.submitbutton = submitbutton;

			onload = function () {
				var titleField = getFormField('title');
				if (titleField) titleField.focus();
			} // onload

			function findToolbarButtons(tasks) {
				var selector = tasks.map(function (task) {
					return 'joomla-toolbar-button[task="piece.' + task + '"] button';
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

			function normalizeValue(value) {
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
					if (normalizeValue(currentState[key]) !== normalizeValue(initialState[key])) {
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
						button.title = Joomla.Text._('COM_BREEZINGFORMSNG_TEST_SAVE_PIECE_BEFORE_TESTS');
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
					syncEditNavigationToolbarButton(link, Joomla.Text._('COM_BREEZINGFORMSNG_TEST_SAVE_PIECE_BEFORE_NAVIGATION'));
				});
			}

			function syncEditUnitTestsButton() {
				var button = document.getElementById('bf-edit-piece-unit-tests-button');
				var field = getFormField('unit_tests');
				if (!button || !field) {
					return;
				}
				var currentValue = String(field.value || '');
				var hasTests = currentValue.trim() !== '';
				var isDirty = isEditTestBlocked();
				var enabled = hasTests && __bfOpts.hasSavedRecord && !isDirty;
				button.disabled = !enabled;
				button.classList.toggle('disabled', !enabled);
				button.setAttribute('aria-disabled', enabled ? 'false' : 'true');
				if (!__bfOpts.hasSavedRecord) {
					button.title = Joomla.Text._('COM_BREEZINGFORMSNG_TEST_SAVE_FIRST_PIECE');
				} else if (isDirty) {
					button.title = Joomla.Text._('COM_BREEZINGFORMSNG_TEST_SAVE_PIECE_BEFORE_UNIT_TESTS');
				} else {
					button.title = enabled ? '' : Joomla.Text._('COM_BREEZINGFORMSNG_TEST_UNIT_TESTS_NONE');
				}
			}

			function runPieceUnitTestsFromEdit() {
				var form = document.adminForm;
				if (!form) {
					return false;
				}
				var button = document.getElementById('bf-edit-piece-unit-tests-button');
				if (button && button.disabled) {
					return false;
				}
				var resultBox = document.getElementById('bf-edit-piece-unit-tests-status');
				var summary = document.getElementById('bf-edit-piece-unit-tests-summary');
				var detailsWrap = document.getElementById('bf-edit-piece-unit-tests-details-wrap');
				var details = document.getElementById('bf-edit-piece-unit-tests-details');
				var payload = new URLSearchParams();
				payload.set('option', 'com_breezingformsng');
				payload.set('task', 'piece.runTestsAjax');
				payload.set('id', getFieldValue('id') || '');
				payload.set('code', getFieldValue('code') || '');
				payload.set('unit_tests', getFieldValue('unit_tests') || '');
				payload.set('test_function', getFieldValue('name') || '');
				payload.set(__bfOpts.csrfToken, '1');

				resultBox.style.display = 'block';
				resultBox.className = 'alert alert-info mt-3';
				summary.textContent = Joomla.Text._('COM_BREEZINGFORMSNG_TEST_RUNNING');
				detailsWrap.style.display = 'none';
				details.textContent = '';

					fetch('index.php', {
						method: 'POST',
						headers: {
							'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
						},
						body: payload.toString()
					})
						.then(function (response) { return response.text(); })
						.then(function (text) {
							var data;
							var normalizedText = String(text || '').trim();
							var jsonMarkers = ['{"error"', '{"warning"', '{"total"'];
							var jsonStart = -1;
							for (var i = 0; i < jsonMarkers.length; i++) {
								var markerIndex = normalizedText.indexOf(jsonMarkers[i]);
								if (markerIndex !== -1) {
									jsonStart = markerIndex;
									break;
								}
							}
							var jsonEnd = normalizedText.lastIndexOf('}');
							if (jsonStart !== -1 && jsonEnd !== -1 && jsonEnd >= jsonStart) {
								normalizedText = normalizedText.slice(jsonStart, jsonEnd + 1);
							}
							try {
								data = JSON.parse(normalizedText);
							} catch (e) {
								throw new Error(text || Joomla.Text._('COM_BREEZINGFORMSNG_TEST_INVALID_SERVER_RESPONSE'));
							}
							return data;
						})
						.then(function (data) {
							if (data.error) {
								resultBox.className = 'alert alert-danger mt-3';
							summary.textContent = data.error;
							return;
						}
						if (data.warning) {
							resultBox.className = 'alert alert-warning mt-3';
							summary.textContent = data.warning;
							return;
						}
						resultBox.className = (data.failures && data.failures.length) ? 'alert alert-warning mt-3' : 'alert alert-success mt-3';
						summary.textContent = String(data.passed || 0) + '/' + String(data.total || 0) + ' ' + Joomla.Text._('COM_BREEZINGFORMSNG_TEST_PASSED_SHORT');
						if (data.failures && data.failures.length) {
							detailsWrap.style.display = 'block';
							details.textContent = data.failures.join('\n\n');
						}
					})
					.catch(function (error) {
						resultBox.className = 'alert alert-danger mt-3';
						summary.textContent = error && error.message ? error.message : String(error);
					});
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
