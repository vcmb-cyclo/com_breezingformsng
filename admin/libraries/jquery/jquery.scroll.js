jQuery(function(){
	
	var shouldDockPanel = jQuery('#triggerScrollable');
	var shouldScrollElementList = jQuery('#scrollElementList');
	var isWideEnough=true;
	var theHeight;
	
	
	function doCheckResizePls(){		
		if(shouldScrollElementList.is(":checked")){
			jQuery("#bfElementExplorer").css({'overflow-y':'auto'});
			jQuery("#bfElementExplorer").css('max-height', theHeight-40).trigger('resize'); //-40 because header
		}else{
			jQuery("#bfElementExplorer").css({height:'auto','overflow-y':'hidden'});
		}
		
		if(shouldDockPanel.is(":checked")){
			jQuery("#menutab .m").css({'overflow-y':'scroll'});
			jQuery("#menutab .m").css('max-height', theHeight-40).trigger('resize'); //-40 because header
		}else{
			jQuery("#menutab .m").css({height:'auto','overflow-y':'hidden'});
			jQuery("#menutab .m").css('max-height', 'none').trigger('resize');
		}
	}
	
	doCheckResizePls(); //callamo na početku
	
	shouldScrollElementList.change(function(){ //i callamo na svaku promjenu checkboxa
		doCheckResizePls();
	});
	
	shouldDockPanel.change(function() {
		doCheckResizePls();
	});
	
	function getVariables(){
		if(jQuery(window).width()>=767){
			theHeight= jQuery(window).height()  - jQuery('#menutab .t').offset().top;
		}else{
			theHeight=400;
		}
	}
	
	setInterval(getVariables, 600);
	
	
});



