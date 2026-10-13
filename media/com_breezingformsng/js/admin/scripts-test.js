var __bfOpts = Joomla.getOptions('com_breezingformsng.scripts-test') || {};

(function () {
				var testCode = __bfOpts.code;
				var defaultFunctionName = __bfOpts.functionName;
				var unitTestsDefinition = __bfOpts.unitTests;
				var requestedTestMode = __bfOpts.testMode;

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
						buttons[i].title = enabled ? '' : Joomla.Text._('COM_BREEZINGFORMSNG_TEST_UNIT_TESTS_NONE');
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
							return Joomla.Text._('COM_BREEZINGFORMSNG_TEST_UNIT_FAILURES_ON_OPEN');
						}
						return Joomla.Text._(
							count === 1
								? 'COM_BREEZINGFORMSNG_TEST_UNIT_FAILURES_1'
								: 'COM_BREEZINGFORMSNG_TEST_UNIT_FAILURES_MORE'
						).replace('%d', count);
					}

				window.submitbutton = function (pressbutton) {
					var action = pressbutton.indexOf('.') === -1 ? pressbutton : pressbutton.split('.').pop();
					Joomla.submitform('script.' + action, document.getElementById('adminForm'));
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
						errorMessage.textContent = Joomla.Text._('COM_BREEZINGFORMSNG_TEST_ENTER_FUNCTION_NAME_TO_TEST');
						return false;
					}
					if (!/^[A-Za-z_$][A-Za-z0-9_$]*$/.test(functionName)) {
						resetUi();
						errorBox.style.display = 'block';
						errorMessage.textContent = Joomla.Text._('COM_BREEZINGFORMSNG_TEST_INVALID_FUNCTION_NAME');
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
						unitTestsSummary.textContent = Joomla.Text._('COM_BREEZINGFORMSNG_TEST_ENTER_FUNCTION_NAME_TO_TEST');
						unitTestsDetailsWrap.style.display = 'none';
						unitTestsDetails.textContent = '';
						return false;
					}
						if (!/^[A-Za-z_$][A-Za-z0-9_$]*$/.test(functionName)) {
						unitTestsBox.style.display = 'block';
						unitTestsBox.className = 'alert alert-danger';
						unitTestsSummary.textContent = Joomla.Text._('COM_BREEZINGFORMSNG_TEST_INVALID_FUNCTION_NAME');
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
						unitTestsSummary.textContent = Joomla.Text._('COM_BREEZINGFORMSNG_TEST_NO_UNIT_TEST_DEFINED');
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
							showAutoOpenUnitWarning(Joomla.Text._('COM_BREEZINGFORMSNG_TEST_UNIT_FAILURES_ON_OPEN'));
							}
							return false;
						} finally {
						window.console = originalConsole;
					}

						unitTestsBox.style.display = 'block';
						unitTestsBox.className = failures.length ? 'alert alert-warning' : 'alert alert-success';
						unitTestsSummary.textContent = passedCount + '/' + tests.length + ' ' + Joomla.Text._('COM_BREEZINGFORMSNG_TEST_PASSED_SHORT');
						if (fromAutoOpen && failures.length) {
							showAutoOpenUnitWarning(formatAutoOpenUnitWarningMessage(failures.length));
						}

					var details = failures.slice();
					if (consoleLines.length) {
						details.push(Joomla.Text._('COM_BREEZINGFORMSNG_TEST_OUTPUT') + ':\n' + consoleLines.join('\n'));
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
						} else if (__bfOpts.autoRun) {
							window.bfRunAllScriptTests();
						} else if (__bfOpts.hasUnitTests) {
							window.bfRunScriptUnitTests(true);
						}
					});
				if (window.Joomla) {
					window.Joomla.submitbutton = window.submitbutton;
				}
			})();
