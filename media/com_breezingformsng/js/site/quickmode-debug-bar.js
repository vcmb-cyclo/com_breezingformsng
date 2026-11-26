/* Frontend debug bar: reload the page with the theme picked in the list. Nothing is saved. */
document.addEventListener('change', (event) => {
    if (event.target.matches('select[data-bfng-debug-theme]') && event.target.value) {
        window.location.assign(event.target.value);
    }
});
