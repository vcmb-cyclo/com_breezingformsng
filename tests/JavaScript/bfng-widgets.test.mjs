import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

// The runtime is a classic browser script: run it in a minimal window.
const source = readFileSync(new URL('../../media/com_breezingformsng/js/site/bfng-widgets.js', import.meta.url), 'utf8');
const window = { Joomla: {} };
window.window = window;
vm.runInNewContext(source, {
    window,
    document: { readyState: 'complete', currentScript: null, documentElement: { lang: 'en' }, querySelectorAll: () => [] },
    Intl,
    Array,
    Date,
    JSON,
    String,
    Object,
    Math,
    parseInt,
    Promise,
});
const raw = window.BFNGWidgets.helpers;
// Results come from another realm: compare them as plain JSON.
const plain = (value) => JSON.parse(JSON.stringify(value ?? null));
const h = Object.fromEntries(Object.entries(raw).map(([name, fn]) => [name, (...args) => {
    const result = fn(...args);
    return typeof result === 'object' ? plain(result) : result;
}]));

test('widgetFill lines are parsed into roles', () => {
    assert.deepEqual(h.parseFill('street=Street\n postcode = Zip \r\ncity=City\nbogus=X\ncountry=\nnoequal'), {
        street: 'Street',
        postcode: 'Zip',
        city: 'City',
    });
    assert.deepEqual(h.parseFill({ City: 'x', city: 'Town' }), { city: 'Town' });
    assert.deepEqual(h.parseFill(''), {});
    assert.deepEqual(h.parseFill(null), {});
});

test('date and time bounds follow the mode and resolve now', () => {
    const now = new Date(2026, 10, 18, 9, 5);

    assert.equal(h.resolveBound('now', 'datetime', now), '2026-11-18T09:05');
    assert.equal(h.resolveBound('NOW', 'date', now), '2026-11-18');
    assert.equal(h.resolveBound('now', 'time', now), '09:05');
    assert.equal(h.resolveBound('2026-12-01T10:00', 'datetime', now), '2026-12-01T10:00');
    assert.equal(h.resolveBound('2026-12-01', 'datetime', now), '');
    assert.equal(h.resolveBound('', 'date', now), '');
    assert.equal(h.resolveBound('08:30', 'time', now), '08:30');
});

test('the datetime config maps the mode to an input type, min, max and a step in seconds', () => {
    const now = new Date(2026, 0, 2, 3, 4);

    assert.deepEqual({ ...h.datetimeConfig({ mode: 'datetime', min: 'now', step: 15 }, now) }, {
        type: 'datetime-local', min: '2026-01-02T03:04', max: '', step: 900,
    });
    assert.deepEqual({ ...h.datetimeConfig({ mode: 'date', step: 15, max: '2027-01-01' }, now) }, {
        type: 'date', min: '', max: '2027-01-01', step: 0,
    });
    assert.equal(h.datetimeConfig({ mode: 'nope', step: 0 }, now).type, 'datetime-local');
    assert.equal(h.datetimeConfig({ mode: 'time', step: 5 }, now).step, 300);
});

test('the country list is localised, sorted, restricted and flagged on request', () => {
    const all = h.buildCountryList('fr', [], false);
    assert.equal(all.length, 249);
    assert.equal(all.find((c) => c.code === 'DE').name, 'Allemagne');
    assert.equal(all[0].flag, '');

    const some = h.buildCountryList('en', ['fr', 'BE', 'xx'], true);
    assert.deepEqual(some.map((c) => c.code), ['BE', 'FR']);
    assert.equal(some[1].flag, '\u{1F1EB}\u{1F1F7}');
    assert.equal(h.flagEmoji('1'), '');
});

test('BAN answers are normalised', () => {
    const json = {
        features: [
            { properties: { label: '8 Boulevard du Port 80000 Amiens', name: '8 Boulevard du Port', postcode: '80000', city: 'Amiens' } },
            { properties: { label: '', name: '', postcode: '', city: '' } },
            null,
        ],
    };

    assert.deepEqual(h.normalizeAddressResults('ban', json, []).map((r) => ({ ...r })), [{
        label: '8 Boulevard du Port 80000 Amiens',
        street: '8 Boulevard du Port',
        postcode: '80000',
        city: 'Amiens',
        country: 'France',
        countryCode: 'FR',
    }]);
    assert.deepEqual(h.normalizeAddressResults('ban', {}, []), []);
});

test('Photon answers are normalised and filtered by country', () => {
    const json = {
        features: [
            { properties: { name: 'Home', housenumber: '5', street: 'Rue Neuve', postcode: '1000', city: 'Bruxelles', country: 'Belgique', countrycode: 'BE' } },
            { properties: { name: 'Paris', country: 'France', countrycode: 'FR' } },
        ],
    };
    const all = h.normalizeAddressResults('photon', json, []);

    assert.equal(all.length, 2);
    assert.equal(all[0].street, '5 Rue Neuve');
    assert.equal(all[0].label, '5 Rue Neuve, 1000 Bruxelles, Belgique');
    assert.equal(all[0].countryCode, 'BE');
    assert.equal(all[1].street, 'Paris');
    assert.equal(all[1].label, 'Paris, France');
    assert.deepEqual(h.normalizeAddressResults('photon', json, ['fr']).map((r) => r.countryCode), ['FR']);
});

test('provider URLs only exist for a known provider', () => {
    assert.match(h.addressUrl('ban', 'a b', 'fr'), /^https:\/\/api-adresse\.data\.gouv\.fr\/search\/\?q=a%20b/);
    assert.match(h.addressUrl('photon', 'x', 'fr'), /photon\.komoot\.io\/api\/\?q=x&limit=6&lang=fr$/);
    assert.doesNotMatch(h.addressUrl('photon', 'x', 'hu'), /lang=/);
    assert.equal(h.addressUrl('', 'x', 'fr'), '');
});

test('the E.164 wrapper returns an empty value for empty or invalid numbers', () => {
    const fake = (number, valid) => ({ getNumber: () => number, isValidNumber: () => valid });

    assert.equal(h.e164Value(fake('+33612345678', true), true), '+33612345678');
    assert.equal(h.e164Value(fake('+33612345678', false), true), '');
    assert.equal(h.e164Value(fake('+33612345678', false), false), '+33612345678');
    assert.equal(h.e164Value(fake('', true), true), '');
    assert.equal(h.e164Value(fake('+0123', true), false), '');
    assert.equal(h.e164Value(null, true), '');
    assert.equal(h.e164Value({ getNumber() { throw new Error('x'); } }, false), '');
});

test('options and country lists are parsed defensively; text falls back to English', () => {
    assert.deepEqual(h.parseOptions('{bad'), {});
    assert.deepEqual(h.parseOptions('[1]'), {});
    assert.deepEqual({ ...h.parseOptions('{"a":1}') }, { a: 1 });
    assert.deepEqual(h.codeList('fr, BE ,fr,,x1'), ['fr', 'be']);
    assert.equal(h.text('COM_X', 'Fallback'), 'Fallback');
    window.Joomla.Text = { _: (key) => (key === 'COM_X' ? 'Translated' : key) };
    assert.equal(h.text('COM_X', 'Fallback'), 'Translated');
    assert.equal(h.text('COM_Y', 'Fallback'), 'Fallback');
});
