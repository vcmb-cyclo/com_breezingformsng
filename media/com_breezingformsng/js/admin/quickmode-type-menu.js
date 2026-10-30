/**
 * Listbox over the #bfElementType select: native options cannot show an icon.
 * The hidden select keeps the value and the change event the editor relies on;
 * the editor dispatches "bfng:type-sync" after setting its value from a script.
 */
(function () {
	'use strict';

	var select = document.getElementById('bfElementType');
	var wrapper = document.getElementById('bfElementTypeIcon');

	if (!select || !wrapper) {
		return;
	}

	var label = document.querySelector('label[for="bfElementType"]');
	var button = document.createElement('button');
	var list = document.createElement('div');
	var buttonText = document.createElement('span');
	var options = [];
	var active = -1;
	var typed = '';
	var typedAt = 0;

	button.type = 'button';
	button.id = 'bfElementTypeButton';
	button.className = 'bfng-type-menu__button';
	button.setAttribute('role', 'combobox');
	button.setAttribute('aria-haspopup', 'listbox');
	button.setAttribute('aria-expanded', 'false');
	button.setAttribute('aria-controls', 'bfElementTypeList');
	buttonText.className = 'bfng-type-menu__text';
	button.appendChild(buttonText);

	list.id = 'bfElementTypeList';
	list.className = 'bfng-type-menu__list';
	list.setAttribute('role', 'listbox');
	list.hidden = true;

	if (label) {
		label.id = label.id || 'bfElementTypeLabel';
		label.htmlFor = button.id;
		button.setAttribute('aria-labelledby', label.id + ' ' + button.id);
		list.setAttribute('aria-labelledby', label.id);
	}

	function addOption(option, parent) {
		var item = document.createElement('div');

		item.id = 'bfElementTypeOption' + options.length;
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

		heading.id = 'bfElementTypeGroup' + index;
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
		wrapper.className = 'bfng-type-select' + (select.value ? ' bfng-type-icon--' + select.value : '');
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
}());
