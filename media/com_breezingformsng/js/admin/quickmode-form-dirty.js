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

    function editorValues() {
        return Array.from(form.querySelectorAll('textarea[id]')).sort(function (first, second) {
            return first.id.localeCompare(second.id);
        }).map(function (field) {
            var name = field.id;
            var editor = JoomlaEditor.get(name);

            if (!editor || typeof editor.getValue !== 'function') {
                return null;
            }

            try {
                return name + '=' + editor.getValue();
            } catch (e) {
                return name + '=';
            }
        }).filter(function (value) {
            return value !== null;
        }).join(' ');
    }

    function treeState() {
        try {
            return JSON.stringify(window.BFQMApp.dataObject);
        } catch (e) {
            return '';
        }
    }

    function formState() {
        return new URLSearchParams(new FormData(form)).toString() + ' ' + editorValues() + ' ' + treeState();
    }

    // quickmode-app.js creates BFQMApp during the load event. Capturing the
    // baseline at DOMContentLoaded would therefore record an empty tree and
    // mark the form as dirty as soon as the QuickMode app is initialized.
    var initialState = null;
    var pendingState = null;

    function sync() {
        if (!window.BFQMApp) {
            return;
        }

        var currentState = formState();

        if (initialState === null) {
            // Some of the Options tab's CodeMirror editors (jf_piece1code..
            // jf_piece4code) aren't registered with JoomlaEditor yet on the
            // very first tick right after load/bfqm:ready - editorValues()
            // silently skips whatever isn't ready, so freezing the baseline
            // immediately would record a smaller editor set than what's
            // there moments later, and that late registration alone (not any
            // real edit) would then look like a change forever after. Only
            // freeze the baseline once two consecutive polls agree, i.e.
            // once the editor set has actually settled.
            if (pendingState !== null && pendingState === currentState) {
                initialState = currentState;
                badge.hidden = true;
            }

            pendingState = currentState;

            return;
        }

        badge.hidden = currentState === initialState;
    }

    form.addEventListener('input', sync);
    form.addEventListener('change', sync);
    window.addEventListener('bfqm:ready', sync);
    window.addEventListener('load', sync);

    // Neither CodeMirror (via JoomlaEditor) nor tree structure
    // edits (app.dataObject, mutated directly by quickmode-app.js's
    // create/move/delete handlers) fire native input/change events on
    // bfForm - poll instead, matching the 500ms interval already used
    // elsewhere in quickmode-app.js for editor visibility.
    setInterval(sync, 500);
});
