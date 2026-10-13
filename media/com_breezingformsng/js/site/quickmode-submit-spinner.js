/* Loading state of the OnePage submit button, shown with the Bootstrap
   spinner. bind() starts it when the button is clicked; the scripts of the
   theme call stop() once a submission fails, or bind() again after they
   replaced the button (bf_restore_submitbutton()).

   A click starts the spinner on the button that was clicked, not on the
   one currently bound: when the click handler of the form replaced the
   button before this one ran, the spinner stays on the discarded node. */
var bfSubmitSpinner = (function () {
    var bound = null;

    function start(button) {
        if (button.getAttribute('aria-busy') === 'true') {
            return;
        }

        var spinner = document.createElement('span');
        spinner.className = 'spinner-border spinner-border-sm me-1 bf-submit-spinner';
        spinner.setAttribute('aria-hidden', 'true');

        button.setAttribute('aria-busy', 'true');
        button.disabled = true;
        button.insertBefore(spinner, button.firstChild);
    }

    function stop() {
        if (!bound) {
            return;
        }

        var spinner = bound.querySelector('.bf-submit-spinner');

        if (spinner) {
            spinner.remove();
        }

        bound.removeAttribute('aria-busy');
        bound.disabled = false;
    }

    function bind(selector) {
        bound = document.querySelector(selector);

        if (bound) {
            bound.addEventListener('click', function () {
                start(this);
            });
        }
    }

    return { bind: bind, stop: stop };
})();
