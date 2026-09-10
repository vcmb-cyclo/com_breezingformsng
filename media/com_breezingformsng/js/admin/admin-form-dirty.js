var __bfDirtyInitialState = '';
var __bfDirtySubmitting = false;
var __bfDirtySubmitbutton = Joomla.submitbutton;

function bfDirtyFormState(form) {
	return new URLSearchParams(new FormData(form)).toString();
}

function bfDirtyIsChanged(form) {
	return bfDirtyFormState(form) !== __bfDirtyInitialState;
}

function bfDirtySyncSaveButton(form) {
	if (!__bfOpts.saveTask) {
		return;
	}

	var button = document.querySelector('joomla-toolbar-button[task="' + __bfOpts.saveTask + '"] button, [onclick*="' + __bfOpts.saveTask + '"]');
	if (!button) {
		return;
	}

	if (button.dataset.bfOriginalTitle === undefined) {
		button.dataset.bfOriginalTitle = button.title;
		button.dataset.bfOriginalTabindex = button.getAttribute('tabindex') || '';
	}

	var dirty = bfDirtyIsChanged(form);
	button.classList.toggle('disabled', !dirty);
	button.setAttribute('aria-disabled', dirty ? 'false' : 'true');
	button.style.pointerEvents = dirty ? '' : 'none';
	button.title = dirty ? button.dataset.bfOriginalTitle : Joomla.Text._('COM_BREEZINGFORMSNG_TEST_NO_CHANGES');

	if (dirty) {
		if (button.dataset.bfOriginalTabindex === '') {
			button.removeAttribute('tabindex');
		} else {
			button.setAttribute('tabindex', button.dataset.bfOriginalTabindex);
		}
	} else {
		button.tabIndex = -1;
	}
}

Joomla.submitbutton = function (task) {
	var form = document.getElementById('adminForm');

	if (__bfOpts.cancelTask && task === __bfOpts.cancelTask && bfDirtyIsChanged(form)
		&& !confirm(Joomla.Text._('COM_BREEZINGFORMSNG_CONFIRM_DISCARD_CHANGES'))) {
		return false;
	}

	// Exports run against the persisted record, so unsaved edits would be
	// silently left out - block the export until the form has been saved.
	if (Array.isArray(__bfOpts.exportTasks) && __bfOpts.exportTasks.indexOf(task) !== -1 && bfDirtyIsChanged(form)) {
		alert(Joomla.Text._('COM_BREEZINGFORMSNG_EXPORT_SAVE_FIRST'));
		return false;
	}

	return __bfDirtySubmitbutton(task);
};

document.addEventListener('DOMContentLoaded', function () {
	var form = document.getElementById('adminForm');
	if (!form) {
		return;
	}

	__bfDirtyInitialState = bfDirtyFormState(form);
	form.addEventListener('breezingformsng:form-submit', function () {
		__bfDirtySubmitting = true;
		// A genuine save/cancel navigates away almost immediately; a file
		// download (CSV/Excel/PDF/XML export) leaves the edit page open, so
		// re-arm the unsaved-changes guard once the response has been served.
		window.setTimeout(function () { __bfDirtySubmitting = false; }, 10000);
	});
	form.addEventListener('input', function () { bfDirtySyncSaveButton(form); });
	form.addEventListener('change', function () { bfDirtySyncSaveButton(form); });
	bfDirtySyncSaveButton(form);

	window.addEventListener('beforeunload', function (event) {
		if (!__bfDirtySubmitting && bfDirtyIsChanged(form)) {
			event.preventDefault();
			event.returnValue = '';
		}
	});
});
