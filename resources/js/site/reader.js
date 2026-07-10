import * as pdfjsLib from 'pdfjs-dist';
import workerUrl from 'pdfjs-dist/build/pdf.worker.min.mjs?url';

pdfjsLib.GlobalWorkerOptions.workerSrc = workerUrl;

/**
 * Protected online reader.
 *
 * The PDF is fetched from an auth-guarded controller endpoint and painted onto
 * a canvas — the file is never linked directly and the viewer exposes no
 * download or print control. (Bytes reaching the browser can always be
 * captured; this raises the bar, it does not make copying impossible.)
 */
export function initReader() {
    const root = document.querySelector('[data-reader]');
    if (!root) return;

    const el = {
        canvas: root.querySelector('[data-reader-canvas]'),
        viewport: root.querySelector('[data-reader-viewport]'),
        spinner: root.querySelector('[data-reader-spinner]'),
        error: root.querySelector('[data-reader-error]'),
        page: root.querySelector('[data-reader-page]'),
        total: root.querySelector('[data-reader-total]'),
        prev: root.querySelector('[data-reader-prev]'),
        next: root.querySelector('[data-reader-next]'),
        zoomIn: root.querySelector('[data-reader-zoom-in]'),
        zoomOut: root.querySelector('[data-reader-zoom-out]'),
        zoomLabel: root.querySelector('[data-reader-zoom-label]'),
    };

    const ctx = el.canvas.getContext('2d', { alpha: false });

    const state = {
        doc: null,
        pageNumber: 1,
        zoom: 1, // 1 = fit to container width
        rendering: false,
        queued: null,
    };

    const MIN_ZOOM = 0.5;
    const MAX_ZOOM = 3;

    async function renderPage(number) {
        if (state.rendering) {
            state.queued = number;
            return;
        }
        state.rendering = true;

        const page = await state.doc.getPage(number);

        // Scale so the page fills the viewport width, then apply the zoom factor.
        const unscaled = page.getViewport({ scale: 1 });
        const fitScale = (el.viewport.clientWidth - 32) / unscaled.width;
        const viewport = page.getViewport({ scale: fitScale * state.zoom });

        // Render at device resolution so text stays sharp on HiDPI screens.
        const dpr = Math.min(window.devicePixelRatio || 1, 2);
        el.canvas.width = Math.floor(viewport.width * dpr);
        el.canvas.height = Math.floor(viewport.height * dpr);
        el.canvas.style.width = `${Math.floor(viewport.width)}px`;
        el.canvas.style.height = `${Math.floor(viewport.height)}px`;

        await page.render({
            canvasContext: ctx,
            viewport,
            ...(dpr !== 1 ? { transform: [dpr, 0, 0, dpr, 0, 0] } : {}),
        }).promise;

        state.rendering = false;

        if (state.queued !== null) {
            const next = state.queued;
            state.queued = null;
            await renderPage(next);
        }
    }

    async function goTo(number) {
        const target = Math.min(Math.max(number, 1), state.doc.numPages);
        state.pageNumber = target;
        el.page.value = target;
        syncButtons();
        await renderPage(target);
        el.viewport.scrollTo({ top: 0 });
    }

    function syncButtons() {
        el.prev.disabled = state.pageNumber <= 1;
        el.next.disabled = state.pageNumber >= state.doc.numPages;
        el.zoomOut.disabled = state.zoom <= MIN_ZOOM;
        el.zoomIn.disabled = state.zoom >= MAX_ZOOM;
        el.zoomLabel.textContent = `${Math.round(state.zoom * 100)}%`;
    }

    async function setZoom(next) {
        state.zoom = Math.min(Math.max(Number(next.toFixed(2)), MIN_ZOOM), MAX_ZOOM);
        syncButtons();
        await renderPage(state.pageNumber);
    }

    // --- Controls ---
    el.prev.addEventListener('click', () => goTo(state.pageNumber - 1));
    el.next.addEventListener('click', () => goTo(state.pageNumber + 1));
    el.zoomIn.addEventListener('click', () => setZoom(state.zoom + 0.25));
    el.zoomOut.addEventListener('click', () => setZoom(state.zoom - 0.25));

    el.page.addEventListener('change', () => {
        const value = parseInt(el.page.value, 10);
        goTo(Number.isNaN(value) ? state.pageNumber : value);
    });

    document.addEventListener('keydown', (event) => {
        if (event.target.matches('input, textarea')) return;
        if (event.key === 'ArrowRight' || event.key === 'PageDown') goTo(state.pageNumber + 1);
        if (event.key === 'ArrowLeft' || event.key === 'PageUp') goTo(state.pageNumber - 1);
    });

    // Re-fit when the window (or the a11y font size) changes the viewport width.
    let resizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => state.doc && renderPage(state.pageNumber), 150);
    });

    // Small deterrent: no "save image as" on the rendered page.
    el.canvas.addEventListener('contextmenu', (event) => event.preventDefault());

    // --- Load ---
    (async () => {
        try {
            state.doc = await pdfjsLib.getDocument({
                url: root.dataset.reader,
                withCredentials: true,
            }).promise;

            el.total.textContent = state.doc.numPages;
            el.page.max = state.doc.numPages;
            el.spinner.classList.add('hidden');
            el.canvas.classList.remove('invisible');

            await goTo(1);
        } catch (error) {
            el.spinner.classList.add('hidden');
            el.error.classList.remove('hidden');
            console.error('reader: failed to load document', error);
        }
    })();
}
