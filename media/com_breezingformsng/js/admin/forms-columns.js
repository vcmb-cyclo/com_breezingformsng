// Default of the Columns menu of the lists: Package of the forms and the audit
// columns Created, Created by and Modified by hidden, until the user makes a
// choice, which table-columns.js then stores. A forms choice stored before these
// columns existed is moved once: Modified went from 9 to 11. Storage can be
// unavailable (blocked site data, private mode): the lists then keep working.
try {
	const keyOf = (table) => `joomla-tablecolumns-bfng-${table}`;
	const defaults = {
		forms: ['4', '9', '10', '12'],
		scripts: ['8', '9', '11'],
		pieces: ['8', '9', '11'],
		integrator: ['7', '8', '10'],
	};
	const marker = `${keyOf('forms')}-audit`;
	const layout = '2';
	const stored = window.localStorage.getItem(keyOf('forms'));

	if (stored !== null && window.localStorage.getItem(marker) !== layout) {
		const kept = stored.split(',')
			.filter((index) => index !== '' && Number(index) < 10)
			.map((index) => (index === '9' ? '11' : index));
		window.localStorage.setItem(keyOf('forms'), [...kept, '9', '10', '12'].join(','));
	}

	Object.entries(defaults).forEach(([table, hidden]) => {
		if (window.localStorage.getItem(keyOf(table)) === null) {
			window.localStorage.setItem(keyOf(table), hidden.join(','));
		}
	});

	window.localStorage.setItem(marker, layout);
} catch {
	// No storage: nothing to preset.
}
