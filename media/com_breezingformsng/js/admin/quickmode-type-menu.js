/**
 * Listbox over a select whose options carry a "bfng-type-icon--*" class: native options cannot show an
 * icon. It serves #bfElementType of the editor and every select marked data-bfng-icon-menu (the
 * integrator). The hidden select keeps the value, the change event and the form submission; the editor
 * dispatches "bfng:type-sync" after setting its value from a script.
 *
 * A select marked data-bfng-type-panel opens a panel instead of a list: a search (names and
 * data-keywords of the options), the last choices and one column for each family.
 */
(function () {
	'use strict';

	Array.prototype.forEach.call(document.querySelectorAll('#bfElementType, select[data-bfng-icon-menu]'), function (select) {
		if (select.hasAttribute('data-bfng-type-panel')) {
			enhancePanel(select);
		} else {
			enhance(select);
		}
	});

// Case and accents do not count in a search.
function fold(text) {
	return text.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase().trim();
}

// The button and the wrapper show the chosen option with its icon.
function showChoice(select, wrapper, text) {
	var selected = Array.prototype.find.call(select.options, function (option) {
		return option.value === select.value;
	});

	text.textContent = selected ? selected.textContent.trim() : '';
	// Only the icon class changes: the wrapper may carry others (bfng, bfng-type-select--form).
	Array.prototype.slice.call(wrapper.classList).forEach(function (name) {
		if (name.indexOf('bfng-type-icon--') === 0) {
			wrapper.classList.remove(name);
		}
	});

	if (selected) {
		Array.prototype.forEach.call(selected.classList, function (name) {
			if (name.indexOf('bfng-type-icon--') === 0) {
				wrapper.classList.add(name);
			}
		});
	}
}

function enhancePanel(select) {
	var wrapper = select.closest('.bfng-type-select');
	var prefix = select.id || 'bfTypePanel' + Math.random().toString(36).slice(2, 8);

	if (!wrapper) {
		return;
	}

	var label = select.id ? document.querySelector('label[for="' + select.id + '"]') : null;
	var button = document.createElement('button');
	var buttonText = document.createElement('span');
	var panel = document.createElement('div');
	var search = document.createElement('input');
	var list = document.createElement('div');
	var empty = document.createElement('p');
	var items = [];
	var active = -1;
	var storageKey = 'bfng-type-recent';

	button.type = 'button';
	button.id = prefix + 'Button';
	button.className = 'bfng-type-menu__button';
	button.setAttribute('aria-haspopup', 'dialog');
	button.setAttribute('aria-expanded', 'false');
	button.setAttribute('aria-controls', prefix + 'Panel');
	buttonText.className = 'bfng-type-menu__text';
	button.appendChild(buttonText);

	panel.id = prefix + 'Panel';
	panel.className = 'bfng-type-panel';
	panel.setAttribute('role', 'dialog');
	panel.hidden = true;

	search.type = 'text';
	search.className = 'bfng-type-panel__search';
	search.autocomplete = 'off';
	search.placeholder = select.dataset.searchLabel || '';
	search.setAttribute('role', 'combobox');
	search.setAttribute('aria-label', search.placeholder);
	search.setAttribute('aria-autocomplete', 'list');
	search.setAttribute('aria-expanded', 'true');
	search.setAttribute('aria-controls', prefix + 'List');

	list.id = prefix + 'List';
	list.className = 'bfng-type-panel__list';
	list.setAttribute('role', 'listbox');

	empty.className = 'bfng-type-panel__empty';
	empty.setAttribute('role', 'status');

	if (label) {
		label.id = label.id || prefix + 'Label';
		label.htmlFor = button.id;
		button.setAttribute('aria-labelledby', label.id + ' ' + button.id);
		panel.setAttribute('aria-labelledby', label.id);
		list.setAttribute('aria-labelledby', label.id);
	}

	// The choices are a convenience of this browser: without storage the panel only lacks them.
	function recent() {
		try {
			var stored = JSON.parse(window.localStorage.getItem(storageKey));

			return Array.isArray(stored) ? stored : [];
		} catch {
			return [];
		}
	}

	function remember(value) {
		try {
			window.localStorage.setItem(storageKey, JSON.stringify([value].concat(recent().filter(function (other) {
				return other !== value;
			})).slice(0, 3)));
		} catch {
			// No storage: nothing to keep.
		}
	}

	function addGroup(title, options, className) {
		var group = document.createElement('div');
		var heading = document.createElement('div');

		heading.id = prefix + 'Group' + list.children.length;
		heading.className = 'bfng-type-menu__group';
		heading.textContent = title;
		group.className = className;
		group.setAttribute('role', 'group');
		group.setAttribute('aria-labelledby', heading.id);
		group.appendChild(heading);
		options.forEach(function (option) {
			var item = document.createElement('div');

			item.id = prefix + 'Option' + items.length;
			item.className = 'bfng-type-menu__option ' + option.className;
			item.setAttribute('role', 'option');
			item.setAttribute('aria-selected', option.value === select.value ? 'true' : 'false');
			item.textContent = option.textContent.trim();
			item.dataset.value = option.value;

			if (option.disabled) {
				item.setAttribute('aria-disabled', 'true');
			}

			group.appendChild(item);
			items.push(item);
		});
		list.appendChild(group);
	}

	// Built again at each opening and search: the editor may change the select meanwhile.
	function render() {
		var term = fold(search.value);
		// A hidden option is a choice made elsewhere: it shows on the button but not in the panel.
		var all = Array.prototype.slice.call(select.querySelectorAll('optgroup > option:not([hidden])'));

		list.textContent = '';
		items = [];
		active = -1;

		if (term === '') {
			var last = recent().map(function (value) {
				return all.find(function (option) {
					return option.value === value;
				});
			}).filter(Boolean);

			if (last.length > 0) {
				addGroup(select.dataset.recentLabel || '', last, 'bfng-type-panel__recent');
			}
		}

		Array.prototype.forEach.call(select.querySelectorAll('optgroup'), function (family) {
			var found = Array.prototype.filter.call(family.children, function (option) {
				return !option.hidden && fold(option.textContent + ' ' + (option.dataset.keywords || '')).indexOf(term) >= 0;
			});

			if (found.length > 0) {
				addGroup(family.label, found, 'bfng-type-panel__family');
			}
		});

		empty.textContent = items.length === 0 ? select.dataset.emptyLabel || '' : '';
	}

	function isEnabled(item) {
		return item.getAttribute('aria-disabled') !== 'true';
	}

	function setActive(index) {
		if (items[active]) {
			items[active].classList.remove('is-active');
		}

		active = index;

		if (items[active]) {
			items[active].classList.add('is-active');
			items[active].scrollIntoView({ block: 'nearest' });
			search.setAttribute('aria-activedescendant', items[active].id);
		} else {
			search.removeAttribute('aria-activedescendant');
		}
	}

	function firstActive() {
		var current = items.findIndex(function (item) {
			return item.parentElement.className === 'bfng-type-panel__family' && item.dataset.value === select.value
				&& isEnabled(item);
		});

		if (current >= 0 && search.value === '') {
			return current;
		}

		// The first column rather than the last choices.
		var family = items.findIndex(function (item) {
			return item.parentElement.className === 'bfng-type-panel__family' && isEnabled(item);
		});

		return family >= 0 ? family : items.findIndex(isEnabled);
	}

	function move(step) {
		var index = active + step;

		while (items[index] && !isEnabled(items[index])) {
			index += step;
		}

		if (items[index]) {
			setActive(index);
		}
	}

	// Left and right keep the row and change the column.
	function moveColumn(step) {
		var item = items[active];

		if (!item) {
			return;
		}

		var groups = Array.prototype.slice.call(list.querySelectorAll('.bfng-type-panel__family'));
		var group = groups[groups.indexOf(item.parentElement) + step];

		if (!group) {
			return;
		}

		var row = Array.prototype.indexOf.call(item.parentElement.querySelectorAll('[role="option"]'), item);
		var targets = group.querySelectorAll('[role="option"]');
		var target = targets[Math.min(row, targets.length - 1)];

		if (isEnabled(target)) {
			setActive(items.indexOf(target));
		}
	}

	// Fixed to the viewport, wider than the button, never outside the window.
	function place() {
		var rect = button.getBoundingClientRect();
		var width = Math.min(760, window.innerWidth - 16);
		var below = window.innerHeight - rect.bottom;
		var above = below < 320 && rect.top > below;

		panel.style.width = width + 'px';
		panel.style.left = Math.max(8, Math.min(rect.left, window.innerWidth - width - 8)) + 'px';
		panel.style.top = above ? 'auto' : rect.bottom + 'px';
		panel.style.bottom = above ? window.innerHeight - rect.top + 'px' : 'auto';
		panel.style.maxHeight = (above ? rect.top : below) - 8 + 'px';
	}

	function open() {
		search.value = '';
		render();
		panel.hidden = false;
		place();
		button.setAttribute('aria-expanded', 'true');
		search.focus();
		setActive(firstActive());
	}

	function close() {
		panel.hidden = true;
		button.setAttribute('aria-expanded', 'false');
		setActive(-1);
	}

	function choose(item) {
		var value = item.dataset.value;

		close();
		button.focus();
		remember(value);

		if (value === select.value) {
			return;
		}

		select.value = value;
		showChoice(select, wrapper, buttonText);
		select.dispatchEvent(new Event('change', { bubbles: true }));
	}

	button.addEventListener('click', function () {
		if (panel.hidden) {
			open();
		} else {
			close();
		}
	});

	button.addEventListener('keydown', function (event) {
		if (panel.hidden && (event.key === 'ArrowDown' || event.key === 'ArrowUp')) {
			event.preventDefault();
			open();
		}
	});

	search.addEventListener('input', function () {
		render();
		setActive(firstActive());
	});

	search.addEventListener('keydown', function (event) {
		var key = event.key;

		if (key === 'Tab') {
			// Back to the button first, so that Tab goes on from there.
			close();
			button.focus();
		} else if (key === 'Escape') {
			event.preventDefault();
			event.stopPropagation();
			close();
			button.focus();
		} else if (key === 'ArrowDown' || key === 'ArrowUp') {
			event.preventDefault();
			move(key === 'ArrowDown' ? 1 : -1);
		} else if ((key === 'ArrowRight' || key === 'ArrowLeft') && search.value === '') {
			event.preventDefault();
			moveColumn(key === 'ArrowRight' ? 1 : -1);
		} else if (key === 'Enter') {
			event.preventDefault();

			if (items[active] && isEnabled(items[active])) {
				choose(items[active]);
			}
		}
	});

	panel.addEventListener('mousedown', function (event) {
		// Keeps the focus on the search, which owns the keyboard.
		if (event.target !== search) {
			event.preventDefault();
		}
	});

	list.addEventListener('click', function (event) {
		var item = event.target.closest('[role="option"]');

		if (item && isEnabled(item)) {
			choose(item);
		}
	});

	document.addEventListener('mousedown', function (event) {
		if (!panel.hidden && !wrapper.contains(event.target)) {
			close();
		}
	});

	window.addEventListener('scroll', function (event) {
		if (!panel.hidden && !panel.contains(event.target)) {
			close();
		}
	}, true);

	// A phone keyboard resizes the window: the panel follows instead of closing.
	window.addEventListener('resize', function () {
		if (!panel.hidden) {
			place();
		}
	});

	select.addEventListener('bfng:type-sync', function () {
		showChoice(select, wrapper, buttonText);
	});

	panel.appendChild(search);
	panel.appendChild(list);
	panel.appendChild(empty);
	select.hidden = true;
	wrapper.appendChild(button);
	wrapper.appendChild(panel);
	showChoice(select, wrapper, buttonText);
}

function enhance(select) {
	var wrapper = select.closest('.bfng-type-select');
	var prefix = select.id || 'bfIconMenu' + Math.random().toString(36).slice(2, 8);

	if (!wrapper) {
		return;
	}

	var label = select.id ? document.querySelector('label[for="' + select.id + '"]') : null;
	var button = document.createElement('button');
	var list = document.createElement('div');
	var buttonText = document.createElement('span');
	var options = [];
	var active = -1;
	var typed = '';
	var typedAt = 0;

	button.type = 'button';
	button.id = prefix + 'Button';
	button.className = 'bfng-type-menu__button';
	button.setAttribute('role', 'combobox');
	button.setAttribute('aria-haspopup', 'listbox');
	button.setAttribute('aria-expanded', 'false');
	button.setAttribute('aria-controls', prefix + 'List');
	buttonText.className = 'bfng-type-menu__text';
	button.appendChild(buttonText);

	list.id = prefix + 'List';
	list.className = 'bfng-type-menu__list';
	list.setAttribute('role', 'listbox');
	list.hidden = true;

	if (label) {
		label.id = label.id || prefix + 'Label';
		label.htmlFor = button.id;
		button.setAttribute('aria-labelledby', label.id + ' ' + button.id);
		list.setAttribute('aria-labelledby', label.id);
	}

	function addOption(option, parent) {
		var item = document.createElement('div');

		item.id = prefix + 'Option' + options.length;
		item.className = 'bfng-type-menu__option' + (option.className ? ' ' + option.className : '');
		item.setAttribute('role', 'option');
		item.textContent = option.textContent.trim();
		item.dataset.value = option.value;

		if (option.disabled) {
			item.setAttribute('aria-disabled', 'true');
		}

		parent.appendChild(item);
		options.push(item);
	}

	Array.prototype.forEach.call(select.children, function (child, index) {
		if (child.tagName !== 'OPTGROUP') {
			addOption(child, list);

			return;
		}

		var group = document.createElement('div');
		var heading = document.createElement('div');

		heading.id = prefix + 'Group' + index;
		heading.className = 'bfng-type-menu__group';
		heading.textContent = child.label;
		group.setAttribute('role', 'group');
		group.setAttribute('aria-labelledby', heading.id);
		group.appendChild(heading);
		Array.prototype.forEach.call(child.children, function (option) {
			addOption(option, group);
		});
		list.appendChild(group);
	});

	function isOpen() {
		return !list.hidden;
	}

	function isEnabled(item) {
		return item.getAttribute('aria-disabled') !== 'true';
	}

	function setActive(index) {
		if (options[active]) {
			options[active].classList.remove('is-active');
		}

		active = index;

		if (options[active]) {
			options[active].classList.add('is-active');
			options[active].scrollIntoView({ block: 'nearest' });
			button.setAttribute('aria-activedescendant', options[active].id);
		} else {
			button.removeAttribute('aria-activedescendant');
		}
	}

	function selectedIndex() {
		return options.findIndex(function (item) {
			return item.dataset.value === select.value;
		});
	}

	function sync() {
		var selected = options[selectedIndex()];

		options.forEach(function (item) {
			item.setAttribute('aria-selected', item === selected ? 'true' : 'false');
		});
		buttonText.textContent = selected ? selected.textContent : '';
		// Only the icon class changes: the wrapper may carry others (bfng, bfng-type-select--form).
		Array.prototype.slice.call(wrapper.classList).forEach(function (name) {
			if (name.indexOf('bfng-type-icon--') === 0) {
				wrapper.classList.remove(name);
			}
		});

		if (selected && selected.className.indexOf('bfng-type-icon--') >= 0) {
			wrapper.classList.add(selected.className.split(/\s+/).filter(function (name) {
				return name.indexOf('bfng-type-icon--') === 0;
			})[0]);
		}
	}

	// Fixed to the viewport: the editor's containers would clip it on a short form.
	function place() {
		var rect = button.getBoundingClientRect();
		var below = window.innerHeight - rect.bottom;
		var above = below < 240 && rect.top > below;

		list.style.left = rect.left + 'px';
		list.style.width = rect.width + 'px';
		list.style.top = above ? 'auto' : rect.bottom + 'px';
		list.style.bottom = above ? window.innerHeight - rect.top + 'px' : 'auto';
		list.style.maxHeight = Math.min(360, (above ? rect.top : below) - 8) + 'px';
	}

	function open() {
		var current = selectedIndex();

		list.hidden = false;
		place();
		button.setAttribute('aria-expanded', 'true');
		setActive(current >= 0 && isEnabled(options[current]) ? current : move(-1, 1));
	}

	function close() {
		list.hidden = true;
		button.setAttribute('aria-expanded', 'false');
		setActive(-1);
	}

	function move(from, step) {
		var index = from + step;

		while (options[index] && !isEnabled(options[index])) {
			index += step;
		}

		return options[index] ? index : from;
	}

	function choose(item) {
		close();
		button.focus();

		if (item.dataset.value === select.value) {
			return;
		}

		select.value = item.dataset.value;
		sync();
		select.dispatchEvent(new Event('change', { bubbles: true }));
	}

	function typeAhead(character) {
		var now = Date.now();

		typed = now - typedAt > 700 ? character : typed + character;
		typedAt = now;

		var match = options.findIndex(function (item) {
			return isEnabled(item) && item.textContent.toLocaleLowerCase().indexOf(typed) === 0;
		});

		if (match >= 0) {
			setActive(match);
		}
	}

	button.addEventListener('click', function () {
		if (isOpen()) {
			close();
		} else {
			open();
		}
	});

	button.addEventListener('keydown', function (event) {
		var key = event.key;

		if (!isOpen()) {
			if (key === 'ArrowDown' || key === 'ArrowUp' || key === 'Enter' || key === ' ') {
				event.preventDefault();
				open();
			}

			return;
		}

		if (key === 'Tab') {
			close();

			return;
		}

		if (key === 'Escape') {
			event.preventDefault();
			close();
		} else if (key === 'ArrowDown') {
			event.preventDefault();
			setActive(move(active, 1));
		} else if (key === 'ArrowUp') {
			event.preventDefault();
			setActive(move(active, -1));
		} else if (key === 'Home') {
			event.preventDefault();
			setActive(move(-1, 1));
		} else if (key === 'End') {
			event.preventDefault();
			setActive(move(options.length, -1));
		} else if (key === 'Enter' || key === ' ') {
			event.preventDefault();

			if (options[active]) {
				choose(options[active]);
			}
		} else if (key.length === 1 && !event.ctrlKey && !event.metaKey && !event.altKey) {
			typeAhead(key.toLocaleLowerCase());
		}
	});

	list.addEventListener('mousedown', function (event) {
		// Keeps the focus on the button, which owns the keyboard.
		event.preventDefault();
	});

	list.addEventListener('click', function (event) {
		var item = event.target.closest('[role="option"]');

		if (item && isEnabled(item)) {
			choose(item);
		}
	});

	document.addEventListener('mousedown', function (event) {
		if (isOpen() && !wrapper.contains(event.target)) {
			close();
		}
	});

	window.addEventListener('scroll', function (event) {
		if (isOpen() && !list.contains(event.target)) {
			close();
		}
	}, true);

	window.addEventListener('resize', function () {
		if (isOpen()) {
			close();
		}
	});

	select.addEventListener('bfng:type-sync', sync);

	select.hidden = true;
	wrapper.appendChild(button);
	wrapper.appendChild(list);
	sync();
}
}());
