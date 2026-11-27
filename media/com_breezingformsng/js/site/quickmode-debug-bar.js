/* Frontend debug bar: theme list, colour mode and technical names next to the fields, which can be moved and edited. */
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

    // The tag of a field: its type icon and technical name, with the facts of its table row below.
    const fieldTag = (row, id) => {
        const holder = document.createElement('span');
        const tag = document.createElement('span');
        const tooltip = document.createElement('span');
        const type = row.dataset.bfngType;

        holder.className = 'bfng-debug-field';
        tag.className = 'bfng-debug-field-name';
        tag.tabIndex = 0;

        if (type) {
            const icon = document.createElement('span');
            icon.className = `bfng bfng-debug-field-icon bfng-type-icon--bfElementType${type}`;
            icon.setAttribute('aria-hidden', 'true');
            tag.append(icon);
        }
        tag.append(row.dataset.bfngField);

        tooltip.className = 'bfng-debug-field-tooltip';
        tooltip.id = id;
        tooltip.setAttribute('role', 'tooltip');
        tag.setAttribute('aria-describedby', id);

        // Cells: technical name, label, type, required, validation.
        const headings = row.closest('table')?.querySelectorAll('thead th') ?? [];
        [...row.cells].forEach((cell, index) => {
            let value = cell.textContent.trim();
            if (index === 0 && Number(row.dataset.bfngId) > 0) {
                value += ` (#${row.dataset.bfngId})`;
            } else if (index === 2 && row.dataset.bfngTypeName) {
                value = `${row.dataset.bfngTypeName} (${value})`;
            }
            if (value === '' || !headings[index]) {
                return;
            }

            const line = document.createElement('span');
            const heading = document.createElement('strong');
            heading.textContent = `${headings[index].textContent} : `;
            line.append(heading, value);
            tooltip.append(line);
        });

        const bar = row.closest('.bfng-debug');
        if (bar?.dataset.bfngSaveUrl && row.dataset.bfngParent !== undefined && tooltip.hasChildNodes()) {
            const hint = document.createElement('em');
            hint.className = 'bfng-debug-field-move';
            hint.textContent = bar.dataset.bfngMoveHint;
            tooltip.append(hint);
            tag.draggable = true;
        }

        holder.append(tag);
        if (tooltip.hasChildNodes()) {
            holder.append(tooltip);
        }

        return holder;
    };

    // A click keeps the help tooltip open (some browsers do not focus a clicked button), Escape closes it.
    document.addEventListener('click', (event) => {
        event.target.closest('.bfng-debug__help, .bfng-debug-field-name')?.focus();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && event.target.matches('.bfng-debug__help, .bfng-debug-field-name')) {
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

    const make = (tag, className, text = '') => {
        const element = document.createElement(tag);
        element.className = className;
        element.textContent = text;

        return element;
    };

    // Moves and settings changed on the page and not yet saved, with the bar to save or cancel them.
    const pendingChanges = (bar, fields) => {
        const moves = [];
        const undo = [];
        // Edited field name: what is to be saved, and its settings before the first edit.
        const edits = new Map();
        let order = [];
        let panel = null;
        let saving = false;
        let saved = false;

        const show = (error = '') => {
            panel.querySelector('.bfng-debug-pending__count').textContent
                = bar.dataset.bfngPendingText.replace('%d', moves.length + edits.size);
            const message = panel.querySelector('.bfng-debug-pending__error');
            message.textContent = error;
            message.hidden = error === '';
        };

        const mark = (field, changed) => field.holder.classList.toggle('bfng-debug-field--changed', changed);

        const close = () => {
            panel?.remove();
            panel = null;
        };

        const cancel = () => {
            for (const { node, parent, next } of undo.reverse()) {
                parent.insertBefore(node, next);
            }
            if (moves.length) {
                fields.splice(0, fields.length, ...order);
            }
            for (const { field, before } of edits.values()) {
                field.edit = before;
            }
            fields.forEach((field) => mark(field, false));
            moves.length = 0;
            undo.length = 0;
            edits.clear();
            close();
        };

        // Posts all changes at once, then shows the form as stored.
        const save = async () => {
            if (saving) {
                return;
            }
            saving = true;
            show();
            let message = bar.dataset.bfngSaveFailed;
            try {
                const response = await fetch(bar.dataset.bfngSaveUrl, {
                    method: 'POST',
                    headers: { Accept: 'application/json' },
                    body: new URLSearchParams({
                        formId: bar.dataset.bfngForm,
                        moves: JSON.stringify(moves),
                        edits: JSON.stringify([...edits.values()].map(({ values }) => values)),
                        note: panel.querySelector('.bfng-debug-pending__note input').value,
                        language: bar.dataset.bfngLanguage,
                        revision: bar.dataset.bfngRevision,
                        [bar.dataset.bfngToken]: '1',
                    }),
                });
                const answer = await response.json();
                if (response.ok && answer.success) {
                    // Nothing left to lose: the page is reloaded without a warning.
                    saved = true;
                    window.location.reload();
                    return;
                }
                message = answer.message || message;
            } catch {
                // No readable answer: the general message.
            }
            saving = false;
            show(message);
        };

        const open = () => {
            if (panel) {
                return;
            }
            panel = make('div', 'bfng-debug-pending');
            const count = make('p', 'bfng-debug-pending__count');
            count.setAttribute('role', 'status');
            const note = make('label', 'bfng-debug-pending__note');
            const input = document.createElement('input');
            input.maxLength = 250;
            input.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    save();
                }
            });
            note.append(make('span', '', bar.dataset.bfngNoteLabel), input);
            const error = make('p', 'bfng-debug-pending__error');
            error.setAttribute('role', 'alert');
            const keep = make('button', 'bfng-debug-pending__save', bar.dataset.bfngSaveLabel);
            keep.type = 'button';
            keep.addEventListener('click', save);
            const drop = make('button', 'bfng-debug-pending__cancel', bar.dataset.bfngCancelLabel);
            drop.type = 'button';
            drop.addEventListener('click', () => {
                if (!saving) {
                    cancel();
                }
            });
            panel.append(count, note, keep, drop, error);
            document.body.append(panel);
        };

        // Leaving the page would lose them.
        window.addEventListener('beforeunload', (event) => {
            if (panel && !saved) {
                event.preventDefault();
                event.returnValue = '';
            }
        });

        return {
            // Puts a field before or after another one, on the page only.
            move(field, target, position) {
                if (saving) {
                    return;
                }
                const siblings = fields.filter((other) => other.parent === field.parent);
                const step = siblings.indexOf(target) - siblings.indexOf(field);
                // Already there.
                if (step === (position === 'before' ? 1 : -1)) {
                    return;
                }
                if (!moves.length) {
                    order = [...fields];
                }
                open();
                undo.push({ node: field.zone, parent: field.zone.parentNode, next: field.zone.nextSibling });
                target.zone[position](field.zone);
                fields.splice(fields.indexOf(field), 1);
                fields.splice(fields.indexOf(target) + (position === 'after' ? 1 : 0), 0, field);
                moves.push({ field: field.name, target: target.name, position });
                mark(field, true);
                show();
            },
            // Keeps the settings given in the window of a field.
            edit(field, settings) {
                if (saving) {
                    return;
                }
                const before = edits.get(field.name)?.before ?? field.edit;
                const same = (a, b) => JSON.stringify(a) === JSON.stringify(b);
                field.edit = {
                    ...field.edit,
                    label: settings.label,
                    hint: settings.hint,
                    required: settings.required === '' ? field.edit.required : settings.required === '1',
                    limits: (field.edit.limits ?? []).map((limit) => ({ ...limit, value: settings.limits[limit.key] })),
                };
                if (same(field.edit, before)) {
                    edits.delete(field.name);
                } else {
                    edits.set(field.name, { field, before, values: { field: field.name, ...settings } });
                }
                const moved = moves.some((move) => move.field === field.name);
                mark(field, moved || edits.has(field.name));
                if (!moves.length && !edits.size) {
                    close();
                    return;
                }
                open();
                show();
            },
        };
    };

    // Drag a tag onto another field of the same page or section, or use Alt and an arrow key.
    const allowMoves = (bar, fields, pending) => {
        let dragged = null;
        let mark = null;
        const form = bar.nextElementSibling;

        const clearMark = () => {
            mark?.zone.removeAttribute('data-bfng-drop');
            mark = null;
        };

        const dropPlace = (event) => {
            const place = dragged && fields.find((field) => field !== dragged && field.movable
                && field.parent === dragged.parent && field.zone.contains(event.target));
            if (!place) {
                return null;
            }
            const box = place.zone.getBoundingClientRect();

            return { field: place, zone: place.zone,
                position: event.clientY > box.top + box.height / 2 ? 'after' : 'before' };
        };

        for (const field of fields) {
            const tag = field.holder.querySelector('.bfng-debug-field-name');
            // Without a block of its own, a field cannot be shown at its new place.
            field.movable = tag.draggable && field.zone !== field.holder;
            if (!field.movable) {
                tag.draggable = false;
                field.holder.querySelector('.bfng-debug-field-move')?.remove();
                continue;
            }

            tag.addEventListener('dragstart', (event) => {
                dragged = field;
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', field.name);
                // No tooltip over the drop places; some browsers cancel a drag if the page changes at its start.
                window.setTimeout(() => form.classList.toggle('bfng-debug-dragging', dragged !== null));
            });
            tag.addEventListener('dragend', () => {
                dragged = null;
                form.classList.remove('bfng-debug-dragging');
                // The focus would keep the tooltip open over the next fields.
                tag.blur();
                clearMark();
            });
            tag.addEventListener('keydown', (event) => {
                const step = { ArrowUp: -1, ArrowDown: 1 }[event.key];
                if (!step || !event.altKey) {
                    return;
                }
                event.preventDefault();
                const siblings = fields.filter((other) => other.movable && other.parent === field.parent);
                const neighbour = siblings[siblings.indexOf(field) + step];
                if (neighbour) {
                    pending.move(field, neighbour, step < 0 ? 'before' : 'after');
                    // Moving its block took the focus away.
                    tag.focus();
                }
            });
        }

        form.addEventListener('dragover', (event) => {
            const place = dropPlace(event);
            clearMark();
            if (place) {
                event.preventDefault();
                place.zone.dataset.bfngDrop = place.position;
                mark = place;
            }
        });
        form.addEventListener('drop', (event) => {
            const place = dropPlace(event);
            clearMark();
            if (place) {
                event.preventDefault();
                // The moved block takes the tag along: no dragend would reach it everywhere.
                const field = dragged;
                dragged = null;
                form.classList.remove('bfng-debug-dragging');
                field.holder.querySelector('.bfng-debug-field-name').blur();
                pending.move(field, place.field, place.position);
            }
        });
    };

    // Window to change the texts of a field, whether it is required and its limits.
    const openWindow = (bar, field, texts, pending, keyboard) => {
        const { name, edit: values } = field;
        const dialog = make('dialog', 'bfng-debug-window');
        const form = make('form', 'bfng-debug-window__form');
        const title = make('h2', 'bfng-debug-window__title', `${texts.title} : ${name}`);
        title.id = `bfng-debug-window-${bar.dataset.bfngForm}`;
        dialog.setAttribute('aria-labelledby', title.id);
        form.append(title);
        if (texts.language) {
            form.append(make('p', 'bfng-debug-window__note', texts.language));
        }

        const row = (text, control, placeholder) => {
            const label = make('label', 'bfng-debug-window__field');
            control.placeholder = placeholder;
            label.append(make('span', '', text), control);
            form.append(label);

            return control;
        };
        const label = row(texts.label, document.createElement('input'), values.labelDefault);
        label.maxLength = 255;
        label.value = values.label;
        label.required = !texts.language;
        const hint = row(texts.hint, document.createElement('textarea'), values.hintDefault);
        hint.rows = 4;
        hint.value = values.hint;

        const check = make('label', 'bfng-debug-window__check');
        const required = document.createElement('input');
        required.type = 'checkbox';
        required.checked = values.required;
        required.disabled = !values.requirable;
        check.append(required, make('span', '', texts.required));
        form.append(check);
        if (!values.requirable) {
            form.append(make('p', 'bfng-debug-window__note', texts.locked));
        }

        // Length, bounds of a number or of a date, as the type of the field has them.
        const limits = (values.limits ?? []).map((limit) => {
            const control = document.createElement('input');
            const format = { date: '2026-12-31', time: '18:00', datetime: '2026-12-31T18:00' }[limit.input];
            if (!format) {
                control.type = 'number';
                control.step = 'any';
            }
            control.name = limit.key;
            row(texts[limit.name], control, format ? `${format} / now` : '');
            control.value = limit.value;

            return control;
        });
        if ((values.limits ?? []).some((limit) => ['date', 'time', 'datetime'].includes(limit.input))) {
            form.append(make('p', 'bfng-debug-window__note', texts.date));
        }

        const buttons = make('div', 'bfng-debug-window__buttons');
        const apply = make('button', 'bfng-debug-window__save', texts.apply);
        const cancel = make('button', 'bfng-debug-window__cancel', texts.cancel);
        cancel.type = 'button';
        cancel.addEventListener('click', () => dialog.close());
        buttons.append(apply, cancel);
        form.append(buttons);

        // Kept on the page until the bar saves it with the other changes.
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            pending.edit(field, {
                label: label.value,
                hint: hint.value,
                // Empty: the setting stays as it is.
                required: values.requirable ? String(Number(required.checked)) : '',
                limits: Object.fromEntries(limits.map((control) => [control.name, control.value])),
            });
            dialog.close();
        });
        dialog.addEventListener('close', () => {
            dialog.remove();
            if (keyboard) {
                field.holder.querySelector('.bfng-debug-field-name').focus();
            }
        });
        dialog.append(form);
        document.body.append(dialog);
        dialog.showModal();
    };

    // A click on a tag, or Enter, opens the window of its field.
    const allowEdits = (bar, fields, pending) => {
        if (!pending || !bar.dataset.bfngEditTexts) {
            return;
        }
        const texts = JSON.parse(bar.dataset.bfngEditTexts);

        for (const field of fields) {
            if (!field.edit || !('label' in field.edit)) {
                continue;
            }
            const tag = field.holder.querySelector('.bfng-debug-field-name');
            const tooltip = field.holder.querySelector('.bfng-debug-field-tooltip');
            if (!tooltip) {
                continue;
            }
            const hint = document.createElement('em');
            hint.textContent = texts.open;
            tooltip.append(hint);
            tag.setAttribute('role', 'button');
            const open = (event) => {
                // Inside a form or a label, the tag must not act on them.
                event.preventDefault();
                tag.blur();
                openWindow(bar, field, texts, pending, event.type === 'keydown');
            };
            tag.addEventListener('click', open);
            tag.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                    open(event);
                }
            });
        }
    };

    const showFieldNames = (bar) => {
        const formWrapper = bar.nextElementSibling;
        if (!formWrapper?.matches('.bfFormDiv')) {
            return;
        }
        const fields = [];
        for (const [index, row] of bar.querySelectorAll('tr[data-bfng-field]').entries()) {
            const name = row.dataset.bfngField;
            const input = formWrapper.querySelector(`[name="${CSS.escape(`ff_nm_${name}[]`)}"]`);

            const wrapper = input?.closest('.bfElemWrap');

            // Widgets such as the phone field keep their value in a hidden input inside the field wrapper.
            if (!input || (input.type === 'hidden' && !wrapper)) {
                continue;
            }

            // The label of the question: the first label of a group of choices belongs to a choice.
            let target = wrapper?.querySelector('legend, [id^="bfLabel"]') ?? wrapper?.querySelector('label') ?? input;
            // A legend stays on top of its fieldset: the tag goes above the whole group.
            if (target.matches('legend')) {
                target = target.closest('fieldset') ?? target;
            }
            const holder = fieldTag(row, `${bar.id || 'bfng-debug'}-field-${index}`);
            target.before(holder);
            const edit = row.dataset.bfngEdit ? JSON.parse(row.dataset.bfngEdit) : null;
            fields.push({ name, parent: row.dataset.bfngParent, edit, holder, zone: wrapper ?? holder.parentElement });
        }

        // Without a wrapper of its own, a field is dropped on its tag.
        for (const field of fields) {
            if (fields.some((other) => other !== field && field.zone.contains(other.holder))) {
                field.zone = field.holder;
            }
        }
        let pending = null;
        if (bar.dataset.bfngSaveUrl) {
            pending = pendingChanges(bar, fields);
            allowMoves(bar, fields, pending);
        }
        allowEdits(bar, fields, pending);
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
