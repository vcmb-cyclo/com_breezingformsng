/**
 * BreezingForms NG - QuickMode "unsaved changes" badge (charte graphique).
 *
 * Scope: tracks both edits to the current page/section/element's fields
 * across the Proprietes/Avance/Options tabs (form#bfForm, including its
 * CodeMirror editors) and tree structure changes - create/move/delete of a
 * page/section/element - which quickmode-app.js makes directly against its
 * in-memory app.dataObject, outside of any form. Renaming a node happens
 * through the Proprietes tab's Titre/Nom fields (already covered by
 * bfForm), not a separate tree-inline-rename control, so it doesn't need
 * its own hook. window.BFQMApp is quickmode-app.js's own app instance,
 * exposed there specifically for this file to read app.dataObject.
 *
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

import { JoomlaEditor } from 'editor-api';

document.addEventListener('DOMContentLoaded', function () {
    var form = document.forms.bfForm;
    var badge = document.getElementById('bfUnsavedBadge');

    if (!form || !badge) {
        return;
    }

    function treeState() {
        try {
            return JSON.stringify(window.BFQMApp.dataObject, function (key, value) {
                return key === 'state' && this.attributes && this.properties ? undefined : value;
            });
        } catch (e) {
            return '';
        }
    }

    function treeStructure() {
        function nodes(value) {
            if (Array.isArray(value)) {
                return value.map(nodes);
            }
            if (!value || typeof value !== 'object') {
                return null;
            }
            return [value.attributes?.id || null, nodes(value.children || [])];
        }
        return JSON.stringify(nodes(window.BFQMApp.dataObject));
    }

    function formState(options) {
        // QuickMode property controls usually have an id but no name, so
        // FormData misses edits to element labels, Stripe settings, etc.
        var fields = Array.from(form.querySelectorAll('input, select, textarea')).filter(function (field) {
            return Boolean(field.closest('#fragment-3')) === options
                && !['button', 'submit', 'reset'].includes(field.type);
        }).map(function (field) {
            var value = field.value;
            if (field.type === 'checkbox' || field.type === 'radio') {
                value = field.checked;
            } else if (field.multiple) {
                value = Array.from(field.selectedOptions, function (option) { return option.value; });
            } else if (field.tagName === 'TEXTAREA' && field.id) {
                try {
                    var editor = JoomlaEditor.get(field.id);
                    if (editor && typeof editor.getValue === 'function') {
                        value = editor.getValue();
                    }
                } catch (e) {
                    value = field.value;
                }
            }
            return [field.id || field.name, value];
        });
        return JSON.stringify(fields);
    }

    // quickmode-app.js creates BFQMApp during the load event. Capturing the
    // baseline at DOMContentLoaded would therefore record an empty tree and
    // mark the form as dirty as soon as the QuickMode app is initialized.
    // Wait for the QuickMode tree and Options fields to settle before taking
    // the baseline. Unmounted editors use their textarea value, so opening
    // an editor later does not count as a change.
    var REQUIRED_STABLE_TICKS = 6;
    var MAX_BASELINE_TICKS = 20;

    var initialState = null;
    var initialOptions = null;
    var initialTree = null;
    var initialStructure = null;
    var selectedNode = null;
    var selectedNodeState = null;
    var previousTree = null;
    var nodeEdited = false;
    var pendingState = null;
    var stableTicks = 0;
    var totalTicks = 0;
    var saved = false;
    var userEdited = false;

    function sync() {
        if (!window.BFQMApp) {
            return;
        }

        var options = formState(true);
        var tree = treeState();
        var structure = treeStructure();
        var node = formState(false);
        var nodeId = window.BFQMApp.selectedTreeElement?.attributes?.id || null;
        var currentState = options + ' ' + tree + ' ' + node;

        if (initialState === null) {
            if (initialStructure === null) {
                initialStructure = structure;
            }
            totalTicks++;
            stableTicks = (pendingState === currentState) ? stableTicks + 1 : 0;
            pendingState = currentState;

            if (stableTicks >= REQUIRED_STABLE_TICKS || totalTicks >= MAX_BASELINE_TICKS) {
                initialState = currentState;
                initialOptions = options;
                initialTree = tree;
                selectedNode = nodeId;
                selectedNodeState = node;
                previousTree = tree;
                badge.hidden = !userEdited && structure === initialStructure;
            }

            return;
        }

        if (nodeId !== selectedNode) {
            // QuickMode commits the previous panel on every selection. That
            // commit can normalize stored properties even when nothing was
            // edited; treat that result as the new clean baseline.
            if (!nodeEdited && previousTree === initialTree && structure === initialStructure) {
                initialTree = tree;
            }
            selectedNode = nodeId;
            selectedNodeState = node;
        }
        nodeEdited = node !== selectedNodeState;
        previousTree = tree;
        badge.hidden = !userEdited && options === initialOptions && tree === initialTree
            && structure === initialStructure && node === selectedNodeState;
    }

    function onFieldEdit(event) {
        // Programmatic population while browsing nodes must stay clean.
        // A real field edit must still warn even during baseline settling.
        if (event.isTrusted !== false) {
            userEdited = true;
        }
        sync();
    }

    form.addEventListener('input', onFieldEdit);
    form.addEventListener('change', onFieldEdit);
    window.addEventListener('bfqm:ready', sync);
    window.addEventListener('load', sync);
    window.addEventListener('bfqm:saved', function () { saved = true; });
    window.addEventListener('beforeunload', function (event) {
        sync();
        if (!saved && (userEdited || initialState !== null && !badge.hidden
            || initialStructure !== null && treeStructure() !== initialStructure)) {
            event.preventDefault();
            event.returnValue = '';
        }
    });

    // Neither CodeMirror (via JoomlaEditor) nor tree structure
    // edits (app.dataObject, mutated directly by quickmode-app.js's
    // create/move/delete handlers) fire native input/change events on
    // bfForm - poll instead, matching the 500ms interval already used
    // elsewhere in quickmode-app.js for editor visibility.
    setInterval(sync, 500);
});
