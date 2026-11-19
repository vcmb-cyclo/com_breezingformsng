(function () {
    'use strict';

    /**
     * The sample forms of the Tools page are ticked, then installed together:
     * the button stays off until one is ticked and shows how many are, a short
     * text says what is ticked (or what to do), and the confirmation names the forms.
     * A click anywhere on a row ticks it: the label of the row covers it.
     *
     * The "check all" box is the one of Joomla (Joomla.checkAll): it fires the
     * change event of the standard boxchecked field, and the highlight of a
     * ticked row is Atum's own row-selected class.
     * Without JavaScript the form is posted as it is.
     */
    function initialize() {
        var form = document.getElementById('bf-tools-sample-form');
        var button = document.getElementById('bf-tools-sample-install');

        if (!form || !button) {
            return;
        }

        var count = document.getElementById('bf-tools-sample-count');
        var summary = document.getElementById('bf-tools-sample-summary');
        var text = function (key, fallback) {
            return window.Joomla && Joomla.Text && Joomla.Text._(key) ? Joomla.Text._(key) : fallback;
        };
        var checkAll = document.getElementById('bf-tools-sample-all');
        var boxes = Array.prototype.slice.call(form.querySelectorAll('input[name="samples[]"]:not(:disabled)'));
        var ticked = function () {
            return boxes.filter(function (box) {
                return box.checked;
            });
        };
        var refresh = function () {
            var total = ticked().length;

            button.disabled = total === 0;

            if (count) {
                count.textContent = total > 0 ? '(' + total + ')' : '';
            }

            if (summary) {
                summary.textContent = total === 0
                    ? text('COM_BREEZINGFORMSNG_TOOLS_SAMPLE_HELP_NONE', '')
                    : (total === 1
                        ? text('COM_BREEZINGFORMSNG_TOOLS_SAMPLE_SELECTED_ONE', '1')
                        : text('COM_BREEZINGFORMSNG_TOOLS_SAMPLE_SELECTED_MANY', '%d').replace('%d', String(total)));
            }

            boxes.forEach(function (box) {
                var row = box.closest('[data-sample]');

                if (row) {
                    row.classList.toggle('row-selected', box.checked);
                }
            });

            if (checkAll) {
                checkAll.checked = total > 0 && total === boxes.length;
                checkAll.indeterminate = total > 0 && total < boxes.length;
            }
        };

        boxes.forEach(function (box) {
            box.addEventListener('change', refresh);
        });

        // Joomla.checkAll sets the boxes without an event of their own, then fires this one.
        if (form.elements.boxchecked) {
            form.elements.boxchecked.addEventListener('change', refresh);
        }

        form.addEventListener('submit', function (event) {
            var labels = ticked().map(function (box) {
                return box.getAttribute('data-label') || box.value;
            });

            if (labels.length === 0 || !window.confirm((form.getAttribute('data-confirm') || '%s').replace('%s', labels.join(', ')))) {
                event.preventDefault();
            }
        });
        refresh();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize);
    } else {
        initialize();
    }
}());
