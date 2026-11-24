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

        return fetch('index.php?option=com_breezingformsng&task=about.display&view=about&layout=audit', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (response) {
            return response.ok ? response.text() : '';
        }).then(function (html) {
            var page = new DOMParser().parseFromString(html, 'text/html');
            var fresh = page.getElementById('bf-audit-orphan-references');

            // Not the About page (an expired session, an error): the block is left as it is.
            if (!page.getElementById('adminForm')) {
                return;
            }

            if (!fresh) {
                block.remove();

                return;
            }

            // The checks are open by default: a check the administrator closed stays closed.
            fresh.querySelectorAll('details[data-bf-orphan-check]').forEach(function (details) {
                details.open = open.indexOf(details.dataset.bfOrphanCheck) !== -1;
            });
            block.replaceWith(fresh);
        });
    }

    // The result is shown right above the orphan references (not at the top of
    // the page, far from the clicked button), and brought into view.
    function showOrphanMessage(type, text) {
        var container = document.getElementById('bf-audit-orphan-messages');

        if (!container) {
            var messages = {};

            messages[type] = [text];
            Joomla.renderMessages(messages);

            return;
        }

        var alert = document.createElement('div');

        alert.className = 'alert alert-' + (type === 'message' ? 'success' : (type === 'error' ? 'danger' : type))
            + ' alert-dismissible fade show mb-0';
        alert.setAttribute('role', 'alert');
        alert.appendChild(document.createTextNode(text));

        var close = document.createElement('button');

        close.type = 'button';
        close.className = 'btn-close';
        close.setAttribute('data-bs-dismiss', 'alert');
        close.setAttribute('aria-label', Joomla.Text._('JCLOSE'));
        alert.appendChild(close);
        container.replaceChildren(alert);
        container.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    // A button that posts its form after a confirmation (uninstalling an extension).
    document.addEventListener('submit', function (event) {
        var message = event.submitter && event.submitter.dataset.bfSubmitConfirm;

        if (message && !window.confirm(message)) {
            event.preventDefault();
        }
    });

    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-bf-orphan-task]');

        if (!button || button.disabled) {
            return;
        }

        event.preventDefault();

        var targetSelect = button.dataset.bfOrphanTargetSelect
            ? document.getElementById(button.dataset.bfOrphanTargetSelect)
            : null;

        // Transfer towards a chosen field: nothing to do until one is selected.
        if (targetSelect && (!targetSelect.value || targetSelect.value === '0')) {
            targetSelect.focus();
            showOrphanMessage('warning', Joomla.Text._('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_TRANSFER_SELECT_FIRST'));

            return;
        }

        if (!window.confirm(button.dataset.bfConfirm || '')) {
            return;
        }

        var params = new URLSearchParams({ orphan_element_id: button.dataset.bfOrphanElement || '0' });

        if (targetSelect) {
            params.set('orphan_target_id', targetSelect.value);
        }

        // The confirmation above is the explicit acknowledgement of an irreversible deletion.
        if (button.dataset.bfOrphanAcknowledge === '1') {
            params.set('orphan_acknowledged', '1');
        }

        if (button.dataset.bfOrphanAll === '1') {
            params.set('orphan_all', '1');
        }

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
                showOrphanMessage('error', result.json.message || Joomla.Text._('JERROR_AN_ERROR_HAS_OCCURRED'));

                return;
            }

            return refreshOrphans().then(function () {
                showOrphanMessage(result.json.deleted === 0 ? 'warning' : 'message', result.json.message);
            });
        }).catch(function () {
            button.disabled = false;
            showOrphanMessage('error', Joomla.Text._('JERROR_AN_ERROR_HAS_OCCURRED'));
        });
    });

    // Reassignment of the values of a missing element to another element: the values that
    // would be moved are listed with a checkbox, and nothing is changed until confirmed.
    // The script is loaded in the page head, before the dialog exists: it is looked up when
    // used, and its events are delegated from the document.
    var reassignContext = null;

    function reassignDialogElement() {
        return document.getElementById('bf-orphan-reassign-dialog');
    }

    function postJson(task, params) {
        params.append(Joomla.getOptions('csrf.token'), '1');

        return fetch('index.php?option=com_breezingformsng&task=' + task + '&format=json', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: params.toString()
        }).then(function (response) {
            return response.json().then(function (json) {
                return { ok: response.ok && json.success, json: json };
            });
        });
    }

    function reassignNote(dialog, text) {
        dialog.querySelector('[data-bf-reassign-note]').textContent = text;
    }

    function reassignBoxes(dialog) {
        return dialog.querySelectorAll('[data-bf-reassign-rows] input[type="checkbox"]:not(:disabled)');
    }

    function openReassignDialog(dialog, data, element, target, targetLabel) {
        var rows = dialog.querySelector('[data-bf-reassign-rows]');
        var labels = dialog.dataset;
        var classes = { move: 'text-success', duplicate: 'text-warning', taken: 'text-body-secondary' };

        rows.replaceChildren();
        (data.rows || []).forEach(function (row) {
            var tr = document.createElement('tr');
            var check = document.createElement('input');
            var box = document.createElement('td');
            var record = document.createElement('td');
            var link = document.createElement('a');
            var value = document.createElement('td');
            var current = document.createElement('td');
            var action = document.createElement('td');

            check.type = 'checkbox';
            check.className = 'form-check-input';
            check.value = String(row.id);
            check.checked = row.action !== 'taken';
            check.disabled = row.action === 'taken';
            box.appendChild(check);
            link.href = labels.bfRecordUrl + encodeURIComponent(row.record);
            link.target = '_blank';
            link.rel = 'noopener';
            link.textContent = '#' + row.record;
            record.appendChild(link);
            value.className = 'text-break';
            value.textContent = row.value;
            current.className = 'text-break';
            current.textContent = row.current || '';
            action.className = classes[row.action] || '';
            action.textContent = labels['bfLabel' + row.action.charAt(0).toUpperCase() + row.action.slice(1)] || '';
            tr.append(box, record, value, current, action);
            rows.appendChild(tr);
        });
        dialog.querySelector('[data-bf-reassign-summary]').textContent =
            (data.from || '') + ' #' + element + ' \u2192 ' + targetLabel;
        dialog.querySelector('[data-bf-reassign-all]').checked = true;
        reassignNote(dialog, data.truncated ? labels.bfLabelTruncated.replace('%d', String((data.rows || []).length)) : '');

        var selectable = reassignBoxes(dialog).length > 0;

        if (!selectable) {
            reassignNote(dialog, labels.bfLabelEmpty);
        }

        dialog.querySelector('[data-bf-reassign-apply]').disabled = !selectable;
        reassignContext = { element: element, target: target };
        dialog.showModal();
    }

    document.addEventListener('change', function (event) {
        var dialog = reassignDialogElement();

        if (dialog && event.target.matches('[data-bf-reassign-all]') && dialog.contains(event.target)) {
            reassignBoxes(dialog).forEach(function (box) {
                box.checked = event.target.checked;
            });
        }
    });

    document.addEventListener('click', function (event) {
        var dialog = reassignDialogElement();

        if (!dialog || !dialog.contains(event.target)) {
            return;
        }

        if (event.target.closest('[data-bf-reassign-cancel]')) {
            dialog.close();

            return;
        }

        var apply = event.target.closest('[data-bf-reassign-apply]');

        if (!apply || apply.disabled || !reassignContext) {
            return;
        }

        var params = new URLSearchParams({
            orphan_element_id: reassignContext.element,
            orphan_target_id: reassignContext.target
        });
        var checked = Array.prototype.filter.call(reassignBoxes(dialog), function (box) {
            return box.checked;
        });

        if (checked.length === 0) {
            reassignNote(dialog, dialog.dataset.bfLabelNoneSelected);

            return;
        }

        checked.forEach(function (box) {
            params.append('orphan_values[]', box.value);
        });
        apply.disabled = true;
        postJson('about.reassignOrphanValuesToElement', params).then(function (result) {
            if (!result.ok) {
                apply.disabled = false;
                reassignNote(dialog, result.json.message || Joomla.Text._('JERROR_AN_ERROR_HAS_OCCURRED'));

                return;
            }

            dialog.close();

            return refreshOrphans().then(function () {
                showOrphanMessage('message', result.json.message);
            });
        }).catch(function () {
            apply.disabled = false;
            reassignNote(dialog, Joomla.Text._('JERROR_AN_ERROR_HAS_OCCURRED'));
        });
    });

    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-bf-orphan-preview]');
        var dialog = reassignDialogElement();

        if (!button || button.disabled || !dialog) {
            return;
        }

        event.preventDefault();

        var select = document.getElementById(button.dataset.bfOrphanTargetSelect);

        if (!select || !select.value || select.value === '0') {
            if (select) {
                select.focus();
            }

            showOrphanMessage('warning', Joomla.Text._('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_TRANSFER_SELECT_FIRST'));

            return;
        }

        var element = button.dataset.bfOrphanElement || '0';
        var target = select.value;
        var targetLabel = select.options[select.selectedIndex].textContent.trim();

        button.disabled = true;
        postJson('about.previewOrphanReassignment', new URLSearchParams({
            orphan_element_id: element,
            orphan_target_id: target
        })).then(function (result) {
            button.disabled = false;

            if (!result.ok) {
                showOrphanMessage('error', result.json.message || Joomla.Text._('JERROR_AN_ERROR_HAS_OCCURRED'));

                return;
            }

            openReassignDialog(dialog, result.json.data || {}, element, target, targetLabel);
        }).catch(function () {
            button.disabled = false;
            showOrphanMessage('error', Joomla.Text._('JERROR_AN_ERROR_HAS_OCCURRED'));
        });
    });

    function initialiseGithubStars() {
        var button = document.querySelector('[data-bf-github-stars]');
        var counter = button ? button.querySelector('[data-bf-github-stars-count]') : null;
        var badge = button ? button.querySelector('.bf-about-github-stars') : null;

        if (!button || !counter || !badge || typeof window.fetch !== 'function') {
            return;
        }

        window.fetch('https://api.github.com/repos/' + button.dataset.bfGithubStars, { headers: { Accept: 'application/vnd.github+json' } })
            .then(function (response) { return response.ok ? response.json() : null; })
            .then(function (repository) {
                if (repository && Number.isInteger(repository.stargazers_count)) {
                    counter.textContent = new Intl.NumberFormat(document.documentElement.lang || undefined).format(repository.stargazers_count);
                    badge.hidden = false;
                }
            })
            .catch(function () {});
    }

    function initialiseAbout() {
        initialiseGithubStars();
        initialiseTableInventorySorting();
        initializeEasterEgg();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialiseAbout);
    } else {
        initialiseAbout();
    }
}());
