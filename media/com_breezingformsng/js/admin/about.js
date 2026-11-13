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

    // Repairs of the orphan rows of the audit: confirmed, sent without reloading
    // the page, which is updated with the answer.
    // The list shows the first missing parents only: after a repair, the block is read
    // again from the page (whose report the repair has just refreshed), so the next
    // ones take the place of the one deleted. The checks left open stay open.
    function refreshOrphans() {
        var block = document.getElementById('bf-audit-orphan-references');

        if (!block) {
            return Promise.resolve();
        }

        var open = Array.prototype.map.call(
            block.querySelectorAll('details[open][data-bf-orphan-check]'),
            function (details) {
                return details.dataset.bfOrphanCheck;
            }
        );

        return fetch('index.php?option=com_breezingformsng&task=about.display&view=about', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (response) {
            return response.text();
        }).then(function (html) {
            var fresh = new DOMParser().parseFromString(html, 'text/html').getElementById('bf-audit-orphan-references');

            if (!fresh) {
                block.remove();

                return;
            }

            open.forEach(function (check) {
                var details = fresh.querySelector('details[data-bf-orphan-check="' + check + '"]');

                if (details) {
                    details.open = true;
                }
            });
            block.replaceWith(fresh);
        });
    }

    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-bf-orphan-task]');

        if (!button || button.disabled) {
            return;
        }

        event.preventDefault();

        if (!window.confirm(button.dataset.bfConfirm || '')) {
            return;
        }

        var params = new URLSearchParams({ orphan_element_id: button.dataset.bfOrphanElement || '0' });

        params.append(Joomla.getOptions('csrf.token'), '1');
        button.disabled = true;

        fetch('index.php?option=com_breezingformsng&task=' + button.dataset.bfOrphanTask + '&format=json', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: params.toString()
        }).then(function (response) {
            return response.json().then(function (json) {
                return { ok: response.ok && json.success, json: json };
            });
        }).then(function (result) {
            if (!result.ok) {
                button.disabled = false;
                Joomla.renderMessages({ error: [result.json.message || Joomla.Text._('JERROR_AN_ERROR_HAS_OCCURRED')] });

                return;
            }

            Joomla.renderMessages({ message: [result.json.message] });
            return refreshOrphans();
        }).catch(function () {
            button.disabled = false;
            Joomla.renderMessages({ error: [Joomla.Text._('JERROR_AN_ERROR_HAS_OCCURRED')] });
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
