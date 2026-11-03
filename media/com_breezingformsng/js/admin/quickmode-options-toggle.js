// The shared Options layout calls bfToggle() from its Script and Piece radios.
// The classic editor loads admin-form.js; QuickMode needs only this helper.
window.bfToggle = function (libId, codeId, value) {
    document.getElementById(libId).style.display = value === '1' ? '' : 'none';
    document.getElementById(codeId).style.display = value === '2' ? '' : 'none';
};
