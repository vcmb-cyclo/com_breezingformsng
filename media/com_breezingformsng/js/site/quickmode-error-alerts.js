/* Extracted from BFQuickMode's inline headers() script (Phase 9c step 2b) -
   the bfShowErrors() display function, only loaded when the form's
   useErrorAlerts property is disabled (see ClassicRenderer::headers()).
   Depends on two globals declared inline right before this file is
   loaded: bfShowDefaultErrors (whether to render the default inline
   error block) and ff_processor.form_id (already emitted by the page
   header, before this function is ever called).

   The per-field "balloon" error prompt branch that used to run here
   (JQuery(...).bfvalidationEngine(...), buildPrompt()/closePrompt())
   was removed: it called a method that was never actually defined
   anywhere in this codebase (the vendored plugin only exposes itself
   as $.fn.validationEngine, not $.fn.bfvalidationEngine), guarded by
   a check on a property that was equally never set - so the branch
   had been dead code, unreachable, since whenever this "bf" renaming
   happened. The vendored jquery.validationEngine.js/-en.js library
   and its CSS have been removed along with it (see
   docs/maintenance/js-libraries-migration-plan.md).

   Each message is also repeated under its field, from the inlineErrorElements
   array that ff_validation() fills. */

var bfFieldErrorObserver = null;

/* Starts the message where its field starts, whatever the label width of the theme. */
function bfAlignFieldError(feedback) {
    var anchor = Array.from(feedback.parentElement.querySelectorAll("input:not([type=hidden]), select, textarea"))
        .find(function (control) {
            return control.getClientRects().length > 0;
        });

    feedback.style.marginInlineStart = "";

    if (!anchor) {
        return;
    }

    anchor = anchor.closest(".bfElementGroup, .bfElementGroupNoWrap, .iti") || anchor;

    var field = anchor.getBoundingClientRect();
    var message = feedback.getBoundingClientRect();
    var offset = getComputedStyle(feedback).direction === "rtl" ? message.right - field.right : field.left - message.left;

    if (offset > 1) {
        var margin = parseFloat(getComputedStyle(feedback).marginInlineStart) || 0;
        feedback.style.marginInlineStart = margin + offset + "px";
    }
}

/* Removes the messages under the fields; ff_validation() calls it before each check. */
function bfClearFieldErrors() {
    document.querySelectorAll(".bfQuickMode [data-bf-invalid]").forEach(function (input) {
        var descriptions = (input.getAttribute("aria-describedby") || "").split(/\s+/).filter(function (id) {
            return id !== "" && id !== input.getAttribute("data-bf-invalid");
        });

        if (descriptions.length > 0) {
            input.setAttribute("aria-describedby", descriptions.join(" "));
        } else {
            input.removeAttribute("aria-describedby");
        }

        input.classList.remove("is-invalid");
        input.removeAttribute("aria-invalid");
        input.removeAttribute("data-bf-invalid");
    });
    document.querySelectorAll(".bfQuickMode .bf-field-error").forEach(function (message) {
        message.remove();
    });

    if (bfFieldErrorObserver) {
        bfFieldErrorObserver.disconnect();
    }
}

function bfShowFieldErrors() {
    bfClearFieldErrors();

    var errors = typeof inlineErrorElements !== "undefined" ? inlineErrorElements : [];

    errors.forEach(function (error, index) {
        var name = String(error[0] || "");
        var message = String(error[1] || "").trim();

        if (name === "" || message === "") {
            return;
        }

        var elements = typeof ff_getElementByName === "function" ? ff_getElementByName(name) : null;
        elements = elements || document.getElementById(name);

        if (!elements) {
            return;
        }

        var inputs = elements.nodeType || typeof elements.length !== "number" ? [elements] : Array.from(elements);
        var wrapper = inputs[0] && inputs[0].closest(".bfElemWrap");

        if (!wrapper) {
            return;
        }

        // Widgets such as the phone field keep their value in a hidden input: mark the visible control too.
        if (inputs[0].type === "hidden") {
            var visible = wrapper.querySelector("input:not([type=hidden]), select, textarea");

            if (visible) {
                inputs = inputs.concat(visible);
            }
        }

        var id = "bfFieldError" + index;
        var feedback = wrapper.querySelector(":scope > .bf-field-error");

        if (feedback) {
            id = feedback.id;
            feedback.textContent += "\n" + message;
        } else {
            feedback = document.createElement("div");
            feedback.className = "bf-field-error";
            feedback.id = id;
            feedback.textContent = message;
            wrapper.append(feedback);
            bfAlignFieldError(feedback);

            // Aligns again when the window is resized or when the page of the field is shown.
            bfFieldErrorObserver = bfFieldErrorObserver || new ResizeObserver(function (entries) {
                entries.forEach(function (entry) {
                    var shown = entry.target.querySelector(":scope > .bf-field-error");

                    if (shown) {
                        window.requestAnimationFrame(function () {
                            bfAlignFieldError(shown);
                        });
                    }
                });
            });
            bfFieldErrorObserver.observe(wrapper);
        }

        inputs.forEach(function (input) {
            if (input.hasAttribute("data-bf-invalid")) {
                return;
            }

            var descriptions = (input.getAttribute("aria-describedby") || "").split(/\s+/).filter(Boolean);
            input.setAttribute("aria-describedby", descriptions.concat(id).join(" "));
            input.setAttribute("aria-invalid", "true");
            input.setAttribute("data-bf-invalid", id);
            input.classList.add("is-invalid");
        });
    });
}

				function bfShowErrors(error){
                                        if (bfShowDefaultErrors) {
                                            JQuery(".bfErrorMessage").html("");
                                            JQuery(".bfErrorMessage").css("display","none");
                                            JQuery(".bfErrorMessage").fadeIn(1500);
                                            var allErrors = "";
                                            var errors = error.split("\n");
                                            for(var i = 0; i < errors.length; i++){
                                                allErrors += "<div class=\"bfError\">" + errors[i] + "</div>";
                                            }
                                            JQuery(".bfErrorMessage").html(allErrors);
                                            JQuery(".bfErrorMessage").css("display","");
                                            bfShowFieldErrors();
                                        }
				}
