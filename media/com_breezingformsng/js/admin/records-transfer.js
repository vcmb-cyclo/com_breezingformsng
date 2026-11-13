// A default value is only for a target field that no source field fills:
// choosing a source field turns the default value of the field off, and
// colors the row of the field, as the server does on loading. The icon
// of the chosen source field is shown beside the list, which cannot show it
// in its closed state.
document.addEventListener('DOMContentLoaded', () => {
	document.querySelectorAll('select[data-bf-transfer-default]').forEach((select) => {
		const input = document.getElementById(select.dataset.bfTransferDefault);
		const icon = select.parentElement.querySelector('.bf-transfer-icon');

		select.addEventListener('change', () => {
			select.closest('tr')?.classList.toggle('bf-transfer-assigned', select.value !== '0');

			if (input) {
				input.disabled = select.value !== '0';
			}

			if (icon) {
				const url = select.selectedOptions[0]?.dataset.icon ?? '';

				icon.classList.toggle('invisible', url === '');

				if (url !== '') {
					icon.src = url;
				}
			}
		});
	});
});
