(function () {
    'use strict';

    /**
     * The sample forms of the Tools page are ticked, then installed together:
     * the button stays off until one is ticked, and the confirmation names them.
     * Without JavaScript the form is posted as it is.
     */
    function initialize() {
        var form = document.getElementById('bf-tools-sample-form');
        var button = document.getElementById('bf-tools-sample-install');

        if (!form || !button) {
            return;
        }

        var boxes = Array.prototype.slice.call(form.querySelectorAll('input[name="samples[]"]:not(:disabled)'));
        var ticked = function () {
            return boxes.filter(function (box) {
                return box.checked;
            });
        };
        var refresh = function () {
            button.disabled = ticked().length === 0;
        };

        boxes.forEach(function (box) {
            box.addEventListener('change', refresh);
        });
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
