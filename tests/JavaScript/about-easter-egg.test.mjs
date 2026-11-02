import fs from 'node:fs';
import vm from 'node:vm';
import assert from 'node:assert/strict';

const source = fs.readFileSync(new URL('../../media/com_breezingformsng/js/admin/about.js', import.meta.url), 'utf8');
const template = fs.readFileSync(new URL('../../administrator/components/com_breezingformsng/tmpl/about/default.php', import.meta.url), 'utf8');
assert.match(template, /<button[\s\S]*?type="button"[\s\S]*?data-bf-easter-egg-hotspot/, 'logo hotspot is a non-submit button');
assert.ok(!source.includes("document.querySelector('.page-title')"), 'page title does not trigger animation');
const start = source.indexOf('function initializeEasterEgg()');
assert.ok(start >= 0, 'initializeEasterEgg exists');
const next = source.indexOf('\n    function ', start + 1);
const fn = source.slice(start, next < 0 ? source.lastIndexOf('initializeEasterEgg();') : next);
const handlers = new Map();
function element(name) {
  return { hidden: true, dataset: {}, attributes: {}, focused: false,
    addEventListener(type, handler) { handlers.set(name + ':' + type, handler); },
    setAttribute(key, value) { this.attributes[key] = value; },
    removeAttribute(key) { delete this[key]; delete this.attributes[key]; },
    focus() { this.focused = true; }
  };
}
const hotspot = element('hotspot'), overlay = element('overlay');
const image = element('image'), close = element('close');
image.dataset.src = '/media/example.webp';
overlay.querySelector = selector => selector.includes('image') ? image : close;
const classes = new Set();
const timers = new Map(); let timerId = 0; let now = 100;
const document = {
  querySelector: selector => selector === '[data-bf-easter-egg-hotspot]' ? hotspot : selector === '[data-bf-easter-egg]' ? overlay : null,
  body: { classList: { add: value => classes.add(value), remove: value => classes.delete(value) } },
  addEventListener: (type, handler) => handlers.set('document:' + type, handler)
};
vm.runInNewContext(fn + '\ninitializeEasterEgg();', { document,
  window: { setTimeout: callback => { timers.set(++timerId, callback); return timerId; }, clearTimeout: id => timers.delete(id) },
  Date: { now: () => ++now }
});
const click = () => handlers.get('hotspot:click')({target: hotspot});
for (let n = 0; n < 4; n++) click();
assert.equal(overlay.hidden, true, 'no open before fifth click');
click();
assert.equal(overlay.hidden, false, 'opens at fifth click');
assert.equal(close.focused, true, 'close receives focus');
assert.equal(timers.size, 0, 'no automatic close timer');
const firstSource = image.src;
handlers.get('close:click')();
assert.equal(overlay.hidden, true, 'X closes overlay');
assert.equal(image.src, undefined, 'closing clears image source');
assert.equal(classes.size, 0, 'closing restores body scrolling');
assert.equal(hotspot.focused, true, 'closing restores focus to the logo button');
for (let n = 0; n < 4; n++) click();
assert.equal(overlay.hidden, true, 'counter resets after opening');
click();
assert.notEqual(image.src, firstSource, 'reopening uses fresh playback URL');
handlers.get('document:keydown')({key: 'Escape'});
assert.equal(overlay.hidden, true, 'Escape closes');
click();
for (const callback of [...timers.values()]) callback();
for (let n = 0; n < 4; n++) click();
assert.equal(overlay.hidden, true, 'inactivity resets click count');
click();
assert.equal(overlay.hidden, false, 'opens after five new clicks');
assert.equal(timers.size, 0, 'overlay remains open without automatic timer');
console.log('PASS: 4/5 clicks, counter reset, inactivity reset, X, Escape, source reset/restart, no automatic close.');
