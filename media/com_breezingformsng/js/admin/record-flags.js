// Ajax toggles of the records list and the record screen: the viewed and
// exported flags ([data-bf-flag]) and, on the record screen, the switch
// between active and archived ([data-bf-state]).
(function () {
	var options = Joomla.getOptions('com_breezingformsng.record-flags') || {};
	var STATE_ACTIVE = 1;
	var STATE_ARCHIVED = 2;
	var STATE_ICONS = {
		1: ['icon-publish', 'text-success'],
		2: ['icon-archive']
	};

	function post(task, params) {
		params.append(options.csrfToken, '1');

		return fetch('index.php?option=com_breezingformsng&task=records.' + task + '&format=json', {
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: params.toString()
		}).then(function (response) {
			return response.json().then(function (json) {
				if (!response.ok || !json.success) {
					throw new Error(json.message || 'HTTP ' + response.status);
				}

				return json.data;
			});
		});
	}

	function setIcon(icon, classes) {
		icon.className = '';
		icon.classList.add.apply(icon.classList, classes);
	}

	function toggleFlag(link) {
		var recordId = link.dataset.bfRecordId;
		var column = link.dataset.bfFlag;
		var flag = link.querySelector('span').classList.contains('icon-check') ? 0 : 1;
		var params = new URLSearchParams({ record_id: recordId, column: column, flag: flag });

		return post('setFlag', params).then(function () {
			// The record screen shows each flag twice: keep both in sync.
			document.querySelectorAll('[data-bf-record-id="' + recordId + '"][data-bf-flag="' + column + '"]').forEach(function (flagLink) {
				var text = flagLink.querySelector('.bf-flag-text');

				setIcon(flagLink.querySelector('span'), flag ? ['icon-check', 'text-success'] : ['icon-times', 'text-danger']);

				if (text) {
					text.textContent = Joomla.Text._(flag ? 'JYES' : 'JNO');
				}
			});
		});
	}

	function toggleState(link) {
		var state = Number(link.dataset.bfState) === STATE_ACTIVE ? STATE_ARCHIVED : STATE_ACTIVE;
		var params = new URLSearchParams({ record_id: link.dataset.bfRecordId, state: state });

		return post('setState', params).then(function (data) {
			link.dataset.bfState = String(data.state);
			setIcon(link.querySelector('span'), STATE_ICONS[data.state]);
			link.querySelector('.bf-state-text').textContent = data.label;
			// The current record may leave the filtered list or move within it.
			// Its pagination links still contain offsets from before this change.
			document.querySelectorAll('nav.pagination__wrapper').forEach(function (pagination) {
				pagination.remove();
			});
		});
	}

	document.addEventListener('click', function (event) {
		var link = event.target.closest('a[data-bf-record-id]');

		if (!link) {
			return;
		}

		event.preventDefault();

		if (link.dataset.bfBusy) {
			return;
		}

		link.dataset.bfBusy = '1';
		(link.dataset.bfState ? toggleState(link) : toggleFlag(link))
			.catch(function () {
				Joomla.renderMessages({ error: [Joomla.Text._('COM_BREEZINGFORMSNG_AJAX_STATE_ERROR')] });
			})
			.finally(function () {
				delete link.dataset.bfBusy;
			});
	});
})();
