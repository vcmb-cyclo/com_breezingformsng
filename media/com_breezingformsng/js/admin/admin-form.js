var __bfOpts = Joomla.getOptions('com_breezingformsng.admin-form') || {};

function bfToggle(libId, codeId, value) {
	document.getElementById(libId).style.display = (value === '1') ? '' : 'none';
	document.getElementById(codeId).style.display = (value === '2') ? '' : 'none';
}

Joomla.submitbutton = function (task) {
	var form = document.getElementById('adminForm');

	if (__bfOpts.confirmDeleteTask && task === __bfOpts.confirmDeleteTask) {
		if (!confirm(Joomla.Text._('JGLOBAL_CONFIRM_DELETE'))) {
			return false;
		}
	}

	var shouldValidate = __bfOpts.validateTask ? task === __bfOpts.validateTask : task !== __bfOpts.cancelTask;
	if (shouldValidate && !form.reportValidity()) {
		return;
	}

	if (task !== '') {
		form.querySelector('[name="task"]').value = task;
	}
	form.dispatchEvent(new CustomEvent('breezingformsng:form-submit'));
	form.submit();
};
