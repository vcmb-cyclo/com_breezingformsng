/* Extracted from BFQuickMode's inline headers() script (Phase 9c step 2b) -
   the "toggle fields" conditional show/hide logic used by QuickMode's
   classic theme. Depends on a global `toggleFieldsArray`, declared by a
   short inline script emitted alongside this file; only referenced from
   inside these functions, so load order between the two does not matter. */
String.prototype.beginsWith = function(t, i) {
  if (i == false) {
    return (t == this.substring(0, t.length));
  } else {
    return (t.toLowerCase() ==
      this.substring(0, t.length).toLowerCase());
  }
}

function bfDeactivateSectionFields() {
  for (var i = 0; i < bfDeactivateSection.length; i++) {
    bfSetFieldValue(bfDeactivateSection[i], "off");
    JQuery("#" + bfDeactivateSection[i] + " .ff_elem").each(function(i) {
      if (JQuery(this).get(0).name && JQuery(this).get(0).name.beginsWith("ff_nm_", true)) {
        bfDeactivateField[JQuery(this).get(0).name] = true;
      }
    });
  }
  for (var i = 0; i < toggleFieldsArray.length; i++) {
    if (toggleFieldsArray[i].state == "turn") {
      bfSetFieldValue(toggleFieldsArray[i].tName, "off");
    }
  }
  
  bfSectionFieldsDeactivated = true;
}

function bfToggleFields(state, tCat, tName, thisBfDeactivateField) {
  if (state == "on") {
    if (tCat == "element") {
      JQuery("[name=\"ff_nm_" + tName + "[]\"]").closest(".bfElemWrap").css("display", "");
      thisBfDeactivateField["ff_nm_" + tName + "[]"] = false;
      bfSetFieldValue(tName, "on");
    } else {
      JQuery("#" + tName).css("display", "");
      bfSetFieldValue(tName, "on");
      JQuery("#" + tName).find(".ff_elem").each(function(i) {
        if (JQuery(this).get(0).name && JQuery(this).get(0).name.beginsWith("ff_nm_", true)) {
          thisBfDeactivateField[JQuery(this).get(0).name] = false;
        }
      });
    }
  } else {
    if (tCat == "element") {
      JQuery("[name=\"ff_nm_" + tName + "[]\"]").closest(".bfElemWrap").css("display", "none");
      thisBfDeactivateField["ff_nm_" + tName + "[]"] = true;
      bfSetFieldValue(tName, "off");
    } else {
      JQuery("#" + tName).css("display", "none");
      bfSetFieldValue(tName, "off");
      JQuery("#" + tName + " .ff_elem").each(function(i) {
        if (JQuery(this).get(0).name && JQuery(this).get(0).name.beginsWith("ff_nm_", true)) {
          thisBfDeactivateField[JQuery(this).get(0).name] = true;
        }
      });
    }
  }
  if (typeof bfRefreshAll != "undefined") {
    bfRefreshAll();
  }
}

function bfSetFieldValue(name, condition) {
  for (var i = 0; i < toggleFieldsArray.length; i++) {
    if (toggleFieldsArray[i].action == "if") {
      if (name == toggleFieldsArray[i].tCat && condition == toggleFieldsArray[i].statement) {

        var element = JQuery("[name=\"ff_nm_" + toggleFieldsArray[i].condition + "[]\"]");

        switch (element.get(0).type) {
          case "text":
          case "textarea":
            if (toggleFieldsArray[i].value == "!empty") {
              element.val("");
            } else {
              element.val(toggleFieldsArray[i].value);
            }
            element.trigger("change");
            break;
          case "select-multiple":
          case "select-one":
            if (toggleFieldsArray[i].value == "!empty") {
              for (var j = 0; j < element.get(0).options.length; j++) {
                element.get(0).options[j].selected = false;
                JQuery(element.get(0).options[j]).trigger("change");
              }
            }
            for (var j = 0; j < element.get(0).options.length; j++) {
              if (element.get(0).options[j].value == toggleFieldsArray[i].value) {
                element.get(0).options[j].selected = true;
                JQuery(element.get(0).options[j]).trigger("change");
              }
            }
            break;
          case "radio":
          case "checkbox":
            var radioLength = element.length;
            if (toggleFieldsArray[i].value == "!empty") {
              for (var j = 0; j < radioLength; j++) {
                element.get(j).checked = false;
                JQuery(element.get(j)).trigger("change");
              }
            }
            for (var j = 0; j < radioLength; j++) {
              if (element.get(j).value == toggleFieldsArray[i].value) {
                element.get(j).checked = true;
                JQuery(element.get(j)).trigger("change");
              }
            }
            break;
        }
      }
    }
  }
}

function bfRegisterToggleFields() {

  var offset = 0;
  var last_offset = 0;
  var limit = 10;
  var limit_cnt = 0;

  if (arguments.length == 1) {
    offset = arguments[0];
  }

  var thisToggleFieldsArray = toggleFieldsArray;
  var thisBfDeactivateField = bfDeactivateField;
  var thisBfToggleFields = bfToggleFields;

  for (var i = offset; limit_cnt < limit && i < toggleFieldsArray.length; i++) {
    // console.log(toggleFieldsArray[i]);
    //  for( var i = 0; i < toggleFieldsArray.length; i++ ){
    if (toggleFieldsArray[i].action == "turn" && (toggleFieldsArray[i].tCat == "element" || toggleFieldsArray[i].tCat == "section")) {
      var toggleField = toggleFieldsArray[i];
      var element = JQuery("[name=\"ff_nm_" + toggleFieldsArray[i].sName + "[]\"]");
      if (element.get(0)) {
        switch (element.get(0).type) {
          case "text":
          case "textarea":
            JQuery("[name=\"ff_nm_" + toggleField.sName + "[]\"]").unbind("blur");
            JQuery("[name=\"ff_nm_" + toggleField.sName + "[]\"]").blur(
              function() {
                for (var k = 0; k < thisToggleFieldsArray.length; k++) {
                  var regExp = "";
                  var testRegExp = null;
                  if (thisToggleFieldsArray[k].value.beginsWith("!", true) && JQuery(this).get(0).name == "ff_nm_" + thisToggleFieldsArray[k].sName + "[]") {
                    regExp = thisToggleFieldsArray[k].value.substring(1, thisToggleFieldsArray[k].value.length);
                    testRegExp = new RegExp(regExp);
                  }

                  if (thisToggleFieldsArray[k].condition == "isnot") {
                    if (
                      ((regExp != "" && testRegExp.test(JQuery(this).val()) == false) || JQuery(this).val() != thisToggleFieldsArray[k].value) && JQuery(this).get(0).name == "ff_nm_" + thisToggleFieldsArray[k].sName + "[]"
                    ) {
                      var names = thisToggleFieldsArray[k].tName.split(",");
                      for (var n = 0; n < names.length; n++) {
                        thisBfToggleFields(thisToggleFieldsArray[k].state, thisToggleFieldsArray[k].tCat, JQuery.trim(names[n]), thisBfDeactivateField);
                      }
                      //break;
                    }
                  } else if (thisToggleFieldsArray[k].condition == "is") {
                    if (
                      ((regExp != "" && testRegExp.test(JQuery(this).val()) == true) || JQuery(this).val() == thisToggleFieldsArray[k].value) && JQuery(this).get(0).name == "ff_nm_" + thisToggleFieldsArray[k].sName + "[]"
                    ) {
                      var names = thisToggleFieldsArray[k].tName.split(",");
                      for (var n = 0; n < names.length; n++) {
                        thisBfToggleFields(thisToggleFieldsArray[k].state, thisToggleFieldsArray[k].tCat, JQuery.trim(names[n]), thisBfDeactivateField);
                      }
                      //break;
                    }
                  }
                }
              }
            );
            break;
          case "select-multiple":
          case "select-one":
            JQuery("[name=\"ff_nm_" + toggleField.sName + "[]\"]").unbind("change");
            JQuery("[name=\"ff_nm_" + toggleField.sName + "[]\"]").change(
              function() {
                var res = JQuery.isArray(JQuery(this).val()) == false ? [JQuery(this).val()] : JQuery(this).val();
                for (var k = 0; k < thisToggleFieldsArray.length; k++) {

                  // The or-case in lists
                  var found = false;
                  var chkGrpValues = new Array();
                  if (thisToggleFieldsArray[k].value.beginsWith("#", true) && JQuery(this).get(0).name == "ff_nm_" + thisToggleFieldsArray[k].sName + "[]") {
                    chkGrpValues = thisToggleFieldsArray[k].value.substring(1, thisToggleFieldsArray[k].value.length).split("|");
                    for (var l = 0; l < chkGrpValues.length; l++) {
                      if (JQuery.inArray(chkGrpValues[l], res) != -1) {
                        found = true;
                        break;
                      }
                    }
                  }
                  // the and-case in lists
                  var foundCount = 0;
                  chkGrpValues2 = new Array();
                  if (thisToggleFieldsArray[k].value.beginsWith("#", true) && JQuery(this).get(0).name == "ff_nm_" + thisToggleFieldsArray[k].sName + "[]") {
                    chkGrpValues2 = thisToggleFieldsArray[k].value.substring(1, thisToggleFieldsArray[k].value.length).split(";");
                    for (var l = 0; l < res.length; l++) {
                      if (JQuery.inArray(res[l], chkGrpValues2) != -1) {
                        foundCount++;
                      }
                    }
                  }

                  if (thisToggleFieldsArray[k].condition == "isnot") {

                    if (
                      (!JQuery.isArray(res) && JQuery(this).val() != thisToggleFieldsArray[k].value && JQuery(this).get(0).name == "ff_nm_" + thisToggleFieldsArray[k].sName + "[]") ||
                      (
                        JQuery.isArray(res) && (JQuery.inArray(thisToggleFieldsArray[k].value, res) == -1 || !found || (foundCount == 0 || foundCount != chkGrpValues2.length)) && JQuery(this).get(0).name == "ff_nm_" + thisToggleFieldsArray[k].sName + "[]"
                      )
                    ) {
                      var names = thisToggleFieldsArray[k].tName.split(",");
                      for (var n = 0; n < names.length; n++) {
                        thisBfToggleFields(thisToggleFieldsArray[k].state, thisToggleFieldsArray[k].tCat, JQuery.trim(names[n]), thisBfDeactivateField);
                      }
                      //break;
                    }
                  } else if (thisToggleFieldsArray[k].condition == "is") {
                    if (
                      (!JQuery.isArray(res) && JQuery(this).val() == thisToggleFieldsArray[k].value && JQuery(this).get(0).name == "ff_nm_" + thisToggleFieldsArray[k].sName + "[]") ||
                      (
                        JQuery.isArray(res) && (JQuery.inArray(thisToggleFieldsArray[k].value, res) != -1 || found || (foundCount != 0 && foundCount == chkGrpValues2.length)) && JQuery(this).get(0).name == "ff_nm_" + thisToggleFieldsArray[k].sName + "[]"
                      )
                    ) {
                      var names = thisToggleFieldsArray[k].tName.split(",");
                      for (var n = 0; n < names.length; n++) {
                        thisBfToggleFields(thisToggleFieldsArray[k].state, thisToggleFieldsArray[k].tCat, JQuery.trim(names[n]), thisBfDeactivateField);
                      }
                      //break;
                    }
                  }
                }
              }
            );
            break;
          case "radio":
          case "checkbox": // needs revision
            var radioLength = JQuery("[name=\"ff_nm_" + toggleField.sName + "[]\"]").length;
            for (var j = 0; j < radioLength; j++) {
              JQuery("#" + JQuery("[name=\"ff_nm_" + toggleField.sName + "[]\"]").get(j).id).off("click");
              JQuery("#" + JQuery("[name=\"ff_nm_" + toggleField.sName + "[]\"]").get(j).id).on("click",
                function() {
                  // NOT O(n^2) since its ony executed on click event!
                  var tarElem = JQuery(this).get(0);

                  for (var k = 0; k < thisToggleFieldsArray.length; k++) {

                    if (tarElem.name == "ff_nm_" + thisToggleFieldsArray[k].sName + "[]" && (tarElem.type == "checkbox" || tarElem.type == "radio")) {
                      var checkedOpts = JQuery("[name=\"" + JQuery(this).get(0).name + "\"]:checked");
                      var selectedVals = [];
                      for (var i = 0; i < checkedOpts.length; i++) {
                        selectedVals.push(checkedOpts[i].value);
                      }

                      var thisGrpVals = [];
                      var found = false;
                      var foundCount = 0;
                      var delimiter = "";

                      if (thisToggleFieldsArray[k].value.beginsWith("#", true)) {
                        if (thisToggleFieldsArray[k].value.indexOf("|") > -1) {
                          delimiter = "|";
                        } else if (thisToggleFieldsArray[k].value.indexOf(";") > -1) {
                          delimiter = ";";
                        }
                        thisGrpVals = thisToggleFieldsArray[k].value.substring(1, thisToggleFieldsArray[k].value.length).split(delimiter);

                        for (var l = 0; l < selectedVals.length; l++) {
                          if (JQuery.inArray(selectedVals[l], thisGrpVals) != -1) {
                            foundCount++;
                            found = true;
                            continue;
                          }
                        }
                      }
                      var names = thisToggleFieldsArray[k].tName.split(",");
                      var n = names.length;

                      if (thisToggleFieldsArray[k].condition == "isnot" && // check the condition 
                        (
                          ( // The simple checked or unchecked
                            (thisToggleFieldsArray[k].value == "!checked" && tarElem.checked == false) ||
                            (thisToggleFieldsArray[k].value == "!unchecked" && tarElem.checked)
                          ) ||
                          ( // simple check using only single value
                            JQuery.inArray(thisToggleFieldsArray[k].value, selectedVals) == -1 ||
                            (JQuery.inArray(thisToggleFieldsArray[k].value, selectedVals) != -1 && selectedVals.length != 1)
                          ) ||
                          ( // multiple values rule using either OR or AND
                            thisToggleFieldsArray[k].value.beginsWith("#", true) &&
                            (
                              (delimiter == "|" && found == false) || (delimiter == ";" && foundCount == thisGrpVals.length)
                            )
                          )
                        )) {
                        n = 0;
                      } else if (thisToggleFieldsArray[k].condition == "is" && // check the condition
                        (
                          ( // the simple checked or unchecked for a single checkbox
                            (thisToggleFieldsArray[k].value == "!checked" && tarElem.checked) ||
                            (thisToggleFieldsArray[k].value == "!unchecked" && tarElem.checked == false)
                          ) ||
                          ( // the simple check using only single value
                            JQuery.inArray(thisToggleFieldsArray[k].value, selectedVals) != -1
                          ) ||
                          ( // multiple values rule using either OR or AND
                            thisToggleFieldsArray[k].value.beginsWith("#", true) &&
                            (
                              delimiter == "|" && found || (delimiter == ";" && foundCount == thisGrpVals.length)
                            )
                          )
                        )
                      ) {
                        n = 0;
                      }
                      for (n; n < names.length; n++) {
                        thisBfToggleFields(thisToggleFieldsArray[k].state, thisToggleFieldsArray[k].tCat, JQuery.trim(names[n]), thisBfDeactivateField);
                      }
                    }
                  }
                });
            }
            break;
        }
      }
    }

    limit_cnt++;
    last_offset = i;
  }

  if (last_offset + 1 < toggleFieldsArray.length) {
    setTimeout("bfRegisterToggleFields( " + last_offset + " )", 100);
  }
  if (last_offset + 1 == toggleFieldsArray.length) {
    bfTriggerRules();
  }
}

function bfTriggerRules() {
  for (var i = 0; i < toggleFieldsArray.length; i++) {
    var curElem = toggleFieldsArray[i];
    if (curElem.action == "turn") {
      if (JQuery("[name=\"ff_nm_" + curElem.sName + "[]\"]").length < 1) {
        break;
      } 

      var elemType = JQuery("[name=\"ff_nm_" + curElem.sName + "[]\"]")[0].type;

      switch (elemType) {
        case "text":
        case "textarea":
          JQuery("[name=\"ff_nm_" + curElem.sName + "[]\"]").triggerHandler("blur");
          break;
        case "radio":
          JQuery("[name=\"ff_nm_" + curElem.sName + "[]\"]").triggerHandler("click");
          break;
        case "checkbox":
          var el = (JQuery("[name=\"ff_nm_" + curElem.sName + "[]\"]"));
          for (count = 0; count < el.length; count++) {
            if (count == 0) {
              JQuery("#" + el.get(0).id).triggerHandler("click");
            } else {
              JQuery("#" + el.get(0).id + "_" + count).triggerHandler("click");
            }
          }
          break;
        case "select-one":
        case "select-multiple":
          JQuery("[name=\"ff_nm_" + curElem.sName + "[]\"]").triggerHandler("change");
          break;
      }
    }
  }
  
  bfToggleFieldsLoaded = true;
}