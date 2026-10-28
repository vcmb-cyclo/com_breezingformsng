var __bfRecordFlagsOpts = Joomla.getOptions('com_breezingformsng.record-flags') || {};

function bfToggleFlag(recordId, column, link) {
	var span = link.querySelector('span');
	var isChecked = span.classList.contains('icon-check');
	var newFlag = isChecked ? 0 : 1;
	var params = new URLSearchParams();
	params.append('record_id', recordId);
	params.append('column', column);
	params.append('flag', newFlag);
	if (__bfRecordFlagsOpts && __bfRecordFlagsOpts.csrfToken) {
		params.append(__bfRecordFlagsOpts.csrfToken, 1);
	}

	fetch('index.php?option=com_breezingformsng&task=records.setFlag&format=json', {
		method: 'POST',
		headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
		body: params.toString()
	}).then(function (response) {
		if (!response.ok) {
			throw new Error('HTTP ' + response.status);
		}

		return response.json();
	}).then(function (data) {
		if (data && data.data) {
			data = typeof data.data === 'string' ? JSON.parse(data.data) : data.data;
		}

		if (data.Result === 'OK') {
			// On the record screen several links show the same flag: keep them in sync.
			var links = link.dataset.bfFlag
				? document.querySelectorAll('[data-bf-flag="' + column + '"]')
				: [link];

			links.forEach(function (flagLink) {
				var icon = flagLink.querySelector('span');
				var text = flagLink.querySelector('.bf-flag-text');

				icon.classList.remove('icon-check', 'text-success', 'icon-times', 'text-danger');
				icon.classList.add.apply(icon.classList, newFlag ? ['icon-check', 'text-success'] : ['icon-times', 'text-danger']);

				if (text) {
					text.textContent = Joomla.Text._(newFlag ? 'JYES' : 'JNO');
				}
			});
		} else {
			throw new Error(data.Message || 'Invalid response');
		}
	}).catch(function () {
		Joomla.renderMessages({ error: [Joomla.Text._('COM_BREEZINGFORMSNG_AJAX_STATE_ERROR')] });
	});
}
