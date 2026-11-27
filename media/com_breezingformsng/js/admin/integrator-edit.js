/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

/* The finalize code has its own form: the toolbar saves it with the rule. */
(() => {
    const form = document.getElementById('adminForm');
    const field = form?.querySelector('input[name="finalizeCode"]');

    field && form.addEventListener('submit', () => {
        const editor = window.Joomla?.editors?.instances?.finalizeCode;

        if (editor) {
            field.value = editor.getValue();
            field.disabled = false;
        }
    });
})();
