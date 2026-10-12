// An export downloads a file without reloading the page: the task has to be
// cleared afterwards, or the next filter change would run the export again.
Joomla.submitbutton = function (task) {
	var form = document.getElementById('adminForm');
	Joomla.submitform(task, form);

	if (task.indexOf('records.export') === 0) {
		form.task.value = '';
	}
};
