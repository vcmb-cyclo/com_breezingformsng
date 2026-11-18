(function () {
    'use strict';

    /**
     * Keeps the open or closed state of the sections of the record screen
     * while one goes from a record to the next: each record is a new page, so
     * without this every section opens again. The state lives in the session
     * storage of the tab, and nothing breaks without it.
     */
    var KEY = 'bfng.record.sections';

    function read() {
        try {
            var state = JSON.parse(window.sessionStorage.getItem(KEY) || '{}');

            return state && typeof state === 'object' ? state : {};
        } catch {
            return {};
        }
    }

    function write(state) {
        try {
            window.sessionStorage.setItem(KEY, JSON.stringify(state));
        } catch {
            // Storage blocked: the sections simply open as usual.
        }
    }

    function initialize() {
        var state = read();

        document.querySelectorAll('[id^="record-section-"].collapse').forEach(function (section) {
            if (state[section.id] === false) {
                // Closed by hand before: shown closed at once, without animation.
                section.classList.remove('show');

                var toggle = document.querySelector('[data-bs-target="#' + section.id + '"]');

                if (toggle) {
                    toggle.classList.add('collapsed');
                    toggle.setAttribute('aria-expanded', 'false');
                }
            }

            ['shown', 'hidden'].forEach(function (name) {
                section.addEventListener(name + '.bs.collapse', function (event) {
                    if (event.target !== section) {
                        return;
                    }

                    var current = read();
                    current[section.id] = name === 'shown';
                    write(current);
                });
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize);
    } else {
        initialize();
    }
}());
