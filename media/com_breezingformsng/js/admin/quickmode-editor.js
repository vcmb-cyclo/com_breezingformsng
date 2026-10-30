import { JoomlaEditor } from 'editor-api';

var __bfOpts = Joomla.getOptions('com_breezingformsng.quickmode-editor') || {};
var bfLangSuffix = __bfOpts.langSuffix || '';

function bfEditorGet() {
	var editor = bfEditorInstance();
	return editor ? editor.getValue() : '';
}

function bfEditorSet(value) {
	var editor = bfEditorInstance();
	if (editor) {
		editor.setValue(value);
	}
}

function bfEditorInstance() {
	return JoomlaEditor.get('bfEditor');
}

function bfLoadText(attempt) {
	attempt = attempt || 0;

	var editor = bfEditorInstance();
	var item = parent.BFQMApp && parent.BFQMApp.selectedTreeElement;

	if (!editor || !item) {
		setTimeout(function () {
			bfLoadText(attempt + 1);
		}, 250);

		return;
	}

	var keyPageIntro = 'pageIntro' + bfLangSuffix;
	var keyDescription = 'description' + bfLangSuffix;

	// workaround for quote bug with jce
	var testEditor = editor.getValue();

	if (testEditor == 'item.properties[keyPageIntro]' || testEditor == 'item.properties[keyDescription]') {
		if (item && item.properties.type == 'page') {
			setTimeout(setIntro, 100);
		} else if (item && item.properties.type == 'section') {
			setTimeout(setDescription, 250);
		}
	} else {
		if (item && item.properties.type == 'page') {
			setTimeout(setIntro0, 100);
		} else if (item && item.properties.type == 'section') {
			setTimeout(setDescription0, 250);
		}
	}
}

function saveText() {
	var keyPageIntro = 'pageIntro' + bfLangSuffix;
	var keyDescription = 'description' + bfLangSuffix;
	var item = parent.BFQMApp.selectedTreeElement;
	if (item && item.properties.type == 'page') {
		item.properties[keyPageIntro] = bfEditorGet();
	} else if (item && item.properties.type == 'section') {
		item.properties[keyDescription] = bfEditorGet();
	}
	document.adminForm.submit();
}

function setIntro0() {
	var key = 'pageIntro' + bfLangSuffix;
	var item = parent.BFQMApp.selectedTreeElement;
	if (typeof item.properties[key] == 'undefined') {
		item.properties[key] = '';
	}
	bfEditorSet('' + item.properties[key] + '');
	var testEditor = bfEditorGet();

	if (testEditor == '+item.properties[key]+'
		|| testEditor == '<div>"+item.properties[key]+"</div>'
		|| testEditor == '<p>"+item.properties[key]+"</p>'
		|| testEditor == '"+item.properties[key]+"'
		|| testEditor == 'item.properties[key]'
		|| testEditor == '<p>item.properties[key]</p>'
		|| testEditor == "<div>item.properties['pageIntro" + bfLangSuffix + "']</div>") {
		setTimeout(setIntro00, 250);
	}
}

function setIntro00() {
	var key = 'pageIntro' + bfLangSuffix;
	var item = parent.BFQMApp.selectedTreeElement;
	if (typeof item.properties[key] == 'undefined') {
		item.properties[key] = '';
	}
	var testEditor = bfEditorGet();
	if (testEditor == '+item.properties[key]+') {
		bfEditorSet('item.properties[key]');
	} else {
		bfEditorSet('' + item.properties[key] + '');
	}
}

function setDescription0() {
	var key = 'description' + bfLangSuffix;
	var item = parent.BFQMApp.selectedTreeElement;
	if (typeof item.properties[key] == 'undefined') {
		item.properties[key] = '';
	}
	bfEditorSet('' + item.properties[key] + '');
	var testEditor = bfEditorGet();

	if (testEditor == '+item.properties[key]+'
		|| testEditor == '<div>"+item.properties[key]+"</div>'
		|| testEditor == '<p>"+item.properties[key]+"</p>'
		|| testEditor == '"+item.properties[key]+"'
		|| testEditor == 'item.properties[key]'
		|| testEditor == '<p>item.properties[key]</p>'
		|| testEditor == '<div>item.properties[key]</div>') {
		setTimeout(setDescription00, 250);
	}
}

function setDescription00() {
	var key = 'description' + bfLangSuffix;
	var item = parent.BFQMApp.selectedTreeElement;
	if (typeof item.properties[key] == 'undefined') {
		item.properties[key] = '';
	}
	var testEditor = bfEditorGet();
	if (testEditor == '+item.properties[key]+') {
		bfEditorSet('item.properties[key]');
	} else {
		bfEditorSet('' + item.properties[key] + '');
	}
}

function setIntro() {
	var key = 'pageIntro' + bfLangSuffix;
	var item = parent.BFQMApp.selectedTreeElement;
	bfEditorSet('' + item.properties[key] + '');
}

function setDescription() {
	var key = 'description' + bfLangSuffix;
	var item = parent.BFQMApp.selectedTreeElement;
	if (typeof item.properties[key] == 'undefined') {
		item.properties[key] = '';
	}
	bfEditorSet('' + item.properties[key] + '');
}

window.saveText = saveText;
bfLoadText(0);
