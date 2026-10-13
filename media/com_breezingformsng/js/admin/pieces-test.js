var __bfOpts = Joomla.getOptions('com_breezingformsng.pieces-test') || {};

Joomla.submitbutton = function (pressbutton) {
	var action = pressbutton.indexOf('.') === -1 ? pressbutton : pressbutton.split('.').pop();
	Joomla.submitform('piece.' + action, document.getElementById('adminForm'));
};
window.submitbutton = Joomla.submitbutton;

window.addEventListener('load', function () {
	var banner = document.getElementById('bf-piece-auto-unit-warning');
	if (banner) {
		window.setTimeout(function () {
			banner.style.display = 'none';
		}, 5000);
	}

	if (!__bfOpts.autoSubmit) {
		return;
	}

	var form = document.getElementById('adminForm');
	if (!form) {
		return;
	}
	if (__bfOpts.forceUnitTestMode) {
		var testModeField = form.querySelector('input[name="test_mode"]');
		if (testModeField) {
			testModeField.value = 'unit';
		}
	}
	var autoOpenField = form.querySelector('input[name="auto_open_tests"]');
	if (autoOpenField) {
		autoOpenField.value = '1';
	}
	form.submit();
});
