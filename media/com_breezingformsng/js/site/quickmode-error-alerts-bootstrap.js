/* global bfShowDefaultErrors, bfErrorPageScoped, ff_currentpage, inlineErrorElements */
/* global ff_getElementByName, ff_getPageByName, ff_switchpage */
/* exported bfShowErrors */

/* Extracted from BFQuickMode's inline headers() script. The per-field error
   array is populated by ff_validation() before bfShowErrors() is called. */
function bfShowErrors(error) {
    if (!bfShowDefaultErrors) {
        return;
    }

    var previousFeedbackIds = new Set(Array.from(document.querySelectorAll(".bfQuickMode .bf-field-error"))
        .map(function (message) {
            return message.id;
        })
        .filter(Boolean));

    document.querySelectorAll(".bfQuickMode [aria-describedby]").forEach(function (field) {
        var descriptions = (field.getAttribute("aria-describedby") || "").split(/\s+/).filter(Boolean);
        var remaining = descriptions.filter(function (description) {
            return !previousFeedbackIds.has(description);
        });

        if (remaining.length > 0) {
            field.setAttribute("aria-describedby", remaining.join(" "));
        } else {
            field.removeAttribute("aria-describedby");
        }
    });

    document.querySelectorAll(".bfQuickMode .is-invalid").forEach(function (field) {
        field.classList.remove("is-invalid");
        field.removeAttribute("aria-invalid");
    });
    document.querySelectorAll(".bfQuickMode .bf-field-error").forEach(function (message) {
        message.remove();
    });

    var fieldErrors = [];
    var validationErrors = typeof inlineErrorElements !== "undefined" ? inlineErrorElements : [];

    for (var index = 0; index < validationErrors.length; index++) {
        var fieldName = String(validationErrors[index][0] || "");
        var message = String(validationErrors[index][1] || "").trim();

        if (fieldName === "" || message === "") {
            continue;
        }

        var existing = fieldErrors.find(function (fieldError) {
            return fieldError.name === fieldName;
        });

        if (existing) {
            existing.message += "\n" + message;
        } else {
            fieldErrors.push({ name: fieldName, message: message });
        }
    }

    var linkedErrors = [];

    fieldErrors.forEach(function (fieldError, fieldIndex) {
        if (typeof ff_getElementByName !== "function") {
            return;
        }

        var fieldElements = ff_getElementByName(fieldError.name);
        var inputs = fieldElements && typeof fieldElements.length === "number" && !fieldElements.nodeType
            ? Array.from(fieldElements)
            : [fieldElements];
        var firstInput = inputs.find(function (input) {
            return input && input.nodeType === 1;
        });

        if (!firstInput) {
            return;
        }

        var field = firstInput.closest(".bfElemWrap");

        if (!field) {
            return;
        }

        var fieldId = firstInput.id;

        if (!fieldId) {
            fieldId = "bfInvalidField" + fieldIndex;
            firstInput.id = fieldId;
        }

        var feedbackId = fieldId + "Feedback";
        inputs.forEach(function (input) {
            if (!input || input.nodeType !== 1) {
                return;
            }

            input.classList.add("is-invalid");
            input.setAttribute("aria-invalid", "true");
            var descriptions = (input.getAttribute("aria-describedby") || "").split(/\s+/).filter(Boolean);

            if (descriptions.indexOf(feedbackId) === -1) {
                descriptions.push(feedbackId);
            }

            input.setAttribute("aria-describedby", descriptions.join(" "));
        });

        var feedback = document.createElement("div");
        feedback.className = "invalid-feedback bf-field-error";
        feedback.id = feedbackId;
        feedback.textContent = fieldError.message;
        field.append(feedback);

        linkedErrors.push({ id: fieldId, name: fieldError.name, message: fieldError.message });
    });

    var summaries = Array.from(document.querySelectorAll(".bfErrorMessage"));
    summaries.forEach(function (summary) {
        summary.replaceChildren();
        summary.style.display = "none";
    });

    var fallbackErrors = [];

    if (linkedErrors.length === 0) {
        fallbackErrors = String(error || "").split("\n").filter(Boolean);
    }

    summaries.forEach(function (summary) {
        var count = linkedErrors.length || fallbackErrors.length;
        var title = document.createElement("strong");
        title.className = "bfErrorSummaryTitle";
        var singular = summary.getAttribute("data-title-singular") || "1 field to correct";
        var plural = summary.getAttribute("data-title-plural") || "%d fields to correct";
        title.textContent = count === 1 ? singular : plural.replace("%d", count);

        var list = document.createElement("ul");
        list.className = "bfErrorSummaryList";

        linkedErrors.forEach(function (fieldError) {
            var link = document.createElement("a");
            link.href = "#" + fieldError.id;
            link.setAttribute("data-bf-field", fieldError.name);
            link.textContent = fieldError.message;

            var item = document.createElement("li");
            item.append(link);
            list.append(item);
        });

        fallbackErrors.forEach(function (message) {
            var item = document.createElement("li");
            item.className = "bfError";
            item.textContent = message;
            list.append(item);
        });

        summary.append(title, list);
    });

    if (bfErrorPageScoped) {
        var pageSummary = document.querySelector("#bfPage" + ff_currentpage + " .bfErrorMessage");

        if (pageSummary) {
            pageSummary.style.display = "";
        }
    } else {
        summaries.forEach(function (summary) {
            summary.style.display = "";
        });
    }
}

document.addEventListener("click", function (event) {
    var link = event.target.closest(".bfErrorSummaryList a[data-bf-field]");

    if (!link) {
        return;
    }

    event.preventDefault();
    var fieldName = link.getAttribute("data-bf-field");
    var fieldElements = typeof ff_getElementByName === "function" ? ff_getElementByName(fieldName) : null;
    var inputs = fieldElements && typeof fieldElements.length === "number" && !fieldElements.nodeType
        ? Array.from(fieldElements)
        : [fieldElements];
    var input = inputs.find(function (field) {
        return field && field.nodeType === 1;
    });
    var page = typeof ff_getPageByName === "function" ? ff_getPageByName(fieldName) : 0;

    if (!input) {
        return;
    }

    if (page > 0 && page !== ff_currentpage && typeof ff_switchpage === "function") {
        ff_switchpage(page);
    }

    window.setTimeout(function () {
        input.scrollIntoView({ behavior: "smooth", block: "center" });
        input.focus();
    }, 0);
});
