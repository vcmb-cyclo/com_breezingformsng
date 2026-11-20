// Default of the Columns menu of the forms list: Package (4) hidden, until
// the user makes a choice, which table-columns.js then stores.
if (window.localStorage.getItem('joomla-tablecolumns-bfng-forms') === null) {
	window.localStorage.setItem('joomla-tablecolumns-bfng-forms', '4');
}
