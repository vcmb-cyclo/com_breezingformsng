/* Frontend debug bar: theme list, colour mode and technical names next to the fields. Nothing is saved. */
(() => {
    const changeOption = (event) => {
        const control = event.target;
        let parameter;
        let value;
        if (control.matches('select[data-bfng-debug-theme]')) {
            parameter = 'bf_debug_theme';
            value = control.value;
        } else if (control.matches('input[data-bfng-debug-names-url]')) {
            parameter = 'bf_debug_names';
            value = control.dataset.bfngDebugNamesUrl;
        } else {
            return;
        }

        if (!value || !URL.canParse(value, window.location.href)) {
            return;
        }

        const target = new URL(value, window.location.href);
        if (!['http:', 'https:'].includes(target.protocol) || target.origin !== window.location.origin
            || target.pathname !== window.location.pathname) {
            return;
        }

        const query = new URLSearchParams(window.location.search);
        if (target.searchParams.has(parameter)) {
            query.set(parameter, target.searchParams.get(parameter));
        } else {
            query.delete(parameter);
        }
        window.location.search = query.toString();
    };

    // A click keeps the help tooltip open (some browsers do not focus a clicked button), Escape closes it.
    document.addEventListener('click', (event) => {
        event.target.closest('.bfng-debug__help')?.focus();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && event.target.matches('.bfng-debug__help')) {
            event.target.blur();
        }
    });

    // Force light or dark: Bootstrap attribute, native controls and prefers-color-scheme rules.
    const applyColorMode = (mode) => {
        document.documentElement.setAttribute('data-bs-theme', mode);
        document.documentElement.style.colorScheme = mode;

        const update = (rules) => {
            for (const rule of rules) {
                if (rule instanceof CSSMediaRule && rule.media.mediaText.includes('prefers-color-scheme')) {
                    rule.media.mediaText = rule.media.mediaText.replace(
                        /\(\s*prefers-color-scheme\s*:\s*(dark|light)\s*\)/g,
                        (match, scheme) => (scheme === mode ? '(min-width: 0px)' : '(max-width: 0px)'),
                    );
                }
                if (rule.cssRules) {
                    update(rule.cssRules);
                }
            }
        };

        for (const sheet of document.styleSheets) {
            try {
                update(sheet.cssRules);
            } catch {
                // A cross-origin stylesheet cannot be read.
            }
        }
    };

    const showFieldNames = (bar) => {
        const formWrapper = bar.nextElementSibling;
        if (!formWrapper?.matches('.bfFormDiv')) {
            return;
        }
        for (const row of bar.querySelectorAll('tr[data-bfng-field]')) {
            const name = row.dataset.bfngField;
            const input = formWrapper.querySelector(`[name="${CSS.escape(`ff_nm_${name}[]`)}"]`);

            const wrapper = input?.closest('.bfElemWrap');

            // Widgets such as the phone field keep their value in a hidden input inside the field wrapper.
            if (!input || (input.type === 'hidden' && !wrapper)) {
                continue;
            }

            const target = wrapper?.querySelector('label') ?? input;
            const tag = document.createElement('span');
            tag.className = 'bfng-debug-field-name';
            tag.textContent = name;
            target.before(tag);
        }
    };

    const start = () => {
        for (const bar of document.querySelectorAll('.bfng-debug')) {
            bar.addEventListener('change', changeOption);
            if (bar.dataset.bfngDebugNames !== '0') {
                showFieldNames(bar);
            }

            if (bar.dataset.bfngDebugColor !== 'auto') {
                applyColorMode(bar.dataset.bfngDebugColor);
            }
        }
    };

    if (document.readyState === 'complete') {
        start();
    } else {
        window.addEventListener('load', start, { once: true });
    }
})();
