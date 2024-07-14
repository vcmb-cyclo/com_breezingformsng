/*
 * Inline Form Validation Engine 1.7.3, jQuery plugin
 * 
 * Copyright(c) 2010, Cedric Dugas
 * http://www.position-absolute.com
 *	
 * Form validation engine allowing custom regex rules to be added.
 * Thanks to Francois Duquette and Teddy Limousin 
 * and everyone helping me find bugs on the forum
 * Licenced under the MIT Licence
 */
(function(JQuery) {
	
	JQuery.fn.bfvalidationEngine = function(settings) {
		
	if(JQuery.bfvalidationEngineLanguage){				// IS THERE A LANGUAGE LOCALISATION ?
		allRules = JQuery.bfvalidationEngineLanguage.allRules;
	}else{
		JQuery.bfvalidationEngine.debug("Validation engine rules are not loaded check your external file");
	}
 	settings = jQuery.extend({
		allrules:allRules,
		validationEventTriggers:"focusout",					
		inlineValidation: true,	
		returnIsValid:false,
		liveEvent:false,
		openDebug: true,
		unbindEngine:true,
		containerOverflow:false,
		containerOverflowDOM:"",
		ajaxSubmit: false,
		scroll:true,
		promptPosition: "topRight",	// OPENNING BOX POSITION, IMPLEMENTED: topLeft, topRight, bottomLeft, centerRight, bottomRight
		success : false,
		beforeSuccess :  function() {},
		failure : function() {}
	}, settings);	
	JQuery.bfvalidationEngine.settings = settings;
	JQuery.bfvalidationEngine.ajaxValidArray = [];	// ARRAY FOR AJAX: VALIDATION MEMORY 
	
	if(settings.inlineValidation === true){ 		// Validating Inline ?
		if(!settings.returnIsValid){					// NEEDED FOR THE SETTING returnIsValid
			// what the hell! orefalo
			//allowReturnIsvalid = false;
			if(settings.liveEvent){						// LIVE event, vast performance improvement over BIND
				JQuery(this).find("[class*=validate]").on(settings.validationEventTriggers,
					function(caller){ 
						if(JQuery(caller).attr("type") != "checkbox")
							_inlinEvent(this);
					});
				JQuery(this).find("[class*=validate][type=checkbox]").on("click", function(caller){ _inlinEvent(this); });
			}else{
				JQuery(this).find("[class*=validate]").not("[type=checkbox]").bind(settings.validationEventTriggers, function(caller){ _inlinEvent(this); });
				JQuery(this).find("[class*=validate][type=checkbox]").bind("click", function(caller){ _inlinEvent(this); });
			}
			
			// what the hell orefalo
			//firstvalid = false;
		}
		
		function _inlinEvent(caller){
			JQuery.bfvalidationEngine.settings = settings;
			if(JQuery.bfvalidationEngine.intercept === false || !JQuery.bfvalidationEngine.intercept){		// STOP INLINE VALIDATION THIS TIME ONLY
				JQuery.bfvalidationEngine.onSubmitValid=false;
				JQuery.bfvalidationEngine.loadValidation(caller); 
			}else{
				JQuery.bfvalidationEngine.intercept = false;
			}
		}
	}
	if (settings.returnIsValid){		// Do validation and return true or false, it bypass everything;
		if (JQuery.bfvalidationEngine.submitValidation(this,settings)){
			return false;
		}else{
			return true;
		}
	}
	JQuery(this).bind("submit", function(caller){   // ON FORM SUBMIT, CONTROL AJAX FUNCTION IF SPECIFIED ON DOCUMENT READY
		JQuery.bfvalidationEngine.onSubmitValid = true;
		JQuery.bfvalidationEngine.settings = settings;
		if(JQuery.bfvalidationEngine.submitValidation(this,settings) === false){
			if(JQuery.bfvalidationEngine.submitForm(this,settings) === true)
				return false;
		}else{
			// orefalo: what the hell is that ?
			settings.failure && settings.failure(); 
			return false;
		}		
	});
	JQuery(".formError").on("click",function(){	 // REMOVE BOX ON CLICK
		JQuery(this).fadeOut(150,function(){ JQuery(this).remove(); });
	});
};	
JQuery.bfvalidationEngine = {
	defaultSetting : function(caller) {		// NOT GENERALLY USED, NEEDED FOR THE API, DO NOT TOUCH
		if(JQuery.bfvalidationEngineLanguage){				
			allRules = JQuery.bfvalidationEngineLanguage.allRules;
		}else{
			JQuery.bfvalidationEngine.debug("Validation engine rules are not loaded check your external file");
		}	
		settings = {
			allrules:allRules,
			validationEventTriggers:"blur",					
			inlineValidation: true,	
			containerOverflow:false,
			containerOverflowDOM:"",
			returnIsValid:false,
			scroll:true,
			unbindEngine:true,
			ajaxSubmit: false,
			promptPosition: "topRight",	// OPENNING BOX POSITION, IMPLEMENTED: topLeft, topRight, bottomLeft, centerRight, bottomRight
			success : false,
			failure : function() {}
		};	
		JQuery.bfvalidationEngine.settings = settings;
	},
	loadValidation : function(caller) {		// GET VALIDATIONS TO BE EXECUTED
		if(!JQuery.bfvalidationEngine.settings)
			JQuery.bfvalidationEngine.defaultSetting();
		var rulesParsing = JQuery(caller).attr('class');
		var rulesRegExp = /\[(.*)\]/;
		var getRules = rulesRegExp.exec(rulesParsing);
		if(getRules === null)
			return false;
		var str = getRules[1];
		var pattern = /\[|,|\]/;
		var result= str.split(pattern);	
		var validateCalll = JQuery.bfvalidationEngine.validateCall(caller,result);
		return validateCalll;
	},
	validateCall : function(caller,rules) {	// EXECUTE VALIDATION REQUIRED BY THE USER FOR THIS FIELD
		var promptText ="";	
		
		if(!JQuery(caller).attr("id"))
			JQuery.bfvalidationEngine.debug("This field have no ID attribut( name & class displayed): "+JQuery(caller).attr("name")+" "+JQuery(caller).attr("class"));

		// what the hell!
		//caller = caller;
		ajaxValidate = false;
		var callerName = JQuery(caller).attr("name");
		JQuery.bfvalidationEngine.isError = false;
		JQuery.bfvalidationEngine.showTriangle = true;
		var callerType = JQuery(caller).attr("type");

		for (var i=0; i<rules.length;i++){
			switch (rules[i]){
			case "optional": 
				if(!JQuery(caller).val()){
					JQuery.bfvalidationEngine.closePrompt(caller);
					return JQuery.bfvalidationEngine.isError;
				}
			break;
			case "required": 
				_required(caller,rules);
			break;
			case "custom": 
				 _customRegex(caller,rules,i);
			break;
			case "exemptString": 
				 _exemptString(caller,rules,i);
			break;
			case "ajax": 
				if(!JQuery.bfvalidationEngine.onSubmitValid)
					_ajax(caller,rules,i);	
			break;
			case "length": 
				 _length(caller,rules,i);
			break;
			case "maxCheckbox": 
				_maxCheckbox(caller,rules,i);
			 	groupname = JQuery(caller).attr("name");
			 	caller = JQuery("input[name='"+groupname+"']");
			break;
			case "minCheckbox": 
				_minCheckbox(caller,rules,i);
				groupname = JQuery(caller).attr("name");
			 	caller = JQuery("input[name='"+groupname+"']");
			break;
			case "equals": 
				 _equals(caller,rules,i);
			break;
			case "funcCall": 
		     	_funcCall(caller,rules,i);
			break;
			default :
			}
		}
		radioHack();
		if (JQuery.bfvalidationEngine.isError === true){
			var linkTofieldText = "." +JQuery.bfvalidationEngine.linkTofield(caller);
			if(linkTofieldText != "."){
				if(!JQuery(linkTofieldText)[0]){
					JQuery.bfvalidationEngine.buildPrompt(caller,promptText,"error");
				}else{	
					JQuery.bfvalidationEngine.updatePromptText(caller,promptText);
				}	
			}else{
				JQuery.bfvalidationEngine.updatePromptText(caller,promptText);
			}
		}else{
			JQuery.bfvalidationEngine.closePrompt(caller);
		}			
		/* UNFORTUNATE RADIO AND CHECKBOX GROUP HACKS */
		/* As my validation is looping input with id's we need a hack for my validation to understand to group these inputs */
		function radioHack(){
	      if(JQuery("input[name='"+callerName+"']").size()> 1 && (callerType == "radio" || callerType == "checkbox")) {        // Hack for radio/checkbox group button, the validation go the first radio/checkbox of the group
	          caller = JQuery("input[name='"+callerName+"'][type!=hidden]:first");     
	          JQuery.bfvalidationEngine.showTriangle = false;
	      }      
	    }
		/* VALIDATION FUNCTIONS */
		function _required(caller,rules){   // VALIDATE BLANK FIELD
			var callerType = JQuery(caller).attr("type");
			if (callerType == "text" || callerType == "password" || callerType == "textarea"){
								
				if(!JQuery(caller).val()){
					JQuery.bfvalidationEngine.isError = true;
					promptText += JQuery.bfvalidationEngine.settings.allrules[rules[i]].alertText+"<br />";
				}	
			}	
			if (callerType == "radio" || callerType == "checkbox" ){
				callerName = JQuery(caller).attr("name");
		
				if(JQuery("input[name='"+callerName+"']:checked").size() === 0) {
					JQuery.bfvalidationEngine.isError = true;
					if(JQuery("input[name='"+callerName+"']").size() == 1) {
						promptText += JQuery.bfvalidationEngine.settings.allrules[rules[i]].alertTextCheckboxe+"<br />"; 
					}else{
						 promptText += JQuery.bfvalidationEngine.settings.allrules[rules[i]].alertTextCheckboxMultiple+"<br />";
					}	
				}
			}	
			if (callerType == "select-one") { // added by paul@kinetek.net for select boxes, Thank you		
				if(!JQuery(caller).val()) {
					JQuery.bfvalidationEngine.isError = true;
					promptText += JQuery.bfvalidationEngine.settings.allrules[rules[i]].alertText+"<br />";
				}
			}
			if (callerType == "select-multiple") { // added by paul@kinetek.net for select boxes, Thank you	
				if(!JQuery(caller).find("option:selected").val()) {
					JQuery.bfvalidationEngine.isError = true;
					promptText += JQuery.bfvalidationEngine.settings.allrules[rules[i]].alertText+"<br />";
				}
			}
		}
		function _customRegex(caller,rules,position){		 // VALIDATE REGEX RULES
			var customRule = rules[position+1];
			var pattern = eval(JQuery.bfvalidationEngine.settings.allrules[customRule].regex);
			
			if(!pattern.test(JQuery(caller).attr('value'))){
				JQuery.bfvalidationEngine.isError = true;
				promptText += JQuery.bfvalidationEngine.settings.allrules[customRule].alertText+"<br />";
			}
		}
		function _exemptString(caller,rules,position){		 // VALIDATE REGEX RULES
			var customString = rules[position+1];
			if(customString == JQuery(caller).attr('value')){
				JQuery.bfvalidationEngine.isError = true;
				promptText += JQuery.bfvalidationEngine.settings.allrules['required'].alertText+"<br />";
			}
		}
		
		function _funcCall(caller,rules,position){  		// VALIDATE CUSTOM FUNCTIONS OUTSIDE OF THE ENGINE SCOPE
			var customRule = rules[position+1];
			var funce = JQuery.bfvalidationEngine.settings.allrules[customRule].nname;
			
			var fn = window[funce];
			if (typeof(fn) === 'function'){
				var fn_result = fn();
				if(!fn_result){
					JQuery.bfvalidationEngine.isError = true;
				}
				
				promptText += JQuery.bfvalidationEngine.settings.allrules[customRule].alertText+"<br />";
			}
		}
		function _ajax(caller,rules,position){				 // VALIDATE AJAX RULES
			
			customAjaxRule = rules[position+1];
			postfile = JQuery.bfvalidationEngine.settings.allrules[customAjaxRule].file;
			fieldValue = JQuery(caller).val();
			ajaxCaller = caller;
			fieldId = JQuery(caller).attr("id");
			ajaxValidate = true;
			ajaxisError = JQuery.bfvalidationEngine.isError;
			
			if(JQuery.bfvalidationEngine.settings.allrules[customAjaxRule].extraData){
				extraData = JQuery.bfvalidationEngine.settings.allrules[customAjaxRule].extraData;
			}else{
				extraData = "";
			}
			/* AJAX VALIDATION HAS ITS OWN UPDATE AND BUILD UNLIKE OTHER RULES */	
			if(!ajaxisError){
				JQuery.ajax({
				   	type: "POST",
				   	url: postfile,
				   	async: true,
				   	data: "validateValue="+fieldValue+"&validateId="+fieldId+"&validateError="+customAjaxRule+"&extraData="+extraData,
				   	beforeSend: function(){		// BUILD A LOADING PROMPT IF LOAD TEXT EXIST		   			
				   		if(JQuery.bfvalidationEngine.settings.allrules[customAjaxRule].alertTextLoad){
				   		
				   			if(!JQuery("div."+fieldId+"formError")[0]){				   				
	 			 				return JQuery.bfvalidationEngine.buildPrompt(ajaxCaller,JQuery.bfvalidationEngine.settings.allrules[customAjaxRule].alertTextLoad,"load");
	 			 			}else{
	 			 				JQuery.bfvalidationEngine.updatePromptText(ajaxCaller,JQuery.bfvalidationEngine.settings.allrules[customAjaxRule].alertTextLoad,"load");
	 			 			}
			   			}
			  	 	},
			  	 	error: function(data,transport){ JQuery.bfvalidationEngine.debug("error in the ajax: "+data.status+" "+transport); },
					success: function(data){					// GET SUCCESS DATA RETURN JSON
						data = eval( "("+data+")");				// GET JSON DATA FROM PHP AND PARSE IT
						ajaxisError = data.jsonValidateReturn[2];
						customAjaxRule = data.jsonValidateReturn[1];
						ajaxCaller = JQuery("#"+data.jsonValidateReturn[0])[0];
						fieldId = ajaxCaller;
						ajaxErrorLength = JQuery.bfvalidationEngine.ajaxValidArray.length;
						existInarray = false;
						
			 			 if(ajaxisError == "false"){			// DATA FALSE UPDATE PROMPT WITH ERROR;
			 			 	
			 			 	_checkInArray(false);				// Check if ajax validation alreay used on this field
			 			 	
			 			 	if(!existInarray){		 			// Add ajax error to stop submit		 		
				 			 	JQuery.bfvalidationEngine.ajaxValidArray[ajaxErrorLength] =  new Array(2);
				 			 	JQuery.bfvalidationEngine.ajaxValidArray[ajaxErrorLength][0] = fieldId;
				 			 	JQuery.bfvalidationEngine.ajaxValidArray[ajaxErrorLength][1] = false;
				 			 	existInarray = false;
			 			 	}
				
			 			 	JQuery.bfvalidationEngine.ajaxValid = false;
							promptText += JQuery.bfvalidationEngine.settings.allrules[customAjaxRule].alertText+"<br />";
							JQuery.bfvalidationEngine.updatePromptText(ajaxCaller,promptText,"",true);				
						 }else{	 
						 	_checkInArray(true);
						 	JQuery.bfvalidationEngine.ajaxValid = true; 			
						 	if(!customAjaxRule)	{
						 		JQuery.bfvalidationEngine.debug("wrong ajax response, are you on a server or in xampp? if not delete de ajax[ajaxUser] validating rule from your form ");}		   
						 	if(JQuery.bfvalidationEngine.settings.allrules[customAjaxRule].alertTextOk){	// NO OK TEXT MEAN CLOSE PROMPT	 			
	 			 				JQuery.bfvalidationEngine.updatePromptText(ajaxCaller,JQuery.bfvalidationEngine.settings.allrules[customAjaxRule].alertTextOk,"pass",true);
 			 				}else{
				 			 	ajaxValidate = false;		 	
				 			 	JQuery.bfvalidationEngine.closePrompt(ajaxCaller);
 			 				}		
			 			 }
			 			function  _checkInArray(validate){
			 				for(var x=0 ;x<ajaxErrorLength;x++){
			 			 		if(JQuery.bfvalidationEngine.ajaxValidArray[x][0] == fieldId){
			 			 			JQuery.bfvalidationEngine.ajaxValidArray[x][1] = validate;
			 			 			existInarray = true;
			 			 		}
			 			 	}
			 			}
			 		}				
				});
			}
		}
		function _equals(caller,rules,position){		 // VALIDATE FIELD MATCH
			var equalsField = rules[position+1];
			
			if(JQuery(caller).attr('value') != JQuery("#"+equalsField).attr('value')){
				JQuery.bfvalidationEngine.isError = true;
				promptText += JQuery.bfvalidationEngine.settings.allrules["equals"].alertText+"<br />";
			}
		}
		function _length(caller,rules,position){    	  // VALIDATE LENGTH
			var startLength = eval(rules[position+1]);
			var endLength = eval(rules[position+2]);
			var feildLength = JQuery(caller).attr('value').length;

			if(feildLength<startLength || feildLength>endLength){
				JQuery.bfvalidationEngine.isError = true;
				promptText += JQuery.bfvalidationEngine.settings.allrules["length"].alertText+startLength+JQuery.bfvalidationEngine.settings.allrules["length"].alertText2+endLength+JQuery.bfvalidationEngine.settings.allrules["length"].alertText3+"<br />";
			}
		}
		function _maxCheckbox(caller,rules,position){  	  // VALIDATE CHECKBOX NUMBER
		
			var nbCheck = eval(rules[position+1]);
			var groupname = JQuery(caller).attr("name");
			var groupSize = JQuery("input[name='"+groupname+"']:checked").size();
			if(groupSize > nbCheck){	
				JQuery.bfvalidationEngine.showTriangle = false;
				JQuery.bfvalidationEngine.isError = true;
				promptText += JQuery.bfvalidationEngine.settings.allrules["maxCheckbox"].alertText+"<br />";
			}
		}
		function _minCheckbox(caller,rules,position){  	  // VALIDATE CHECKBOX NUMBER
		
			var nbCheck = eval(rules[position+1]);
			var groupname = JQuery(caller).attr("name");
			var groupSize = JQuery("input[name='"+groupname+"']:checked").size();
			if(groupSize < nbCheck){	
			
				JQuery.bfvalidationEngine.isError = true;
				JQuery.bfvalidationEngine.showTriangle = false;
				promptText += JQuery.bfvalidationEngine.settings.allrules["minCheckbox"].alertText+" "+nbCheck+" "+JQuery.bfvalidationEngine.settings.allrules["minCheckbox"].alertText2+"<br />";
			}
		}
		return (JQuery.bfvalidationEngine.isError) ? JQuery.bfvalidationEngine.isError : false;
	},
	submitForm : function(caller){

		if (JQuery.bfvalidationEngine.settings.success) {	// AJAX SUCCESS, STOP THE LOCATION UPDATE
			if(JQuery.bfvalidationEngine.settings.unbindEngine) JQuery(caller).unbind("submit");
			var serializedForm = JQuery(caller).serialize();
			JQuery.bfvalidationEngine.settings.success && JQuery.bfvalidationEngine.settings.success(serializedForm);
			return true;
		}
		return false;
	},
	buildPrompt : function(caller,promptText,type,ajaxed) {			// ERROR PROMPT CREATION AND DISPLAY WHEN AN ERROR OCCUR
		if(!JQuery.bfvalidationEngine.settings) {
			JQuery.bfvalidationEngine.defaultSetting();
		}
		var deleteItself = "." + JQuery(caller).attr("id") + "formError";
	
		if(JQuery(deleteItself)[0]) {
			JQuery(deleteItself).stop();
			JQuery(deleteItself).remove();
		}
		var divFormError = document.createElement('div');
		var formErrorContent = document.createElement('div');
		var linkTofield = JQuery.bfvalidationEngine.linkTofield(caller);
		JQuery(divFormError).addClass("formError");
		
		if(type == "pass")
			JQuery(divFormError).addClass("greenPopup");
		if(type == "load")
			JQuery(divFormError).addClass("blackPopup");
		if(ajaxed)
			JQuery(divFormError).addClass("ajaxed");
		
		JQuery(divFormError).addClass(linkTofield);
		JQuery(formErrorContent).addClass("formErrorContent");
		
		if(JQuery.bfvalidationEngine.settings.containerOverflow)		// Is the form contained in an overflown container?
			JQuery(caller).before(divFormError);
		else
			JQuery("body").append(divFormError);
				
		JQuery(divFormError).append(formErrorContent);
			
		if(JQuery.bfvalidationEngine.showTriangle != false){		// NO TRIANGLE ON MAX CHECKBOX AND RADIO
			var arrow = document.createElement('div');
			JQuery(arrow).addClass("formErrorArrow");
			JQuery(divFormError).append(arrow);
			if(JQuery.bfvalidationEngine.settings.promptPosition == "bottomLeft" || JQuery.bfvalidationEngine.settings.promptPosition == "bottomRight") {
				JQuery(arrow).addClass("formErrorArrowBottom");
				JQuery(arrow).html('<div class="line1"><!-- --></div><div class="line2"><!-- --></div><div class="line3"><!-- --></div><div class="line4"><!-- --></div><div class="line5"><!-- --></div><div class="line6"><!-- --></div><div class="line7"><!-- --></div><div class="line8"><!-- --></div><div class="line9"><!-- --></div><div class="line10"><!-- --></div>');
			}
			else if(JQuery.bfvalidationEngine.settings.promptPosition == "topLeft" || JQuery.bfvalidationEngine.settings.promptPosition == "topRight"){
				JQuery(divFormError).append(arrow);
				JQuery(arrow).html('<div class="line10"><!-- --></div><div class="line9"><!-- --></div><div class="line8"><!-- --></div><div class="line7"><!-- --></div><div class="line6"><!-- --></div><div class="line5"><!-- --></div><div class="line4"><!-- --></div><div class="line3"><!-- --></div><div class="line2"><!-- --></div><div class="line1"><!-- --></div>');
			}
		}
		JQuery(formErrorContent).html(promptText);
		
		var calculatedPosition = JQuery.bfvalidationEngine.calculatePosition(caller,promptText,type,ajaxed,divFormError);
		calculatedPosition.callerTopPosition +="px";
		calculatedPosition.callerleftPosition +="px";
		calculatedPosition.marginTopSize +="px";
		JQuery(divFormError).css({
			"top":calculatedPosition.callerTopPosition,
			"left":calculatedPosition.callerleftPosition,
			"marginTop":calculatedPosition.marginTopSize,
			"opacity":0
		});
		//orefalo - what the hell
		//return JQuery(divFormError).animate({"opacity":0.87},function(){return true;});
		return JQuery(divFormError).animate({"opacity":0.87});	
	},
	updatePromptText : function(caller,promptText,type,ajaxed) {	// UPDATE TEXT ERROR IF AN ERROR IS ALREADY DISPLAYED
		
		var linkTofield = JQuery.bfvalidationEngine.linkTofield(caller);
		var updateThisPrompt =  "."+linkTofield;
		
		if(type == "pass")
			JQuery(updateThisPrompt).addClass("greenPopup");
		else
			JQuery(updateThisPrompt).removeClass("greenPopup");
		
		if(type == "load")
			JQuery(updateThisPrompt).addClass("blackPopup");
		else
			JQuery(updateThisPrompt).removeClass("blackPopup");
		
		if(ajaxed)
			JQuery(updateThisPrompt).addClass("ajaxed");
		else
			JQuery(updateThisPrompt).removeClass("ajaxed");
	
		JQuery(updateThisPrompt).find(".formErrorContent").html(promptText);
		
		var calculatedPosition = JQuery.bfvalidationEngine.calculatePosition(caller,promptText,type,ajaxed,updateThisPrompt);
		calculatedPosition.callerTopPosition +="px";
		calculatedPosition.callerleftPosition +="px";
		calculatedPosition.marginTopSize +="px";
		JQuery(updateThisPrompt).animate({ "top":calculatedPosition.callerTopPosition,"marginTop":calculatedPosition.marginTopSize });
	},
	calculatePosition : function(caller,promptText,type,ajaxed,divFormError){
		
		var callerTopPosition,callerleftPosition,inputHeight,marginTopSize;
		var callerWidth =  JQuery(caller).width();
		
		if(JQuery.bfvalidationEngine.settings.containerOverflow){		// Is the form contained in an overflown container?
			callerTopPosition = 0;
			callerleftPosition = 0;
			inputHeight = JQuery(divFormError).height();					// compasation for the triangle
			marginTopSize = "-"+inputHeight;
		}else{
			callerTopPosition = JQuery(caller).offset().top;
			callerleftPosition = JQuery(caller).offset().left;
			inputHeight = JQuery(divFormError).height();
			marginTopSize = 0;
		}
		
		/* POSITIONNING */
		if(JQuery.bfvalidationEngine.settings.promptPosition == "topRight"){ 
			if(JQuery.bfvalidationEngine.settings.containerOverflow){		// Is the form contained in an overflown container?
				callerleftPosition += callerWidth -30;
			}else{
				callerleftPosition +=  callerWidth -30; 
				callerTopPosition += -inputHeight; 
			}
		}
		if(JQuery.bfvalidationEngine.settings.promptPosition == "topLeft"){ callerTopPosition += -inputHeight -10; }
		
		if(JQuery.bfvalidationEngine.settings.promptPosition == "centerRight"){ callerleftPosition +=  callerWidth +13; }
		
		if(JQuery.bfvalidationEngine.settings.promptPosition == "bottomLeft"){
			callerTopPosition = callerTopPosition + JQuery(caller).height() + 15;
		}
		if(JQuery.bfvalidationEngine.settings.promptPosition == "bottomRight"){
			callerleftPosition +=  callerWidth -30;
			callerTopPosition +=  JQuery(caller).height() +5;
		}
		return {
			"callerTopPosition":callerTopPosition,
			"callerleftPosition":callerleftPosition,
			"marginTopSize":marginTopSize
		};
	},
	linkTofield : function(caller){
		var linkTofield = JQuery(caller).attr("id") + "formError";
		linkTofield = linkTofield.replace(/\[/g,""); 
		linkTofield = linkTofield.replace(/\]/g,"");
		return linkTofield;
	},
	closePrompt : function(caller,outside) {						// CLOSE PROMPT WHEN ERROR CORRECTED
		if(!JQuery.bfvalidationEngine.settings){
			JQuery.bfvalidationEngine.defaultSetting();
		}
		if(outside){
			JQuery(caller).fadeTo("fast",0,function(){
				JQuery(caller).remove();
			});
			return false;
		}
		
		// orefalo -- review conditions non sense
		if(typeof(ajaxValidate)=='undefined')
		{ ajaxValidate = false; }
		if(!ajaxValidate){
			var linkTofield = JQuery.bfvalidationEngine.linkTofield(caller);
			var closingPrompt = "."+linkTofield;
			JQuery(closingPrompt).fadeTo("fast",0,function(){
				JQuery(closingPrompt).remove();
			});
		}
	},
	debug : function(error) {
		if(!JQuery.bfvalidationEngine.settings.openDebug) return false;
		if(!JQuery("#debugMode")[0]){
			JQuery("body").append("<div id='debugMode'><div class='debugError'><strong>This is a debug mode, you got a problem with your form, it will try to help you, refresh when you think you nailed down the problem</strong></div></div>");
		}
		JQuery(".debugError").append("<div class='debugerror'>"+error+"</div>");
	},			
	submitValidation : function(caller) {					// FORM SUBMIT VALIDATION LOOPING INLINE VALIDATION
		var stopForm = false;
		JQuery.bfvalidationEngine.ajaxValid = true;
		var toValidateSize = JQuery(caller).find("[class*=validate]").size();
		
		JQuery(caller).find("[class*=validate]").each(function(){
			var linkTofield = JQuery.bfvalidationEngine.linkTofield(this);
			
			if(!JQuery("."+linkTofield).hasClass("ajaxed")){	// DO NOT UPDATE ALREADY AJAXED FIELDS (only happen if no normal errors, don't worry)
				var validationPass = JQuery.bfvalidationEngine.loadValidation(this);
				return(validationPass) ? stopForm = true : "";					
			};
		});
		var ajaxErrorLength = JQuery.bfvalidationEngine.ajaxValidArray.length;		// LOOK IF SOME AJAX IS NOT VALIDATE
		for(var x=0;x<ajaxErrorLength;x++){
	 		if(JQuery.bfvalidationEngine.ajaxValidArray[x][1] == false)
	 			JQuery.bfvalidationEngine.ajaxValid = false;
 		}
		if(stopForm || !JQuery.bfvalidationEngine.ajaxValid){		// GET IF THERE IS AN ERROR OR NOT FROM THIS VALIDATION FUNCTIONS
			if(JQuery.bfvalidationEngine.settings.scroll){
				if(!JQuery.bfvalidationEngine.settings.containerOverflow){
					var destination = JQuery(".formError:not('.greenPopup'):first").offset().top;
					JQuery(".formError:not('.greenPopup')").each(function(){
						var testDestination = JQuery(this).offset().top;
						if(destination>testDestination)
							destination = JQuery(this).offset().top;
					});
					JQuery("html:not(:animated),body:not(:animated)").animate({ scrollTop: destination}, 1100);
				}else{
					var destination = JQuery(".formError:not('.greenPopup'):first").offset().top;
					var scrollContainerScroll = JQuery(JQuery.bfvalidationEngine.settings.containerOverflowDOM).scrollTop();
					var scrollContainerPos = - parseInt(JQuery(JQuery.bfvalidationEngine.settings.containerOverflowDOM).offset().top);
					destination = scrollContainerScroll + destination + scrollContainerPos -5;
					var scrollContainer = JQuery.bfvalidationEngine.settings.containerOverflowDOM+":not(:animated)";
					
					JQuery(scrollContainer).animate({ scrollTop: destination}, 1100);
				}
			}
			return true;
		}else{
			return false;
		}
	}
};
})(jQuery);