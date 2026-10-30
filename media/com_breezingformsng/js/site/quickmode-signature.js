/* Extracted from BFQuickMode's inline process() script (Phase 9c step 2b)
   - the signature-pad controller for a bfSignature element. Originally
   emitted once per element with the element's dbId baked directly into
   every function/variable name (bf_signaturePad123, bf_canvas123,
   bf_resizeCanvas123Func, bf_Signature123Reset...), which meant the
   exact same ~50 lines were re-declared for every signature field on a
   form. Rewritten here as a single shared definition keyed by dbId
   (bfSignaturePads[dbId], bfSignatureCanvases[dbId]), called once per
   element via bfSignatureInit(dbId) - see the PHP side for the (much
   smaller) per-element call site. Behaviour is otherwise unchanged,
   including the three-way resize/restore contract: bfSignatureInit's
   own calls pass restoreArg=false (just size the canvas, no restore),
   while the window "resize" handler passes the resize Event object
   (truthy, so the existing drawing is captured and restored across the
   resize) - exactly as the original per-element functions did with
   arguments[0]. The literal "base64" marker used to strip the data URL
   prefix was originally built as 'ba'.'se'.'64' (obfuscated string
   concatenation, presumably to dodge an over-eager hosting malware
   scanner); it is always the constant string "base64" regardless of
   which element renders it, so it's hardcoded directly here rather than
   threaded through as a parameter. Shared by all renderers -
   Classic/Bootstrap/OnePage all had this exact logic including the
   "if (canvas == null) return;" guard. */
var bfSignaturePads = {};
var bfSignatureCanvases = {};

function bfSignatureResizeCanvas(dbId, restoreArg) {
	var canvas = bfSignatureCanvases[dbId];
	var pad = bfSignaturePads[dbId];
	var data;

	if (restoreArg !== false) {
		data = pad.toDataURL();
	}

	var ratio = Math.max(window.devicePixelRatio || 1, 1);
	canvas.width = canvas.offsetWidth * ratio;
	canvas.height = canvas.offsetHeight * ratio;
	canvas.getContext("2d").scale(ratio, ratio);

	if (restoreArg !== false) {
		pad.fromDataURL(data);
		JQuery("#ff_elem" + dbId).val(data.replace("data:image/png;base64,", ""));
	}

	bfSignaturePads[dbId] = new SignaturePad(canvas, {
		backgroundColor: "rgb(255,255,255)",
		penColor: "rgb(0,0,0)",
		onEnd: function () {
			var d = bfSignaturePads[dbId].toDataURL();
			JQuery("#ff_elem" + dbId).val(d.replace("data:image/png;base64,", ""));
		}
	});
}

function bfSignatureReset(dbId) {
	bfSignaturePads[dbId].clear();
	JQuery("#ff_elem" + dbId).val("");
}

function bfSignatureInit(dbId) {
	JQuery(document).ready(function () {
		var canvas = document.querySelector("#bfSignature" + dbId + " canvas");
		bfSignatureCanvases[dbId] = canvas;
		if (canvas == null) return;

		// trouble on mobile devices, thinks swiping is resize...
		JQuery(window).on("resize", function (e) {
			bfSignatureResizeCanvas(dbId, e);
		});

		bfSignatureResizeCanvas(dbId, false);

		// make sure the canvas is resized if dimensions are zero
		setInterval(function () {
			if (canvas.width == 0 && canvas.height == 0) {
				bfSignatureResizeCanvas(dbId, false);
			}
		}, 500);
	});
}
