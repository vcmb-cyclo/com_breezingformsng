(function () {
    'use strict';

    if (window.BFJoomlaJQuery) {
        // jTree's bundled legacy jQuery clone (administrator/.../jtree/_lib.js)
        // overwrites window.JQuery/$ globally after it loads. It has already
        // captured its own reference by this point, so restoring the native
        // jQuery here is safe and closes the leak for any code that runs later.
        window.jQuery = window.BFJoomlaJQuery;
        window.$ = window.BFJoomlaDollar || window.BFJoomlaJQuery;
        window.JQuery = window.BFJoomlaJQuery;
    }
}());
