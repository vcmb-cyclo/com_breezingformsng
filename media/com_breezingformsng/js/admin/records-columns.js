// Default of the Columns menu of the records list: Viewed (5) and Exported (6)
// hidden, until the user makes a choice, which table-columns.js then stores.
if (window.localStorage.getItem('joomla-tablecolumns-bfng-records') === null) {
	window.localStorage.setItem('joomla-tablecolumns-bfng-records', '5,6');
}
