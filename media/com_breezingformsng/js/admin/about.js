(function () {
    'use strict';

    function initializeEasterEgg() {
        var hotspot = document.querySelector('[data-bf-easter-egg-hotspot]');
        var overlay = document.querySelector('[data-bf-easter-egg]');
        var image = overlay ? overlay.querySelector('[data-bf-easter-egg-image]') : null;
        var closeButton = overlay ? overlay.querySelector('[data-bf-easter-egg-close]') : null;
        var clickCount = 0;
        var resetTimer = 0;

        if (!hotspot || !overlay || !image || !closeButton) {
            return;
        }

        var close = function () {
            overlay.hidden = true;
            overlay.setAttribute('aria-hidden', 'true');
            image.removeAttribute('src');
            document.body.classList.remove('bf-easter-egg-open');
            hotspot.focus();
        };

        var open = function () {
            var source = String(image.dataset.src || '');
            image.src = source + (source.indexOf('?') === -1 ? '?' : '&') + 'play=' + Date.now();
            overlay.hidden = false;
            overlay.setAttribute('aria-hidden', 'false');
            document.body.classList.add('bf-easter-egg-open');
            closeButton.focus();
        };

        hotspot.addEventListener('click', function () {
            clickCount += 1;
            window.clearTimeout(resetTimer);
            resetTimer = window.setTimeout(function () {
                clickCount = 0;
            }, 3000);

            if (clickCount < 5) {
                return;
            }

            clickCount = 0;
            window.clearTimeout(resetTimer);
            open();
        });

        closeButton.addEventListener('click', close);
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !overlay.hidden) {
                close();
            }
        });
    }

    function initialiseTableInventorySorting() {
        document.querySelectorAll('[data-bf-sortable-table]').forEach(function (table) {
            var buttons = table.querySelectorAll('[data-bf-table-sort]');
            var body = table.tBodies[0];

            if (!body) {
                return;
            }

            buttons.forEach(function (button, index) {
                button.addEventListener('click', function () {
                    var direction = button.parentElement.getAttribute('aria-sort') === 'ascending' ? -1 : 1;
                    var numeric = button.dataset.bfTableSort === 'number';
                    var rows = Array.from(body.rows).map(function (row, position) {
                        var cell = row.cells[index];
                        var value = cell ? (cell.dataset.bfSortValue || cell.textContent.trim()) : '';

                        return { row: row, position: position, value: value };
                    });

                    rows.sort(function (left, right) {
                        var comparison;

                        if (numeric) {
                            comparison = Number(left.value) - Number(right.value);
                        } else {
                            comparison = left.value.localeCompare(right.value, undefined, { sensitivity: 'base' });
                        }

                        return comparison === 0 ? left.position - right.position : comparison * direction;
                    });

                    rows.forEach(function (item) {
                        body.appendChild(item.row);
                    });
                    buttons.forEach(function (otherButton) {
                        otherButton.parentElement.setAttribute('aria-sort', otherButton === button && direction === 1 ? 'ascending' : otherButton === button ? 'descending' : 'none');
                    });
                });
            });
        });
    }

    document.addEventListener('change', function (event) {
        var target = event.target;

        if (!(target instanceof HTMLInputElement)) {
            return;
        }

        var group = target.dataset.bfSelectAll || target.dataset.bfSelectItem || '';

        if (group === '') {
            return;
        }

        var masterSelector = '[data-bf-select-all="' + group + '"]';
        var itemSelector = '[data-bf-select-item="' + group + '"]';

        if (target.matches(masterSelector)) {
            document.querySelectorAll(itemSelector).forEach(function (checkbox) {
                checkbox.checked = target.checked;
            });
        }

        var masters = document.querySelectorAll(masterSelector);
        var items = document.querySelectorAll(itemSelector);
        var checkedCount = document.querySelectorAll(itemSelector + ':checked').length;

        masters.forEach(function (master) {
            master.checked = items.length > 0 && checkedCount === items.length;
            master.indeterminate = checkedCount > 0 && checkedCount < items.length;
        });
    });

    function initialiseAbout() {
        initialiseTableInventorySorting();
        initializeEasterEgg();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialiseAbout);
    } else {
        initialiseAbout();
    }
}());
