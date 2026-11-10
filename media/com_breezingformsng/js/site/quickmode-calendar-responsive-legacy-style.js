/* Extracted from BFQuickModeBootstrap's and BFQuickModeOnePage's inline
   process() scripts (Phase 9c step 2b) - the year/month scroller buttons
   added next to a responsive calendar picker's year/month dropdowns.
   Only loaded once per page, the first time a bfCalendarResponsive
   element is rendered. Kept separate from the classic theme's
   quickmode-calendar-responsive.js: the "prev year" icon's inline style
   has a pre-existing typo ("vertical - align" with stray spaces,
   silently ignored as invalid CSS by the browser) that only exists in
   these two themes - preserved as-is rather than "fixed" during this
   relocation. Depends on two globals declared inline right before this
   file is loaded: bfPickerMinusYearIcon and bfPickerPlusYearIcon (the
   prev/next icon URLs). */
function bf_add_yearscroller(fieldname) {
if (!JQuery("#bfCalExt" + fieldname).length) {
// prev
if (JQuery(".bfCalendarResponsiveContainer" + fieldname + " .picker__select--year").get(0).selectedIndex > 0) {
JQuery(".bfCalendarResponsiveContainer" + fieldname + " .picker__select--year").before('<img id="bfCalExt' + fieldname + '" onclick="JQuery(\'.bfCalendarResponsiveContainer' + fieldname + ' .picker__select--year\').get(0).selectedIndex=JQuery(\'.bfCalendarResponsiveContainer' + fieldname + ' .picker__select--year\').get(0).selectedIndex-1;JQuery(\'.bfCalendarResponsiveContainer' + fieldname + ' .picker__select--year\').trigger(\'change\')" border="0" src="' + bfPickerMinusYearIcon + '" style="width: 30px; vertical - align: top; cursor: pointer; " />');
}
// next
if (JQuery(".bfCalendarResponsiveContainer" + fieldname + " .picker__select--year").get(0).selectedIndex + 1 < JQuery(".bfCalendarResponsiveContainer" + fieldname + " .picker__select--year").get(0).options.length) {
JQuery(".bfCalendarResponsiveContainer" + fieldname + " .picker__select--year").after('<img id="bfCalExt' + fieldname + '" onclick="JQuery(\'.bfCalendarResponsiveContainer' + fieldname + ' .picker__select--year\').get(0).selectedIndex=JQuery(\'.bfCalendarResponsiveContainer' + fieldname + ' .picker__select--year\').get(0).selectedIndex+1;JQuery(\'.bfCalendarResponsiveContainer' + fieldname + ' .picker__select--year\').trigger(\'change\')" border="0" src="' + bfPickerPlusYearIcon + '" style="width: 30px; vertical-align: top; cursor:pointer;" />');
}

JQuery('.bfCalendarResponsiveContainer' + fieldname + ' .picker__select--year').on('change', function () {
bf_add_yearscroller(fieldname);
});
JQuery('.bfCalendarResponsiveContainer' + fieldname + ' .picker__select--month').on('change', function () {
bf_add_yearscroller(fieldname);
});

var yearValue = JQuery('.bfCalendarResponsiveContainer' + fieldname + ' .picker__select--year').val();
var yearInterval = setInterval(function () {
if (yearValue != JQuery('.bfCalendarResponsiveContainer' + fieldname + ' .picker__select--year').val()) {
clearInterval(yearInterval);
bf_add_yearscroller(fieldname);
}
}, 200);

var monthValue = JQuery('.bfCalendarResponsiveContainer' + fieldname + ' .picker__select--month').val();
var monthInterval = setInterval(function () {
if (monthValue != JQuery('.bfCalendarResponsiveContainer' + fieldname + ' .picker__select--month').val()) {
clearInterval(monthInterval);
bf_add_yearscroller(fieldname);
}
}, 200);
}
}
