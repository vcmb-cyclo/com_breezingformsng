// The tree panel switches of the QuickMode editor, kept in this browser.
(function () {
	'use strict';

	function init() {
		var wrapper = document.getElementById('bfQuickModeWrapper');
		var toolbar = document.getElementById('subhead-container');

		if (!wrapper) {
			return;
		}

		[
			['triggerScrollable', 'bfng-split--docked', 'bfng.quickmode.docked'],
			['scrollElementList', 'bfng-split--tree-scroll', 'bfng.quickmode.treeScroll']
		].forEach(function (setting) {
			var input = document.getElementById(setting[0]);

			input.checked = localStorage.getItem(setting[2]) === '1';
			wrapper.classList.toggle(setting[1], input.checked);

			input.addEventListener('change', function () {
				wrapper.classList.toggle(setting[1], input.checked);
				localStorage.setItem(setting[2], input.checked ? '1' : '0');
			});
		});

		if (toolbar) {
			new ResizeObserver(function () {
				wrapper.style.setProperty('--bfng-dock-top', toolbar.offsetHeight + 'px');
			}).observe(toolbar);
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
}());
