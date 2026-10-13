var __bfToggleOpts = Joomla.getOptions('com_breezingformsng.admin-toggle-published') || {};

function bfSetToggleIcon(link, state, stateType) {
	var span = link.querySelector('span');

	span.className = stateType === 'debug'
		? (state ? 'fa fa-bug text-success' : 'fa fa-bug text-muted')
		: (state ? 'icon-publish' : 'icon-unpublish');
	link.classList.toggle('active', Boolean(state));
	link.title = stateType === 'debug'
		? Joomla.Text._(state ? 'COM_BREEZINGFORMSNG_DEBUG_MODE_ENABLED' : 'COM_BREEZINGFORMSNG_DEBUG_MODE_DISABLED')
		: Joomla.Text._(state ? 'JPUBLISHED' : 'JUNPUBLISHED');

	if (link.hasAttribute('aria-pressed')) {
		link.setAttribute('aria-pressed', state ? 'true' : 'false');
		link.setAttribute('aria-label', link.title);
	}
}

function bfRequestFormState(id, task, state, stateType, link) {
	link.setAttribute('aria-busy', 'true');

	return fetch('index.php?option=com_breezingformsng&task=' + task + '&format=json', {
		method: 'POST',
		headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
		body: 'id=' + encodeURIComponent(id) + '&state=' + state + '&' + __bfToggleOpts.csrfToken + '=1'
	}).then(function (response) {
		if (!response.ok) {
			throw new Error('HTTP ' + response.status);
		}

		return response.json();
	}).then(function (data) {
		if (data && data.data) {
			data = typeof data.data === 'string' ? JSON.parse(data.data) : data.data;
		}

		if (data.Result !== 'OK') {
			throw new Error(data.Message || 'Invalid response');
		}

		bfSetToggleIcon(link, Number(data.State), stateType);

		return Number(data.State);
	}).catch(function () {
		Joomla.renderMessages({ error: [Joomla.Text._('COM_BREEZINGFORMSNG_AJAX_STATE_ERROR')] });

		return null;
	}).finally(function () {
		link.removeAttribute('aria-busy');
	});
}

window.bfTogglePublished = function (id, view, link) {
	var span = link.querySelector('span');
	var isPublished = span.classList.contains('icon-publish');
	var newState = isPublished ? 0 : 1;
	bfRequestFormState(id, view + '.setPublished', newState, 'published', link);
};

document.addEventListener('click', function (event) {
	var link = event.target.closest('.js-bf-form-state');

	if (!link) {
		return;
	}

	event.preventDefault();

	if (link.hasAttribute('aria-busy')) {
		return;
	}

	var state = link.classList.contains('active') ? 0 : 1;
	bfRequestFormState(
		link.dataset.itemFormId,
		link.dataset.itemTask,
		state,
		link.dataset.stateType,
		link
	);
});
