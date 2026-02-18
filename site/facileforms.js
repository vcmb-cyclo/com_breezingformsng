/**
* BreezingForms - A Joomla Forms Application
* @version 1.4.4
* @package BreezingForms
* @copyright (C) 2004-2005 by Peter Koch
* @copyright (C) 2024 by EVH
* @license Released under the terms of the GNU General Public License
**/
function swal(msg){

	alert(msg);

}
function ff_traceWindow()
{
       if(typeof console != "undefined" && typeof console.log == "function" ){
           console.log("BF-Trace", ff_processor.traceBuffer);
       }
} // ff_traceWindow

function ff_trim(s)
{
	var n = s.length;
	if (n == 0) return '';
	var b = 0;
	var ws = ' \r\n\t';
	while (b<n && ws.indexOf(s.charAt(b))>=0) b++;
	if (b == n) return '';
	while (ws.indexOf(s.charAt(n-1))>=0) n--;
	return s.substring(b,n);
} // ff_trim

function ff_redirect(url)
{
	var target = 'self';
	if (arguments.length>1) target = arguments[1].toLowerCase();
	var method = 'post';
	if (arguments.length>2) method = arguments[2].toLowerCase();
	switch (method) {
		case 'get':
			switch (target) {
				case 'top':
					top.location.href = url;
					break;
				case 'parent':
					parent.location.href = url;
					break;
				default: // self
					document.location.href = url;
			} // switch
			break;
		default: { // post
			var f = document.createElement('form');
			var pos = url.indexOf('?');
			var params = new Array();
			if (pos < 0) {
				f.action = url;
			} else {
				f.action = url.substring(0, pos) +'?';
				var pms = url.substring(pos+1, url.length).split('&');
				for (var p = 0; p < pms.length; p++) {
					var pm = pms[p].split('=');
					var prop = '';
					if (pm.length > 0) prop = ff_trim(pm[0]);
					var val = '';
					if (pm.length > 1 ) val = ff_trim(unescape(pm[1]));
					if (prop!='' && val!='' && prop.indexOf('ff_')==0) {
                                          params[params.length] = new Array(prop, val);
                                        }
                                        else if (prop!='' && val!='') {
                                          f.action += '&' +prop +'=' +val;
                                        }
				} // for
			} // if
			f.name = 'ff_redirect';
			f.method = 'post';
			f.enctype = 'multipart/form-data';
			switch (target) {
				case 'blank' : f.target = '_blank';  break;
				case 'top'   : f.target = '_top';    break;
				case 'parent': f.target = '_parent'; break;
				default      : f.target = '_self';
			} // switch
			var p;
			for (p = 0; p < params.length; p++) {
				var e = document.createElement('input');
				e.type = 'hidden';
				e.name = params[p][0];
				e.value = params[p][1];
				f.appendChild(e);
			} // for
			document.body.appendChild(f);
			f.submit();
		} // post
	} // switch
} // ff_redirect

function ff_redirectParent(url)
{
	var method = 'post';
	if (arguments.length>1) method = arguments[1].toLowerCase();
	if (ff_processor.inframe)
		ff_redirect(url, 'parent', method);
	else
		ff_redirect(url, 'self', method);
} // ff_redirectParent

function ff_redirectTop(url)
{
	var method = 'post';
	if (arguments.length>1) method = arguments[1].toLowerCase();
	ff_redirect(url, 'top', method);
} // ff_redirectTop"

function ff_returnHome()
{
	ff_redirectParent(ff_processor.homepage);
} // ff_returnHome

// Ajout d'un span après un élément de type "Range"

document.addEventListener('DOMContentLoaded', function() {
    ff_range();
});

function ff_range(element) {
    const sliders = document.querySelectorAll('input[type="range"]');

    sliders.forEach((slider) => {
        slider.parentNode.classList.add('range');

        let spanElement = slider.nextElementSibling;

        if (!spanElement || spanElement.tagName !== 'SPAN') {
            spanElement = document.createElement('span');
            slider.parentNode.insertBefore(spanElement, slider.nextSibling);
        }

        const updateSliderValue = function () {
            spanElement.textContent = slider.value;
        };

        updateSliderValue();

        if (!slider.hasAttribute('data-bf-range-bound')) {
            slider.setAttribute('data-bf-range-bound', '1');

            slider.addEventListener('input', function() {
                updateSliderValue();
            });

            slider.addEventListener('change', function() {
                updateSliderValue();
            });
        }

        const form = slider.form;
        if (form && !form.hasAttribute('data-bf-range-reset-bound')) {
            form.setAttribute('data-bf-range-reset-bound', '1');
            form.addEventListener('reset', function() {
                setTimeout(function() {
                    form.querySelectorAll('input[type="range"]').forEach((rangeSlider) => {
                        const rangeSpan = rangeSlider.nextElementSibling;
                        if (rangeSpan && rangeSpan.tagName === 'SPAN') {
                            rangeSpan.textContent = rangeSlider.value;
                        }
                    });
                }, 0);
            });
        }
    });
}
