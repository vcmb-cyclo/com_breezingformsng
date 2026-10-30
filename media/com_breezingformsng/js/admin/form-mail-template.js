import { JoomlaEditor } from 'editor-api';

document.addEventListener('change', (event) => {
	const toggle = event.target.closest('[data-bf-mail-template-toggle]');

	if (toggle) {
		document.getElementById(toggle.dataset.bfMailTemplateToggle).hidden = toggle.value !== '1';
		document.getElementById(toggle.dataset.bfMailDefaultToggle).hidden = toggle.value === '1';
	}
});

document.addEventListener('click', (event) => {
	const button = event.target.closest('[data-bf-mail-placeholder]');

	if (button) {
		JoomlaEditor.get(button.dataset.bfMailTarget).replaceSelection(button.dataset.bfMailPlaceholder);
	}
});

// form.submit() in admin-form.js does not fire the submit event CodeMirror syncs its textarea on.
document.addEventListener('breezingformsng:form-submit', () => {
	const template = document.getElementById('jf_mb_email_custom_template');

	if (template) {
		template.value = JoomlaEditor.get(template.id).getValue();
	}
}, true);
