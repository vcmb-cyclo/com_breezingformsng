import { readFileSync } from 'node:fs';
import assert from 'node:assert/strict';
import test from 'node:test';
import { QuickmodeTreeModel, getTreeKeyCommand } from '../../media/com_breezingformsng/js/admin/quickmode-tree-model.js';

function element(id, dbId = 7) {
    return {
        attributes: { class: 'bfQuickModeElementClass', id },
        properties: { type: 'element', bfName: id, name: id, dbId },
        data: { title: id },
        children: []
    };
}

function section(id, child = element(`${id}Element`)) {
    return {
        attributes: { class: 'bfQuickModeSectionClass', id },
        properties: { type: 'section', name: id },
        data: { title: id },
        children: [child]
    };
}

function page(id, children = []) {
    return {
        attributes: { class: 'bfQuickModePageClass', id },
        properties: { type: 'page', pageNumber: Number(id.slice(-1)) },
        data: { title: id },
        children
    };
}

function tree() {
    return {
        attributes: { class: 'bfQuickModeRootClass', id: 'bfQuickModeRoot' },
        properties: { type: 'root', name: 'TestForm', lastPageThankYou: true },
        children: [page('bfQuickModePage1', [section('section1')]), page('bfQuickModePage2')]
    };
}

test('indexes nodes and returns parents and elements without a DOM', () => {
    const root = tree();
    const model = new QuickmodeTreeModel(root);

    assert.equal(model.find('section1').attributes.id, 'section1');
    assert.equal(model.getParent('section1').attributes.id, 'bfQuickModePage1');
    assert.deepEqual(model.getElements().map((node) => node.attributes.id), ['section1Element']);
});

test('inserts, duplicates and keeps cloned identifiers unique', () => {
    const root = tree();
    const model = new QuickmodeTreeModel(root);
    const newSection = section('newSection', element('newElement', 11));

    model.insert('bfQuickModePage2', newSection);
    const result = model.cloneInto('section1', 'bfQuickModePage2');
    const clone = result.node;

    assert.notEqual(clone.attributes.id, 'section1');
    assert.notEqual(clone.children[0].attributes.id, 'section1Element');
    assert.equal(clone.children[0].properties.dbId, 0);
    assert.equal(model.getParent(clone.attributes.id).attributes.id, 'bfQuickModePage2');
});

test('moves only to valid destinations and leaves invalid moves unchanged', () => {
    const root = tree();
    const model = new QuickmodeTreeModel(root);
    const original = JSON.stringify(root);

    assert.throws(() => model.move('section1', 'section1Element', 'inside'));
    assert.equal(JSON.stringify(root), original);

    model.move('section1Element', 'bfQuickModePage2', 'inside');
    assert.equal(model.getParent('section1Element').attributes.id, 'bfQuickModePage2');
});

test('renumbers pages and updates the thank-you-page script after removal', () => {
    const root = tree();
    const model = new QuickmodeTreeModel(root);

    model.move('bfQuickModePage2', 'bfQuickModePage1', 'before');
    model.renumberPages('Page ');
    assert.deepEqual(root.children.map((node) => node.attributes.id), ['bfQuickModePage1', 'bfQuickModePage2']);
    assert.equal(root.children[0].data.title, 'Page 1');
    assert.match(root.properties.submittedScriptCode, /ff_switchpage\(2\)/);

    model.remove('bfQuickModePage2');
    model.renumberPages('Page ');
    assert.equal(root.children.length, 1);
    assert.equal(root.properties.submittedScriptCondidtion, -1);
});

test('explains why a node cannot be inserted under the selected one', () => {
    const root = tree();
    const model = new QuickmodeTreeModel(root);
    const selected = (id) => model.find(id);

    assert.equal(model.getInsertionRefusal(selected('bfQuickModePage1'), element('new')), null);
    assert.equal(model.getInsertionRefusal(selected('section1'), element('new')), null);
    assert.equal(model.getInsertionRefusal(root, page('bfQuickModePage3')), null);

    assert.equal(model.getInsertionRefusal(root, element('new')), 'form');
    assert.equal(model.getInsertionRefusal(root, section('newSection')), 'form');
    assert.equal(model.getInsertionRefusal(selected('section1Element'), element('new')), 'element');
    assert.equal(model.getInsertionRefusal(selected('section1Element'), section('newSection')), 'element');
    assert.equal(model.getInsertionRefusal(selected('bfQuickModePage1'), page('bfQuickModePage3')), 'page');
});

test('maps element nodes to their element type option', () => {
    const root = tree();
    const model = new QuickmodeTreeModel(root);
    const typed = (properties) => ({ ...element('typed'), properties: { type: 'element', ...properties } });

    assert.equal(model.getElementTypeOption(typed({ bfType: 'bfTextfield' })), 'bfElementTypeText');
    assert.equal(model.getElementTypeOption(typed({ bfType: 'bfNumberInput', range: false })), 'bfElementTypeNumberInput');
    assert.equal(model.getElementTypeOption(typed({ bfType: 'bfNumberInput', range: true })), 'bfElementTypeSlider');
    assert.equal(model.getElementTypeOption(typed({ bfType: 'bfCalendarResponsive' })), 'bfElementTypeCalendarResponsive');

    assert.equal(model.getElementTypeOption(typed({})), null);
    assert.equal(model.getElementTypeOption(root), null);
    assert.equal(model.getElementTypeOption(model.find('bfQuickModePage1')), null);
    assert.equal(model.getElementTypeOption(section('section2')), null);
});

test('Textfield widget presets map to their own type option', () => {
    const root = { attributes: { class: 'bfQuickModeRootClass', id: 'root' }, properties: { type: 'root' }, children: [] };
    const model = new QuickmodeTreeModel(root);
    const typed = (properties) => ({ ...element('typed'), properties: { type: 'element', ...properties } });

    assert.equal(model.getElementTypeOption(typed({ bfType: 'bfCheckbox', widget: 'consent' })), 'bfElementTypeConsent');
    assert.equal(model.getElementTypeOption(typed({ bfType: 'bfCheckbox' })), 'bfElementTypeCheckbox');
    assert.equal(model.getPropertiesPanel('bfElementTypeRobot'), 'bfElementTypeRobot');
    assert.equal(model.getElementTypeOption(typed({ bfType: 'bfCaptcha' })), 'bfElementTypeCaptcha');
    assert.equal(model.isRobotOption('bfElementTypeReCaptcha'), true);
    assert.equal(model.isRobotOption('bfElementTypeText'), false);
    // Changing the method keeps the names and texts, never the settings of the former method.
    assert.deepEqual(
        model.carryRobotSettings(
            { label: 'Check', label_translationfr_FR: 'Contrôle', bfName: 'Check', pubkey: 'k', widget: 'robot', dbId: 7 },
            { label: 'untitled', bfName: 'id', bfType: 'bfCaptcha', dbId: 0 }
        ),
        { label: 'Check', label_translationfr_FR: 'Contrôle', bfName: 'Check', bfType: 'bfCaptcha', dbId: 0 }
    );
    assert.equal(model.getPropertiesPanel('bfElementTypeConsent'), 'bfElementTypeCheckbox');
    assert.equal(model.getPropertiesPanel('bfElementTypeSlider'), 'bfElementTypeNumberInput');
    assert.equal(model.getPropertiesPanel('bfElementTypeUrl'), 'bfElementTypeText');
    assert.equal(model.getPropertiesPanel('bfElementTypeSelect'), 'bfElementTypeSelect');
    assert.equal(model.getElementTypeOption(typed({ bfType: 'bfTextfield', widget: 'phone' })), 'bfElementTypePhone');
    assert.equal(model.getElementTypeOption(typed({ bfType: 'bfTextfield', widget: 'datetime' })), 'bfElementTypeDateTime');
    assert.equal(model.getElementTypeOption(typed({ bfType: 'bfTextfield', widget: 'country' })), 'bfElementTypeCountry');
    assert.equal(model.getElementTypeOption(typed({ bfType: 'bfTextfield', widget: 'address' })), 'bfElementTypeAddress');
    assert.equal(model.getElementTypeOption(typed({ bfType: 'bfTextfield', widget: '' })), 'bfElementTypeText');
    assert.equal(model.getElementTypeOption(typed({ bfType: 'bfTextfield', widget: 'toString' })), 'bfElementTypeText');
    assert.equal(model.getElementTypeOption(typed({ bfType: 'bfTextfield' })), 'bfElementTypeText');

    assert.equal(model.getWidgetFromOption('bfElementTypeCountry'), 'country');
    assert.equal(model.getWidgetFromOption('bfElementTypeText'), '');
    assert.equal(model.getWidgetFromOption('bfElementTypeSlider'), '');
});

test('applying a widget preset keeps the properties and only fills missing defaults', () => {
    const model = new QuickmodeTreeModel({ attributes: { class: 'bfQuickModeRootClass', id: 'root' }, properties: { type: 'root' }, children: [] });
    const properties = { bfType: 'bfTextfield', label: 'Phone', required: true, widgetDefaultCountry: 'be' };

    model.applyWidgetPreset(properties, 'phone');
    assert.equal(properties.widget, 'phone');
    assert.equal(properties.label, 'Phone');
    assert.equal(properties.required, true);
    assert.equal(properties.widgetDefaultCountry, 'be');
    assert.equal(properties.widgetShowFlags, true);
    assert.equal(properties.widgetMode, 'datetime');
    assert.equal(properties.widgetStep, 15);
    assert.equal(properties.widgetProvider, '');
    assert.equal(properties.widgetMinChars, 3);

    model.applyWidgetPreset(properties, '');
    assert.equal(properties.widget, '');
    assert.equal(properties.label, 'Phone');

    const plain = { bfType: 'bfTextfield' };
    model.applyWidgetPreset(plain, 'unknown');
    assert.deepEqual(plain, { bfType: 'bfTextfield', widget: '' });
});

function ids(model, id) {
    return model.getChildren(model.find(id)).map((node) => node.attributes.id);
}

test('keyboard move swaps siblings and crosses into the neighbouring container', () => {
    const root = tree();
    root.children[0].children.push(section('section2'));
    root.children[0].children.push(section('section3'));
    const model = new QuickmodeTreeModel(root);

    let target = model.getKeyboardMove('section2', 'up');
    assert.deepEqual(target, { parentId: 'bfQuickModePage1', index: 0 });
    model.move('section2', target.parentId, 'inside', target.index);
    assert.deepEqual(ids(model, 'bfQuickModePage1'), ['section2', 'section1', 'section3']);

    target = model.getKeyboardMove('section2', 'down');
    model.move('section2', target.parentId, 'inside', target.index);
    assert.deepEqual(ids(model, 'bfQuickModePage1'), ['section1', 'section2', 'section3']);

    assert.equal(model.getKeyboardMove('section1', 'up'), null);

    target = model.getKeyboardMove('section3', 'down');
    assert.deepEqual(target, { parentId: 'bfQuickModePage2', index: 0 });
    model.move('section3', target.parentId, 'inside', target.index);
    assert.deepEqual(ids(model, 'bfQuickModePage2'), ['section3']);

    target = model.getKeyboardMove('section3', 'up');
    assert.deepEqual(target, { parentId: 'bfQuickModePage1', index: 2 });
});

test('keyboard move reorders pages and refuses the root and the edges', () => {
    const model = new QuickmodeTreeModel(tree());

    assert.deepEqual(model.getKeyboardMove('bfQuickModePage1', 'down'), { parentId: 'bfQuickModeRoot', index: 1 });
    assert.equal(model.getKeyboardMove('bfQuickModePage1', 'up'), null);
    assert.equal(model.getKeyboardMove('bfQuickModePage2', 'down'), null);
    assert.equal(model.getKeyboardMove('bfQuickModeRoot', 'down'), null);
    assert.equal(model.getKeyboardMove('missing', 'down'), null);
    assert.equal(model.getKeyboardMove('section1', 'left'), null);
});

test('maps tree key events to commands', () => {
    assert.equal(getTreeKeyCommand({ key: 'ArrowUp', altKey: true }), 'move-up');
    assert.equal(getTreeKeyCommand({ key: 'ArrowDown', altKey: true }), 'move-down');
    assert.equal(getTreeKeyCommand({ key: 'ArrowDown' }), null);
    assert.equal(getTreeKeyCommand({ key: 'ArrowDown', altKey: true, ctrlKey: true }), null);
    assert.equal(getTreeKeyCommand({ key: 'F10', shiftKey: true }), 'context-menu');
    assert.equal(getTreeKeyCommand({ key: 'F10' }), null);
    assert.equal(getTreeKeyCommand({ key: 'ContextMenu' }), 'context-menu');
    assert.equal(getTreeKeyCommand(null), null);
});

test('the properties list shows the type of a consent box', () => {
    const source = readFileSync(new URL('../../media/com_breezingformsng/js/admin/quickmode-app-properties.js', import.meta.url), 'utf8');
    assert.match(source, /val\(mdata\.widget === 'consent' \? 'bfElementTypeConsent' : 'bfElementTypeCheckbox'\)/);
});
