var __bfRecordFlagsOpts = Joomla.getOptions('com_breezingformsng.record-flags') || {};

function bfToggleFlag(recordId, column, link, detailCellId) {
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
			if (newFlag) {
				span.classList.remove('icon-times', 'text-danger');
				span.classList.add('icon-check', 'text-success');
			} else {
				span.classList.remove('icon-check', 'text-success');
				span.classList.add('icon-times', 'text-danger');
			}

			if (detailCellId) {
				var detailCell = document.getElementById(detailCellId);
				if (detailCell) {
					detailCell.textContent = Joomla.Text._(newFlag ? 'JYES' : 'JNO');
				}
			}
		} else {
			throw new Error(data.Message || 'Invalid response');
		}
	}).catch(function () {
		Joomla.renderMessages({ error: [Joomla.Text._('COM_BREEZINGFORMSNG_AJAX_STATE_ERROR')] });
	});
}
