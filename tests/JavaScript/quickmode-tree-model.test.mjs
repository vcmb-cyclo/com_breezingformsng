import assert from 'node:assert/strict';
import test from 'node:test';
import { QuickmodeTreeModel } from '../../media/com_breezingformsng/js/admin/quickmode-tree-model.js';

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
