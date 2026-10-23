/* Extracted from BFQuickMode's inline headers() script (Phase 9c step 2b) -
   page navigation, field summarizer and max-length helpers used by
   QuickMode's Bootstrap theme (identical to the classic theme's
   quickmode-core-helpers.js except for the summarizer visibility toggle,
   which targets .bfElemWrap via closest() instead of parent()). Depends on globals declared by other inline
   scripts on the same page: ff_processor.form_id (set up by the page
   header, before this script ever runs) and bfCharsLeftLabel (the
   translated "chars left" suffix, declared inline right before this file
   is loaded). */
                        function bf_validate_nextpage(element, action)
                        {
                            if(typeof bfUseErrorAlerts != "undefined"){
                             JQuery(".bfErrorMessage").html("");
                             JQuery(".bfErrorMessage").css("display","none");
                            }

                            error = ff_validation(ff_currentpage);
                            if (error != "") {
                               if(typeof bfUseErrorAlerts == ""){
                                   alert(error);
                                } else {
                                   bfShowErrors(error);
                                }
                                ff_validationFocus("");
                            } else {
                                ff_switchpage(ff_currentpage+1);
                                self.scrollTo(0,0);
                            }
                        }

			function bfCheckMaxlength(id, maxlength, showMaxlength){
				if( JQuery("#ff_elem"+id).val().length > maxlength ){
					JQuery("#ff_elem"+id).val( JQuery("#ff_elem"+id).val().substring(0, maxlength) );
				}
				if(showMaxlength){
					JQuery("#bfMaxLengthCounter"+id).text( "(" + (maxlength - JQuery("#ff_elem"+id).val().length) + " " + bfCharsLeftLabel + ")" );
				}
			}
			function bfRegisterSummarize(id, connectWith, type, emptyMessage, hideIfEmpty){
				bfSummarizers.push( { id : id, connectWith : connectWith, type : type, emptyMessage : emptyMessage, hideIfEmpty : hideIfEmpty } );
			}
			function bfField(name){
				var value = "";
				switch(ff_getElementByName(name).type){
					case "radio":
						if(JQuery("[name=\""+ff_getElementByName(name).name+"\"]:checked").val() != "" && typeof JQuery("[name=\""+ff_getElementByName(name).name+"\"]:checked").val() != "undefined"){
							value = JQuery("[name=\""+ff_getElementByName(name).name+"\"]:checked").val();
							if(!isNaN(value)){
								value = Number(value);
							}
						}
						break;
					case "checkbox":
					case "select-one":
					case "select-multiple":
						var nodeList = document[ff_processor.form_id][""+ff_getElementByName(name).name+""];
						if(ff_getElementByName(name).type == "checkbox" && typeof nodeList.length == "undefined"){
							if(typeof JQuery("[name=\""+ff_getElementByName(name).name+"\"]:checked").val() != "undefined"){
								value = JQuery("[name=\""+ff_getElementByName(name).name+"\"]:checked").val();
								if(!isNaN(value)){
									value = Number(value);
								}
							}
						} else {
							var val = "";
							for(var j = 0; j < nodeList.length; j++){
								if(nodeList[j].checked || nodeList[j].selected){
									val += nodeList[j].value + ", ";
								}
							}
							if(val != ""){
								value = val.substr(0, val.length - 2);
								if(!isNaN(value)){
									value = Number(value);
								}
							}
						}
						break;
					default:
						if(!isNaN(ff_getElementByName(name).value)){
							value = Number(ff_getElementByName(name).value);
						} else {
							value = ff_getElementByName(name).value;
						}
				}
				return value;
			}
			function populateSummarizers(){
				// cleaning first

				for(var i = 0; i < bfSummarizers.length; i++){
					JQuery("#"+bfSummarizers[i].id).parent().css("display", "");
					JQuery("#"+bfSummarizers[i].id).html("<span class=\"bfNotAvailable\">"+bfSummarizers[i].emptyMessage+"</span>");
				}
				for(var i = 0; i < bfSummarizers.length; i++){
					var summVal = "";
					switch(bfSummarizers[i].type){
						case "bfTextfield":
						case "bfTextarea":
						case "bfHidden":
						case "bfCalendar":
						case "bfNumberInput":
                        case "bfCalendarResponsive":
						case "bfFile":
							if(JQuery("[name=\"ff_nm_"+bfSummarizers[i].connectWith+"[]\"]").val() != ""){
								JQuery("#"+bfSummarizers[i].id).text( JQuery("[name=\"ff_nm_"+bfSummarizers[i].connectWith+"[]\"]").val() ).html();
								var breakableText = JQuery("#"+bfSummarizers[i].id).html().replace(/\\r/g, "").replace(/\\n/g, "<br/>");

								if(breakableText != ""){
									var calc = null;
									calc = typeof window["bfFieldCalc"+bfSummarizers[i].id] == "function" ? window["bfFieldCalc"+bfSummarizers[i].id] : null;
									if(calc){
										breakableText = calc(breakableText);
									}
								}

								JQuery("#"+bfSummarizers[i].id).html(breakableText);
								summVal = breakableText;
							}
						break;
						case "bfRadioGroup":
						case "bfCheckbox":
							if(JQuery("[name=\"ff_nm_"+bfSummarizers[i].connectWith+"[]\"]:checked").val() != "" && typeof JQuery("[name=\"ff_nm_"+bfSummarizers[i].connectWith+"[]\"]:checked").val() != "undefined"){
								var theText = JQuery("[name=\"ff_nm_"+bfSummarizers[i].connectWith+"[]\"]:checked").val();
								var calc = null;
								if(theText != ""){
									calc = typeof window["bfFieldCalc"+bfSummarizers[i].id] == "function" ? window["bfFieldCalc"+bfSummarizers[i].id] : null;
									if(calc){
										theText = calc(theText);
									}
								}
								// fieldCalc is admin-authored and may return HTML; raw option values are text.
								if(calc){
									JQuery("#"+bfSummarizers[i].id).html( theText );
								} else {
									JQuery("#"+bfSummarizers[i].id).text( theText );
								}
								summVal = theText;
							}
						break;
						case "bfCheckboxGroup":
						case "bfSelect":
							var val = "";
							var nodeList = document[ff_processor.form_id]["ff_nm_"+bfSummarizers[i].connectWith+"[]"];

							for(var j = 0; j < nodeList.length; j++){
								if(nodeList[j].checked || nodeList[j].selected){
									val += nodeList[j].value + ", ";
								}
							}
							if(val != ""){
								var theText = val.substr(0, val.length - 2);
								var calc = null;
								if(theText != ""){
									calc = typeof window["bfFieldCalc"+bfSummarizers[i].id] == "function" ? window["bfFieldCalc"+bfSummarizers[i].id] : null;
									if(calc){
										theText = calc(theText);
									}
								}
								// fieldCalc is admin-authored and may return HTML; raw option values are text.
								if(calc){
									JQuery("#"+bfSummarizers[i].id).html( theText );
								} else {
									JQuery("#"+bfSummarizers[i].id).text( theText );
								}
								summVal = theText;
							}
						break;
					}

					if( ( bfSummarizers[i].hideIfEmpty && summVal == "" ) || ( typeof bfDeactivateField != "undefined" && bfDeactivateField["ff_nm_"+bfSummarizers[i].connectWith+"[]"] ) ){
                        JQuery("#"+bfSummarizers[i].id).closest(".bfElemWrap").css("display", "none");
					} else {
                        JQuery("#"+bfSummarizers[i].id).closest(".bfElemWrap").css("display", "block");
					}
				}
			}
