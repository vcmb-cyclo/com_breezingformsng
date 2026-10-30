import js from '@eslint/js';
import globals from 'globals';

// The scripts loaded through the Joomla web asset manager share the browser
// global scope: legacy files read each other's globals, so the scope rules
// (no-undef, no-unused-vars, no-redeclare) only warn. Every other recommended
// rule, and any syntax error, fails the build.
const sharedScope = {
    'no-undef': 'warn',
    'no-unused-vars': 'warn',
    'no-redeclare': 'warn',
};

const browserGlobals = {
    ...globals.browser,
    Joomla: 'readonly',
    jQuery: 'readonly',
    JQuery: 'readonly',
};

export default [
    {
        ignores: ['**/*.min.js'],
    },
    {
        files: ['media/com_breezingformsng/js/**/*.js'],
        languageOptions: {
            ecmaVersion: 2022,
            sourceType: 'script',
            globals: browserGlobals,
        },
        rules: {
            ...js.configs.recommended.rules,
            ...sharedScope,
        },
    },
    {
        // ES modules of the QuickMode editor.
        files: [
            'media/com_breezingformsng/js/admin/quickmode-app.js',
            'media/com_breezingformsng/js/admin/quickmode-app-properties.js',
            'media/com_breezingformsng/js/admin/form-mail-template.js',
            'media/com_breezingformsng/js/admin/quickmode-editor.js',
            'media/com_breezingformsng/js/admin/quickmode-form-dirty.js',
            'media/com_breezingformsng/js/admin/quickmode-tree-model.js',
        ],
        languageOptions: {
            sourceType: 'module',
        },
    },
    {
        files: [
            'scripts/**/*.mjs',
            'tests/JavaScript/**/*.mjs',
            'tests/Browser/**/*.mjs',
            'eslint.config.mjs',
            'playwright.config.mjs',
            'stylelint.config.mjs',
        ],
        languageOptions: {
            ecmaVersion: 'latest',
            sourceType: 'module',
            globals: globals.node,
        },
        rules: js.configs.recommended.rules,
    },
    {
        // page.evaluate() callbacks run in the page and read its globals.
        files: ['tests/Browser/**/*.mjs'],
        languageOptions: {
            globals: { ...globals.node, ...browserGlobals },
        },
        rules: sharedScope,
    },
];
