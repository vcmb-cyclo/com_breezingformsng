/**
 * BreezingForms NG - field widgets of the QuickMode forms.
 *
 * One script for the phone (intl-tel-input), date and time, country, address
 * suggestions and drag-and-drop upload widgets. The PHP renderers mark a
 * control with data-bfng-widget and data-bfng-options (JSON); this script
 * upgrades it. A widget that cannot start leaves the plain input untouched.
 *
 * Settings come from Joomla.getOptions('com_breezingformsng.widgets'):
 * { base: '/media/com_breezingformsng/', lang: 'fr', dateLocale: 'fr-FR' }.
 */
(function () {
    'use strict';

    var READY = 'data-bfng-ready';
    var ISO_COUNTRIES = 'AD AE AF AG AI AL AM AO AQ AR AS AT AU AW AX AZ BA BB BD BE BF BG BH BI BJ BL BM BN BO BQ BR BS BT BV BW BY BZ CA CC CD CF CG CH CI CK CL CM CN CO CR CU CV CW CX CY CZ DE DJ DK DM DO DZ EC EE EG EH ER ES ET FI FJ FK FM FO FR GA GB GD GE GF GG GH GI GL GM GN GP GQ GR GS GT GU GW GY HK HM HN HR HT HU ID IE IL IM IN IO IQ IR IS IT JE JM JO JP KE KG KH KI KM KN KP KR KW KY KZ LA LB LC LI LK LR LS LT LU LV LY MA MC MD ME MF MG MH MK ML MM MN MO MP MQ MR MS MT MU MV MW MX MY MZ NA NC NE NF NG NI NL NO NP NR NU NZ OM PA PE PF PG PH PK PL PM PN PR PS PT PW PY QA RE RO RS RU RW SA SB SC SD SE SG SH SI SJ SK SL SM SN SO SR SS ST SV SX SY SZ TC TD TF TG TH TJ TK TL TM TN TO TR TT TV TW TZ UA UG UM US UY UZ VA VC VE VG VI VN VU WF WS YE YT ZA ZM ZW'.split(' ');
    var PHOTON_LANGUAGES = ['en', 'de', 'fr', 'it'];
    var SCRIPT_SOURCE = document.currentScript && document.currentScript.src ? document.currentScript.src : '';
    var uid = 0;

    /* ---------- pure helpers (exported for the tests) ---------- */

    function text(key, fallback) {
        try {
            var value = window.Joomla && window.Joomla.Text && typeof window.Joomla.Text._ === 'function'
                ? window.Joomla.Text._(key, fallback) : '';
            return value && value !== key ? value : fallback;
        } catch {
            return fallback;
        }
    }

    function settings() {
        var value;
        try {
            value = window.Joomla && typeof window.Joomla.getOptions === 'function'
                ? window.Joomla.getOptions('com_breezingformsng.widgets') : null;
        } catch {
            value = null;
        }
        value = value && typeof value === 'object' ? value : {};
        var base = typeof value.base === 'string' && value.base !== '' ? value.base : '';
        if (base === '' && SCRIPT_SOURCE.indexOf('/js/site/') > 0) {
            base = SCRIPT_SOURCE.slice(0, SCRIPT_SOURCE.indexOf('/js/site/') + 1);
        }
        if (base === '') {
            base = '/media/com_breezingformsng/';
        }
        if (base.slice(-1) !== '/') {
            base += '/';
        }
        var lang = typeof value.lang === 'string' && value.lang !== '' ? value.lang : (document.documentElement.lang || 'en').slice(0, 2);
        return {
            base: base,
            lang: lang.toLowerCase(),
            dateLocale: typeof value.dateLocale === 'string' && value.dateLocale !== '' ? value.dateLocale : lang,
        };
    }

    function parseOptions(raw) {
        try {
            var value = JSON.parse(raw || '{}');
            return value && typeof value === 'object' && !Array.isArray(value) ? value : {};
        } catch {
            return {};
        }
    }

    /** Lower case ISO codes of a list given as an array or as a comma separated string. */
    function codeList(value) {
        var items = Array.isArray(value) ? value : String(value || '').split(',');
        var seen = {};
        return items.map(function (item) { return String(item).trim().toLowerCase(); })
            .filter(function (item) {
                if (!/^[a-z]{2}$/.test(item) || seen[item]) {
                    return false;
                }
                seen[item] = true;
                return true;
            });
    }

    /** widgetFill: "role=FieldName" lines (or the already parsed object) to { role: FieldName }. */
    function parseFill(value) {
        var roles = ['street', 'postcode', 'city', 'country'];
        var result = {};
        var pairs = [];
        if (value && typeof value === 'object') {
            Object.keys(value).forEach(function (role) { pairs.push([role, value[role]]); });
        } else {
            String(value || '').split(/\r\n|\r|\n/).forEach(function (line) {
                var cut = line.indexOf('=');
                if (cut > 0) {
                    pairs.push([line.slice(0, cut), line.slice(cut + 1)]);
                }
            });
        }
        pairs.forEach(function (pair) {
            var role = String(pair[0]).trim().toLowerCase();
            var name = String(pair[1] === null || pair[1] === undefined ? '' : pair[1]).trim();
            if (roles.indexOf(role) >= 0 && name !== '') {
                result[role] = name;
            }
        });
        return result;
    }

    function pad(number, length) {
        return String(number).padStart(length || 2, '0');
    }

    function formatLocal(date, mode) {
        var day = pad(date.getFullYear(), 4) + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate());
        var time = pad(date.getHours()) + ':' + pad(date.getMinutes());
        return mode === 'date' ? day : (mode === 'time' ? time : day + 'T' + time);
    }

    /** A min/max bound for the mode: "now" becomes the browser's current time, a wrong shape becomes "". */
    function resolveBound(value, mode, now) {
        var raw = String(value === null || value === undefined ? '' : value).trim();
        if (raw === '') {
            return '';
        }
        if (raw.toLowerCase() === 'now') {
            // Without a date, now does not define a bound across midnight.
            return mode === 'time' ? '' : formatLocal(now instanceof Date ? now : new Date(), mode);
        }
        var shapes = {
            datetime: /^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/,
            date: /^\d{4}-\d{2}-\d{2}$/,
            time: /^\d{2}:\d{2}$/,
        };
        return shapes[mode] && shapes[mode].test(raw) ? raw : '';
    }

    function datetimeConfig(options, now) {
        var mode = ['datetime', 'date', 'time'].indexOf(options.mode) >= 0 ? options.mode : 'datetime';
        var step = parseInt(options.step, 10);
        var min = resolveBound(options.min, mode, now);
        if (mode === 'datetime' && step > 0 && String(options.min).trim().toLowerCase() === 'now') {
            var start = new Date(now);
            var minutes = start.getHours() * 60 + start.getMinutes();
            var nextSlot = Math.ceil(minutes / step) * step;
            start.setHours(0, nextSlot, 0, 0);
            min = formatLocal(start, mode);
        }
        return {
            type: mode === 'datetime' ? 'datetime-local' : mode,
            min: min,
            max: resolveBound(options.max, mode, now),
            step: mode !== 'date' && step > 0 ? step * 60 : 0,
        };
    }

    function flagEmoji(code) {
        var upper = String(code).toUpperCase();
        if (!/^[A-Z]{2}$/.test(upper)) {
            return '';
        }
        return String.fromCodePoint(0x1F1E6 + upper.charCodeAt(0) - 65, 0x1F1E6 + upper.charCodeAt(1) - 65);
    }

    /** [{ code, name, flag }] sorted by localised name, restricted to allowed (lower or upper case) when given. */
    function buildCountryList(locale, allowed, showFlags) {
        var names = null;
        try {
            names = new Intl.DisplayNames([locale], { type: 'region' });
        } catch {
            names = null;
        }
        var allow = codeList(allowed);
        var codes = ISO_COUNTRIES.filter(function (code) {
            return allow.length === 0 || allow.indexOf(code.toLowerCase()) >= 0;
        });
        var collator;
        try {
            collator = new Intl.Collator([locale]);
        } catch {
            collator = new Intl.Collator();
        }
        return codes.map(function (code) {
            var name;
            try {
                name = names ? names.of(code) || '' : '';
            } catch {
                name = '';
            }
            return { code: code, name: name || code, flag: showFlags ? flagEmoji(code) : '' };
        }).sort(function (a, b) { return collator.compare(a.name, b.name); });
    }

    function clean(value) {
        return value === null || value === undefined ? '' : String(value).trim();
    }

    function joinParts(parts) {
        var seen = {};
        return parts.map(clean).filter(function (part) {
            if (part === '' || seen[part.toLowerCase()]) {
                return false;
            }
            seen[part.toLowerCase()] = true;
            return true;
        }).join(', ');
    }

    /** Suggestions of the BAN (api-adresse.data.gouv.fr) or Photon GeoJSON answer, in one shape. */
    function normalizeAddressResults(provider, json, countries) {
        var features = json && Array.isArray(json.features) ? json.features : [];
        var allow = codeList(countries);
        var results = [];
        features.forEach(function (feature) {
            var p = feature && feature.properties && typeof feature.properties === 'object' ? feature.properties : null;
            if (!p) {
                return;
            }
            var item;
            if (provider === 'ban') {
                item = {
                    label: clean(p.label),
                    street: clean(p.name || p.street),
                    postcode: clean(p.postcode),
                    city: clean(p.city),
                    country: 'France',
                    countryCode: 'FR',
                };
            } else {
                var street = clean(p.street) !== ''
                    ? joinParts([[clean(p.housenumber), clean(p.street)].join(' ')]) : clean(p.name);
                item = {
                    label: '',
                    street: street,
                    postcode: clean(p.postcode),
                    city: clean(p.city || p.locality || p.county),
                    country: clean(p.country),
                    countryCode: clean(p.countrycode).toUpperCase(),
                };
                item.label = joinParts([clean(p.street) !== '' ? street : clean(p.name), item.postcode + ' ' + item.city, item.country]);
            }
            if (item.street === '' && item.postcode === '' && item.city === '' && clean(p.name) === '') {
                return;
            }
            if (item.label === '') {
                item.label = joinParts([item.street, item.postcode + ' ' + item.city, item.country]);
            }
            if (item.label === '' || (allow.length > 0 && allow.indexOf(item.countryCode.toLowerCase()) < 0)) {
                return;
            }
            results.push(item);
        });
        return results;
    }

    function addressUrl(provider, query, lang) {
        var q = encodeURIComponent(query);
        if (provider === 'ban') {
            return 'https://api-adresse.data.gouv.fr/search/?q=' + q + '&limit=6&autocomplete=1';
        }
        if (provider === 'photon') {
            return 'https://photon.komoot.io/api/?q=' + q + '&limit=6' + (PHOTON_LANGUAGES.indexOf(lang) >= 0 ? '&lang=' + lang : '');
        }
        return '';
    }

    /** The E.164 value to post: '' when empty or, with validation on, when the number is not valid. */
    function e164Value(iti, validate) {
        try {
            if (!iti) {
                return '';
            }
            if (validate && iti.isValidNumber() !== true) {
                return '';
            }
            var number = String(iti.getNumber() || '');
            return /^\+[1-9]\d{7,14}$/.test(number) ? number : '';
        } catch {
            return '';
        }
    }

    /* ---------- DOM helpers ---------- */

    function element(tag, className, content) {
        var node = document.createElement(tag);
        if (className) {
            node.className = className;
        }
        if (content !== undefined) {
            node.textContent = content;
        }
        return node;
    }

    function fire(node, type) {
        node.dispatchEvent(new Event(type, { bubbles: true }));
    }

    function nextId(prefix) {
        uid += 1;
        return prefix + uid;
    }

    var loading = {};

    function loadOnce(key, start) {
        if (!loading[key]) {
            loading[key] = start().catch(function (error) {
                delete loading[key];
                throw error;
            });
        }
        return loading[key];
    }

    function loadScript(url) {
        return new Promise(function (resolve, reject) {
            var script = document.createElement('script');
            script.src = url;
            script.async = true;
            script.onload = resolve;
            script.onerror = function () { reject(new Error('script')); };
            document.head.appendChild(script);
        });
    }

    function loadStyle(url) {
        return new Promise(function (resolve, reject) {
            var link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = url;
            link.onload = resolve;
            link.onerror = function () { reject(new Error('style')); };
            document.head.appendChild(link);
        });
    }

    /* ---------- phone ---------- */

    function loadPhoneLibrary(config) {
        var dir = config.base + 'libraries/intl-tel-input/';
        return loadOnce('phone', function () {
            return Promise.all([
                loadStyle(dir + 'css/intlTelInput.min.css').catch(function () { return null; }),
                typeof window.intlTelInput === 'function' ? Promise.resolve() : loadScript(dir + 'js/intlTelInputWithUtils.min.js'),
            ]).then(function () {
                if (typeof window.intlTelInput !== 'function') {
                    throw new Error('intl-tel-input');
                }
            });
        });
    }

    function loadPhoneTranslations(config) {
        var supported = ['en', 'fr', 'de', 'it', 'es', 'hu', 'nl', 'tr'];
        if (supported.indexOf(config.lang) < 0 || config.lang === 'en') {
            return Promise.resolve(null);
        }
        return loadOnce('phone-lang-' + config.lang, function () {
            return import(config.base + 'libraries/intl-tel-input/js/locale/' + config.lang + '.js')
                .then(function (module) { return module.default || null; });
        }).catch(function () { return null; });
    }

    function initPhone(input, options, config) {
        return Promise.all([loadPhoneLibrary(config), loadPhoneTranslations(config)]).then(function (loaded) {
            var countries = codeList(options.countries);
            var initial = codeList(options.defaultCountry)[0] || '';
            var validate = options.validate !== false;
            var settingsForIti = {
                separateDialCode: options.dialCode !== false,
                showFlags: options.flags !== false,
                strictMode: true,
                countryNameLocale: config.lang,
                initialCountry: initial,
            };
            if (countries.length > 0) {
                settingsForIti.onlyCountries = countries;
                if (countries.indexOf(initial) < 0) {
                    settingsForIti.initialCountry = countries[0];
                }
            }
            if (loaded[1]) {
                settingsForIti.uiTranslations = loaded[1];
            }

            var name = input.getAttribute('name') || '';
            var id = input.id;
            // A stored value is restored by the server (value / data-bfng-value) or by the hydration of an edited record.
            var start = input.value || input.getAttribute('data-bfng-value') || '';
            var iti = window.intlTelInput(input, settingsForIti);

            // The visible input has no name: the hidden mirror carries name, id and the E.164 number.
            var hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = name;
            input.removeAttribute('name');
            if (id) {
                hidden.id = id;
                input.id = id + '_tel';
                Array.prototype.forEach.call(document.querySelectorAll('label[for]'), function (label) {
                    if (label.getAttribute('for') === id) {
                        label.setAttribute('for', input.id);
                    }
                });
            }
            var anchor = input.closest('.iti') || input;
            anchor.parentNode.insertBefore(hidden, anchor.nextSibling);

            var error = element('div', 'bfng-widget-error');
            error.id = nextId('bfng-phone-error-');
            error.hidden = true;
            anchor.parentNode.insertBefore(error, hidden.nextSibling);

            var sync = function (report) {
                hidden.value = e164Value(iti, validate);
                var bad = report && validate && input.value.trim() !== '' && hidden.value === '';
                var message = bad ? text('COM_BREEZINGFORMSNG_WIDGET_PHONE_INVALID', 'Enter a valid phone number.') : '';
                input.classList.toggle('is-invalid', bad);
                input.setAttribute('aria-invalid', bad ? 'true' : 'false');
                var descriptions = (input.getAttribute('aria-describedby') || '').split(/\s+/)
                    .filter(function (id) { return id && id !== error.id; });
                if (bad) {
                    descriptions.push(error.id);
                }
                if (descriptions.length) {
                    input.setAttribute('aria-describedby', descriptions.join(' '));
                } else {
                    input.removeAttribute('aria-describedby');
                }
                error.textContent = message;
                error.hidden = !bad;
                announcing = true;
                fire(hidden, 'change');
                announcing = false;
            };
            var announcing = false;

            // The hydration of an edited record writes the stored number into the field that holds the name.
            hidden.addEventListener('change', function () {
                var stored = hidden.value.trim();
                if (announcing || stored === '' || stored === e164Value(iti, validate)) {
                    return;
                }
                iti.setNumber(stored);
                sync(false);
            });

            input.addEventListener('input', function () { sync(false); });
            input.addEventListener('blur', function () { sync(true); });
            input.addEventListener('countrychange', function () { sync(false); });
            if (start && start.trim() !== '') {
                iti.setNumber(start);
            }
            hidden.value = e164Value(iti, validate);
        });
    }

    /* ---------- datetime ---------- */

    function initDatetime(input, options) {
        var config = datetimeConfig(options, new Date());
        input.type = config.type;
        if (config.min !== '') {
            input.min = config.min;
        }
        if (config.max !== '') {
            input.max = config.max;
        }
        if (config.step > 0) {
            input.step = String(config.step);
        }
    }

    /* ---------- country ---------- */

    function initCountry(select, options, config) {
        if (select.tagName !== 'SELECT') {
            return;
        }
        var wanted = (select.getAttribute('data-value') || select.value || '').toUpperCase();
        var fallback = codeList(options.defaultCountry)[0] || '';
        var list = buildCountryList(config.dateLocale, options.countries, options.flags !== false);
        var blank = null;
        Array.prototype.forEach.call(select.options, function (option) {
            if (option.value === '' && blank === null) {
                blank = option;
            }
        });
        select.textContent = '';
        select.appendChild(blank || new Option('', ''));
        list.forEach(function (country) {
            select.appendChild(new Option((country.flag ? country.flag + ' ' : '') + country.name, country.code));
        });
        var known = list.map(function (country) { return country.code; });
        if (wanted !== '' && known.indexOf(wanted) >= 0) {
            select.value = wanted;
        } else if (wanted === '' && fallback !== '' && known.indexOf(fallback.toUpperCase()) >= 0) {
            select.value = fallback.toUpperCase();
        } else {
            select.value = '';
        }
    }

    /* ---------- address ---------- */

    function fieldsNamed(name) {
        return Array.prototype.slice.call(document.getElementsByName('ff_nm_' + name + '[]'));
    }

    function setFieldValue(field, value) {
        if (field.tagName === 'SELECT') {
            var match = null;
            Array.prototype.forEach.call(field.options, function (option) {
                if (match === null && (option.value.toUpperCase() === value.toUpperCase() || option.text.trim().toLowerCase() === value.toLowerCase())) {
                    match = option;
                }
            });
            if (match === null) {
                return;
            }
            field.value = match.value;
        } else {
            field.value = value;
        }
        fire(field, 'input');
        fire(field, 'change');
    }

    function fillFields(fill, item) {
        Object.keys(fill).forEach(function (role) {
            fieldsNamed(fill[role]).forEach(function (field) {
                var value = item[role] || '';
                if (role === 'country' && field.tagName === 'SELECT' && item.countryCode) {
                    value = item.countryCode;
                }
                if (value !== '') {
                    setFieldValue(field, value);
                }
            });
        });
    }

    function initAddress(input, options, config) {
        var provider = options.provider === 'ban' || options.provider === 'photon' ? options.provider : '';
        if (provider === '') {
            return;
        }
        var minChars = Math.max(1, parseInt(options.minChars, 10) || 3);
        var fill = parseFill(options.fill);
        var wrap = element('div', 'bfng-address');
        input.parentNode.insertBefore(wrap, input);
        wrap.appendChild(input);
        var list = element('ul', 'bfng-address-list');
        list.id = nextId('bfng-address-list-');
        list.setAttribute('role', 'listbox');
        list.setAttribute('aria-label', text('COM_BREEZINGFORMSNG_WIDGET_ADDRESS_LISTBOX_LABEL', 'Address suggestions'));
        list.hidden = true;
        wrap.appendChild(list);

        input.setAttribute('role', 'combobox');
        input.setAttribute('aria-autocomplete', 'list');
        input.setAttribute('aria-haspopup', 'listbox');
        input.setAttribute('aria-expanded', 'false');
        input.setAttribute('aria-controls', list.id);
        input.setAttribute('autocomplete', 'off');

        var items = [];
        var active = -1;
        var timer = 0;
        var controller = null;

        function setActive(index) {
            active = index;
            Array.prototype.forEach.call(list.children, function (node, position) {
                var on = position === index;
                node.setAttribute('aria-selected', on ? 'true' : 'false');
                node.classList.toggle('is-active', on);
            });
            if (index >= 0 && list.children[index]) {
                input.setAttribute('aria-activedescendant', list.children[index].id);
                if (typeof list.children[index].scrollIntoView === 'function') {
                    list.children[index].scrollIntoView({ block: 'nearest' });
                }
            } else {
                input.removeAttribute('aria-activedescendant');
            }
        }

        function close() {
            window.clearTimeout(timer);
            if (controller) {
                controller.abort();
                controller = null;
            }
            list.hidden = true;
            list.textContent = '';
            items = [];
            input.setAttribute('aria-expanded', 'false');
            setActive(-1);
        }

        function choose(index) {
            var item = items[index];
            if (!item) {
                return;
            }
            close();
            input.value = item.label;
            fire(input, 'change');
            fillFields(fill, item);
        }

        function render(results) {
            list.textContent = '';
            items = results;
            if (results.length === 0) {
                var empty = element('li', 'bfng-address-empty', text('COM_BREEZINGFORMSNG_WIDGET_ADDRESS_NO_RESULT', 'No suggestion'));
                empty.setAttribute('role', 'option');
                empty.setAttribute('aria-disabled', 'true');
                empty.id = nextId('bfng-address-option-');
                list.appendChild(empty);
            }
            results.forEach(function (item, index) {
                var option = element('li', 'bfng-address-option', item.label);
                option.id = nextId('bfng-address-option-');
                option.setAttribute('role', 'option');
                option.setAttribute('aria-selected', 'false');
                option.addEventListener('mousedown', function (event) {
                    event.preventDefault();
                    choose(index);
                });
                list.appendChild(option);
            });
            list.hidden = false;
            input.setAttribute('aria-expanded', 'true');
            setActive(-1);
        }

        function search() {
            var query = input.value.trim();
            if (query.length < minChars) {
                close();
                return;
            }
            if (controller) {
                controller.abort();
            }
            controller = new AbortController();
            var current = controller;
            fetch(addressUrl(provider, query, config.lang), { signal: current.signal, headers: { Accept: 'application/json' } })
                .then(function (response) { return response.ok ? response.json() : Promise.reject(new Error('http')); })
                .then(function (json) {
                    if (controller === current) {
                        render(normalizeAddressResults(provider, json, options.countries));
                    }
                })
                .catch(function () {
                    if (controller === current && !current.signal.aborted) {
                        close();
                    }
                });
        }

        input.addEventListener('input', function () {
            window.clearTimeout(timer);
            timer = window.setTimeout(search, 300);
        });
        input.addEventListener('keydown', function (event) {
            var open = !list.hidden;
            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                if (!open || items.length === 0) {
                    return;
                }
                event.preventDefault();
                var step = event.key === 'ArrowDown' ? 1 : -1;
                setActive((active + step + items.length) % items.length);
            } else if (event.key === 'Enter' && open && active >= 0) {
                event.preventDefault();
                choose(active);
            } else if (event.key === 'Escape' && open) {
                event.preventDefault();
                close();
            }
        });
        input.addEventListener('blur', function () {
            window.setTimeout(close, 100);
        });
    }

    /* ---------- dropzone ---------- */

    function fileSize(bytes) {
        if (bytes >= 1048576) {
            return (bytes / 1048576).toFixed(1) + ' MB';
        }
        return Math.max(1, Math.round(bytes / 1024)) + ' KB';
    }

    function initDropzone(input) {
        if (input.type !== 'file') {
            return;
        }
        var wrap = element('div', 'bfng-dropzone');
        input.parentNode.insertBefore(wrap, input);
        wrap.appendChild(input);
        input.classList.add('bfng-dropzone-input');

        var zone = element('div', 'bfng-dropzone-area');
        zone.tabIndex = 0;
        zone.setAttribute('role', 'button');
        zone.setAttribute('aria-label', text('COM_BREEZINGFORMSNG_WIDGET_DROPZONE_BROWSE', 'Browse files'));
        zone.appendChild(element('span', 'bfng-dropzone-icon'));
        zone.lastChild.setAttribute('aria-hidden', 'true');
        zone.appendChild(element('span', 'bfng-dropzone-text', text('COM_BREEZINGFORMSNG_WIDGET_DROPZONE_PROMPT', 'Drop files here or browse')));
        wrap.appendChild(zone);

        var list = element('ul', 'bfng-dropzone-files');
        list.setAttribute('aria-label', text('COM_BREEZINGFORMSNG_WIDGET_DROPZONE_FILES', 'Selected files'));
        list.hidden = true;
        wrap.appendChild(list);

        function refresh() {
            list.textContent = '';
            Array.prototype.forEach.call(input.files || [], function (file) {
                var row = element('li', 'bfng-dropzone-file');
                row.appendChild(element('span', 'bfng-dropzone-name', file.name));
                row.appendChild(element('span', 'bfng-dropzone-size', fileSize(file.size)));
                list.appendChild(row);
            });
            list.hidden = list.children.length === 0;
        }

        function assign(files) {
            var transfer = new DataTransfer();
            var chosen = Array.prototype.slice.call(files);
            if (!input.multiple) {
                chosen = chosen.slice(0, 1);
            }
            chosen.forEach(function (file) { transfer.items.add(file); });
            input.files = transfer.files;
            fire(input, 'change');
        }

        zone.addEventListener('click', function () { input.click(); });
        zone.addEventListener('keydown', function (event) {
            if (event.target === zone && (event.key === 'Enter' || event.key === ' ')) {
                event.preventDefault();
                input.click();
            }
        });
        ['dragenter', 'dragover'].forEach(function (type) {
            zone.addEventListener(type, function (event) {
                event.preventDefault();
                zone.classList.add('is-dragover');
            });
        });
        zone.addEventListener('dragleave', function () { zone.classList.remove('is-dragover'); });
        zone.addEventListener('drop', function (event) {
            event.preventDefault();
            zone.classList.remove('is-dragover');
            if (event.dataTransfer && event.dataTransfer.files && event.dataTransfer.files.length > 0) {
                assign(event.dataTransfer.files);
            }
        });
        input.addEventListener('change', refresh);
        refresh();
    }

    /* ---------- bootstrap ---------- */

    function upgrade(node, config) {
        var kind = node.getAttribute('data-bfng-widget');
        var options = parseOptions(node.getAttribute('data-bfng-options'));
        if (kind === 'phone') {
            node.setAttribute(READY, 'pending');
            initPhone(node, options, config).then(function () {
                node.setAttribute(READY, '1');
            }).catch(function () {
                node.setAttribute(READY, 'failed');
            });
            return;
        }
        if (kind === 'datetime') {
            initDatetime(node, options);
        } else if (kind === 'country') {
            initCountry(node, options, config);
        } else if (kind === 'address') {
            initAddress(node, options, config);
        } else if (kind === 'dropzone') {
            initDropzone(node);
        }
        node.setAttribute(READY, '1');
    }

    function init(root) {
        var config = settings();
        var scope = root && typeof root.querySelectorAll === 'function' ? root : document;
        Array.prototype.forEach.call(scope.querySelectorAll('[data-bfng-widget]'), function (node) {
            if (node.hasAttribute(READY)) {
                return;
            }
            try {
                upgrade(node, config);
            } catch {
                node.setAttribute(READY, 'failed');
            }
        });
    }

    window.BFNGWidgets = {
        init: init,
        helpers: {
            text: text,
            parseOptions: parseOptions,
            codeList: codeList,
            parseFill: parseFill,
            resolveBound: resolveBound,
            datetimeConfig: datetimeConfig,
            flagEmoji: flagEmoji,
            buildCountryList: buildCountryList,
            normalizeAddressResults: normalizeAddressResults,
            addressUrl: addressUrl,
            e164Value: e164Value,
        },
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { init(document); });
    } else {
        init(document);
    }
}());
