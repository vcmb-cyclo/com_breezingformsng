// Generated from quickmode-elements-js.php — DO NOT EDIT MANUALLY
// Element factory functions — require BFQMConfig to be defined before this file
/* global BFQMConfig, JQuery */

var BFQMElements = (function () {
	var self = {};
	self.createTextfield = function (id) {
		return {
			attributes: {

				"class": "bfQuickModeElementClass",

				id: id,
				mdata: JSON.stringify(
					{
						deletable: true,
						type: 'element'
					}
				)
			},
			data: { title: "untitled element", icon: BFQMConfig.iconBase + 'icon_text-field.png' },
			properties: {
				type: 'element',
				bfType: 'bfTextfield',
				label: 'untitled element',
				labelPosition: 'left',
				bfName: id,
				dbId: 0,
				orderNumber: -1,
				tabIndex: -1,
				logging: true,
				hideLabel: false,
				required: false,
				hint: '',
				off: false,

				placeholder: '',
				value: '',
				maxLength: '',
				readonly: false,
				password: false,
				mailback: false,
				mailbackAsSender: false,
				mailbackfile: '',
				size: '',

				validationCondition: 0,
				validationId: 0,
				validationCode: '',
				validationMessage: '',
				validationFunctionName: '',
				initCondition: 0,
				initId: 0,
				initCode: '',
				initFunctionName: '',
				initFormEntry: 0,
				initPageEntry: 0,
				actionCondition: 0,
				actionId: 0,
				actionCode: '',
				actionFunctionName: '',
				actionClick: 0,
				actionBlur: 0,
				actionChange: 0,
				actionFocus: 0,
				actionSelect: 0,
				hideInMailback: false
			}
		};
	};

	self.createNumberInput = function (id) {
		return {
			attributes: {

				"class": "bfQuickModeElementClass",

				id: id,
				mdata: JSON.stringify(
					{
						deletable: true,
						type: 'element'
					}
				)
			},
			data: { title: "untitled element", icon: BFQMConfig.iconBase + 'icon_text-field.png' },
			properties: {
				type: 'element',
				bfType: 'bfNumberInput',
				label: 'untitled element',
				labelPosition: 'left',
				bfName: id,
				dbId: 0,
				orderNumber: -1,
				tabIndex: -1,
				logging: true,
				hideLabel: false,
				required: false,
				hint: '',
				off: false,

				placeholder: '',
				value: '',
				maxLength: '',
				size: '',
				range: false,

				validationCondition: 0,
				validationId: 0,
				validationCode: '',
				validationMessage: '',
				validationFunctionName: '',
				initCondition: 0,
				initId: 0,
				initCode: '',
				initFunctionName: '',
				initFormEntry: 0,
				initPageEntry: 0,
				actionCondition: 0,
				actionId: 0,
				actionCode: '',
				actionFunctionName: '',
				actionClick: 0,
				actionBlur: 0,
				actionChange: 0,
				actionFocus: 0,
				actionSelect: 0,
				hideInMailback: false,

				step: 1,
				max: '',
				min: ''
			}
		};
	};

	self.createTextarea = function (id) {
		return {
			attributes: {
				"class": 'bfQuickModeElementClass',
				id: id,
				mdata: JSON.stringify(
					{
						deletable: true,
						type: 'element'
					}
				)
			},
			data: { title: "untitled element", icon: BFQMConfig.iconBase + 'icon_text-area.png' },
			properties: {
				type: 'element',
				bfType: 'bfTextarea',
				label: 'untitled element',
				labelPosition: 'left',
				bfName: id,
				dbId: 0,
				orderNumber: -1,
				tabIndex: -1,
				logging: true,
				hideLabel: false,
				required: false,
				hint: '',
				off: false,

				placeholder: '',
				is_html: false,
				value: '',
				width: '',
				height: '',
				maxlength: 0,
				showMaxlengthCounter: true,
				readonly: false,

				validationCondition: 0,
				validationId: 0,
				validationCode: '',
				validationMessage: '',
				validationFunctionName: '',
				initCondition: 0,
				initId: 0,
				initCode: '',
				initFunctionName: '',
				initFormEntry: 0,
				initPageEntry: 0,
				actionCondition: 0,
				actionId: 0,
				actionCode: '',
				actionFunctionName: '',
				actionClick: 0,
				actionBlur: 0,
				actionChange: 0,
				actionFocus: 0,
				actionSelect: 0,
				hideInMailback: false
			}
		};
	};

	self.createRadioGroup = function (id) {
		return {
			attributes: {
				"class": 'bfQuickModeElementClass',
				id: id,
				mdata: JSON.stringify(
					{
						deletable: true,
						type: 'element'
					}
				)
			},
			data: { title: "untitled element", icon: BFQMConfig.iconBase + 'icon_radio.png' },
			properties: {
				type: 'element',
				bfType: 'bfRadioGroup',
				label: 'untitled element',
				labelPosition: 'left',
				bfName: id,
				dbId: 0,
				orderNumber: -1,
				tabIndex: -1,
				logging: true,
				hideLabel: false,
				required: false,
				hint: '',
				off: false,

				group: "1;Yes;yes\n0;No;no",
				readonly: false,
				wrap: false,

				validationCondition: 0,
				validationId: 0,
				validationCode: '',
				validationMessage: '',
				validationFunctionName: '',
				initCondition: 0,
				initId: 0,
				initCode: '',
				initFunctionName: '',
				initFormEntry: 0,
				initPageEntry: 0,
				actionCondition: 0,
				actionId: 0,
				actionCode: '',
				actionFunctionName: '',
				actionClick: 0,
				actionBlur: 0,
				actionChange: 0,
				actionFocus: 0,
				actionSelect: 0,
				hideInMailback: false
			}
		};
	};

	self.createSignature = function (id) {
		return {
			attributes: {
				"class": 'bfQuickModeElementClass',
				id: id,
				mdata: JSON.stringify(
					{
						deletable: true,
						type: 'element'
					}
				)
			},
			data: { title: "untitled element", icon: BFQMConfig.iconBase + 'icon_sign.png' },
			properties: {
				type: 'element',
				bfType: 'bfSignature',
				label: 'untitled element',
				labelPosition: 'left',
				bfName: id,
				dbId: 0,
				orderNumber: -1,
				tabIndex: -1,
				logging: true,
				hideLabel: false,
				required: false,
				hint: '',
				off: false,

				mailback: 1,

				validationCondition: 0,
				validationId: 0,
				validationCode: '',
				validationMessage: '',
				validationFunctionName: '',
				initCondition: 0,
				initId: 0,
				initCode: '',
				initFunctionName: '',
				initFormEntry: 0,
				initPageEntry: 0,
				actionCondition: 0,
				actionId: 0,
				actionCode: '',
				actionFunctionName: '',
				actionClick: 0,
				actionBlur: 0,
				actionChange: 0,
				actionFocus: 0,
				actionSelect: 0,
				hideInMailback: false
			}
		};
	};

	self.createCheckboxGroup = function (id) {
		return {
			attributes: {
				"class": 'bfQuickModeElementClass',
				id: id,
				mdata: JSON.stringify(
					{
						deletable: true,
						type: 'element'
					}
				)
			},
			data: { title: "untitled element", icon: BFQMConfig.iconBase + 'icon_check-box.png' },
			properties: {
				type: 'element',
				bfType: 'bfCheckboxGroup',
				label: 'untitled element',
				labelPosition: 'left',
				bfName: id,
				dbId: 0,
				orderNumber: -1,
				tabIndex: -1,
				logging: true,
				hideLabel: false,
				required: false,
				hint: '',
				off: false,

				group: "0;Title 1;value1\n0;Title 2;value2\n0;Title 3;value3",
				readonly: false,
				wrap: false,
				mailback: 1,

				validationCondition: 0,
				validationId: 0,
				validationCode: '',
				validationMessage: '',
				validationFunctionName: '',
				initCondition: 0,
				initId: 0,
				initCode: '',
				initFunctionName: '',
				initFormEntry: 0,
				initPageEntry: 0,
				actionCondition: 0,
				actionId: 0,
				actionCode: '',
				actionFunctionName: '',
				actionClick: 0,
				actionBlur: 0,
				actionChange: 0,
				actionFocus: 0,
				actionSelect: 0,
				hideInMailback: false
			}
		};
	};

	self.createCheckbox = function (id) {
		return {
			attributes: {
				"class": 'bfQuickModeElementClass',
				id: id,
				mdata: JSON.stringify(
					{
						deletable: true,
						type: 'element'
					}
				)
			},
			data: { title: "untitled element", icon: BFQMConfig.iconBase + 'icon_check-box.png' },
			properties: {
				type: 'element',
				bfType: 'bfCheckbox',
				label: 'untitled element',
				labelPosition: 'left',
				bfName: id,
				dbId: 0,
				orderNumber: -1,
				tabIndex: -1,
				logging: true,
				hideLabel: false,
				required: false,
				hint: '',
				off: false,

				value: "",
				checked: false,
				readonly: false,
				mailbackAccept: false,
				mailbackConnectWith: '',

				validationCondition: 0,
				validationId: 0,
				validationCode: '',
				validationMessage: '',
				validationFunctionName: '',
				initCondition: 0,
				initId: 0,
				initCode: '',
				initFunctionName: '',
				initFormEntry: 0,
				initPageEntry: 0,
				actionCondition: 0,
				actionId: 0,
				actionCode: '',
				actionFunctionName: '',
				actionClick: 0,
				actionBlur: 0,
				actionChange: 0,
				actionFocus: 0,
				actionSelect: 0,
				hideInMailback: false
			}
		};
	};

	self.createSelect = function (id) {
		return {
			attributes: {
				"class": 'bfQuickModeElementClass',
				id: id,
				mdata: JSON.stringify(
					{
						deletable: true,
						type: 'element'
					}
				)
			},
			data: { title: "untitled element", icon: BFQMConfig.iconBase + 'icon_select.png' },
			properties: {
				type: 'element',
				bfType: 'bfSelect',
				label: 'untitled element',
				labelPosition: 'left',
				bfName: id,
				dbId: 0,
				orderNumber: -1,
				tabIndex: -1,
				logging: true,
				hideLabel: false,
				required: false,
				hint: '',
				off: false,

				list: "0;Title 1;value1\n0;Title 2;value2\n0;Title 3;value3",
				readonly: false,
				multiple: false,
				mailback: false,
				width: '',
				height: '',

				validationCondition: 0,
				validationId: 0,
				validationCode: '',
				validationMessage: '',
				validationFunctionName: '',
				initCondition: 0,
				initId: 0,
				initCode: '',
				initFunctionName: '',
				initFormEntry: 0,
				initPageEntry: 0,
				actionCondition: 0,
				actionId: 0,
				actionCode: '',
				actionFunctionName: '',
				actionClick: 0,
				actionBlur: 0,
				actionChange: 0,
				actionFocus: 0,
				actionSelect: 0,
				hideInMailback: false
			}
		};
	};

	self.createFile = function (id) {
		return {
			attributes: {
				"class": 'bfQuickModeElementClass',
				id: id,
				mdata: JSON.stringify(
					{
						deletable: true,
						type: 'element'
					}
				)
			},
			data: { title: "untitled element", icon: BFQMConfig.iconBase + 'icon_file.png' },
			properties: {
				type: 'element',
				bfType: 'bfFile',
				label: 'untitled element',
				labelPosition: 'left',
				bfName: id,
				dbId: 0,
				orderNumber: -1,
				tabIndex: -1,
				logging: true,
				hideLabel: false,
				required: false,
				hint: '',
				off: false,

				readonly: false,

				uploadDirectory: '{ff_uploads}',
				timestamp: false,
				allowedFileExtensions: 'zip,rar,pdf,doc,xls,ppt,jpg,jpeg,gif,png',
				attachToUserMail: false,
				attachToAdminMail: false,
				html5: false,
				flashUploader: false,
				flashUploaderMulti: false,
				flashUploaderBytes: 0,
				flashUploaderTransparent: true,
				flashUploaderWidth: 64,
				flashUploaderHeight: 64,

				useUrl: false,
				useUrlDownloadDirectory: '',
				resize_target_width: 0,
				resize_target_height: 0,
				resize_type: '',
				resize_bgcolor: '#ffffff',

				validationCondition: 0,
				validationId: 0,
				validationCode: '',
				validationMessage: '',
				validationFunctionName: '',
				initCondition: 0,
				initId: 0,
				initCode: '',
				initFunctionName: '',
				initFormEntry: 0,
				initPageEntry: 0,
				actionCondition: 0,
				actionId: 0,
				actionCode: '',
				actionFunctionName: '',
				actionClick: 0,
				actionBlur: 0,
				actionChange: 0,
				actionFocus: 0,
				actionSelect: 0,
				hideInMailback: false
			}
		};
	};

	self.createSubmitButton = function (id) {
		return {
			attributes: {
				"class": 'bfQuickModeElementClass',
				id: id,
				mdata: JSON.stringify(
					{
						deletable: true,
						type: 'element'
					}
				)
			},
			data: { title: "untitled element", icon: BFQMConfig.iconBase + 'icon_submit-button.png' },
			properties: {
				type: 'element',
				bfType: 'bfSubmitButton',
				label: 'untitled element',
				labelPosition: 'left',
				bfName: id,
				dbId: 0,
				orderNumber: -1,
				tabIndex: -1,
				logging: false,
				hideLabel: true,
				required: false,
				hint: '',
				off: false,

				readonly: false,
				value: '',
				src: '',

				validationCondition: 0,
				validationId: 0,
				validationCode: '',
				validationMessage: '',
				validationFunctionName: '',
				initCondition: 0,
				initId: 0,
				initCode: '',
				initFunctionName: '',
				initFormEntry: 0,
				initPageEntry: 0,
				actionCondition: 0,
				actionId: 0,
				actionCode: '',
				actionFunctionName: '',
				actionClick: 0,
				actionBlur: 0,
				actionChange: 0,
				actionFocus: 0,
				actionSelect: 0,
				hideInMailback: false
			}
		};
	};

	self.createHidden = function (id) {
		return {
			attributes: {
				"class": 'bfQuickModeElementClass',
				id: id,
				mdata: JSON.stringify(
					{
						deletable: true,
						type: 'element'
					}
				)
			},
			data: { title: "untitled element", icon: BFQMConfig.iconBase + 'icon_hidden-input.png' },
			properties: {
				type: 'element',
				bfType: 'bfHidden',
				label: 'untitled element',
				labelPosition: 'left',
				bfName: id,
				dbId: 0,
				orderNumber: -1,
				tabIndex: -1,
				logging: true,
				hideLabel: true,
				required: false,
				hint: '',
				off: false,

				readonly: false,
				value: '',

				validationCondition: 0,
				validationId: 0,
				validationCode: '',
				validationMessage: '',
				validationFunctionName: '',
				initCondition: 0,
				initId: 0,
				initCode: '',
				initFunctionName: '',
				initFormEntry: 0,
				initPageEntry: 0,
				actionCondition: 0,
				actionId: 0,
				actionCode: '',
				actionFunctionName: '',
				actionClick: 0,
				actionBlur: 0,
				actionChange: 0,
				actionFocus: 0,
				actionSelect: 0,
				hideInMailback: false
			}
		};
	};

	self.createCaptcha = function (id) {
		return {
			attributes: {
				"class": 'bfQuickModeElementClass',
				id: id,
				mdata: JSON.stringify(
					{
						deletable: true,
						type: 'element'
					}
				)
			},
			data: { title: "untitled element", icon: BFQMConfig.iconBase + 'icon_captcha.png' },
			properties: {
				type: 'element',
				bfType: 'bfCaptcha',
				label: 'untitled element',
				labelPosition: 'left',
				bfName: id,
				dbId: 0,
				orderNumber: -1,
				tabIndex: -1,
				logging: false,
				hideLabel: false,
				required: false,
				hint: '',
				off: false,

				readonly: false,

				validationCondition: 0,
				validationId: 0,
				validationCode: '',
				validationMessage: '',
				validationFunctionName: '',
				initCondition: 0,
				initId: 0,
				initCode: '',
				initFunctionName: '',
				initFormEntry: 0,
				initPageEntry: 0,
				actionCondition: 0,
				actionId: 0,
				actionCode: '',
				actionFunctionName: '',
				actionClick: 0,
				actionBlur: 0,
				actionChange: 0,
				actionFocus: 0,
				actionSelect: 0,
				hideInMailback: false
			}
		};
	};

	self.createReCaptcha = function (id) {
		return {
			attributes: {
				"class": 'bfQuickModeElementClass',
				id: id,
				mdata: JSON.stringify(
					{
						deletable: true,
						type: 'element'
					}
				)
			},
			data: { title: "untitled element", icon: BFQMConfig.iconBase + 'icon_captcha.png' },
			properties: {
				type: 'element',
				bfType: 'bfReCaptcha',
				label: 'untitled element',
				labelPosition: 'left',
				bfName: id,
				dbId: 0,
				orderNumber: -1,
				tabIndex: -1,
				logging: false,
				hideLabel: false,
				required: false,
				hint: '',
				off: false,

				readonly: false,

				pubkey: '',
				privkey: '',
				theme: 'red',

				validationCondition: 0,
				validationId: 0,
				validationCode: '',
				validationMessage: '',
				validationFunctionName: '',
				initCondition: 0,
				initId: 0,
				initCode: '',
				initFunctionName: '',
				initFormEntry: 0,
				initPageEntry: 0,
				actionCondition: 0,
				actionId: 0,
				actionCode: '',
				actionFunctionName: '',
				actionClick: 0,
				actionBlur: 0,
				actionChange: 0,
				actionFocus: 0,
				actionSelect: 0,
				hideInMailback: false
			}
		};
	};

	self.createCalendar = function (id) {
		return {
			attributes: {
				"class": 'bfQuickModeElementClass',
				id: id,
				mdata: JSON.stringify(
					{
						deletable: true,
						type: 'element'
					}
				)
			},
			data: { title: "untitled element", icon: BFQMConfig.iconBase + 'icon_calendar.png' },
			properties: {
				type: 'element',
				bfType: 'bfCalendar',
				label: 'untitled element',
				labelPosition: 'left',
				bfName: id,
				dbId: 0,
				orderNumber: -1,
				tabIndex: -1,
				logging: true,
				hideLabel: false,
				required: false,
				hint: '',
				off: false,

				readonly: false,
				format: '%Y-%m-%d',
				value: '...',
				size: '',

				validationCondition: 0,
				validationId: 0,
				validationCode: '',
				validationMessage: '',
				validationFunctionName: '',
				initCondition: 0,
				initId: 0,
				initCode: '',
				initFunctionName: '',
				initFormEntry: 0,
				initPageEntry: 0,
				actionCondition: 0,
				actionId: 0,
				actionCode: '',
				actionFunctionName: '',
				actionClick: 0,
				actionBlur: 0,
				actionChange: 0,
				actionFocus: 0,
				actionSelect: 0,
				hideInMailback: false,
				showTime: 0,
				timeFormat: 1,
				singleHeader: '',
				todayButton: 1,
				weekNumbers: 1,
				minYear: '',
				maxYear: '',
				firstDay: '1'
			}
		};
	};

	self.createCalendarResponsive = function (id) {
		return {
			attributes: {
				"class": 'bfQuickModeElementClass',
				id: id,
				mdata: JSON.stringify(
					{
						deletable: true,
						type: 'element'
					}
				)
			},
			data: { title: "untitled element", icon: BFQMConfig.iconBase + 'icon_calendar.png' },
			properties: {
				type: 'element',
				bfType: 'bfCalendarResponsive',
				label: 'untitled element',
				labelPosition: 'left',
				bfName: id,
				dbId: 0,
				orderNumber: -1,
				tabIndex: -1,
				logging: true,
				hideLabel: false,
				required: false,
				hint: '',
				off: false,

				readonly: false,
				format: 'yyyy-mm-dd',
				value: '...',
				size: '',

				validationCondition: 0,
				validationId: 0,
				validationCode: '',
				validationMessage: '',
				validationFunctionName: '',
				initCondition: 0,
				initId: 0,
				initCode: '',
				initFunctionName: '',
				initFormEntry: 0,
				initPageEntry: 0,
				actionCondition: 0,
				actionId: 0,
				actionCode: '',
				actionFunctionName: '',
				actionClick: 0,
				actionBlur: 0,
				actionChange: 0,
				actionFocus: 0,
				actionSelect: 0,
				hideInMailback: false
			}
		};
	};



	self.createPayPal = function (id) {
		return {
			attributes: {
				"class": 'bfQuickModeElementClass',
				id: id,
				mdata: JSON.stringify(
					{
						deletable: true,
						type: 'element'
					}
				)
			},
			data: { title: "untitled element", icon: BFQMConfig.iconBase + 'icon_paypal.png' },
			properties: {
				type: 'element',
				bfType: 'bfPayPal',
				label: 'untitled element',
				labelPosition: 'left',
				bfName: id,
				dbId: 0,
				orderNumber: -1,
				tabIndex: -1,
				logging: true,
				hideLabel: false,
				required: false,
				hint: '',
				off: false,

				readonly: false,
				testaccount: false,
				downloadableFile: false,
				filepath: '',
				downloadTries: 1,
				business: '',
				token: '',
				testBusiness: '',
				testToken: '',
				itemname: '',
				itemnumber: '',
				amount: '',
				tax: '',
				thankYouPage: '',
				cancelURL: '',
				locale: 'us',
				currencyCode: 'USD',
				image: 'https://crosstec.org/media/contentbuilder/upload/paypal.gif',
				sendNotificationAfterPayment: false,
				useIpn: false,

				validationCondition: 0,
				validationId: 0,
				validationCode: '',
				validationMessage: '',
				validationFunctionName: '',
				initCondition: 0,
				initId: 0,
				initCode: '',
				initFunctionName: '',
				initFormEntry: 0,
				initPageEntry: 0,
				actionCondition: 0,
				actionId: 0,
				actionCode: '',
				actionFunctionName: 'ff_validate_submit',
				actionClick: 1,
				actionBlur: 0,
				actionChange: 0,
				actionFocus: 0,
				actionSelect: 0,
				hideInMailback: false
			}
		};
	};

	self.createSummarize = function (id) {
		return {
			attributes: {
				"class": 'bfQuickModeElementClass',
				id: id,
				mdata: JSON.stringify(
					{
						deletable: true,
						type: 'element'
					}
				)
			},
			data: { title: "untitled element", icon: BFQMConfig.iconBase + 'icon_summarize.png' },
			properties: {
				type: 'element',
				bfType: 'bfSummarize',
				label: 'untitled element',
				labelPosition: 'left',
				bfName: id,
				dbId: 0,
				orderNumber: -1,
				tabIndex: -1,
				logging: false,
				hideLabel: false,
				required: false,
				hint: '',
				readonly: false,
				off: false,

				connectWith: '',
				connectType: '',
				useElementLabel: true,
				emptyMessage: 'not available',
				hideIfEmpty: false,
				fieldCalc: '',

				validationCondition: 0,
				validationId: 0,
				validationCode: '',
				validationMessage: '',
				validationFunctionName: '',
				initCondition: 0,
				initId: 0,
				initCode: '',
				initFunctionName: '',
				initFormEntry: 0,
				initPageEntry: 0,
				actionCondition: 0,
				actionId: 0,
				actionCode: '',
				actionFunctionName: '',
				actionClick: 0,
				actionBlur: 0,
				actionChange: 0,
				actionFocus: 0,
				actionSelect: 0,
				hideInMailback: false
			}
		};
	};


	self.createStripe = function (id) {
		return {
			attributes: {
				"class": 'bfQuickModeElementClass',
				id: id,
				mdata: JSON.stringify(
					{
						deletable: true,
						type: 'element'
					}
				)
			},
			data: { title: "untitled element", icon: BFQMConfig.iconBase + 'icon_stripe.png' },
			properties: {
				type: 'element',
				bfType: 'bfStripe',
				label: 'untitled element',
				labelPosition: 'left',
				bfName: id,
				dbId: 0,
				orderNumber: -1,
				tabIndex: -1,
				logging: true,
				hideLabel: false,
				required: false,
				hint: '',
				off: false,

				readonly: false,
				downloadableFile: false,
				filepath: '',
				downloadTries: 1,
				secretKey: '',
				publishableKey: '',
				itemname: '',
				amount: '',
				thankYouPage: '',
				currencyCode: 'USD',
				image: BFQMConfig.siteRoot + 'media/com_breezingformsng/images/site/stripe.png',
				sendNotificationAfterPayment: false,
				emailfield: '',
				klarna: false,
				ideal: false,

				validationCondition: 0,
				validationId: 0,
				validationCode: '',
				validationMessage: '',
				validationFunctionName: '',
				initCondition: 0,
				initId: 0,
				initCode: '',
				initFunctionName: '',
				initFormEntry: 0,
				initPageEntry: 0,
				actionCondition: 0,
				actionId: 0,
				actionCode: '',
				actionFunctionName: 'ff_validate_submit',
				actionClick: 1,
				actionBlur: 0,
				actionChange: 0,
				actionFocus: 0,
				actionSelect: 0,
				hideInMailback: false
			}
		};
	};

	return self;
}());
