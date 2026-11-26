/* One-page QuickMode initializer, kept separate from the Classic/Bootstrap one. */
bfToggleFieldsLoaded = false;
bfSectionFieldsDeactivated = false;

document.addEventListener('DOMContentLoaded', function () {
    var JQuery = window.jQuery;

    if (!JQuery) {
        return;
    }

    if (typeof bfFade != "undefined") bfFade();
    if (typeof bfRollover != "undefined") bfRollover();
    if (typeof bfRollover2 != "undefined") bfRollover2();
    if (typeof bfRegisterToggleFields != "undefined") {
        bfRegisterToggleFields();
    } else {
        bfToggleFieldsLoaded = true;
    }
    if (typeof bfDeactivateSectionFields != "undefined") {
        bfDeactivateSectionFields();
    } else {
        bfSectionFieldsDeactivated = true;
    }
    JQuery(".bfQuickMode .hasTip").css("color", "inherit");
    JQuery(".bfQuickMode .bfTooltip").css("color", "inherit");
    JQuery("input[type=text]").bind("keypress", function(evt) {
        if (evt.keyCode == 13) {
            evt.preventDefault();
        }
    });
});
