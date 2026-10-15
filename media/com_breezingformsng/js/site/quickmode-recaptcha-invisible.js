/* Extracted from BFQuickMode's inline process() script (Phase 9c step 2b,
   bfReCaptcha case, invisible-captcha variant). Shared by the Classic,
   Bootstrap and OnePage renderers. A successful check resets
   window.bfInvisibleRecaptcha when `resetFlagOnCallback` is set, then
   submits through `submitFunction`: ff_validate_submit by default,
   bf_validate_submit for OnePage. */
function bfInitInvisibleReCaptcha(options) {
	window.bfInvisibleRecaptcha = true;

	function bfCallReCaptchaSubmit() {
		if (options.hasFlashUpload) {
			if (typeof bfAjaxObject101 == 'undefined' && typeof bfReCaptchaLoaded == 'undefined') {
				bfDoFlashUpload();
			} else {
				window[options.submitFunction || 'ff_validate_submit'](this, 'click');
			}
		} else {
			window[options.submitFunction || 'ff_validate_submit'](this, 'click');
		}
	}

	function recaptchaCheckedCallback(token) {
		if (options.resetFlagOnCallback && token != '') {
			window.bfInvisibleRecaptcha = false;
		}
		if (typeof bf_htmltextareainit != 'undefined') {
			bf_htmltextareainit();
		}
		bfCallReCaptchaSubmit();
	}

	window.recaptchaExpiredCallback = function () {
		grecaptcha.reset();
	};

	window.onloadBFNewRecaptchaCallback = function () {
		grecaptcha.render('bfInvisibleReCaptchaContainer', {
			sitekey: options.sitekey,
			'expired-callback': recaptchaExpiredCallback,
			callback: recaptchaCheckedCallback,
			badge: options.badge,
			size: 'invisible'
		});
	};
}
