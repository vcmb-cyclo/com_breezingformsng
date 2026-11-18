(function () {
    'use strict';

    /**
     * Fills in the badge of the Audit tile of the Tools page: the audit scans
     * the tables, so it is asked for in the background and the page shows a
     * spinner until the number of issues is known.
     */
    function initialize() {
        var form = document.getElementById('bf-tools-audit-form');
        var badge = document.querySelector('[data-bf-audit-badge]');

        if (!form || !badge || !form.dataset.bfSummaryUrl) {
            return;
        }

        var show = function (css, html, label) {
            badge.className = 'bf-tile__badge badge ' + css;
            badge.innerHTML = html;
            badge.setAttribute('title', label);
        };
        var text = function (key) {
            return Joomla.Text._(key);
        };
        var visuallyHidden = function (label) {
            var span = document.createElement('span');
            span.className = 'visually-hidden';
            span.textContent = ' – ' + label;

            return span.outerHTML;
        };

        fetch(form.dataset.bfSummaryUrl, {
            method: 'POST',
            body: new FormData(form),
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (response) {
            return response.ok ? response.json() : Promise.reject(new Error(String(response.status)));
        }).then(function (payload) {
            var issues = payload && payload.data ? parseInt(payload.data.issues, 10) : NaN;

            if (isNaN(issues)) {
                throw new Error('invalid');
            }

            if (issues === 0) {
                var clean = text('COM_BREEZINGFORMSNG_TOOLS_AUDIT_CLEAN');
                show('bg-success', '<span class="icon-check" aria-hidden="true"></span>' + visuallyHidden(clean), clean);

                return;
            }

            var label = text(issues === 1 ? 'COM_BREEZINGFORMSNG_TOOLS_AUDIT_ISSUES_1' : 'COM_BREEZINGFORMSNG_TOOLS_AUDIT_ISSUES')
                .replace('%d', String(issues));
            show('bg-danger', String(issues) + visuallyHidden(label), label);
        }).catch(function () {
            var label = text('COM_BREEZINGFORMSNG_TOOLS_AUDIT_UNAVAILABLE');
            show('bg-secondary', '?' + visuallyHidden(label), label);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize);
    } else {
        initialize();
    }
}());
