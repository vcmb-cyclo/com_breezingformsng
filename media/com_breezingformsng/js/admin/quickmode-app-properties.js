/* Extracted from quickmode-app.js — QuickMode element property save/populate handlers. */
import { JoomlaEditor } from 'editor-api';

/* global BFQMConfig, JQuery, jQuery, Joomla */
(function () {
    'use strict';

    window.BFQM = window.BFQM || {};

    window.BFQM.installPropertyHandlers = function (appScope) {
        appScope.saveTextProperties = function (mdata, item) {
            mdata.value = JQuery('#bfElementTypeTextValue').val();
            mdata['value_translation'+BFQMConfig.lang] = JQuery('#bfElementTypeTextValueTrans').val();
            mdata.placeholder = JQuery('#bfElementTypeTextPlaceholder').val();
            mdata['placeholder_translation'+BFQMConfig.lang] = JQuery('#bfElementTypeTextPlaceholderTrans').val();
            mdata.bfName = JQuery('#bfElementName').val();
            mdata.logging = JQuery('#bfElementAdvancedLogging').attr('checked');
            mdata.label = JQuery('#bfElementLabel').val();
            mdata['label_translation'+BFQMConfig.lang] = JQuery('#bfElementLabelTrans').val();
            mdata.maxLength = JQuery('#bfElementTypeTextMaxLength').val();
            mdata.icon = JQuery('#bfElementTypeTextIcon').val();

            mdata.hint = JQuery('#bfElementTypeTextHint').val();
            mdata['hint_translation'+BFQMConfig.lang] = JQuery('#bfElementTypeTextHintTrans').val();

            mdata.password = JQuery('#bfElementAdvancedPassword').attr('checked');
            mdata.readonly = JQuery('#bfElementAdvancedReadOnly').attr('checked');
            mdata.mailback = JQuery('#bfElementAdvancedMailback').attr('checked');
            mdata.mailbackAsSender = JQuery('#bfElementAdvancedMailbackAsSender').attr('checked');
            mdata.mailbackfile = JQuery('#bfElementAdvancedMailbackfile').val();
            mdata.labelPosition = JQuery('#bfElementAdvancedLabelPosition').val();
            mdata.tabIndex = JQuery('#bfElementAdvancedTabIndex').val();
            mdata.off = JQuery('#bfElementAdvancedTurnOff').attr('checked');
            mdata.hideInMailback = JQuery('#bfElementAdvancedHideInMailback').attr('checked');
            mdata.hideLabel = JQuery('#bfElementAdvancedHideLabel').attr('checked');
            mdata.size = JQuery('#bfElementTypeTextSize').val();
            mdata.orderNumber = JQuery('#bfElementOrderNumber').val();
            mdata.required = JQuery('#bfElementValidationRequired').attr('checked');
            item.properties = mdata;
        };

        appScope.populateTextProperties = function (mdata) {

            JQuery('#bfElementTypeTextValue').val(mdata.value);
            JQuery('#bfElementTypeTextValueTrans').val(typeof mdata['value_translation'+BFQMConfig.lang] != "undefined" ? mdata['value_translation'+BFQMConfig.lang] : "");

            if (typeof mdata.placeholder == "undefined") {
                mdata['placeholder'] = '';
            }
            JQuery('#bfElementTypeTextPlaceholder').val(mdata.placeholder);
            JQuery('#bfElementTypeTextPlaceholderTrans').val(typeof mdata['placeholder_translation'+BFQMConfig.lang] != "undefined" ? mdata['placeholder_translation'+BFQMConfig.lang] : "");

            JQuery('#bfElementName').val(mdata.bfName);
            JQuery('#bfElementLabel').val(mdata.label);
            JQuery('#bfElementLabelTrans').val(typeof mdata['label_translation'+BFQMConfig.lang] != "undefined" ? mdata['label_translation'+BFQMConfig.lang] : "");
            JQuery('#bfElementAdvancedLogging').attr('checked', mdata.logging);
            JQuery('#bfElementTypeTextMaxLength').val(mdata.maxLength);

            JQuery('#bfElementTypeTextHint').val(mdata.hint);
            JQuery('#bfElementTypeTextHintTrans').val(typeof mdata['hint_translation'+BFQMConfig.lang] != "undefined" ? mdata['hint_translation'+BFQMConfig.lang] : "");

            JQuery('#bfElementAdvancedPassword').attr('checked', mdata.password);
            JQuery('#bfElementAdvancedReadOnly').attr('checked', mdata.readonly);
            JQuery('#bfElementAdvancedMailback').attr('checked', mdata.mailback);
            JQuery('#bfElementAdvancedMailbackAsSender').attr('checked', mdata.mailbackAsSender);
            JQuery('#bfElementAdvancedMailbackfile').val(mdata.mailbackfile);
            JQuery('#bfElementAdvancedLabelPosition').val(mdata.labelPosition);
            JQuery('#bfElementAdvancedTabIndex').val(mdata.tabIndex);
            JQuery('#bfElementAdvancedHideInMailback').attr('checked', mdata.hideInMailback);
            JQuery('#bfElementAdvancedTurnOff').attr('checked', mdata.off);
            JQuery('#bfElementAdvancedHideLabel').attr('checked', mdata.hideLabel);
            JQuery('#bfElementTypeTextSize').val(mdata.size);
            JQuery('#bfElementOrderNumber').val(mdata.orderNumber);
            JQuery('#bfElementValidationRequired').attr('checked', mdata.required);

            JQuery('#bfElementTypeTextIcon').val(mdata.icon);
        };

        // TEXTAREA
        appScope.saveTextareaProperties = function (mdata, item) {
            mdata.value = JQuery('#bfElementTypeTextareaValue').val();
            mdata['value_translation'+BFQMConfig.lang] = JQuery('#bfElementTypeTextareaValueTrans').val();

            mdata.placeholder = JQuery('#bfElementTypeTextareaPlaceholder').val();
            mdata['placeholder_translation'+BFQMConfig.lang] = JQuery('#bfElementTypeTextareaPlaceholderTrans').val();

            mdata.is_html = JQuery('#bfElementTypeTextareaIsHtml').attr('checked');
            mdata.bfName = JQuery('#bfElementName').val();
            mdata.logging = JQuery('#bfElementTextareaAdvancedLogging').attr('checked');

            mdata.label = JQuery('#bfElementLabel').val();
            mdata['label_translation'+BFQMConfig.lang] = JQuery('#bfElementLabelTrans').val();

            mdata.hint = JQuery('#bfElementTypeTextareaHint').val();
            mdata['hint_translation'+BFQMConfig.lang] = JQuery('#bfElementTypeTextareaHintTrans').val();
            mdata.icon = JQuery('#bfElementTypeTextareaIcon').val();


            mdata.width = JQuery('#bfElementTypeTextareaWidth').val();
            mdata.height = JQuery('#bfElementTypeTextareaHeight').val();
            mdata.maxlength = JQuery('#bfElementTypeTextareaMaxLength').val();
            mdata.showMaxlengthCounter = JQuery('#bfElementTypeTextareaMaxLengthShow').attr('checked');
            mdata.labelPosition = JQuery('#bfElementAdvancedLabelPosition').val();
            mdata.tabIndex = JQuery('#bfElementAdvancedTabIndex').val();
            mdata.hideInMailback = JQuery('#bfElementAdvancedHideInMailback').attr('checked');
            mdata.off = JQuery('#bfElementAdvancedTurnOff').attr('checked');
            mdata.hideLabel = JQuery('#bfElementTextareaAdvancedHideLabel').attr('checked');
            mdata.orderNumber = JQuery('#bfElementTextareaAdvancedOrderNumber').val();
            mdata.required = JQuery('#bfElementValidationRequired').attr('checked');
            item.properties = mdata;
        };

        appScope.populateTextareaProperties = function (mdata) {
            JQuery('#bfElementTypeTextareaValue').val(mdata.value);
            JQuery('#bfElementTypeTextareaValueTrans').val(typeof mdata['value_translation'+BFQMConfig.lang] != "undefined" ? mdata['value_translation'+BFQMConfig.lang] : "");

            if (typeof mdata.placeholder == "undefined") {
                mdata['placeholder'] = '';
            }
            JQuery('#bfElementTypeTextareaPlaceholder').val(mdata.placeholder);
            JQuery('#bfElementTypeTextareaPlaceholderTrans').val(typeof mdata['placeholder_translation'+BFQMConfig.lang] != "undefined" ? mdata['placeholder_translation'+BFQMConfig.lang] : "");

            JQuery('#bfElementTypeTextareaIsHtml').attr('checked', mdata.is_html);
            JQuery('#bfElementName').val(mdata.bfName);

            JQuery('#bfElementLabel').val(mdata.label);
            JQuery('#bfElementLabelTrans').val(typeof mdata['label_translation'+BFQMConfig.lang] != "undefined" ? mdata['label_translation'+BFQMConfig.lang] : "");

            JQuery('#bfElementTextareaAdvancedLogging').attr('checked', mdata.logging);

            JQuery('#bfElementTypeTextareaHint').val(mdata.hint);
            JQuery('#bfElementTypeTextareaHintTrans').val(typeof mdata['hint_translation'+BFQMConfig.lang] != "undefined" ? mdata['hint_translation'+BFQMConfig.lang] : "");

            JQuery('#bfElementAdvancedLabelPosition').val(mdata.labelPosition);
            JQuery('#bfElementAdvancedTabIndex').val(mdata.tabIndex);
            JQuery('#bfElementAdvancedHideInMailback').attr('checked', mdata.hideInMailback);
            JQuery('#bfElementAdvancedTurnOff').attr('checked', mdata.off);
            JQuery('#bfElementTextareaAdvancedHideLabel').attr('checked', mdata.hideLabel);
            JQuery('#bfElementTypeTextareaWidth').val(mdata.width);
            JQuery('#bfElementTypeTextareaHeight').val(mdata.height);
            JQuery('#bfElementTypeTextareaIsHtml').val(mdata.is_html);
            // compat 723
            if (typeof mdata.maxlength == "undefined") {
                mdata["maxlength"] = 0;
            }
            if (typeof mdata.showMaxlengthCounter == "undefined") {
                mdata["showMaxlengthCounter"] = true;
            }
            // end compat 723
            JQuery('#bfElementTypeTextareaMaxLength').val(!isNaN(mdata.maxlength) ? mdata.maxlength : 0);
            JQuery('#bfElementTypeTextareaMaxLengthShow').attr('checked', mdata.showMaxlengthCounter);
            JQuery('#bfElementTextareaAdvancedOrderNumber').val(mdata.orderNumber);
            JQuery('#bfElementValidationRequired').attr('checked', mdata.required);

            JQuery('#bfElementTypeTextareaIcon').val(mdata.icon);
        };

        // NUMBER ELEMENT
        appScope.saveNumberInputProperties = function (mdata, item) {
            mdata.value = JQuery('#bfElementTypeNumberInputValue').val();
            mdata['value_translation'+BFQMConfig.lang] = JQuery('#bfElementTypeNumberInputValueTrans').val();
            mdata.placeholder = JQuery('#bfElementTypeNumberInputPlaceholder').val();
            mdata['placeholder_translation'+BFQMConfig.lang] = JQuery('#bfElementTypeNumberInputPlaceholderTrans').val();
            mdata.bfName = JQuery('#bfElementName').val();
            mdata.logging = JQuery('#bfElementNumberInputAdvancedLogging').attr('checked');
            mdata.range = JQuery('#bfElementNumberInputAdvancedRange').attr('checked');
            mdata.label = JQuery('#bfElementLabel').val();
            mdata['label_translation'+BFQMConfig.lang] = JQuery('#bfElementLabelTrans').val();
            mdata.maxLength = JQuery('#bfElementTypeNumberInputMaxLength').val();
            mdata.icon = JQuery('#bfElementTypeNumberInputIcon').val();

            mdata.hint = JQuery('#bfElementTypeNumberInputHint').val();
            mdata['hint_translation'+BFQMConfig.lang] = JQuery('#bfElementTypeNumberInputHintTrans').val();

            mdata.labelPosition = JQuery('#bfElementAdvancedLabelPosition').val();
            mdata.tabIndex = JQuery('#bfElementAdvancedTabIndex').val();
            mdata.off = JQuery('#bfElementAdvancedTurnOff').attr('checked');
            mdata.hideInMailback = JQuery('#bfElementAdvancedHideInMailback').attr('checked');
            mdata.hideLabel = JQuery('#bfElementNumberInputAdvancedHideLabel').attr('checked');
            mdata.size = JQuery('#bfElementTypeNumberInputSize').val();
            mdata.orderNumber = JQuery('#bfElementNumberInputOrderNumber').val();
            mdata.required = JQuery('#bfElementValidationRequired').attr('checked');

            mdata.step = JQuery('#bfElementNumberInputAdvancedStep').val();
            mdata.min = JQuery('#bfElementNumberInputAdvancedMin').val();
            mdata.max = JQuery('#bfElementNumberInputAdvancedMax').val();

            item.properties = mdata;
        };

        appScope.populateNumberInputProperties = function (mdata) {

            JQuery('#bfElementTypeNumberInputValue').val(mdata.value);
            JQuery('#bfElementTypeNumberInputValueTrans').val(typeof mdata['value_translation'+BFQMConfig.lang] != "undefined" ? mdata['value_translation'+BFQMConfig.lang] : "");

            if (typeof mdata.placeholder == "undefined") {
                mdata['placeholder'] = '';
            }
            JQuery('#bfElementTypeNumberInputPlaceholder').val(mdata.placeholder);
            JQuery('#bfElementTypeNumberInputPlaceholderTrans').val(typeof mdata['placeholder_translation'+BFQMConfig.lang] != "undefined" ? mdata['placeholder_translation'+BFQMConfig.lang] : "");

            JQuery('#bfElementName').val(mdata.bfName);
            JQuery('#bfElementLabel').val(mdata.label);
            JQuery('#bfElementLabelTrans').val(typeof mdata['label_translation'+BFQMConfig.lang] != "undefined" ? mdata['label_translation'+BFQMConfig.lang] : "");
            JQuery('#bfElementNumberInputAdvancedLogging').attr('checked', mdata.logging);
            JQuery('#bfElementNumberInputAdvancedRange').attr('checked', mdata.range);
            JQuery('#bfElementTypeNumberInputMaxLength').val(mdata.maxLength);

            JQuery('#bfElementTypeNumberInputHint').val(mdata.hint);
            JQuery('#bfElementTypeNumberInputHintTrans').val(typeof mdata['hint_translation'+BFQMConfig.lang] != "undefined" ? mdata['hint_translation'+BFQMConfig.lang] : "");
        
            JQuery('#bfElementAdvancedLabelPosition').val(mdata.labelPosition);
            JQuery('#bfElementAdvancedTabIndex').val(mdata.tabIndex);
            JQuery('#bfElementAdvancedHideInMailback').attr('checked', mdata.hideInMailback);
            JQuery('#bfElementAdvancedTurnOff').attr('checked', mdata.off);
            JQuery('#bfElementNumberInputAdvancedHideLabel').attr('checked', mdata.hideLabel);
            JQuery('#bfElementTypeNumberInputSize').val(mdata.size);
            JQuery('#bfElementNumberInputOrderNumber').val(mdata.orderNumber);
            JQuery('#bfElementValidationRequired').attr('checked', mdata.required);

            JQuery('#bfElementTypeNumberInputIcon').val(mdata.icon);

            JQuery('#bfElementNumberInputAdvancedStep').val(mdata.step);
            JQuery('#bfElementNumberInputAdvancedMin').val(mdata.min);
            JQuery('#bfElementNumberInputAdvancedMax').val(mdata.max);
        };

        // RADIOS
        appScope.saveRadioGroupProperties = function (mdata, item) {
            // dynamic properties
            mdata.group = JQuery('#bfElementTypeRadioGroupGroups').val();
            mdata['group_translation'+BFQMConfig.lang] = JQuery('#bfElementTypeRadioGroupGroupsTrans').val();

            mdata.readonly = JQuery('#bfElementTypeRadioGroupReadonly').attr('checked');
            mdata.wrap = JQuery('#bfElementTypeRadioGroupWrap').attr('checked');

            mdata.hint = JQuery('#bfElementTypeRadioGroupHint').val();
            mdata['hint_translation'+BFQMConfig.lang] = JQuery('#bfElementTypeRadioGroupHintTrans').val();

            mdata.hideLabel = JQuery('#bfElementRadioGroupAdvancedHideLabel').attr('checked');
            mdata.logging = JQuery('#bfElementRadioGroupAdvancedLogging').attr('checked');
            mdata.orderNumber = JQuery('#bfElementRadioGroupAdvancedOrderNumber').val();
            // static properties
            mdata.bfName = JQuery('#bfElementName').val();

            mdata.label = JQuery('#bfElementLabel').val();
            mdata['label_translation'+BFQMConfig.lang] = JQuery('#bfElementLabelTrans').val();

            mdata.labelPosition = JQuery('#bfElementAdvancedLabelPosition').val();
            mdata.tabIndex = JQuery('#bfElementAdvancedTabIndex').val();
            mdata.hideInMailback = JQuery('#bfElementAdvancedHideInMailback').attr('checked');
            mdata.off = JQuery('#bfElementAdvancedTurnOff').attr('checked');
            mdata.required = JQuery('#bfElementValidationRequired').attr('checked');

            item.properties = mdata;
        };

        appScope.populateRadioGroupProperties = function (mdata) {
            // dynamic properties
            JQuery('#bfElementTypeRadioGroupGroups').val(mdata.group);
            JQuery('#bfElementTypeRadioGroupGroupsTrans').val(typeof mdata['group_translation'+BFQMConfig.lang] != "undefined" ? mdata['group_translation'+BFQMConfig.lang] : "");

            JQuery('#bfElementTypeRadioGroupReadonly').attr('checked', mdata.readonly);
            JQuery('#bfElementTypeRadioGroupWrap').attr('checked', mdata.wrap);

            JQuery('#bfElementTypeRadioGroupHint').val(mdata.hint);
            JQuery('#bfElementTypeRadioGroupHintTrans').val(typeof mdata['hint_translation'+BFQMConfig.lang] != "undefined" ? mdata['hint_translation'+BFQMConfig.lang] : "");

            JQuery('#bfElementRadioGroupAdvancedHideLabel').attr('checked', mdata.hideLabel);
            JQuery('#bfElementRadioGroupAdvancedLogging').attr('checked', mdata.logging);
            JQuery('#bfElementRadioGroupAdvancedOrderNumber').val(mdata.orderNumber);
            // static properties
            JQuery('#bfElementName').val(mdata.bfName);

            JQuery('#bfElementLabel').val(mdata.label);
            JQuery('#bfElementLabelTrans').val(typeof mdata['label_translation'+BFQMConfig.lang] != "undefined" ? mdata['label_translation'+BFQMConfig.lang] : "");

            JQuery('#bfElementAdvancedTabIndex').val(mdata.tabIndex);
            JQuery('#bfElementAdvancedHideInMailback').attr('checked', mdata.hideInMailback);
            JQuery('#bfElementAdvancedTurnOff').attr('checked', mdata.off);
            JQuery('#bfElementAdvancedLabelPosition').val(mdata.labelPosition);
            JQuery('#bfElementValidationRequired').attr('checked', mdata.required);
        };

        // Checkboxgroup
        appScope.saveCheckboxGroupProperties = function (mdata, item) {
            // dynamic properties
            mdata.group = JQuery('#bfElementTypeCheckboxGroupGroups').val();
            mdata['group_translation'+BFQMConfig.lang] = JQuery('#bfElementTypeCheckboxGroupGroupsTrans').val();

            mdata.readonly = JQuery('#bfElementTypeCheckboxGroupReadonly').attr('checked');
            mdata.wrap = JQuery('#bfElementTypeCheckboxGroupWrap').attr('checked');

            mdata.hint = JQuery('#bfElementTypeCheckboxGroupHint').val();
            mdata['hint_translation'+BFQMConfig.lang] = JQuery('#bfElementTypeCheckboxGroupHintTrans').val();

            mdata.hideLabel = JQuery('#bfElementCheckboxGroupAdvancedHideLabel').attr('checked');
            mdata.logging = JQuery('#bfElementCheckboxGroupAdvancedLogging').attr('checked');
            mdata.orderNumber = JQuery('#bfElementCheckboxGroupAdvancedOrderNumber').val();
            // static properties
            mdata.bfName = JQuery('#bfElementName').val();

            mdata.label = JQuery('#bfElementLabel').val();
            mdata['label_translation'+BFQMConfig.lang] = JQuery('#bfElementLabelTrans').val();

            mdata.labelPosition = JQuery('#bfElementAdvancedLabelPosition').val();
            mdata.tabIndex = JQuery('#bfElementAdvancedTabIndex').val();
            mdata.hideInMailback = JQuery('#bfElementAdvancedHideInMailback').attr('checked');
            mdata.off = JQuery('#bfElementAdvancedTurnOff').attr('checked');
            mdata.required = JQuery('#bfElementValidationRequired').attr('checked');

            item.properties = mdata;
        };

        appScope.populateCheckboxGroupProperties = function (mdata) {
            // dynamic properties
            JQuery('#bfElementTypeCheckboxGroupGroups').val(mdata.group);
            JQuery('#bfElementTypeCheckboxGroupGroupsTrans').val(typeof mdata['group_translation'+BFQMConfig.lang] != "undefined" ? mdata['group_translation'+BFQMConfig.lang] : "");

            JQuery('#bfElementTypeCheckboxGroupReadonly').attr('checked', mdata.readonly);
            JQuery('#bfElementTypeCheckboxGroupWrap').attr('checked', mdata.wrap);

            JQuery('#bfElementTypeCheckboxGroupHint').val(mdata.hint);
            JQuery('#bfElementTypeCheckboxGroupHintTrans').val(typeof mdata['hint_translation'+BFQMConfig.lang] != "undefined" ? mdata['hint_translation'+BFQMConfig.lang] : "");

            JQuery('#bfElementCheckboxGroupAdvancedHideLabel').attr('checked', mdata.hideLabel);
            JQuery('#bfElementCheckboxGroupAdvancedLogging').attr('checked', mdata.logging);
            JQuery('#bfElementCheckboxGroupAdvancedOrderNumber').val(mdata.orderNumber);
            // static properties
            JQuery('#bfElementName').val(mdata.bfName);

            JQuery('#bfElementLabel').val(mdata.label);
            JQuery('#bfElementLabelTrans').val(typeof mdata['label_translation'+BFQMConfig.lang] != "undefined" ? mdata['label_translation'+BFQMConfig.lang] : "");

            JQuery('#bfElementAdvancedTabIndex').val(mdata.tabIndex);
            JQuery('#bfElementAdvancedHideInMailback').attr('checked', mdata.hideInMailback);
            JQuery('#bfElementAdvancedTurnOff').attr('checked', mdata.off);
            JQuery('#bfElementAdvancedLabelPosition').val(mdata.labelPosition);
            JQuery('#bfElementValidationRequired').attr('checked', mdata.required);
        };

        // Signature
        appScope.saveSignatureProperties = function (mdata, item) {
            // dynamic properties

            mdata.hint = JQuery('#bfElementTypeSignatureHint').val();
            mdata['hint_translation'+BFQMConfig.lang] = JQuery('#bfElementTypeSignatureHintTrans').val();

            mdata.hideLabel = JQuery('#bfElementSignatureAdvancedHideLabel').attr('checked');
            mdata.logging = JQuery('#bfElementSignatureAdvancedLogging').attr('checked');
            mdata.orderNumber = JQuery('#bfElementSignatureAdvancedOrderNumber').val();
            // static properties
            mdata.bfName = JQuery('#bfElementName').val();

            mdata.label = JQuery('#bfElementLabel').val();
            mdata['label_translation'+BFQMConfig.lang] = JQuery('#bfElementLabelTrans').val();

            mdata.labelPosition = JQuery('#bfElementAdvancedLabelPosition').val();
            mdata.tabIndex = JQuery('#bfElementAdvancedTabIndex').val();
            mdata.hideInMailback = JQuery('#bfElementAdvancedHideInMailback').attr('checked');
            mdata.off = JQuery('#bfElementAdvancedTurnOff').attr('checked');
            mdata.required = JQuery('#bfElementValidationRequired').attr('checked');

            item.properties = mdata;
        };

        appScope.populateSignatureProperties = function (mdata) {
            // dynamic properties

            JQuery('#bfElementTypeSignatureHint').val(mdata.hint);
            JQuery('#bfElementTypeSignatureHintTrans').val(typeof mdata['hint_translation'+BFQMConfig.lang] != "undefined" ? mdata['hint_translation'+BFQMConfig.lang] : "");

            JQuery('#bfElementSignatureAdvancedHideLabel').attr('checked', mdata.hideLabel);
            JQuery('#bfElementSignatureAdvancedLogging').attr('checked', mdata.logging);
            JQuery('#bfElementSignatureAdvancedOrderNumber').val(mdata.orderNumber);
            // static properties
            JQuery('#bfElementName').val(mdata.bfName);

            JQuery('#bfElementLabel').val(mdata.label);
            JQuery('#bfElementLabelTrans').val(typeof mdata['label_translation'+BFQMConfig.lang] != "undefined" ? mdata['label_translation'+BFQMConfig.lang] : "");

            JQuery('#bfElementAdvancedTabIndex').val(mdata.tabIndex);
            JQuery('#bfElementAdvancedHideInMailback').attr('checked', mdata.hideInMailback);
            JQuery('#bfElementAdvancedTurnOff').attr('checked', mdata.off);
            JQuery('#bfElementAdvancedLabelPosition').val(mdata.labelPosition);
            JQuery('#bfElementValidationRequired').attr('checked', mdata.required);
        };

        // Checkbox
        appScope.saveCheckboxProperties = function (mdata, item) {
            // dynamic properties
            mdata.value = JQuery('#bfElementTypeCheckboxValue').val() == '' ? 'checked' : JQuery('#bfElementTypeCheckboxValue').val();
            mdata.checked = JQuery('#bfElementTypeCheckboxChecked').attr('checked');
            mdata.readonly = JQuery('#bfElementTypeCheckboxReadonly').attr('checked');
            mdata.mailbackAccept = JQuery('#bfElementCheckboxAdvancedMailbackAccept').attr('checked');
            mdata.mailbackConnectWith = JQuery('#bfElementCheckboxAdvancedMailbackConnectWith').val();

            mdata.hint = JQuery('#bfElementTypeCheckboxHint').val();
            mdata['hint_translation'+BFQMConfig.lang] = JQuery('#bfElementTypeCheckboxHintTrans').val();

            mdata.hideLabel = JQuery('#bfElementCheckboxAdvancedHideLabel').attr('checked');
            mdata.logging = JQuery('#bfElementCheckboxAdvancedLogging').attr('checked');
            mdata.orderNumber = JQuery('#bfElementCheckboxAdvancedOrderNumber').val();
            // static properties
            mdata.bfName = JQuery('#bfElementName').val();

            mdata.label = JQuery('#bfElementLabel').val();
            mdata['label_translation'+BFQMConfig.lang] = JQuery('#bfElementLabelTrans').val();

            mdata.labelPosition = JQuery('#bfElementAdvancedLabelPosition').val();
            mdata.tabIndex = JQuery('#bfElementAdvancedTabIndex').val();
            mdata.hideInMailback = JQuery('#bfElementAdvancedHideInMailback').attr('checked');
            mdata.off = JQuery('#bfElementAdvancedTurnOff').attr('checked');
            mdata.required = JQuery('#bfElementValidationRequired').attr('checked');

            item.properties = mdata;
        };

        appScope.populateCheckboxProperties = function (mdata) {
            // dynamic properties
            JQuery('#bfElementTypeCheckboxValue').val(mdata.value);
            JQuery('#bfElementTypeCheckboxChecked').attr('checked', mdata.checked);
            JQuery('#bfElementCheckboxAdvancedMailbackAccept').attr('checked', mdata.mailbackAccept);
            JQuery('#bfElementCheckboxAdvancedMailbackConnectWith').val(mdata.mailbackConnectWith);
            JQuery('#bfElementTypeCheckboxReadonly').attr('checked', mdata.readonly);

            JQuery('#bfElementTypeCheckboxHint').val(mdata.hint);
            JQuery('#bfElementTypeCheckboxHintTrans').val(typeof mdata['hint_translation'+BFQMConfig.lang] != "undefined" ? mdata['hint_translation'+BFQMConfig.lang] : "");

            JQuery('#bfElementCheckboxAdvancedHideLabel').attr('checked', mdata.hideLabel);
            JQuery('#bfElementCheckboxAdvancedLogging').attr('checked', mdata.logging);
            JQuery('#bfElementCheckboxAdvancedOrderNumber').val(mdata.orderNumber);
            // static properties
            JQuery('#bfElementName').val(mdata.bfName);

            JQuery('#bfElementLabel').val(mdata.label);
            JQuery('#bfElementLabelTrans').val(typeof mdata['label_translation'+BFQMConfig.lang] != "undefined" ? mdata['label_translation'+BFQMConfig.lang] : "");

            JQuery('#bfElementAdvancedTabIndex').val(mdata.tabIndex);
            JQuery('#bfElementAdvancedHideInMailback').attr('checked', mdata.hideInMailback);
            JQuery('#bfElementAdvancedTurnOff').attr('checked', mdata.off);
            JQuery('#bfElementAdvancedLabelPosition').val(mdata.labelPosition);
            JQuery('#bfElementValidationRequired').attr('checked', mdata.required);
        };

        // Select
        appScope.saveSelectProperties = function (mdata, item) {
            // dynamic properties
            mdata.list = JQuery('#bfElementTypeSelectList').val();
            mdata['list_translation'+BFQMConfig.lang] = JQuery('#bfElementTypeSelectListTrans').val();

            mdata.width = JQuery('#bfElementTypeSelectListWidth').val();
            mdata.height = JQuery('#bfElementTypeSelectListHeight').val();
            mdata.readonly = JQuery('#bfElementTypeSelectReadonly').attr('checked');
            mdata.multiple = JQuery('#bfElementTypeSelectMultiple').attr('checked');
            mdata.mailback = JQuery('#bfElementSelectAdvancedMailback').attr('checked');

            mdata.hint = JQuery('#bfElementTypeSelectHint').val();
            mdata['hint_translation'+BFQMConfig.lang] = JQuery('#bfElementTypeSelectHintTrans').val();

            mdata.hideLabel = JQuery('#bfElementSelectAdvancedHideLabel').attr('checked');
            mdata.logging = JQuery('#bfElementSelectAdvancedLogging').attr('checked');
            mdata.orderNumber = JQuery('#bfElementSelectAdvancedOrderNumber').val();
            // static properties
            mdata.bfName = JQuery('#bfElementName').val();

            mdata.label = JQuery('#bfElementLabel').val();
            mdata['label_translation'+BFQMConfig.lang] = JQuery('#bfElementLabelTrans').val();

            mdata.labelPosition = JQuery('#bfElementAdvancedLabelPosition').val();
            mdata.tabIndex = JQuery('#bfElementAdvancedTabIndex').val();
            mdata.hideInMailback = JQuery('#bfElementAdvancedHideInMailback').attr('checked');
            mdata.off = JQuery('#bfElementAdvancedTurnOff').attr('checked');
            mdata.required = JQuery('#bfElementValidationRequired').attr('checked');

            item.properties = mdata;
        };

        appScope.populateSelectProperties = function (mdata) {
            // dynamic properties
            JQuery('#bfElementTypeSelectList').val(mdata.list);
            JQuery('#bfElementTypeSelectListTrans').val(typeof mdata['list_translation'+BFQMConfig.lang] != "undefined" ? mdata['list_translation'+BFQMConfig.lang] : "");

            // compat 723
            if (typeof mdata.width == "undefined") {
                mdata['width'] = '';
            }
            if (typeof mdata.height == "undefined") {
                mdata['height'] = '';
            }
            // compat 723 end
            JQuery('#bfElementTypeSelectListWidth').val(mdata.width);
            JQuery('#bfElementTypeSelectListHeight').val(mdata.height);
            JQuery('#bfElementTypeSelectReadonly').attr('checked', mdata.readonly);
            JQuery('#bfElementTypeSelectMultiple').attr('checked', mdata.multiple);
            JQuery('#bfElementSelectAdvancedMailback').attr('checked', mdata.mailback);

            JQuery('#bfElementTypeSelectHint').val(mdata.hint);
            JQuery('#bfElementTypeSelectHintTrans').val(typeof mdata['hint_translation'+BFQMConfig.lang] != "undefined" ? mdata['hint_translation'+BFQMConfig.lang] : "");

            JQuery('#bfElementSelectAdvancedHideLabel').attr('checked', mdata.hideLabel);
            JQuery('#bfElementSelectAdvancedLogging').attr('checked', mdata.logging);
            JQuery('#bfElementSelectAdvancedOrderNumber').val(mdata.orderNumber);
            // static properties
            JQuery('#bfElementName').val(mdata.bfName);

            JQuery('#bfElementLabel').val(mdata.label);
            JQuery('#bfElementLabelTrans').val(typeof mdata['label_translation'+BFQMConfig.lang] != "undefined" ? mdata['label_translation'+BFQMConfig.lang] : "");

            JQuery('#bfElementAdvancedTabIndex').val(mdata.tabIndex);
            JQuery('#bfElementAdvancedHideInMailback').attr('checked', mdata.hideInMailback);
            JQuery('#bfElementAdvancedTurnOff').attr('checked', mdata.off);
            JQuery('#bfElementAdvancedLabelPosition').val(mdata.labelPosition);
            JQuery('#bfElementValidationRequired').attr('checked', mdata.required);
        };

        // File
        appScope.saveFileProperties = function (mdata, item) {
            // dynamic properties
            mdata.uploadDirectory = JQuery('#bfElementFileAdvancedUploadDirectory').val();
            mdata.timestamp = JQuery('#bfElementFileAdvancedTimestamp').attr('checked');
            mdata.allowedFileExtensions = JQuery('#bfElementFileAdvancedAllowedFileExtensions').val();
            mdata.attachToUserMail = JQuery('#bfElementFileAdvancedAttachToUserMail').attr('checked');
            mdata.attachToAdminMail = JQuery('#bfElementFileAdvancedAttachToAdminMail').attr('checked');

            mdata.html5 = JQuery('#bfElementFileAdvancedHtml5Uploader').attr('checked');

            mdata.readonly = JQuery('#bfElementTypeFileReadonly').attr('checked');

            mdata.hint = JQuery('#bfElementTypeFileHint').val();
            mdata['hint_translation'+BFQMConfig.lang] = JQuery('#bfElementTypeFileHintTrans').val();

            mdata.useUrl = JQuery('#bfElementFileAdvancedUseUrl').attr('checked');
            mdata.useUrlDownloadDirectory = JQuery('#bfElementFileAdvancedUseUrlDownloadDirectory').val();

            mdata.resize_target_width = JQuery('#bfElementFileAdvancedResizeTargetWidth').val();
            mdata.resize_target_height = JQuery('#bfElementFileAdvancedResizeTargetHeight').val();
            mdata.resize_type = JQuery('#bfElementFileAdvancedResizeType').val();
            mdata.resize_bgcolor = JQuery('#bfElementFileAdvancedResizeBgcolor').val();

            mdata.hideLabel = JQuery('#bfElementFileAdvancedHideLabel').attr('checked');
            mdata.logging = JQuery('#bfElementFileAdvancedLogging').attr('checked');
            mdata.orderNumber = JQuery('#bfElementFileAdvancedOrderNumber').val();
            mdata.flashUploader = JQuery('#bfElementFileAdvancedFlashUploader').attr('checked');
            mdata.flashUploaderMulti = JQuery('#bfElementFileAdvancedFlashUploaderMulti').attr('checked');
            mdata.flashUploaderBytes = JQuery('#bfElementFileAdvancedFlashUploaderBytes').val();
            mdata.flashUploaderWidth = JQuery('#bfElementFileAdvancedFlashUploaderWidth').val();
            mdata.flashUploaderHeight = JQuery('#bfElementFileAdvancedFlashUploaderHeight').val();
            mdata.flashUploaderTransparent = JQuery('#bfElementFileAdvancedFlashUploaderTransparent').attr('checked');
            // static properties
            mdata.bfName = JQuery('#bfElementName').val();

            mdata.label = JQuery('#bfElementLabel').val();
            mdata['label_translation'+BFQMConfig.lang] = JQuery('#bfElementLabelTrans').val();

            mdata.labelPosition = JQuery('#bfElementAdvancedLabelPosition').val();
            mdata.tabIndex = JQuery('#bfElementAdvancedTabIndex').val();
            mdata.hideInMailback = JQuery('#bfElementAdvancedHideInMailback').attr('checked');
            mdata.off = JQuery('#bfElementAdvancedTurnOff').attr('checked');
            mdata.required = JQuery('#bfElementValidationRequired').attr('checked');

            item.properties = mdata;
        };

        appScope.populateFileProperties = function (mdata) {
            // dynamic properties
            JQuery('#bfElementFileAdvancedUploadDirectory').val(mdata.uploadDirectory);
            JQuery('#bfElementFileAdvancedTimestamp').attr('checked', mdata.timestamp);
            JQuery('#bfElementFileAdvancedAllowedFileExtensions').val(mdata.allowedFileExtensions);
            JQuery('#bfElementFileAdvancedAttachToUserMail').attr('checked', mdata.attachToUserMail);
            JQuery('#bfElementFileAdvancedAttachToAdminMail').attr('checked', mdata.attachToAdminMail);

            JQuery('#bfElementFileAdvancedHtml5Uploader').attr('checked', mdata.html5);

            JQuery('#bfElementTypeFileReadonly').attr('checked', mdata.readonly);

            JQuery('#bfElementTypeFileHint').val(mdata.hint);
            JQuery('#bfElementTypeFileHintTrans').val(typeof mdata['hint_translation'+BFQMConfig.lang] != "undefined" ? mdata['hint_translation'+BFQMConfig.lang] : "");

            JQuery('#bfElementFileAdvancedHideLabel').attr('checked', mdata.hideLabel);
            if (mdata.useUrl && mdata.useUrlDownloadDirectory == '') {
                mdata.useUrlDownloadDirectory = BFQMConfig.siteRoot + 'media/breezingforms/uploads';
            }

            JQuery('#bfElementFileAdvancedResizeTargetWidth').val(mdata.resize_target_width);
            JQuery('#bfElementFileAdvancedResizeTargetHeight').val(mdata.resize_target_height);
            JQuery('#bfElementFileAdvancedResizeType').val(mdata.resize_type);
            JQuery('#bfElementFileAdvancedResizeBgcolor').val(mdata.resize_bgcolor);

            JQuery('#bfElementFileAdvancedUseUrl').attr('checked', mdata.useUrl);
            JQuery('#bfElementFileAdvancedUseUrlDownloadDirectory').val(mdata.useUrlDownloadDirectory);
            JQuery('#bfElementFileAdvancedLogging').attr('checked', mdata.logging);
            JQuery('#bfElementFileAdvancedOrderNumber').val(mdata.orderNumber);
            JQuery('#bfElementFileAdvancedFlashUploader').attr('checked', mdata.flashUploader);
            JQuery('#bfElementFileAdvancedFlashUploaderMulti').attr('checked', mdata.flashUploaderMulti);
            JQuery('#bfElementFileAdvancedFlashUploaderBytes').val(mdata.flashUploaderBytes);
            JQuery('#bfElementFileAdvancedFlashUploaderWidth').val(mdata.flashUploaderWidth);
            JQuery('#bfElementFileAdvancedFlashUploaderHeight').val(mdata.flashUploaderHeight);
            JQuery('#bfElementFileAdvancedFlashUploaderTransparent').attr('checked', mdata.flashUploaderTransparent);
            // static properties
            JQuery('#bfElementName').val(mdata.bfName);

            JQuery('#bfElementLabel').val(mdata.label);
            JQuery('#bfElementLabelTrans').val(typeof mdata['label_translation'+BFQMConfig.lang] != "undefined" ? mdata['label_translation'+BFQMConfig.lang] : "");

            JQuery('#bfElementAdvancedTabIndex').val(mdata.tabIndex);
            JQuery('#bfElementAdvancedHideInMailback').attr('checked', mdata.hideInMailback);
            JQuery('#bfElementAdvancedTurnOff').attr('checked', mdata.off);
            JQuery('#bfElementAdvancedLabelPosition').val(mdata.labelPosition);
            JQuery('#bfElementValidationRequired').attr('checked', mdata.required);
        };

        // SUBMIT BUTTON
        appScope.saveSubmitButtonProperties = function (mdata, item) {
            // dynamic properties
            mdata.src = JQuery('#bfElementSubmitButtonAdvancedSrc').val();
            mdata['src_translation'+BFQMConfig.lang] = JQuery('#bfElementSubmitButtonAdvancedSrcTrans').val();

            mdata.value = JQuery('#bfElementTypeSubmitButtonValue').val();
            mdata['value_translation'+BFQMConfig.lang] = JQuery('#bfElementTypeSubmitButtonValueTrans').val();

            mdata.hint = JQuery('#bfElementTypeSubmitButtonHint').val();
            mdata['hint_translation'+BFQMConfig.lang] = JQuery('#bfElementTypeSubmitButtonHintTrans').val();

            mdata.hideLabel = JQuery('#bfElementSubmitButtonAdvancedHideLabel').attr('checked');
            // static properties
            mdata.bfName = JQuery('#bfElementName').val();

            mdata.label = JQuery('#bfElementLabel').val();
            mdata['label_translation'+BFQMConfig.lang] = JQuery('#bfElementLabelTrans').val();

            mdata.labelPosition = JQuery('#bfElementAdvancedLabelPosition').val();
            mdata.tabIndex = JQuery('#bfElementAdvancedTabIndex').val();
            mdata.hideInMailback = JQuery('#bfElementAdvancedHideInMailback').attr('checked');
            mdata.off = JQuery('#bfElementAdvancedTurnOff').attr('checked');

            item.properties = mdata;
        };

        appScope.populateSubmitButtonProperties = function (mdata) {
            // dynamic properties
            JQuery('#bfElementSubmitButtonAdvancedSrc').val(mdata.src);
            JQuery('#bfElementSubmitButtonAdvancedSrcTrans').val(typeof mdata['src_translation'+BFQMConfig.lang] != "undefined" ? mdata['src_translation'+BFQMConfig.lang] : "");

            JQuery('#bfElementTypeSubmitButtonValue').val(mdata.value);
            JQuery('#bfElementTypeSubmitButtonValueTrans').val(typeof mdata['value_translation'+BFQMConfig.lang] != "undefined" ? mdata['value_translation'+BFQMConfig.lang] : "");

            JQuery('#bfElementTypeSubmitButtonHint').val(mdata.hint);
            JQuery('#bfElementTypeSubmitButtonHintTrans').val(typeof mdata['hint_translation'+BFQMConfig.lang] != "undefined" ? mdata['hint_translation'+BFQMConfig.lang] : "");

            JQuery('#bfElementSubmitButtonAdvancedHideLabel').attr('checked', mdata.hideLabel);
            // static properties
            JQuery('#bfElementName').val(mdata.bfName);

            JQuery('#bfElementLabel').val(mdata.label);
            JQuery('#bfElementLabelTrans').val(typeof mdata['label_translation'+BFQMConfig.lang] != "undefined" ? mdata['label_translation'+BFQMConfig.lang] : "");

            JQuery('#bfElementAdvancedTabIndex').val(mdata.tabIndex);
            JQuery('#bfElementAdvancedHideInMailback').attr('checked', mdata.hideInMailback);
            JQuery('#bfElementAdvancedTurnOff').attr('checked', mdata.off);
            JQuery('#bfElementAdvancedLabelPosition').val(mdata.labelPosition);
        };

        // CAPTCHA
        appScope.saveCaptchaProperties = function (mdata, item) {
            // dynamic properties
            mdata.hint = JQuery('#bfElementTypeCaptchaHint').val();
            mdata['hint_translation'+BFQMConfig.lang] = JQuery('#bfElementTypeCaptchaHintTrans').val();

            mdata.width = JQuery('#bfElementTypeCaptchaWidth').val();
            mdata.hideLabel = JQuery('#bfElementCaptchaAdvancedHideLabel').attr('checked');
            // static properties
            mdata.bfName = JQuery('#bfElementName').val();

            mdata.label = JQuery('#bfElementLabel').val();
            mdata['label_translation'+BFQMConfig.lang] = JQuery('#bfElementLabelTrans').val();

            mdata.labelPosition = JQuery('#bfElementAdvancedLabelPosition').val();
            mdata.tabIndex = JQuery('#bfElementAdvancedTabIndex').val();
            mdata.off = JQuery('#bfElementAdvancedTurnOff').attr('checked');
            mdata.hideInMailback = JQuery('#bfElementAdvancedHideInMailback').attr('checked');
            item.properties = mdata;
        };

        // RECAPTCHA
        appScope.saveReCaptchaProperties = function (mdata, item) {
            // dynamic properties
            mdata.hint = JQuery('#bfElementTypeReCaptchaHint').val();
            mdata['hint_translation'+BFQMConfig.lang] = JQuery('#bfElementTypeReCaptchaHintTrans').val();

            mdata.hideLabel = JQuery('#bfElementReCaptchaAdvancedHideLabel').attr('checked');

            mdata.pubkey = JQuery('#bfElementTypeReCaptchaPubkey').val();
            mdata.privkey = JQuery('#bfElementTypeReCaptchaPrivkey').val();
            mdata.theme = JQuery('#bfElementTypeReCaptchaTheme').val();
            mdata.size = JQuery('#bfElementTypeReCaptchaSize').val();

            mdata.newCaptcha = JQuery('#bfElementTypeReCaptchaNew').attr('checked');
            mdata.invisibleCaptcha = JQuery('#bfElementTypeReCaptchaInvisible').attr('checked');

            // static properties
            mdata.bfName = JQuery('#bfElementName').val();

            mdata.label = JQuery('#bfElementLabel').val();
            mdata['label_translation'+BFQMConfig.lang] = JQuery('#bfElementLabelTrans').val();

            mdata.labelPosition = JQuery('#bfElementAdvancedLabelPosition').val();
            mdata.tabIndex = JQuery('#bfElementAdvancedTabIndex').val();

            mdata.off = JQuery('#bfElementAdvancedTurnOff').attr('checked');
            mdata.hideInMailback = JQuery('#bfElementAdvancedHideInMailback').attr('checked');

            item.properties = mdata;
        };

        appScope.populateReCaptchaProperties = function (mdata) {
            // dynamic properties
            JQuery('#bfElementTypeReCaptchaHint').val(mdata.hint);
            JQuery('#bfElementTypeReCaptchaHintTrans').val(typeof mdata['hint_translation'+BFQMConfig.lang] != "undefined" ? mdata['hint_translation'+BFQMConfig.lang] : "");

            JQuery('#bfElementReCaptchaAdvancedHideLabel').attr('checked', mdata.hideLabel);

            JQuery('#bfElementTypeReCaptchaPubkey').val(mdata.pubkey);
            JQuery('#bfElementTypeReCaptchaPrivkey').val(mdata.privkey);
            JQuery('#bfElementTypeReCaptchaTheme').val(mdata.theme);
            JQuery('#bfElementTypeReCaptchaSize').val(mdata.size);

            JQuery('#bfElementTypeReCaptchaNew').attr('checked', mdata.newCaptcha);
            JQuery('#bfElementTypeReCaptchaInvisible').attr('checked', mdata.invisibleCaptcha);

            // static properties
            JQuery('#bfElementName').val(mdata.bfName);

            JQuery('#bfElementLabel').val(mdata.label);
            JQuery('#bfElementLabelTrans').val(typeof mdata['label_translation'+BFQMConfig.lang] != "undefined" ? mdata['label_translation'+BFQMConfig.lang] : "");

            JQuery('#bfElementAdvancedTabIndex').val(mdata.tabIndex);
            JQuery('#bfElementAdvancedTurnOff').attr('checked', mdata.off);
            JQuery('#bfElementAdvancedLabelPosition').val(mdata.labelPosition);
            JQuery('#bfElementAdvancedHideInMailback').attr('checked', mdata.hideInMailback);
        };

        appScope.populateCaptchaProperties = function (mdata) {
            // dynamic properties
            JQuery('#bfElementTypeCaptchaHint').val(mdata.hint);
            JQuery('#bfElementTypeCaptchaHintTrans').val(typeof mdata['hint_translation'+BFQMConfig.lang] != "undefined" ? mdata['hint_translation'+BFQMConfig.lang] : "");

            JQuery('#bfElementTypeCaptchaWidth').val(mdata.width);
            JQuery('#bfElementCaptchaAdvancedHideLabel').attr('checked', mdata.hideLabel);
            // static properties
            JQuery('#bfElementName').val(mdata.bfName);

            JQuery('#bfElementLabel').val(mdata.label);
            JQuery('#bfElementLabelTrans').val(typeof mdata['label_translation'+BFQMConfig.lang] != "undefined" ? mdata['label_translation'+BFQMConfig.lang] : "");

            JQuery('#bfElementAdvancedTabIndex').val(mdata.tabIndex);
            JQuery('#bfElementAdvancedTurnOff').attr('checked', mdata.off);
            JQuery('#bfElementAdvancedLabelPosition').val(mdata.labelPosition);
            JQuery('#bfElementAdvancedHideInMailback').attr('checked', mdata.hideInMailback);
        };

        // CALENDAR RESPONSIVE
        appScope.saveCalendarResponsiveProperties = function (mdata, item) {
            // dynamic properties
            mdata.format = JQuery('#bfElementTypeCalendarResponsiveFormat').val();
            mdata['format_translation'+BFQMConfig.lang] = JQuery('#bfElementTypeCalendarResponsiveFormatTrans').val();

            mdata.value = JQuery('#bfElementTypeCalendarResponsiveValue').val();
            mdata['value_translation'+BFQMConfig.lang] = JQuery('#bfElementTypeCalendarResponsiveValueTrans').val();

            mdata.size = JQuery('#bfElementTypeCalendarResponsiveSize').val();
            mdata.icon = JQuery('#bfElementTypeCalendarResponsiveIcon').val();

            mdata.hint = JQuery('#bfElementTypeCalendarResponsiveHint').val();
            mdata['hint_translation'+BFQMConfig.lang] = JQuery('#bfElementTypeCalendarResponsiveHintTrans').val();

            mdata.hideLabel = JQuery('#bfElementCalendarResponsiveAdvancedHideLabel').attr('checked');
            // static properties
            mdata.bfName = JQuery('#bfElementName').val();

            mdata.label = JQuery('#bfElementLabel').val();
            mdata['label_translation'+BFQMConfig.lang] = JQuery('#bfElementLabelTrans').val();

            mdata.labelPosition = JQuery('#bfElementAdvancedLabelPosition').val();
            mdata.tabIndex = JQuery('#bfElementAdvancedTabIndex').val();
            mdata.hideInMailback = JQuery('#bfElementAdvancedHideInMailback').attr('checked');
            mdata.off = JQuery('#bfElementAdvancedTurnOff').attr('checked');
            mdata.required = JQuery('#bfElementValidationRequired').attr('checked');

            item.properties = mdata;
        };

        appScope.populateCalendarResponsiveProperties = function (mdata) {
            // dynamic properties
            JQuery('#bfElementTypeCalendarResponsiveFormat').val(mdata.format);
            JQuery('#bfElementTypeCalendarResponsiveFormatTrans').val(typeof mdata['format_translation'+BFQMConfig.lang] != "undefined" ? mdata['format_translation'+BFQMConfig.lang] : "");

            JQuery('#bfElementTypeCalendarResponsiveValue').val(mdata.value);
            JQuery('#bfElementTypeCalendarResponsiveValueTrans').val(typeof mdata['value_translation'+BFQMConfig.lang] != "undefined" ? mdata['value_translation'+BFQMConfig.lang] : "");

            JQuery('#bfElementTypeCalendarResponsiveSize').val(mdata.size);

            JQuery('#bfElementTypeCalendarResponsiveHint').val(mdata.hint);
            JQuery('#bfElementTypeCalendarResponsiveHintTrans').val(typeof mdata['hint_translation'+BFQMConfig.lang] != "undefined" ? mdata['hint_translation'+BFQMConfig.lang] : "");

            JQuery('#bfElementCalendarResponsiveAdvancedHideLabel').attr('checked', mdata.hideLabel);
            // static properties
            JQuery('#bfElementName').val(mdata.bfName);

            JQuery('#bfElementLabel').val(mdata.label);
            JQuery('#bfElementLabelTrans').val(typeof mdata['label_translation'+BFQMConfig.lang] != "undefined" ? mdata['label_translation'+BFQMConfig.lang] : "");

            JQuery('#bfElementAdvancedTabIndex').val(mdata.tabIndex);
            JQuery('#bfElementAdvancedHideInMailback').attr('checked', mdata.hideInMailback);
            JQuery('#bfElementAdvancedTurnOff').attr('checked', mdata.off);
            JQuery('#bfElementAdvancedLabelPosition').val(mdata.labelPosition);
            JQuery('#bfElementValidationRequired').attr('checked', mdata.required);

            JQuery('#bfElementTypeCalendarResponsiveIcon').val(mdata.icon);
        };

        // CALENDAR
        appScope.saveCalendarProperties = function (mdata, item) {
            // dynamic properties
            mdata.format = JQuery('#bfElementTypeCalendarFormat').val();
            mdata['format_translation'+BFQMConfig.lang] = JQuery('#bfElementTypeCalendarFormatTrans').val();

            mdata.value = JQuery('#bfElementTypeCalendarValue').val();
            mdata['value_translation'+BFQMConfig.lang] = JQuery('#bfElementTypeCalendarValueTrans').val();

            mdata.size = JQuery('#bfElementTypeCalendarSize').val();
            mdata.icon = JQuery('#bfElementTypeCalendarIcon').val();

            mdata.hint = JQuery('#bfElementTypeCalendarHint').val();
            mdata['hint_translation'+BFQMConfig.lang] = JQuery('#bfElementTypeCalendarHintTrans').val();

            mdata.hideLabel = JQuery('#bfElementCalendarAdvancedHideLabel').attr('checked');
            // static properties
            mdata.bfName = JQuery('#bfElementName').val();

            mdata.label = JQuery('#bfElementLabel').val();
            mdata['label_translation'+BFQMConfig.lang] = JQuery('#bfElementLabelTrans').val();

            mdata.labelPosition = JQuery('#bfElementAdvancedLabelPosition').val();
            mdata.tabIndex = JQuery('#bfElementAdvancedTabIndex').val();
            mdata.hideInMailback = JQuery('#bfElementAdvancedHideInMailback').attr('checked');
            mdata.off = JQuery('#bfElementAdvancedTurnOff').attr('checked');
            mdata.required = JQuery('#bfElementValidationRequired').attr('checked');

            /* versions > 3.7 */
            mdata.showTime = JQuery('#bfElementCalendarAdvancedShowTime').is(':checked');
            mdata.timeFormat = JQuery('#bfElementCalendarAdvancedTimeFormat').is(':checked');
            mdata.singleHeader = JQuery('#bfElementCalendarAdvancedSingleHeader').is(':checked');
            mdata.todayButton = JQuery('#bfElementCalendarAdvancedTodayButton').is(':checked');
            mdata.weekNumbers = JQuery('#bfElementCalendarAdvancedWeekNumbers').is(':checked');
            mdata.minYear = JQuery('#bfElementCalendarAdvancedMinYear').val();
            mdata.maxYear = JQuery('#bfElementCalendarAdvancedMaxYear').val();
            mdata.firstDay = JQuery('#bfElementCalendarAdvancedFirstDay').val();

            item.properties = mdata;
        };

        appScope.populateCalendarProperties = function (mdata) {
            // dynamic properties
            JQuery('#bfElementTypeCalendarFormat').val(mdata.format);
            JQuery('#bfElementTypeCalendarFormatTrans').val(typeof mdata['format_translation'+BFQMConfig.lang] != "undefined" ? mdata['format_translation'+BFQMConfig.lang] : "");

            JQuery('#bfElementTypeCalendarValue').val(mdata.value);
            JQuery('#bfElementTypeCalendarValueTrans').val(typeof mdata['value_translation'+BFQMConfig.lang] != "undefined" ? mdata['value_translation'+BFQMConfig.lang] : "");

            JQuery('#bfElementTypeCalendarSize').val(mdata.size);

            JQuery('#bfElementTypeCalendarHint').val(mdata.hint);
            JQuery('#bfElementTypeCalendarHintTrans').val(typeof mdata['hint_translation'+BFQMConfig.lang] != "undefined" ? mdata['hint_translation'+BFQMConfig.lang] : "");

            JQuery('#bfElementCalendarAdvancedHideLabel').attr('checked', mdata.hideLabel);
            // static properties
            JQuery('#bfElementName').val(mdata.bfName);

            JQuery('#bfElementLabel').val(mdata.label);
            JQuery('#bfElementLabelTrans').val(typeof mdata['label_translation'+BFQMConfig.lang] != "undefined" ? mdata['label_translation'+BFQMConfig.lang] : "");

            JQuery('#bfElementAdvancedTabIndex').val(mdata.tabIndex);
            JQuery('#bfElementAdvancedHideInMailback').attr('checked', mdata.hideInMailback);
            JQuery('#bfElementAdvancedTurnOff').attr('checked', mdata.off);
            JQuery('#bfElementAdvancedLabelPosition').val(mdata.labelPosition);
            JQuery('#bfElementValidationRequired').attr('checked', mdata.required);

            JQuery('#bfElementTypeCalendarIcon').val(mdata.icon);

            /* > 3.7 */
            JQuery('#bfElementCalendarAdvancedShowTime').prop('checked', !!mdata.showTime);
            JQuery('#bfElementCalendarAdvancedTimeFormat').prop('checked', !!mdata.timeFormat);
            JQuery('#bfElementCalendarAdvancedSingleHeader').prop('checked', !!mdata.singleHeader);
            JQuery('#bfElementCalendarAdvancedTodayButton').prop('checked', !!mdata.todayButton);
            JQuery('#bfElementCalendarAdvancedWeekNumbers').prop('checked', !!mdata.weekNumbers);
            JQuery('#bfElementCalendarAdvancedMinYear').val(mdata.minYear);
            JQuery('#bfElementCalendarAdvancedMaxYear').val(mdata.maxYear);
            JQuery('#bfElementCalendarAdvancedFirstDay').val(mdata.firstDay);
        };

        // Hidden
        appScope.saveHiddenProperties = function (mdata, item) {
            // dynamic properties
            mdata.value = JQuery('#bfElementTypeHiddenValue').val();
            mdata.logging = JQuery('#bfElementHiddenAdvancedLogging').attr('checked');
            mdata.orderNumber = JQuery('#bfElementHiddenAdvancedOrderNumber').val();
            // static properties
            mdata.bfName = JQuery('#bfElementName').val();

            mdata.label = JQuery('#bfElementLabel').val();
            mdata['label_translation'+BFQMConfig.lang] = JQuery('#bfElementLabelTrans').val();

            mdata.labelPosition = JQuery('#bfElementAdvancedLabelPosition').val();
            mdata.tabIndex = JQuery('#bfElementAdvancedTabIndex').val();
            mdata.hideInMailback = JQuery('#bfElementAdvancedHideInMailback').attr('checked');
            mdata.off = JQuery('#bfElementAdvancedTurnOff').attr('checked');

            item.properties = mdata;
        };

        appScope.populateHiddenProperties = function (mdata) {
            // dynamic properties
            JQuery('#bfElementTypeHiddenValue').val(mdata.value);
            JQuery('#bfElementHiddenAdvancedLogging').attr('checked', mdata.logging);
            JQuery('#bfElementHiddenAdvancedOrderNumber').val(mdata.orderNumber);
            // static properties
            JQuery('#bfElementName').val(mdata.bfName);

            JQuery('#bfElementLabel').val(mdata.label);
            JQuery('#bfElementLabelTrans').val(typeof mdata['label_translation'+BFQMConfig.lang] != "undefined" ? mdata['label_translation'+BFQMConfig.lang] : "");

            JQuery('#bfElementAdvancedTabIndex').val(mdata.tabIndex);
            JQuery('#bfElementAdvancedHideInMailback').attr('checked', mdata.hideInMailback);
            JQuery('#bfElementAdvancedTurnOff').attr('checked', mdata.off);
            JQuery('#bfElementAdvancedLabelPosition').val(mdata.labelPosition);
        };

        // SUMMARIZE
        appScope.saveSummarizeProperties = function (mdata, item) {
            // dynamic properties
            var val = JQuery('#bfElementTypeSummarizeConnectWith').val();
            if (val != '') {
                var name = val.split(":")[0];
                var type = val.split(":")[1];
                mdata.connectWith = name;
                mdata.connectType = type;
            }

            mdata.useElementLabel = JQuery('#bfElementTypeSummarizeUseElementLabel').attr('checked');
            mdata.hideIfEmpty = JQuery('#bfElementTypeSummarizeHideIfEmpty').attr('checked');
            mdata.fieldCalc = JQuery('#bfElementAdvancedSummarizeCalc').val();

            mdata.emptyMessage = JQuery('#bfElementTypeSummarizeEmptyMessage').val();
            mdata['emptyMessage_translation'+BFQMConfig.lang] = JQuery('#bfElementTypeSummarizeEmptyMessageTrans').val();

            if (mdata.useElementLabel) {
                var items = new Array();
                appScope.getItemsFlattened(appScope.dataObject, items);
                for (var i = 0; i < items.length; i++) {
                    if (items[i].properties.bfName == name) {
                        JQuery('#bfElementLabel').val(items[i].properties.label);
                        JQuery('#bfElementLabelTrans').val(typeof items[i].properties['label_translation'+BFQMConfig.lang] != "undefined" ? items[i].properties['label_translation'+BFQMConfig.lang] : "");
                        break;
                    }
                }
            }
            // static properties
            mdata.bfName = JQuery('#bfElementName').val();

            mdata.label = JQuery('#bfElementLabel').val();
            mdata['label_translation'+BFQMConfig.lang] = JQuery('#bfElementLabelTrans').val();

            mdata.labelPosition = JQuery('#bfElementAdvancedLabelPosition').val();
            mdata.tabIndex = JQuery('#bfElementAdvancedTabIndex').val();
            mdata.hideInMailback = JQuery('#bfElementAdvancedHideInMailback').attr('checked');
            item.properties = mdata;
        };

        appScope.populateSummarizeProperties = function (mdata) {
            var items = new Array();
            appScope.getItemsFlattened(appScope.dataObject, items);
            JQuery('#bfElementTypeSummarizeConnectWith').empty();
            var option = document.createElement('option');
            JQuery(option).val('');
            JQuery(option).text(BFQMConfig.labels['COM_BREEZINGFORMSNG_CHOOSE_ONE']);
            JQuery('#bfElementTypeSummarizeConnectWith').append(option);
            for (var i = 0; i < items.length; i++) {
                switch (items[i].properties.bfType) {
                    case 'bfTextfield':
                    case 'bfTextarea':
                    case 'bfRadioGroup':
                    case 'bfCheckboxGroup':
                    case 'bfCheckbox':
                    case 'bfSelect':
                    case 'bfFile':
                    case 'bfHidden':
                    case 'bfCalendar':
                    case 'bfNumberInput':
                    case 'bfCalendarResponsive':
                        var option = document.createElement('option');
                        JQuery(option).val(items[i].properties.bfName + ":" + items[i].properties.bfType);
                        JQuery(option).text(items[i].properties.label + " (" + items[i].properties.bfName + ")");
                        JQuery('#bfElementTypeSummarizeConnectWith').append(option);
                        break;
                }
            }
            // dynamic properties
            JQuery('#bfElementTypeSummarizeConnectWith').val(mdata.connectWith + ":" + mdata.connectType);
            JQuery('#bfElementTypeSummarizeEmptyMesssage').val(mdata.emptyMessage);
            JQuery('#bfElementTypeSummarizeUseElementLabel').attr('checked', mdata.useElementLabel);

            JQuery('#bfElementTypeSummarizeEmptyMessage').val(mdata.emptyMessage);
            JQuery('#bfElementTypeSummarizeEmptyMessageTrans').val(typeof mdata['emptyMessage_translation'+BFQMConfig.lang] != "undefined" ? mdata['emptyMessage_translation'+BFQMConfig.lang] : "");

            JQuery('#bfElementTypeSummarizeHideIfEmpty').attr('checked', mdata.hideIfEmpty);
            JQuery('#bfElementAdvancedSummarizeCalc').val(mdata.fieldCalc);
            // static properties
            JQuery('#bfElementName').val(mdata.bfName);

            JQuery('#bfElementLabel').val(mdata.label);
            JQuery('#bfElementLabelTrans').val(typeof mdata['label_translation'+BFQMConfig.lang] != "undefined" ? mdata['label_translation'+BFQMConfig.lang] : "");

            JQuery('#bfElementAdvancedTabIndex').val(mdata.tabIndex);
            JQuery('#bfElementAdvancedLabelPosition').val(mdata.labelPosition);
            JQuery('#bfElementAdvancedHideInMailback').attr('checked', mdata.hideInMailback);
        };



        // STRIPE BUTTON
        appScope.saveStripeProperties = function (mdata, item) {
            // dynamic properties

            // DEFAULT

            // account
            mdata.secretKey = JQuery('#bfElementTypeStripeSecretKey').val();
            mdata.publishableKey = JQuery('#bfElementTypeStripePublishableKey').val();
            mdata.itemname = JQuery('#bfElementTypeStripeItemname').val();
            mdata.amount = JQuery('#bfElementTypeStripeAmount').val();
            mdata.thankYouPage = JQuery('#bfElementTypeStripeThankYouPage').val();

            mdata.currencyCode = JQuery('#bfElementTypeStripeCurrencyCode').val();
            mdata.sendNotificationAfterPayment = JQuery('#bfElementTypeStripeSendNotificationAfterPayment').attr('checked');

            // ADVANCED

            mdata.image = JQuery('#bfElementStripeAdvancedImage').val();
            mdata['image_translation'+BFQMConfig.lang] = JQuery('#bfElementStripeAdvancedImageTrans').val();


            // file
            mdata.downloadableFile = JQuery('#bfElementStripeAdvancedDownloadableFile').attr('checked');
            mdata.filepath = JQuery('#bfElementStripeAdvancedFilepath').val();
            mdata.downloadTries = JQuery('#bfElementStripeAdvancedDownloadTries').val();

            // OTHER ADVANCED
            mdata.hint = JQuery('#bfElementTypeStripeHint').val();
            mdata['hint_translation'+BFQMConfig.lang] = JQuery('#bfElementTypeStripeHintTrans').val();

            mdata.hideLabel = JQuery('#bfElementStripeAdvancedHideLabel').attr('checked');
            mdata.emailfield = JQuery('#bfElementStripeAdvancedEmailField').val();

            // static properties
            mdata.bfName = JQuery('#bfElementName').val();

            mdata.label = JQuery('#bfElementLabel').val();
            mdata['label_translation'+BFQMConfig.lang] = JQuery('#bfElementLabelTrans').val();

            mdata.labelPosition = JQuery('#bfElementAdvancedLabelPosition').val();
            mdata.tabIndex = JQuery('#bfElementAdvancedTabIndex').val();
            mdata.hideInMailback = JQuery('#bfElementAdvancedHideInMailback').attr('checked');
            mdata.off = JQuery('#bfElementAdvancedTurnOff').attr('checked');
            item.properties = mdata;
        };

        appScope.populateStripeProperties = function (mdata) {
            // dynamic properties

            // DEFAULT

            // account
            JQuery('#bfElementTypeStripeSecretKey').val(mdata.secretKey);
            JQuery('#bfElementTypeStripePublishableKey').val(mdata.publishableKey);

            JQuery('#bfElementTypeStripeItemname').val(mdata.itemname);
            JQuery('#bfElementTypeStripeAmount').val(mdata.amount);
            JQuery('#bfElementTypeStripeThankYouPage').val(mdata.thankYouPage);
            JQuery('#bfElementTypeStripeCurrencyCode').val(mdata.currencyCode);
            JQuery('#bfElementTypeStripeSendNotificationAfterPayment').attr('checked', mdata.sendNotificationAfterPayment);
            // ADVANCED

            JQuery('#bfElementStripeAdvancedImage').val(mdata.image);
            JQuery('#bfElementStripeAdvancedImageTrans').val(typeof mdata['image_translation'+BFQMConfig.lang] != "undefined" ? mdata['image_translation'+BFQMConfig.lang] : "");

            JQuery('#bfElementStripeAdvancedEmailField').val(mdata.emailfield);

            // file
            JQuery('#bfElementStripeAdvancedDownloadableFile').attr('checked', mdata.downloadableFile);
            JQuery('#bfElementStripeAdvancedFilepath').val(mdata.filepath);
            JQuery('#bfElementStripeAdvancedDownloadTries').val(mdata.downloadTries);

            JQuery('#bfElementTypeStripeHint').val(mdata.hint);
            JQuery('#bfElementTypeStripeHintTrans').val(typeof mdata['hint_translation'+BFQMConfig.lang] != "undefined" ? mdata['hint_translation'+BFQMConfig.lang] : "");

            JQuery('#bfElementStripeAdvancedHideLabel').attr('checked', mdata.hideLabel);

            // static properties
            JQuery('#bfElementName').val(mdata.bfName);

            JQuery('#bfElementLabel').val(mdata.label);
            JQuery('#bfElementLabelTrans').val(typeof mdata['label_translation'+BFQMConfig.lang] != "undefined" ? mdata['label_translation'+BFQMConfig.lang] : "");

            JQuery('#bfElementAdvancedTabIndex').val(mdata.tabIndex);
            JQuery('#bfElementAdvancedHideInMailback').attr('checked', mdata.hideInMailback);
            JQuery('#bfElementAdvancedTurnOff').attr('checked', mdata.off);
            JQuery('#bfElementAdvancedLabelPosition').val(mdata.labelPosition);
        };


        // PAYPAL BUTTON
        appScope.savePayPalProperties = function (mdata, item) {
            // dynamic properties

            // DEFAULT

            // account
            mdata.business = JQuery('#bfElementTypePayPalBusiness').val();
            mdata.token = JQuery('#bfElementTypePayPalToken').val();
            mdata.cancelURL = JQuery('#bfElementTypePayPalCancelURL').val();
            mdata.itemname = JQuery('#bfElementTypePayPalItemname').val();
            mdata.itemnumber = JQuery('#bfElementTypePayPalItemnumber').val();
            mdata.amount = JQuery('#bfElementTypePayPalAmount').val();
            mdata.tax = JQuery('#bfElementTypePayPalTax').val();
            mdata.thankYouPage = JQuery('#bfElementTypePayPalThankYouPage').val();
            mdata.locale = JQuery('#bfElementTypePayPalLocale').val();
            mdata.currencyCode = JQuery('#bfElementTypePayPalCurrencyCode').val();
            mdata.sendNotificationAfterPayment = JQuery('#bfElementTypePayPalSendNotificationAfterPayment').attr('checked');

            // ADVANCED

            mdata.useIpn = JQuery('#bfElementPayPalAdvancedUseIpn').attr('checked');

            mdata.image = JQuery('#bfElementPayPalAdvancedImage').val();
            mdata['image_translation'+BFQMConfig.lang] = JQuery('#bfElementPayPalAdvancedImageTrans').val();

            // testaccount
            mdata.testaccount = JQuery('#bfElementPayPalAdvancedTestaccount').attr('checked');
            mdata.testBusiness = JQuery('#bfElementPayPalAdvancedTestBusiness').val();
            mdata.testToken = JQuery('#bfElementPayPalAdvancedTestToken').val();

            // file
            mdata.downloadableFile = JQuery('#bfElementPayPalAdvancedDownloadableFile').attr('checked');
            mdata.filepath = JQuery('#bfElementPayPalAdvancedFilepath').val();
            mdata.downloadTries = JQuery('#bfElementPayPalAdvancedDownloadTries').val();

            // OTHER ADVANCED
            mdata.hint = JQuery('#bfElementTypePayPalHint').val();
            mdata['hint_translation'+BFQMConfig.lang] = JQuery('#bfElementTypePayPalHintTrans').val();

            mdata.hideLabel = JQuery('#bfElementPayPalAdvancedHideLabel').attr('checked');

            // static properties
            mdata.bfName = JQuery('#bfElementName').val();

            mdata.label = JQuery('#bfElementLabel').val();
            mdata['label_translation'+BFQMConfig.lang] = JQuery('#bfElementLabelTrans').val();

            mdata.labelPosition = JQuery('#bfElementAdvancedLabelPosition').val();
            mdata.tabIndex = JQuery('#bfElementAdvancedTabIndex').val();
            mdata.hideInMailback = JQuery('#bfElementAdvancedHideInMailback').attr('checked');
            mdata.off = JQuery('#bfElementAdvancedTurnOff').attr('checked');
            item.properties = mdata;
        };

        appScope.populatePayPalProperties = function (mdata) {
            // dynamic properties

            // DEFAULT

            // account
            JQuery('#bfElementTypePayPalBusiness').val(mdata.business);
            JQuery('#bfElementTypePayPalToken').val(mdata.token);
            JQuery('#bfElementTypePayPalCancelURL').val(mdata.cancelURL);
            JQuery('#bfElementTypePayPalItemname').val(mdata.itemname);
            JQuery('#bfElementTypePayPalItemnumber').val(mdata.itemnumber);
            JQuery('#bfElementTypePayPalAmount').val(mdata.amount);
            JQuery('#bfElementTypePayPalTax').val(mdata.tax);
            JQuery('#bfElementTypePayPalThankYouPage').val(mdata.thankYouPage);
            JQuery('#bfElementTypePayPalLocale').val(mdata.locale);
            JQuery('#bfElementTypePayPalCurrencyCode').val(mdata.currencyCode);
            JQuery('#bfElementTypePayPalSendNotificationAfterPayment').attr('checked', mdata.sendNotificationAfterPayment);
            // ADVANCED

            JQuery('#bfElementPayPalAdvancedImage').val(mdata.image);
            JQuery('#bfElementPayPalAdvancedImageTrans').val(typeof mdata['image_translation'+BFQMConfig.lang] != "undefined" ? mdata['image_translation'+BFQMConfig.lang] : "");


            // testaccount
            JQuery('#bfElementPayPalAdvancedTestaccount').attr('checked', mdata.testaccount);
            JQuery('#bfElementPayPalAdvancedTestBusiness').val(mdata.testBusiness);
            JQuery('#bfElementPayPalAdvancedTestToken').val(mdata.testToken);

            // file
            JQuery('#bfElementPayPalAdvancedDownloadableFile').attr('checked', mdata.downloadableFile);
            JQuery('#bfElementPayPalAdvancedFilepath').val(mdata.filepath);
            JQuery('#bfElementPayPalAdvancedDownloadTries').val(mdata.downloadTries);
            if (typeof mdata.useIpn == "undefined") {
                mdata['useIpn'] = false;
            }
            JQuery('#bfElementPayPalAdvancedUseIpn').attr('checked', mdata.useIpn);

            JQuery('#bfElementTypePayPalHint').val(mdata.hint);
            JQuery('#bfElementTypePayPalHintTrans').val(typeof mdata['hint_translation'+BFQMConfig.lang] != "undefined" ? mdata['hint_translation'+BFQMConfig.lang] : "");

            JQuery('#bfElementPayPalAdvancedHideLabel').attr('checked', mdata.hideLabel);

            // static properties
            JQuery('#bfElementName').val(mdata.bfName);

            JQuery('#bfElementLabel').val(mdata.label);
            JQuery('#bfElementLabelTrans').val(typeof mdata['label_translation'+BFQMConfig.lang] != "undefined" ? mdata['label_translation'+BFQMConfig.lang] : "");

            JQuery('#bfElementAdvancedTabIndex').val(mdata.tabIndex);
            JQuery('#bfElementAdvancedHideInMailback').attr('checked', mdata.hideInMailback);
            JQuery('#bfElementAdvancedTurnOff').attr('checked', mdata.off);
            JQuery('#bfElementAdvancedLabelPosition').val(mdata.labelPosition);
        };

        // SOFORTUEBERWEISUNG BUTTON
        appScope.saveSofortueberweisungProperties = function (mdata, item) {
            // dynamic properties

            // DEFAULT

            // account
            mdata.user_id = JQuery('#bfElementTypeSofortueberweisungUserId').val();
            mdata.project_id = JQuery('#bfElementTypeSofortueberweisungProjectId').val();
            mdata.project_password = JQuery('#bfElementTypeSofortueberweisungProjectPassword').val();

            mdata.reason_1 = JQuery('#bfElementTypeSofortueberweisungReason1').val();
            mdata.reason_2 = JQuery('#bfElementTypeSofortueberweisungReason2').val();
            mdata.amount = JQuery('#bfElementTypeSofortueberweisungAmount').val();
            mdata.thankYouPage = JQuery('#bfElementTypeSofortueberweisungThankYouPage').val();
            mdata.language_id = JQuery('#bfElementTypeSofortueberweisungLanguageId').val();
            mdata.currency_id = JQuery('#bfElementTypeSofortueberweisungCurrencyId').val();
            mdata.mailback = JQuery('#bfElementTypeSofortueberweisungMailback').attr('checked');
            mdata.sendNotificationAfterPayment = JQuery('#bfElementTypeSofortueberweisungSendNotificationAfterPayment').attr('checked');

            // ADVANCED

            mdata.image = JQuery('#bfElementSofortueberweisungAdvancedImage').val();
            mdata['image_translation'+BFQMConfig.lang] = JQuery('#bfElementSofortueberweisungAdvancedImageTrans').val();

            // file
            mdata.downloadableFile = JQuery('#bfElementSofortueberweisungAdvancedDownloadableFile').attr('checked');
            mdata.filepath = JQuery('#bfElementSofortueberweisungAdvancedFilepath').val();
            mdata.downloadTries = JQuery('#bfElementSofortueberweisungAdvancedDownloadTries').val();

            // OTHER ADVANCED
            mdata.hint = JQuery('#bfElementTypeSofortueberweisungHint').val();
            mdata['hint_translation'+BFQMConfig.lang] = JQuery('#bfElementTypeSofortueberweisungHintTrans').val();

            mdata.hideLabel = JQuery('#bfElementSofortueberweisungAdvancedHideLabel').attr('checked');

            // static properties
            mdata.bfName = JQuery('#bfElementName').val();

            mdata.label = JQuery('#bfElementLabel').val();
            mdata['label_translation'+BFQMConfig.lang] = JQuery('#bfElementLabelTrans').val();

            mdata.labelPosition = JQuery('#bfElementAdvancedLabelPosition').val();
            mdata.tabIndex = JQuery('#bfElementAdvancedTabIndex').val();
            mdata.hideInMailback = JQuery('#bfElementAdvancedHideInMailback').attr('checked');
            mdata.off = JQuery('#bfElementAdvancedTurnOff').attr('checked');
            item.properties = mdata;
        };

        appScope.populateSofortueberweisungProperties = function (mdata) {
            // dynamic properties

            // DEFAULT

            // account
            JQuery('#bfElementTypeSofortueberweisungUserId').val(mdata.user_id);
            JQuery('#bfElementTypeSofortueberweisungProjectId').val(mdata.project_id);
            JQuery('#bfElementTypeSofortueberweisungProjectPassword').val(mdata.project_password);

            JQuery('#bfElementTypeSofortueberweisungReason1').val(mdata.reason_1);
            JQuery('#bfElementTypeSofortueberweisungReason2').val(mdata.reason_2);
            JQuery('#bfElementTypeSofortueberweisungAmount').val(mdata.amount);
            JQuery('#bfElementTypeSofortueberweisungThankYouPage').val(mdata.thankYouPage);
            JQuery('#bfElementTypeSofortueberweisungLanguageId').val(mdata.language_id);
            JQuery('#bfElementTypeSofortueberweisungCurrencyId').val(mdata.currency_id);
            JQuery('#bfElementTypeSofortueberweisungMailback').attr('checked', mdata.mailback);
            JQuery('#bfElementTypeSofortueberweisungSendNotificationAfterPayment').attr('checked', mdata.sendNotificationAfterPayment);

            // ADVANCED

            JQuery('#bfElementSofortueberweisungAdvancedImage').val(mdata.image);
            JQuery('#bfElementSofortueberweisungAdvancedImageTrans').val(typeof mdata['image_translation'+BFQMConfig.lang] != "undefined" ? mdata['image_translation'+BFQMConfig.lang] : "");

            // file
            JQuery('#bfElementSofortueberweisungAdvancedDownloadableFile').attr('checked', mdata.downloadableFile);
            JQuery('#bfElementSofortueberweisungAdvancedFilepath').val(mdata.filepath);
            JQuery('#bfElementSofortueberweisungAdvancedDownloadTries').val(mdata.downloadTries);

            // OTHER ADVANCED
            JQuery('#bfElementTypeSofortueberweisungHint').val(mdata.hint);
            JQuery('#bfElementTypeSofortueberweisungHintTrans').val(typeof mdata['hint_translation'+BFQMConfig.lang] != "undefined" ? mdata['hint_translation'+BFQMConfig.lang] : "");

            JQuery('#bfElementSofortueberweisungAdvancedHideLabel').attr('checked', mdata.hideLabel);

            // static properties
            JQuery('#bfElementName').val(mdata.bfName);

            JQuery('#bfElementLabel').val(mdata.label);
            JQuery('#bfElementLabelTrans').val(typeof mdata['label_translation'+BFQMConfig.lang] != "undefined" ? mdata['label_translation'+BFQMConfig.lang] : "");

            JQuery('#bfElementAdvancedTabIndex').val(mdata.tabIndex);
            JQuery('#bfElementAdvancedHideInMailback').attr('checked', mdata.hideInMailback);
            JQuery('#bfElementAdvancedTurnOff').attr('checked', mdata.off);
            JQuery('#bfElementAdvancedLabelPosition').val(mdata.labelPosition);
        };

        appScope.saveSelectedElementProperties = function () {
            if (appScope.selectedTreeElement) {
                var mdata = appScope.getProperties(appScope.selectedTreeElement);
                if (mdata) {
                    var item = appScope.findDataObjectItem(JQuery(appScope.selectedTreeElement).attr('id'), appScope.dataObject);
                    if (item) {

                        switch (mdata.bfType) {
                            case 'bfSummarize':
                                appScope.saveSummarizeProperties(mdata, item);
                                break;
                            case 'bfHidden':
                                appScope.saveHiddenProperties(mdata, item);
                                appScope.saveValidation(mdata, item);
                                appScope.saveInit(mdata, item);
                                break;
                            case 'bfTextfield':
                                appScope.saveTextProperties(mdata, item);
                                appScope.saveValidation(mdata, item);
                                appScope.saveInit(mdata, item);
                                appScope.saveAction(mdata, item);
                                break;
                            case 'bfTextarea':
                                appScope.saveTextareaProperties(mdata, item);
                                appScope.saveValidation(mdata, item);
                                appScope.saveInit(mdata, item);
                                appScope.saveAction(mdata, item);
                                break;
                            case 'bfRadioGroup':
                                appScope.saveRadioGroupProperties(mdata, item);
                                appScope.saveValidation(mdata, item);
                                appScope.saveInit(mdata, item);
                                appScope.saveAction(mdata, item);
                                break;
                            case 'bfSubmitButton':
                                appScope.saveSubmitButtonProperties(mdata, item);
                                appScope.saveAction(mdata, item);
                                break;
                            case 'bfStripe':
                                appScope.saveStripeProperties(mdata, item);
                                appScope.saveAction(mdata, item);
                                break;
                            case 'bfSignature':
                                appScope.saveSignatureProperties(mdata, item);
                                appScope.saveValidation(mdata, item);
                                appScope.saveInit(mdata, item);
                                appScope.saveAction(mdata, item);
                                break;
                            case 'bfPayPal':
                                appScope.savePayPalProperties(mdata, item);
                                appScope.saveAction(mdata, item);
                                break;
                            case 'bfSofortueberweisung':
                                appScope.saveSofortueberweisungProperties(mdata, item);
                                appScope.saveAction(mdata, item);
                                break;
                            case 'bfCaptcha':
                                appScope.saveCaptchaProperties(mdata, item);
                                appScope.saveAction(mdata, item);
                                break;
                            case 'bfReCaptcha':
                                appScope.saveReCaptchaProperties(mdata, item);
                                appScope.saveAction(mdata, item);
                                break;
                            case 'bfCalendar':
                                appScope.saveCalendarProperties(mdata, item);
                                appScope.saveValidation(mdata, item);
                                break;
                            case 'bfCalendarResponsive':
                                appScope.saveCalendarResponsiveProperties(mdata, item);
                                appScope.saveValidation(mdata, item);
                                break;
                            case 'bfCheckboxGroup':
                                appScope.saveCheckboxGroupProperties(mdata, item);
                                appScope.saveValidation(mdata, item);
                                appScope.saveInit(mdata, item);
                                appScope.saveAction(mdata, item);
                                break;
                            case 'bfCheckbox':
                                appScope.saveCheckboxProperties(mdata, item);
                                appScope.saveValidation(mdata, item);
                                appScope.saveInit(mdata, item);
                                appScope.saveAction(mdata, item);
                                break;
                            case 'bfSelect':
                                appScope.saveSelectProperties(mdata, item);
                                appScope.saveValidation(mdata, item);
                                appScope.saveInit(mdata, item);
                                appScope.saveAction(mdata, item);
                                break;
                            case 'bfFile':
                                appScope.saveFileProperties(mdata, item);
                                appScope.saveValidation(mdata, item);
                                appScope.saveInit(mdata, item);
                                appScope.saveAction(mdata, item);
                                break;
                            case 'bfNumberInput':
                                appScope.saveNumberInputProperties(mdata, item);
                                appScope.saveValidation(mdata, item);
                                appScope.saveInit(mdata, item);
                                appScope.saveAction(mdata, item);
                                break;
                        }
                        item.attributes.id = JQuery('#bfElementName').val();
                        JQuery(appScope.selectedTreeElement).attr('id', JQuery('#bfElementName').val());
                    }
                }
            }
        };

        appScope.saveValidation = function (mdata, item) {
            mdata.validationId = JQuery('#bfValidationScriptSelection').val();
            mdata.validationCode = JoomlaEditor.get('bfValidationCode').getValue();
            mdata.validationMessage = JQuery('#bfValidationMessage').val();
            mdata['validationMessage_translation'+BFQMConfig.lang] = JQuery('#bfValidationMessageTrans').val();

            if (JQuery('#bfValidationTypeLibrary').get(0).checked) {
                mdata.validationCondition = 1;
                for (var i = 0; i < appScope.elementScripts.validation.length; i++) {
                    if (appScope.elementScripts.validation[i].id == JQuery('#bfValidationScriptSelection').val()) {
                        mdata.validationFunctionName = appScope.elementScripts.validation[i].name;
                        break;
                    }
                }

            } else if (JQuery('#bfValidationTypeCustom').get(0).checked) {
                mdata.validationCondition = 2;
                mdata.validationFunctionName = 'ff_' + mdata.bfName + '_validation';
            } else {
                mdata.validationCondition = 0;
            }
            item.properties = mdata;
        };

        appScope.saveInit = function (mdata, item) {
            if (JQuery('#bfInitFormEntry').get(0).checked) {
                mdata.initFormEntry = 1;
            } else {
                mdata.initFormEntry = 0;
            }

            if (JQuery('#bfInitPageEntry').get(0).checked) {
                mdata.initPageEntry = 1;
            } else {
                mdata.initPageEntry = 0;
            }

            mdata.initId = JQuery('#bfInitScriptSelection').val();
            mdata.initCode = JoomlaEditor.get('bfInitCode').getValue();

            if (JQuery('#bfInitTypeLibrary').get(0).checked) {
                mdata.initCondition = 1;
                for (var i = 0; i < appScope.elementScripts.init.length; i++) {
                    if (appScope.elementScripts.init[i].id == JQuery('#bfInitScriptSelection').val()) {
                        mdata.initScript = appScope.elementScripts.init[i].name;
                        break;
                    }
                }

            } else if (JQuery('#bfInitTypeCustom').get(0).checked) {
                mdata.initCondition = 2;
                mdata.initFunctionName = 'ff_' + mdata.bfName + '_init';
            } else {
                mdata.initCondition = 0;
            }
            item.properties = mdata;
        };

        appScope.saveAction = function (mdata, item) {

            mdata.actionId = JQuery('#bfActionsScriptSelection').val();
            mdata.actionCode = JoomlaEditor.get('bfActionCode').getValue();

            if (JQuery('#bfActionTypeLibrary').get(0).checked) {
                mdata.actionCondition = 1;
                for (var i = 0; i < appScope.elementScripts.action.length; i++) {
                    if (appScope.elementScripts.action[i].id == JQuery('#bfActionsScriptSelection').val()) {
                        mdata.actionFunctionName = appScope.elementScripts.action[i].name;
                        break;
                    }
                }
            } else if (JQuery('#bfActionTypeCustom').get(0).checked) {
                mdata.actionCondition = 2;
                mdata.actionFunctionName = 'ff_' + mdata.bfName + '_action';
            } else {
                mdata.actionCondition = 0;
            }

            if (JQuery('#bfActionClick').get(0).checked && mdata.actionCondition > 0) {
                mdata.actionClick = 1;
            } else {
                mdata.actionClick = 0;
            }

            if (JQuery('#bfActionBlur').get(0).checked && mdata.actionCondition > 0) {
                mdata.actionBlur = 1;
            } else {
                mdata.actionBlur = 0;
            }

            if (JQuery('#bfActionChange').get(0).checked && mdata.actionCondition > 0) {
                mdata.actionChange = 1;
            } else {
                mdata.actionChange = 0;
            }

            if (JQuery('#bfActionFocus').get(0).checked && mdata.actionCondition > 0) {
                mdata.actionFocus = 1;
            } else {
                mdata.actionFocus = 0;
            }

            if (JQuery('#bfActionSelect').get(0).checked && mdata.actionCondition > 0) {
                mdata.actionSelect = 1;
            } else {
                mdata.actionSelect = 0;
            }

            item.properties = mdata;
        };

        appScope.populateSelectedElementProperties = function () {
            if (appScope.selectedTreeElement) {
                var mdata = appScope.getProperties(appScope.selectedTreeElement);

                // compat 723
                if (typeof mdata.off == "undefined") {
                    mdata['off'] = false;
                }
                // compat 723 end

                if (mdata) {
                    var item = appScope.findDataObjectItem(JQuery(appScope.selectedTreeElement).attr('id'), appScope.dataObject);
                    if (item) {
                        item.data.title = JQuery("<div/>").text(mdata.label).html();
                        JQuery('#bfValidationScript').css('display', 'none');
                        JQuery('#bfInitScript').css('display', 'none');
                        JQuery('#bfActionScript').css('display', 'none');

                        JQuery('#bfElementTypeText').css('display', 'none');
                        JQuery('#bfElementTypeTextarea').css('display', 'none');
                        JQuery('#bfElementTypeRadioGroup').css('display', 'none');
                        JQuery('#bfElementTypeSubmitButton').css('display', 'none');
                        JQuery('#bfElementTypeStripe').css('display', 'none');
                        JQuery('#bfElementTypeSignature').css('display', 'none');
                        JQuery('#bfElementTypePayPal').css('display', 'none');
                        JQuery('#bfElementTypeSofortueberweisung').css('display', 'none');
                        JQuery('#bfElementTypeCaptcha').css('display', 'none');
                        JQuery('#bfElementTypeReCaptcha').css('display', 'none');
                        JQuery('#bfElementTypeCalendar').css('display', 'none');
                        JQuery('#bfElementTypeCalendarResponsive').css('display', 'none');
                        JQuery('#bfElementTypeCheckboxGroup').css('display', 'none');
                        JQuery('#bfElementTypeCheckbox').css('display', 'none');
                        JQuery('#bfElementTypeSelect').css('display', 'none');
                        JQuery('#bfElementTypeFile').css('display', 'none');
                        JQuery('#bfElementTypeHidden').css('display', 'none');
                        JQuery('#bfElementTypeSummarize').css('display', 'none');
                        JQuery('#bfElementTypeNumberInput').css('display', 'none');

                        JQuery('#bfElementTypeTextAdvanced').css('display', 'none');
                        JQuery('#bfElementTypeTextareaAdvanced').css('display', 'none');
                        JQuery('#bfElementTypeRadioGroupAdvanced').css('display', 'none');
                        JQuery('#bfElementTypeSubmitButtonAdvanced').css('display', 'none');
                        JQuery('#bfElementTypeStripeAdvanced').css('display', 'none');
                        JQuery('#bfElementTypeSignatureAdvanced').css('display', 'none');
                        JQuery('#bfElementTypePayPalAdvanced').css('display', 'none');
                        JQuery('#bfElementTypeSofortueberweisungAdvanced').css('display', 'none');
                        JQuery('#bfElementTypeCaptchaAdvanced').css('display', 'none');
                        JQuery('#bfElementTypeReCaptchaAdvanced').css('display', 'none');
                        JQuery('#bfElementTypeCalendarAdvanced').css('display', 'none');
                        JQuery('#bfElementTypeCalendarResponsiveAdvanced').css('display', 'none');
                        JQuery('#bfElementTypeCheckboxGroupAdvanced').css('display', 'none');
                        JQuery('#bfElementTypeCheckboxAdvanced').css('display', 'none');
                        JQuery('#bfElementTypeSelectAdvanced').css('display', 'none');
                        JQuery('#bfElementTypeFileAdvanced').css('display', 'none');
                        JQuery('#bfElementTypeHiddenAdvanced').css('display', 'none');
                        JQuery('#bfElementTypeSummarizeAdvanced').css('display', 'none');
                        JQuery('#bfElementTypeNumberInputAdvanced').css('display', 'none');
                        JQuery('#bfElementValidationRequiredSet').css('display', 'none');

                        JQuery('#bfAdvancedLeaf').css('display', '');
                        JQuery('#bfHideInMailback').css('display', '');

                        switch (mdata.bfType) {
                            case 'bfNumberInput':
                                JQuery('#bfElementType').val(mdata.range ? 'bfElementTypeSlider' : 'bfElementTypeNumberInput');
                                appScope.populateNumberInputProperties(mdata);
                                appScope.populateElementValidationScript();
                                appScope.populateElementInitScript();
                                appScope.populateElementActionScript();
                                break;
                            case 'bfSummarize':
                                JQuery('#bfHideInMailback').css('display', 'none');
                                JQuery('#bfElementType').val('bfElementTypeSummarize');
                                appScope.populateSummarizeProperties(mdata);
                                break;
                            case 'bfHidden':
                                JQuery('#bfElementType').val('bfElementTypeHidden');
                                JQuery('#bfAdvancedLeaf').css('display', 'none');
                                appScope.populateHiddenProperties(mdata);
                                appScope.populateElementValidationScript();
                                appScope.populateElementInitScript();
                                break;
                            case 'bfTextfield':
                                JQuery('#bfElementType').val('bfElementTypeText');
                                appScope.populateTextProperties(mdata);
                                appScope.populateElementValidationScript();
                                appScope.populateElementInitScript();
                                appScope.populateElementActionScript();
                                break;
                            case 'bfTextarea':
                                JQuery('#bfElementType').val('bfElementTypeTextarea');
                                appScope.populateTextareaProperties(mdata);
                                appScope.populateElementValidationScript();
                                appScope.populateElementInitScript();
                                appScope.populateElementActionScript();
                                break;
                            case 'bfRadioGroup':
                                JQuery('#bfElementType').val('bfElementTypeRadioGroup');
                                appScope.populateRadioGroupProperties(mdata);
                                appScope.populateElementValidationScript();
                                appScope.populateElementInitScript();
                                appScope.populateElementActionScript();
                                break;
                            case 'bfSubmitButton':
                                JQuery('#bfElementType').val('bfElementTypeSubmitButton');
                                appScope.populateSubmitButtonProperties(mdata);
                                appScope.populateElementActionScript();
                                break;
                            case 'bfStripe':
                                JQuery('#bfElementType').val('bfElementTypeStripe');
                                appScope.populateStripeProperties(mdata);
                                appScope.populateElementActionScript();
                                break;
                            case 'bfSignature':
                                JQuery('#bfElementType').val('bfElementTypeSignature');
                                appScope.populateSignatureProperties(mdata);
                                appScope.populateElementValidationScript();
                                appScope.populateElementInitScript();
                                appScope.populateElementActionScript();
                                break;
                            case 'bfPayPal':
                                JQuery('#bfElementType').val('bfElementTypePayPal');
                                appScope.populatePayPalProperties(mdata);
                                appScope.populateElementActionScript();
                                break;
                            case 'bfSofortueberweisung':
                                JQuery('#bfElementType').val('bfElementTypeSofortueberweisung');
                                appScope.populateSofortueberweisungProperties(mdata);
                                appScope.populateElementActionScript();
                                break;
                            case 'bfCaptcha':
                                JQuery('#bfHideInMailback').css('display', 'none');
                                JQuery('#bfElementType').val('bfElementTypeCaptcha');
                                appScope.populateCaptchaProperties(mdata);
                                break;
                            case 'bfReCaptcha':
                                JQuery('#bfHideInMailback').css('display', 'none');
                                JQuery('#bfElementType').val('bfElementTypeReCaptcha');
                                appScope.populateReCaptchaProperties(mdata);
                                break;
                            case 'bfCalendar':
                                JQuery('#bfElementType').val('bfElementTypeCalendar');
                                appScope.populateCalendarProperties(mdata);
                                appScope.populateElementValidationScript();
                                break;
                            case 'bfCalendarResponsive':
                                JQuery('#bfElementType').val('bfElementTypeCalendarResponsive');
                                appScope.populateCalendarResponsiveProperties(mdata);
                                appScope.populateElementValidationScript();
                                break;
                            case 'bfCheckboxGroup':
                                JQuery('#bfElementType').val('bfElementTypeCheckboxGroup');
                                appScope.populateCheckboxGroupProperties(mdata);
                                appScope.populateElementValidationScript();
                                appScope.populateElementInitScript();
                                appScope.populateElementActionScript();
                                break;
                            case 'bfCheckbox':
                                JQuery('#bfElementType').val('bfElementTypeCheckbox');
                                appScope.populateCheckboxProperties(mdata);
                                appScope.populateElementValidationScript();
                                appScope.populateElementInitScript();
                                appScope.populateElementActionScript();
                                break;
                            case 'bfSelect':
                                JQuery('#bfElementType').val('bfElementTypeSelect');
                                appScope.populateSelectProperties(mdata);
                                appScope.populateElementValidationScript();
                                appScope.populateElementInitScript();
                                appScope.populateElementActionScript();
                                break;
                            case 'bfFile':
                                JQuery('#bfElementType').val('bfElementTypeFile');
                                appScope.populateFileProperties(mdata);
                                appScope.populateElementValidationScript();
                                appScope.populateElementInitScript();
                                appScope.populateElementActionScript();
                                break;
                        }

                        if (JQuery('#bfElementType').val() != '') {
                            var selectedElementType = JQuery('#bfElementType').val();
                            var mappedElementType = selectedElementType === 'bfElementTypeSlider' ? 'bfElementTypeNumberInput' : selectedElementType;
                            JQuery('#bfElementTypeClass').css('display', 'none');
                            JQuery('#' + mappedElementType).css('display', '');
                            JQuery('#' + mappedElementType + "Advanced").css('display', '');
                            if (mdata.bfType != 'bfHidden') {
                                JQuery('#bfElementValidationRequiredSet').css('display', '');
                            }
                        }
                    }
                }
            }
        };

        appScope.populateElementValidationScript = function () {

            var mdata = appScope.getProperties(appScope.selectedTreeElement);
            if (mdata) {

                JQuery('#bfValidationScript').css('display', '');

                JQuery('#bfValidationScriptSelection').empty();
                for (var i = 0; i < appScope.elementScripts.validation.length; i++) {
                    var option = document.createElement('option');
                    JQuery(option).val(appScope.elementScripts.validation[i].id);
                    JQuery(option).text(appScope.elementScripts.validation[i].package + '::' + appScope.elementScripts.validation[i].name);
                    if (appScope.elementScripts.validation[i].id == mdata.validationId) {
                        JQuery(option).get(0).setAttribute('selected', true);
                    }
                    JQuery('#bfValidationScriptSelection').append(option);
                }

                JQuery('#bfValidationMessage').val(mdata.validationMessage);
                JQuery('#bfValidationMessageTrans').val(typeof mdata['validationMessage_translation'+BFQMConfig.lang] != "undefined" ? mdata['validationMessage_translation'+BFQMConfig.lang] : "");

                JoomlaEditor.get('bfValidationCode').setValue(mdata.validationCode);

                switch (mdata.validationCondition) {
                    case 1:
                        JQuery('.bfValidationType').attr('checked', '');
                        JQuery('#bfValidationTypeLibrary').attr('checked', true);
                        JQuery('#bfValidationScriptLibrary').css('display', '');
                        JQuery('#bfValidationScriptCustom').css('display', 'none');
                        JQuery('#bfValidationScriptFlags').css('display', '');
                        JQuery('#bfValidationScriptLibrary').css('display', '');
                        JQuery('#bfValidationScriptCustom').css('display', 'none');
                        appScope.setValidationScriptDescription();
                        break;
                    case 2:
                        JQuery('.bfValidationType').attr('checked', '');
                        JQuery('#bfValidationTypeCustom').attr('checked', true);
                        JQuery('#bfValidationScriptFlags').css('display', '');
                        JQuery('#bfValidationScriptLibrary').css('display', 'none');
                        JQuery('#bfValidationScriptCustom').css('display', '');
                        break;
                    default:
                        JQuery('.bfValidationType').attr('checked', '');
                        JQuery('#bfValidationTypeNone').attr('checked', true);
                        JQuery('#bfValidationScriptFlags').css('display', 'none');
                        JQuery('#bfValidationScriptLibrary').css('display', 'none');
                        JQuery('#bfValidationScriptCustom').css('display', 'none');
                }
            }

        };

        appScope.populateElementInitScript = function () {

            var mdata = appScope.getProperties(appScope.selectedTreeElement);
            if (mdata) {

                JQuery('#bfInitScript').css('display', '');

                JQuery('#bfInitScriptSelection').empty();
                for (var i = 0; i < appScope.elementScripts.init.length; i++) {
                    var option = document.createElement('option');
                    JQuery(option).val(appScope.elementScripts.init[i].id);
                    JQuery(option).text(appScope.elementScripts.init[i].package + '::' + appScope.elementScripts.init[i].name);
                    if (appScope.elementScripts.init[i].id == mdata.initId) {
                        JQuery(option).get(0).setAttribute('selected', true);
                    }
                    JQuery('#bfInitScriptSelection').append(option);
                }

                if (mdata.initFormEntry == 1) {
                    JQuery('#bfInitFormEntry').get(0).checked = true;
                } else {
                    JQuery('#bfInitFormEntry').get(0).checked = false;
                }

                if (mdata.initPageEntry == 1) {
                    JQuery('#bfInitPageEntry').get(0).checked = true;
                } else {
                    JQuery('#bfInitPageEntry').get(0).checked = false;
                }

                JoomlaEditor.get('bfInitCode').setValue(mdata.initCode);

                switch (mdata.initCondition) {
                    case 1:
                        JQuery('.bfInitType').attr('checked', '');
                        JQuery('#bfInitTypeLibrary').attr('checked', true);
                        JQuery('#bfInitScriptLibrary').css('display', '');
                        JQuery('#bfInitScriptCustom').css('display', 'none');
                        JQuery('#bfInitScriptFlags').css('display', '');
                        JQuery('#bfInitScriptLibrary').css('display', '');
                        JQuery('#bfInitScriptCustom').css('display', 'none');
                        appScope.setInitScriptDescription();
                        break;
                    case 2:
                        JQuery('.bfInitType').attr('checked', '');
                        JQuery('#bfInitTypeCustom').attr('checked', true);
                        JQuery('#bfInitScriptFlags').css('display', '');
                        JQuery('#bfInitScriptLibrary').css('display', 'none');
                        JQuery('#bfInitScriptCustom').css('display', '');
                        break;
                    default:
                        JQuery('.bfInitType').attr('checked', '');
                        JQuery('#bfInitTypeNone').attr('checked', true);
                        JQuery('#bfInitScriptFlags').css('display', 'none');
                        JQuery('#bfInitScriptLibrary').css('display', 'none');
                        JQuery('#bfInitScriptCustom').css('display', 'none');
                }
            }
        };

        appScope.populateElementActionScript = function () {

            var mdata = appScope.getProperties(appScope.selectedTreeElement);
            if (mdata) {

                JQuery('#bfActionScript').css('display', '');

                if (mdata.bfType == 'bfStripe' || mdata.bfType == 'bfSofortueberweisung' || mdata.bfType == 'bfPayPal' || mdata.bfType == 'bfIcon' || mdata.bfType == 'bfImageButton' || mdata.bfType == 'bfSubmitButton') {
                    JQuery('.bfAction').css('display', 'none');
                    JQuery('.bfActionLabel').css('display', 'none');
                    JQuery('#bfActionClick').css('display', '');
                    JQuery('#bfActionClickLabel').css('display', '');
                } else {
                    JQuery('.bfAction').css('display', '');
                    JQuery('.bfActionLabel').css('display', '');
                }

                JQuery('#bfActionsScriptSelection').empty();

                for (var i = 0; i < appScope.elementScripts.action.length; i++) {

                    var option = document.createElement('option');

                    JQuery(option).val(appScope.elementScripts.action[i].id);
                    JQuery(option).text(appScope.elementScripts.action[i].package + '::' + appScope.elementScripts.action[i].name);

                    if (appScope.elementScripts.action[i].id == mdata.actionId) {

                        JQuery(option).get(0).setAttribute('selected', true);
                    }

                    JQuery('#bfActionsScriptSelection').append(option);
                }

                if (mdata.actionClick == 1) {
                    JQuery('#bfActionClick').get(0).checked = true;
                } else {
                    JQuery('#bfActionClick').get(0).checked = false;
                }

                if (mdata.actionBlur == 1) {
                    JQuery('#bfActionBlur').get(0).checked = true;
                } else {
                    JQuery('#bfActionBlur').get(0).checked = false;
                }

                if (mdata.actionChange == 1) {
                    JQuery('#bfActionChange').get(0).checked = true;
                } else {
                    JQuery('#bfActionChange').get(0).checked = false;
                }

                if (mdata.actionFocus == 1) {
                    JQuery('#bfActionFocus').get(0).checked = true;
                } else {
                    JQuery('#bfActionFocus').get(0).checked = false;
                }

                if (mdata.actionSelect == 1) {
                    JQuery('#bfActionSelect').get(0).checked = true;
                } else {
                    JQuery('#bfActionSelect').get(0).checked = false;
                }

                JoomlaEditor.get('bfActionCode').setValue(mdata.actionCode);

                switch (mdata.actionCondition) {
                    case 1:
                        JQuery('.bfActionType').attr('checked', '');
                        JQuery('#bfActionTypeLibrary').attr('checked', true);
                        JQuery('#bfActionScriptLibrary').css('display', '');
                        JQuery('#bfActionScriptCustom').css('display', 'none');
                        JQuery('#bfActionScriptFlags').css('display', '');
                        JQuery('#bfActionScriptLibrary').css('display', '');
                        JQuery('#bfActionScriptCustom').css('display', 'none');
                        appScope.setActionScriptDescription();
                        break;
                    case 2:
                        JQuery('.bfActionType').attr('checked', '');
                        JQuery('#bfActionTypeCustom').attr('checked', true);
                        JQuery('#bfActionScriptFlags').css('display', '');
                        JQuery('#bfActionScriptLibrary').css('display', 'none');
                        JQuery('#bfActionScriptCustom').css('display', '');
                        break;
                    default:
                        JQuery('.bfActionType').attr('checked', '');
                        JQuery('#bfActionTypeNone').attr('checked', true);
                        JQuery('#bfActionScriptFlags').css('display', 'none');
                        JQuery('#bfActionScriptLibrary').css('display', 'none');
                        JQuery('#bfActionScriptCustom').css('display', 'none');
                }

            }
        };

        appScope.createTreeItem = function (obj) {
            if (appScope.selectedTreeElement) {
                var selectedId = JQuery(appScope.selectedTreeElement).attr('id');
                var selected = appScope.treeModel.find(selectedId);
                var inserted = false;

                if (selected && appScope.treeModel.canContain(selected, obj)) {
                    try {
                        inserted = Boolean(appScope.treeModel.insert(selectedId, obj));

                        if (appScope.treeModel.getNodeType(selected) === 'root') {
                            appScope.treeModel.renumberPages(BFQMConfig.labels['COM_BREEZINGFORMSNG_PAGE']);
                        }
                    } catch (error) {
                        alert(BFQMConfig.labels['COM_BREEZINGFORMSNG_NEW_SECTION_ERROR']);
                    }
                } else {
                    alert(BFQMConfig.labels['COM_BREEZINGFORMSNG_NEW_SECTION_ERROR']);
                }

                if (inserted) {
                    JQuery.tree_reference('bfElementExplorer').refresh();
                }
            }
        };

        /**
         Section properties
         */
        appScope.saveSectionProperties = function () {
            var mdata = appScope.getProperties(appScope.selectedTreeElement);
            if (mdata) {
                var item = appScope.findDataObjectItem(JQuery(appScope.selectedTreeElement).attr('id'), appScope.dataObject);
                if (item) {
                    mdata.bfType = JQuery('#bfSectionType').val();
                    mdata.displayType = JQuery('#bfSectionDisplayType').val();
                    mdata.title = JQuery('#bfSectionTitle').val();
                    mdata['title_translation'+BFQMConfig.lang] = JQuery('#bfSectionTitleTrans').val();

                    mdata.name = JQuery('#bfSectionName').val();
                    mdata.off = JQuery('#bfSectionAdvancedTurnOff').attr('checked');

                    item.properties = mdata;
                    item.data.title = JQuery('#bfSectionTitle').val();
                }
            }
        };

        appScope.populateSectionProperties = function () {
            if (appScope.selectedTreeElement) {
                var mdata = appScope.getProperties(appScope.selectedTreeElement);
                // compat 723
                if (typeof mdata.off == "undefined") {
                    mdata['off'] = false;
                }
                // compat 723 end
                if (mdata) {
                    var item = appScope.findDataObjectItem(JQuery(appScope.selectedTreeElement).attr('id'), appScope.dataObject);
                    if (item) {
                        item.data.title = mdata.title;

                        JQuery('#bfSectionType').val(mdata.bfType);
                        JQuery('#bfSectionDisplayType').val(mdata.displayType);

                        JQuery('#bfSectionTitle').val(mdata.title);
                        JQuery('#bfSectionTitleTrans').val(typeof mdata['title_translation'+BFQMConfig.lang] != "undefined" ? mdata['title_translation'+BFQMConfig.lang] : "");

                        // compat 723
                        JQuery('#bfSectionName').val(typeof mdata.name == "undefined" ? '' : mdata.name);
                        // compat 723 end
                        JQuery('#bfSectionAdvancedTurnOff').attr('checked', mdata.off);
                    }
                }
            }
        };

        /**
         Form properties
         */
        appScope.saveFormProperties = function () {
            var mdata = appScope.getProperties(appScope.selectedTreeElement);
            if (mdata) {
                var item = appScope.findDataObjectItem(JQuery(appScope.selectedTreeElement).attr('id'), appScope.dataObject);
                if (item) {
                    mdata.title = JQuery('#bfFormTitle').val();
                    mdata['title_translation'+BFQMConfig.lang] = JQuery('#bfFormTitleTrans').val();

                    mdata.name = JQuery('#bfFormName').val();
                    mdata.description = JQuery('#bfFormDescription').val();
                    mdata.mailRecipient = JQuery('#bfFormMailRecipient').val();
                    mdata.mailNotification = JQuery('#bfFormMailNotification').attr('checked');
                    mdata.submitInclude = JQuery('#bfSubmitIncludeYes').prop('checked');
                    mdata.themebootstrapLabelTop = JQuery('#bfThemeBootstrapLabelTopYes').attr('checked');
                    mdata.themebootstrapUseHeroUnit = JQuery('#bfThemeBootstrapUseHeroUnitYes').attr('checked');
                    mdata.themebootstrapUseWell = JQuery('#bfThemeBootstrapUseWellYes').attr('checked');
                    mdata.themebootstrapUseProgress = JQuery('#bfThemeBootstrapUseProgressYes').attr('checked');

                    mdata.themebootstrapThemeEngine = JQuery('#bfThemeBootstrapThemeBootstrap').attr('checked') ? 'bootstrap' : 'breezingforms';

                    mdata.themebootstrapMode = JQuery('#bfThemeBootstrapModeYes').attr('checked');
                    delete mdata.themebootstrapUse3;
                    delete mdata.themebootstrap3builtin;
                    delete mdata.themebootstrap3classpfx;
                    delete mdata.themeusebootstraplegacy;

                    mdata.submitLabel = JQuery('#bfFormSubmitLabel').val();
                    mdata['submitLabel_translation'+BFQMConfig.lang] = JQuery('#bfFormSubmitLabelTrans').val();

                    mdata.cancelInclude = JQuery('#bfCancelIncludeYes').prop('checked');

                    mdata.cancelLabel = JQuery('#bfFormCancelLabel').val();
                    mdata['cancelLabel_translation'+BFQMConfig.lang] = JQuery('#bfFormCancelLabelTrans').val();

                    mdata.pagingInclude = JQuery('#bfPagingIncludeYes').prop('checked');

                    mdata.pagingNextLabel = JQuery('#bfFormPagingNextLabel').val();
                    mdata['pagingNextLabel_translation'+BFQMConfig.lang] = JQuery('#bfFormPagingNextLabelTrans').val();

                    mdata.pagingPrevLabel = JQuery('#bfFormPagingPrevLabel').val();
                    mdata['pagingPrevLabel_translation'+BFQMConfig.lang] = JQuery('#bfFormPagingPrevLabelTrans').val();

                    mdata.theme = JQuery('#bfTheme').val();
                    mdata.themebootstrap = JQuery('#bfThemeBootstrap').val();
                    mdata.themebootstrapvars = typeof JQuery('#bfThemeBootstrapVars').get(0) != "undefined" ? JQuery('#bfThemeBootstrapVars').val() : '';
                    if (!mdata.themebootstrapbefore) {
                        mdata['themebootstrapbefore'] = '';
                    }
                    mdata.themebootstrapbefore = typeof JQuery('#bfThemeBootstrapBefore').get(0) != "undefined" ? JQuery('#bfThemeBootstrapBefore').val() : '';
                    mdata.fadeIn = JQuery('#bfElementAdvancedFadeIn').attr('checked');
                    mdata.useErrorAlerts = JQuery('#bfElementAdvancedUseErrorAlerts').attr('checked');

                    mdata.joomlaHint = JQuery('#bfElementAdvancedJoomlaHint').attr('checked');

                    mdata.useDefaultErrors = JQuery('#bfElementAdvancedUseDefaultErrors').attr('checked');
                    mdata.useBalloonErrors = JQuery('#bfElementAdvancedUseBalloonErrors').attr('checked');
                    mdata.lastPageThankYou = JQuery('#bfFormLastPageThankYou').attr('checked');
                    mdata.rollover = JQuery('#bfElementAdvancedRollover').attr('checked');
                    mdata.rolloverColor = JQuery('#bfElementAdvancedRolloverColor').val();
                    mdata.toggleFields = JQuery('#bfElementAdvancedToggleFields').val();
                    var pagesSize = JQuery('#bfQuickModeRoot').children("ul").children("li").size();
                    if (mdata.lastPageThankYou && pagesSize > 1) {
                        mdata.submittedScriptCondidtion = 2;
                        mdata.submittedScriptCode = 'function ff_' + mdata.name + '_submitted(status, message){if(status==0){ff_switchpage(' + pagesSize + ');}else{alert(message);}}';
                    } else {
                        mdata.submittedScriptCondidtion = -1;
                    }
                    item.properties = mdata;
                }
            }
        };

        appScope.populateFormProperties = function () {
            if (appScope.selectedTreeElement) {
                var mdata = appScope.getProperties(appScope.selectedTreeElement);
                if (mdata) {
                    // setting the node's data
                    var item = appScope.findDataObjectItem(JQuery(appScope.selectedTreeElement).attr('id'), appScope.dataObject);
                    if (item) {
                        item.data.title = mdata.title;
                        JQuery('#bfFormTitleTrans').val(typeof mdata['title_translation'+BFQMConfig.lang] != "undefined" ? mdata['title_translation'+BFQMConfig.lang] : "");

                        JQuery('#bfElementAdvancedFadeIn').attr('checked', mdata.fadeIn);
                        JQuery('#bfFormLastPageThankYou').attr('checked', mdata.lastPageThankYou);
                        JQuery('#bfElementAdvancedUseErrorAlerts').attr('checked', mdata.useErrorAlerts);

                        JQuery('#bfElementAdvancedJoomlaHint').attr('checked', mdata.joomlaHint);

                        JQuery('#bfElementAdvancedUseDefaultErrors').attr('checked', mdata.useDefaultErrors);
                        JQuery('#bfElementAdvancedUseBalloonErrors').attr('checked', mdata.useBalloonErrors);
                        if (mdata.submitInclude) {
                            JQuery('#bfSubmitIncludeYes').prop('checked', true).trigger('change');
                            JQuery('#bfSubmitIncludeNo').prop('checked', false);
                        } else {
                            JQuery('#bfSubmitIncludeYes').prop('checked', false).trigger('change');
                            JQuery('#bfSubmitIncludeNo').prop('checked', true);
                        }
                        if (mdata.themebootstrapLabelTop) {
                            JQuery('#bfThemeBootstrapLabelTopYes').attr('checked', true);
                            JQuery('#bfThemeBootstrapLabelTopNo').attr('checked', false);
                        } else {
                            JQuery('#bfThemeBootstrapLabelTopYes').attr('checked', false);
                            JQuery('#bfThemeBootstrapLabelTopNo').attr('checked', true);
                        }
                        if (mdata.themebootstrapMode) {
                            JQuery('#bfThemeBootstrapModeYes').attr('checked', true);
                            JQuery('#bfThemeBootstrapModeNo').attr('checked', false);
                        } else {
                            JQuery('#bfThemeBootstrapModeYes').attr('checked', false);
                            JQuery('#bfThemeBootstrapModeNo').attr('checked', true);
                        }

                        if (mdata.themebootstrapThemeEngine == 'bootstrap') {
                            JQuery('#bfThemeBootstrapThemeBootstrap').attr('checked', true);
                            JQuery('#bfThemeBootstrapThemeBreezingForms').attr('checked', false);
                            JQuery('#bfThemeBootstrapDiv').css("display", "block");
                            JQuery('#bfThemeBreezingFormsDiv').css("display", "none");

                            // disable rollover
                            JQuery("#bfRollOverToggle").css("display", "none");
                            // disable label positions
                            JQuery("#bfLabelPositionToggle").css("display", "none");
                            // disable fading
                            JQuery("#bfFadingEffectToggle").css("display", "none");

                        } else {
                            JQuery('#bfThemeBootstrapThemeBootstrap').attr('checked', false);
                            JQuery('#bfThemeBootstrapThemeBreezingForms').attr('checked', true);
                            JQuery('#bfThemeBootstrapDiv').css("display", "none");
                            JQuery('#bfThemeBreezingFormsDiv').css("display", "block");
                        }
                        if (mdata.themebootstrapUseHeroUnit) {
                            JQuery('#bfThemeBootstrapUseHeroUnitYes').attr('checked', true);
                            JQuery('#bfThemeBootstrapUseHeroUnitNo').attr('checked', false);
                        } else {
                            JQuery('#bfThemeBootstrapUseHeroUnitYes').attr('checked', false);
                            JQuery('#bfThemeBootstrapUseHeroUnitNo').attr('checked', true);
                        }
                        if (mdata.themebootstrapUseWell) {
                            JQuery('#bfThemeBootstrapUseWellYes').attr('checked', true);
                            JQuery('#bfThemeBootstrapUseWellNo').attr('checked', false);
                        } else {
                            JQuery('#bfThemeBootstrapUseWellYes').attr('checked', false);
                            JQuery('#bfThemeBootstrapUseWellNo').attr('checked', true);
                        }
                        if (mdata.themebootstrapUseProgress) {
                            JQuery('#bfThemeBootstrapUseProgressYes').attr('checked', true);
                            JQuery('#bfThemeBootstrapUseProgressNo').attr('checked', false);
                        } else {
                            JQuery('#bfThemeBootstrapUseProgressYes').attr('checked', false);
                            JQuery('#bfThemeBootstrapUseProgressNo').attr('checked', true);
                        }


                        JQuery('#bfFormSubmitLabel').val(mdata.submitLabel);
                        JQuery('#bfFormSubmitLabelTrans').val(typeof mdata['submitLabel_translation'+BFQMConfig.lang] != "undefined" ? mdata['submitLabel_translation'+BFQMConfig.lang] : "");

                        if (mdata.cancelInclude) {
                            JQuery('#bfCancelIncludeYes').prop('checked', true).trigger('change');
                            JQuery('#bfCancelIncludeNo').prop('checked', false);
                        } else {
                            JQuery('#bfCancelIncludeYes').prop('checked', false).trigger('change');
                            JQuery('#bfCancelIncludeNo').prop('checked', true);
                        }

                        JQuery('#bfFormCancelLabel').val(mdata.cancelLabel);
                        JQuery('#bfFormCancelLabelTrans').val(typeof mdata['cancelLabel_translation'+BFQMConfig.lang] != "undefined" ? mdata['cancelLabel_translation'+BFQMConfig.lang] : "");

                        if (mdata.pagingInclude) {
                            JQuery('#bfPagingIncludeYes').prop('checked', true).trigger('change');
                            JQuery('#bfPagingIncludeNo').prop('checked', false);
                        } else {
                            JQuery('#bfPagingIncludeYes').prop('checked', false).trigger('change');
                            JQuery('#bfPagingIncludeNo').prop('checked', true);
                        }

                        JQuery('#bfFormPagingNextLabel').val(mdata.pagingNextLabel);
                        JQuery('#bfFormPagingNextLabelTrans').val(typeof mdata['pagingNextLabel_translation'+BFQMConfig.lang] != "undefined" ? mdata['pagingNextLabel_translation'+BFQMConfig.lang] : "");

                        JQuery('#bfFormPagingPrevLabel').val(mdata.pagingPrevLabel);
                        JQuery('#bfFormPagingPrevLabelTrans').val(typeof mdata['pagingPrevLabel_translation'+BFQMConfig.lang] != "undefined" ? mdata['pagingPrevLabel_translation'+BFQMConfig.lang] : "");

                        JQuery('#bfTheme').val(mdata.theme);
                        JQuery('#bfThemeBootstrap').val(mdata.themebootstrap);
                        JQuery('#bfThemeBootstrapBefore').val(mdata.themebootstrap);
                        JQuery('#bfElementAdvancedRollover').attr('checked', mdata.rollover);
                        JQuery('#bfElementAdvancedRolloverColor').val(mdata.rolloverColor);
                        JQuery('#bfElementAdvancedToggleFields').val(mdata.toggleFields);
                    }
                }
            }
        };

        /**
         Page Properties
         */
        appScope.savePageProperties = function () {
            var mdata = appScope.getProperties(appScope.selectedTreeElement);
            if (mdata) {
                var item = appScope.findDataObjectItem(JQuery(appScope.selectedTreeElement).attr('id'), appScope.dataObject);
                if (item) {
                    item.properties = mdata;
                }
            }
        };

        appScope.populatePageProperties = function () {
            if (appScope.selectedTreeElement) {
                var mdata = appScope.getProperties(appScope.selectedTreeElement);
                if (mdata) {
                    // setting the node's data
                    var item = appScope.findDataObjectItem(JQuery(appScope.selectedTreeElement).attr('id'), appScope.dataObject);
                    if (item) {
                        // no properties yet to set
                    }
                }
            }
        };
    };
}());
