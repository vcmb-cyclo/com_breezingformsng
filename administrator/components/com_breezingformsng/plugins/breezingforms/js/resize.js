document.querySelectorAll('.breezingforms_iframe_plg[data-autoheight="1"]').forEach((frame) => {
    let observer;
    const observe = () => {
        observer?.disconnect();
        try {
            const body = frame.contentDocument?.body;
            if (!body) return;
            const resize = () => {
                frame.style.height = `${body.scrollHeight + 15}px`;
            };
            observer = new ResizeObserver(resize);
            observer.observe(body);
            resize();
        } catch {
            // A form may redirect its frame to another origin after submission.
        }
    };
    frame.addEventListener('load', observe);
    observe();
});
