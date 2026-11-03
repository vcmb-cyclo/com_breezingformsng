import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';

const source = readFileSync(new URL('../../media/com_breezingformsng/js/admin/quickmode-form-dirty.js', import.meta.url), 'utf8')
    .replace("import { JoomlaEditor } from 'editor-api';", '');
const option = { id: 'subject', type: 'text', value: 'Original', closest: () => ({}) };
const nodeField = { id: 'actionType', type: 'text', value: 'none', closest: () => null };
const stripeField = { id: 'bfElementTypeStripeAmount', type: 'text', value: '12.00', closest: () => null };
const stripeChoice = { id: 'bfElementTypeStripeAmountSourceField', type: 'radio', checked: false, closest: () => null };
const optionEditor = { id: 'optionCode', tagName: 'TEXTAREA', value: 'return true;', closest: () => ({}) };
const nodeEditor = { id: 'nodeCode', tagName: 'TEXTAREA', value: 'initial', closest: () => null };
const form = {
    fields: [option, nodeField, stripeField, stripeChoice, optionEditor, nodeEditor],
    events: new Map(),
    addEventListener(name, callback) { this.events.set(name, callback); },
    querySelectorAll() { return this.fields; },
};
const badge = { hidden: false };
const app = {
    dataObject: { attributes: { id: 'root' }, properties: { title: 'Form' }, state: 'open', children: [] },
    selectedTreeElement: { attributes: { id: 'first' } },
};
let tick;
let ready;
const windowEvents = new Map();
const editorInstances = new Map();
const context = {
    JoomlaEditor: { get: (id) => editorInstances.get(id) },
    Set,
    URLSearchParams,
    window: { BFQMApp: app, addEventListener(name, callback) { windowEvents.set(name, callback); } },
    document: {
        forms: { bfForm: form },
        getElementById: () => badge,
        addEventListener(event, callback) { if (event === 'DOMContentLoaded') ready = callback; },
    },
    setInterval(callback) { tick = callback; },
};
runInNewContext(source, context);
ready();
tick();
app.dataObject.children.push({ attributes: { id: 'early-element' }, properties: {}, children: [] });
const earlyLeave = { prevented: false, preventDefault() { this.prevented = true; } };
windowEvents.get('beforeunload')(earlyLeave);
assert.equal(earlyLeave.prevented, true, 'an element added during initialization still blocks leaving');
app.dataObject.children.pop();
for (let index = 0; index < 8; index++) tick();
assert.equal(badge.hidden, true, 'the initial form is clean');

app.selectedTreeElement = { attributes: { id: 'second' } };
nodeField.value = 'custom';
nodeEditor.value = 'second node';
app.dataObject.properties.normalized = '';
tick();
assert.equal(badge.hidden, true, 'selecting another node is not an edit');

app.dataObject.children.push({ attributes: { id: 'new-element' }, properties: {}, children: [] });
app.selectedTreeElement = app.dataObject.children[0];
tick();
assert.equal(badge.hidden, false, 'adding and selecting an element is detected');
const leave = { prevented: false, preventDefault() { this.prevented = true; } };
windowEvents.get('beforeunload')(leave);
assert.equal(leave.prevented, true, 'leaving with a new element warns before data loss');
app.dataObject.children.pop();
app.selectedTreeElement = { attributes: { id: 'second' } };
tick();
assert.equal(badge.hidden, true, 'removing the new element restores the clean state');

app.dataObject.state = 'close';
tick();
assert.equal(badge.hidden, true, 'collapsing the tree is not an edit');

editorInstances.set('optionCode', { getValue: () => optionEditor.value });
tick();
assert.equal(badge.hidden, true, 'mounting an unchanged editor is not an edit');

nodeField.value = 'library';
tick();
assert.equal(badge.hidden, false, 'editing the selected node is detected');
nodeField.value = 'custom';
tick();
assert.equal(badge.hidden, true, 'reverting the node edit clears the badge');

stripeField.value = '25.00';
tick();
assert.equal(badge.hidden, false, 'editing an unnamed Stripe property is detected');
stripeField.value = '12.00';
tick();
assert.equal(badge.hidden, true, 'reverting the Stripe property clears the badge');

stripeChoice.checked = true;
tick();
assert.equal(badge.hidden, false, 'changing a Stripe choice is detected');
stripeChoice.checked = false;
tick();
assert.equal(badge.hidden, true, 'reverting a Stripe choice clears the badge');

app.dataObject.properties.title = 'Edited form';
tick();
assert.equal(badge.hidden, false, 'editing the form tree is detected');
app.dataObject.properties.title = 'Form';
tick();
assert.equal(badge.hidden, true, 'reverting the tree edit clears the badge');

option.value = 'Changed';
tick();
assert.equal(badge.hidden, false, 'editing form options is detected');
option.value = 'Original';
tick();
assert.equal(badge.hidden, true, 'reverting an option clears the badge');

nodeField.value = 'edited by user';
form.events.get('input')({ isTrusted: true });
assert.equal(badge.hidden, false, 'editing an ordinary element displays the badge');
const editedLeave = { prevented: false, preventDefault() { this.prevented = true; } };
windowEvents.get('beforeunload')(editedLeave);
assert.equal(editedLeave.prevented, true, 'an ordinary element edit blocks leaving');
windowEvents.get('bfqm:saved')();
const savedLeave = { prevented: false, preventDefault() { this.prevented = true; } };
windowEvents.get('beforeunload')(savedLeave);
assert.equal(savedLeave.prevented, false, 'successful save navigation is allowed');
