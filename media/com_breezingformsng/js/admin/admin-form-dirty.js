var __bfDirtyInitialState = '';
var __bfDirtySubmitting = false;
var __bfDirtySubmitbutton = Joomla.submitbutton;

function bfDirtyFormState(form) {
	// `task` is toolbar-click metadata, not form content: Joomla.submitbutton()
	// mutates it in place before every submit, including exports which leave
	// the edit page open - excluding it keeps the comparison to actual fields.
	var params = new URLSearchParams(new FormData(form));
	params.delete('task');
	return params.toString();
}

function bfDirtyIsChanged(form) {
	return bfDirtyFormState(form) !== __bfDirtyInitialState;
}

// The native toolbar button disables itself (setDisabled), so a click or a
// keyboard shortcut cannot save an unchanged record. The title sits on the
// toolbar element, as a disabled button shows none.
function bfDirtySyncSaveButton(form) {
	if (!__bfOpts.saveTask) {
		return;
	}

	var toolbarButton = document.querySelector('joomla-toolbar-button[task="' + __bfOpts.saveTask + '"]');
	// Not upgraded yet: whenDefined() syncs it once the element is defined.
	if (!toolbarButton || typeof toolbarButton.setDisabled !== 'function') {
		return;
	}

	var dirty = bfDirtyIsChanged(form);
	toolbarButton.setDisabled(!dirty);
	toolbarButton.title = dirty ? '' : Joomla.Text._('COM_BREEZINGFORMSNG_TEST_NO_CHANGES');
}

// Native Joomla dialogs (joomla.dialog, loaded through the import map).
function bfDirtyDialog(type, key) {
	return import('joomla.dialog').then(function (module) {
		return module.default[type](Joomla.Text._(key));
	});
}

Joomla.submitbutton = function (task) {
	var form = document.getElementById('adminForm');

	var isDiscardTask = (__bfOpts.cancelTask && task === __bfOpts.cancelTask)
		|| (Array.isArray(__bfOpts.discardTasks) && __bfOpts.discardTasks.indexOf(task) !== -1);
	if (isDiscardTask && bfDirtyIsChanged(form)) {
		bfDirtyDialog('confirm', 'COM_BREEZINGFORMSNG_CONFIRM_DISCARD_CHANGES').then(function (confirmed) {
			if (confirmed) {
				__bfDirtySubmitbutton(task);
			}
		});

		return false;
	}

	// Exports run against the persisted record, so unsaved edits would be
	// silently left out - block the export until the form has been saved.
	if (Array.isArray(__bfOpts.exportTasks) && __bfOpts.exportTasks.indexOf(task) !== -1 && bfDirtyIsChanged(form)) {
		bfDirtyDialog('alert', 'COM_BREEZINGFORMSNG_EXPORT_SAVE_FIRST');

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
	customElements.whenDefined('joomla-toolbar-button').then(function () { bfDirtySyncSaveButton(form); });

	window.addEventListener('beforeunload', function (event) {
		if (!__bfDirtySubmitting && bfDirtyIsChanged(form)) {
			event.preventDefault();
			event.returnValue = '';
		}
	});
});
