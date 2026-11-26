/* Frontend debug bar: theme list, colour mode and technical names next to the fields. Nothing is saved. */
(() => {
    document.addEventListener('change', (event) => {
        if (event.target.matches('select[data-bfng-debug-theme]') && event.target.value) {
            window.location.assign(event.target.value);
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
        for (const row of bar.querySelectorAll('tr[data-bfng-field]')) {
            const name = row.dataset.bfngField;
            const input = document.getElementsByName(`ff_nm_${name}[]`)[0];

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
            showFieldNames(bar);

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
