// Default of the Columns menu of the forms list: Package (4) hidden, until
// the user makes a choice, which table-columns.js then stores. Storage can be
// unavailable (blocked site data, private mode): the list then keeps working.
try {
	if (window.localStorage.getItem('joomla-tablecolumns-bfng-forms') === null) {
		window.localStorage.setItem('joomla-tablecolumns-bfng-forms', '4');
	}
} catch {
	// No storage: nothing to preset.
}
