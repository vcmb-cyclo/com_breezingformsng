/* Extracted from BFQuickMode's inline headers() script (Phase 9c step 2b) -
   the unconditional document-ready initializer that wires up whichever
   optional effects/features are active on this form (fading, rollover,
   toggle fields, section deactivation, tooltip color, and Enter-key
   suppression on single-line text fields). Each optional feature is
   invoked only if its function was actually defined by this form's
   configuration.

   The validation-engine wiring that used to run here (JQuery.
   bfvalidationEngine/.bfvalidationEngineLanguage) was removed: it was
   dead code, guarded by a property (JQuery.bfvalidationEngine) that
   was never set anywhere in this codebase - see
   docs/maintenance/js-libraries-migration-plan.md. */
bfToggleFieldsLoaded = false;
bfSectionFieldsDeactivated = false;
document.addEventListener('DOMContentLoaded', function () {
	var JQuery = window.jQuery;

	if (!JQuery) {
		return;
	}

	if(typeof bfFade != "undefined")bfFade();
	if(typeof bfRollover != "undefined")bfRollover();
	if(typeof bfRollover2 != "undefined")bfRollover2();
	if(typeof bfRegisterToggleFields != "undefined"){
	    bfRegisterToggleFields();
        }else{
            bfToggleFieldsLoaded = true;
        }
	if(typeof bfDeactivateSectionFields != "undefined"){
	    bfDeactivateSectionFields();
	}else{
	    bfSectionFieldsDeactivated = true;
	}
	JQuery(".bfQuickMode .hasTip").css("color","inherit"); // fixing label text color issue
	JQuery(".bfQuickMode .bfTooltip").css("color","inherit"); // fixing label text color issue
        JQuery("input[type=text]").bind("keypress", function(evt) {
            if(evt.keyCode == 13) {
                evt.preventDefault();
            }
        });
});
