(function () {
    'use strict';

    /**
     * Manage a package: each kind of item is a list of checkboxes. A ticked box is an item of
     * the package; a click changes that row only. Against the saved state, a row shows "to
     * attach" or "to detach", each list a delta (+2 -1) and the bar below the lists the total,
     * in an aria-live zone. "All" and "None" act on the visible rows of their list, so the
     * filter of a long list never loses the rows it hides. The form is posted as it is: the
     * names of the fields and the server side do not change.
     */
    function initialize() {
        var form = document.querySelector('[data-bf-lists]');
        var summary = document.getElementById('bf-package-summary');
        var reset = document.getElementById('bf-package-reset');

        if (!form) {
            return;
        }

        var text = function (key) {
            return window.Joomla && Joomla.Text && Joomla.Text._(key) ? Joomla.Text._(key) : '';
        };
        var sections = Array.prototype.slice.call(form.querySelectorAll('[data-bf-section]'));
        var boxesOf = function (section) {
            return Array.prototype.slice.call(section.querySelectorAll('input[type="checkbox"]'));
        };
        var visible = function (box) {
            var row = box.closest('[data-bf-row]');

            return !row || !row.classList.contains('d-none');
        };
        var refresh = function () {
            var attached = 0;
            var detached = 0;

            sections.forEach(function (section) {
                var boxes = boxesOf(section);
                var toAttach = 0;
                var toDetach = 0;
                var ticked = 0;

                boxes.forEach(function (box) {
                    var saved = box.getAttribute('data-bf-saved') === '1';
                    var row = box.closest('[data-bf-row]');
                    var attach = box.checked && !saved;
                    var detach = !box.checked && saved;

                    ticked += box.checked ? 1 : 0;
                    toAttach += attach ? 1 : 0;
                    toDetach += detach ? 1 : 0;

                    if (row) {
                        var attachFlag = row.querySelector('[data-bf-flag="attach"]');
                        var detachFlag = row.querySelector('[data-bf-flag="detach"]');

                        row.classList.toggle('row-selected', box.checked);

                        if (attachFlag) {
                            attachFlag.classList.toggle('d-none', !attach);
                        }

                        if (detachFlag) {
                            detachFlag.classList.toggle('d-none', !detach);
                        }
                    }
                });

                var count = section.querySelector('[data-bf-count]');
                var delta = section.querySelector('[data-bf-delta]');

                if (count) {
                    count.textContent = ticked + ' / ' + boxes.length;
                }

                if (delta) {
                    delta.textContent = toAttach + toDetach === 0 ? '' : '+' + toAttach + ' −' + toDetach;
                }

                attached += toAttach;
                detached += toDetach;
            });

            if (summary) {
                summary.textContent = attached + detached === 0
                    ? text('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_UPDATE_NO_CHANGES')
                    : text('COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_UPDATE_PENDING_CHANGES')
                        .replace('%1$d', String(attached))
                        .replace('%2$d', String(detached));
            }

            if (reset) {
                reset.hidden = attached + detached === 0;
            }
        };

        sections.forEach(function (section) {
            var filter = section.querySelector('[data-bf-filter]');

            section.addEventListener('change', refresh);

            section.addEventListener('click', function (event) {
                var all = event.target.closest('[data-bf-all]');
                var none = event.target.closest('[data-bf-none]');

                if (!all && !none) {
                    return;
                }

                boxesOf(section).forEach(function (box) {
                    if (visible(box)) {
                        box.checked = Boolean(all);
                    }
                });
                refresh();
            });

            if (filter) {
                filter.addEventListener('input', function () {
                    var needle = filter.value.trim().toLowerCase();

                    Array.prototype.forEach.call(section.querySelectorAll('[data-bf-row]'), function (row) {
                        var title = (row.getAttribute('data-bf-search') || row.querySelector('[data-bf-title]').textContent).toLowerCase();

                        row.classList.toggle('d-none', needle !== '' && title.indexOf(needle) === -1);
                    });
                });

                // Enter filters, it does not submit the form (the export would start).
                filter.addEventListener('keydown', function (event) {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                    }
                });
            }
        });

        if (reset) {
            reset.addEventListener('click', function () {
                sections.forEach(function (section) {
                    boxesOf(section).forEach(function (box) {
                        box.checked = box.getAttribute('data-bf-saved') === '1';
                    });
                });
                refresh();
            });
        }

        refresh();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize);
    } else {
        initialize();
    }
}());
