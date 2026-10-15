/* Extracted from BFQuickModeOnePage's inline process() script (Phase 9c
   step 2b) - the chunked/queued file upload controller, only loaded when
   the form has at least one bfFile element with flashUploader or html5
   enabled. Kept separate from the classic/Bootstrap variant
   (quickmode-flash-upload.js): OnePage always alert()s on validation
   failure instead of respecting bfUseErrorAlerts/bfShowErrors, resets the
   submit button's spinner (global `bfSubmitSpinner`, see
   quickmode-submit-spinner.js) on failure, and calls this theme's own
   bf_validate_submit() instead of the shared ff_validate_submit() once
   all queued uploads finish. */
var bfUploaders = [];
var bfUploaderErrorElements = [];
var bfFlashUploadInterval = null;
var bfFlashUploaders = new Array();
var bfFlashUploadersLength = 0;
function bfRefreshAll(){
    for( var i = 0; i < bfUploaders.length; i++ ){
        bfUploaders[i].refresh();
    }
}
function bfInitAll(){
    for( var i = 0; i < bfUploaders.length; i++ ){
        bfUploaders[i].init();
    }
}
function bfDoFlashUpload(){
    JQuery("#bfSubmitMessage").css("visibility","hidden");
    JQuery("#bfSubmitMessage").css("display","none");
    JQuery("#bfSubmitMessage").css("z-index","999999");
    JQuery(".bfErrorMessage").html("");
    JQuery(".bfErrorMessage").css("display","none");
    for(var i = 0; i < bfUploaderErrorElements.length; i++){
        JQuery("#"+bfUploaderErrorElements[i]).html("");
    }
    bfUploaderErrorElements = [];
    if(ff_validation(0) == ""){
        try{
            bfFlashUploadInterval = window.setInterval( bfCheckFlashUploadProgress, 1000 );
            if(bfFlashUploadersLength > 0){
                JQuery("#bfFileQueue").bfcenter(true);
                JQuery("#bfFileQueue").css("visibility","visible");
                for( var i = 0; i < bfUploaders.length; i++ ){
                    bfUploaders[i].start();
                }
            }
        } catch(e){alert(e)}
    } else {
        if(typeof bfUseErrorAlerts == "undefined"){
            alert(error);
        } else {
            alert(error);
        }
        ff_validationFocus("");
        document.getElementById("bfSubmitButton").disabled = false;
        bfSubmitSpinner.stop();
    }
}
function bfCheckFlashUploadProgress(){
    if( JQuery("#bfFileQueue").html() == "" ){ // empty indicates that all queues are uploaded or in any way cancelled
        JQuery("#bfFileQueue").css("visibility","hidden");
        window.clearInterval( bfFlashUploadInterval );
        if(typeof bfAjaxObject101 != 'undefined' || typeof bfReCaptchaLoaded != 'undefined'){
            ff_submitForm2();
        }else{
            bf_validate_submit(document.getElementById("bfSubmitButton"), "click");
        }
        JQuery(".bfFlashFileQueueClass").html("");
        if(bfFlashUploadersLength > 0){
            JQuery("#bfSubmitMessage").bfcenter(true);
            JQuery("#bfSubmitMessage").css("visibility","visible");
            JQuery("#bfSubmitMessage").css("display","block");
            JQuery("#bfSubmitMessage").css("z-index","999999");
        }

    }
}
