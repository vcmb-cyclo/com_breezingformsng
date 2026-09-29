Joomla.submitbutton = function (task) {
	var form = document.getElementById('adminForm');
	if (task === 'records.remove') {
		if (!confirm(Joomla.Text._('COM_BREEZINGFORMSNG_CONFIRM_DELETE_RECORDS'))) {
			return false;
		}
	}
	form.querySelector('input[name="task"]').value = task;
	form.submit();
	return true;
};

document.addEventListener('DOMContentLoaded', function () {
	var markToggle = document.querySelector('#toolbar-mark-options button.button-mark-options');
	var boxchecked = document.querySelector('#adminForm input[name="boxchecked"]');
	if (!markToggle || !boxchecked) {
		return;
	}
	var updateMarkToggle = function () {
		markToggle.disabled = parseInt(boxchecked.value, 10) === 0;
	};
	boxchecked.addEventListener('change', updateMarkToggle);
	updateMarkToggle();
});
