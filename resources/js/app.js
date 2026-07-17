import './bootstrap';

import Alpine from 'alpinejs';
import persist from '@alpinejs/persist';
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.css';

import lookupTable from './admin/lookup-table';
import articleForm from './admin/article-form';
import uploadForm from './admin/upload-form';
import readerForm from './admin/reader-form';
import computerSessionCountdown from './admin/computer-session-countdown';
import computerSessionForm from './admin/computer-session-form';

// Alpine.js (persist plugin — remembers sidebar/dark mode state)
Alpine.plugin(persist);
window.Alpine = Alpine;

// Lookup management table (add/edit modal)
Alpine.data('lookupTable', lookupTable);

// Article form (journal autocomplete + dependent issue select)
Alpine.data('articleForm', articleForm);

// Form upload with a live progress bar (large PDF uploads)
Alpine.data('uploadForm', uploadForm);

// Reader form (student/staff label swap + photo upload progress)
Alpine.data('readerForm', readerForm);

// Computer checkout: live countdown + auto-filled location preview
Alpine.data('computerSessionCountdown', computerSessionCountdown);
Alpine.data('computerSessionForm', computerSessionForm);

// Shared 1s clock — one interval total (not one per countdown row), only
// ticking on pages that actually render a computer-session countdown.
Alpine.store('clock', { now: Date.now() });

// 3-language rich text editor (TinyMCE loaded lazily) — used in news/page forms.
Alpine.data('richEditor', () => ({
    active: 'uz',
    inited: {},
    init() {
        this.mount(this.active);
    },
    mount(locale) {
        if (this.inited[locale]) return;
        const run = () => {
            this.inited[locale] = true;
            this.$nextTick(() => window.initTinyEditor(this.$refs['ta_' + locale]));
        };
        if (window.initTinyEditor) {
            run();
        } else {
            window.addEventListener('tiny:ready', run, { once: true });
        }
    },
    select(locale) {
        this.active = locale;
        this.mount(locale);
    },
}));

/**
 * Locale of the language currently selected in a form (uz/ru/kk, or null when
 * no language is chosen). Translatable lookup selects — e.g. the book type —
 * label their options in this locale.
 */
Alpine.store('lookupLocale', { value: null });

// Shared modal for confirming deletion (instead of native confirm)
Alpine.store('confirm', {
    open: false,
    action: '',
    message: '',
    method: 'DELETE',
    ask(action, message, method = 'DELETE') {
        this.action = action;
        this.message = message || '';
        this.method = method || 'DELETE';
        this.open = true;
    },
    close() {
        this.open = false;
    },
});

Alpine.start();

// Date picker (dashboard filter)
flatpickr('.datepicker', {
    mode: 'range',
    static: true,
    monthSelectorType: 'static',
    dateFormat: 'M j',
});

// Dashboard charts: ApexCharts is heavy — load it only on the dashboard (code-split).
if (document.querySelector('[data-dashboard]')) {
    import('./admin/charts.js').then((m) => m.initDashboardCharts());
}

// Tick the shared clock only on pages that render a computer-session countdown.
if (document.querySelector('[data-computer-session-countdown]')) {
    setInterval(() => { Alpine.store('clock').now = Date.now(); }, 1000);
}

// PDF.js is heavy — load it only on the protected online reader page (code-split).
if (document.querySelector('[data-reader]')) {
    import('./site/reader.js').then((m) => m.initReader());
}

// TinyMCE is heavy (~1MB) — load it dynamically only on pages with a rich editor (code-split).
if (document.querySelector('[data-rich-editor]')) {
    import('./admin/editor.js').then(() => {
        window.dispatchEvent(new Event('tiny:ready'));
    });
}

/**
 * Instantly create a lookup (type, language, publisher, author, category).
 * Used by the modal quick-create in admin forms.
 *
 * @param {string} type   LookupService type (e.g. 'book_type')
 * @param {object|string} name  Translatable: {uz, ru, kk}; plain: string
 * @param {object} extra  Extra data (e.g. {parent_id})
 * @returns {Promise<{id: number, name: string}>}
 */
window.lookupCreate = async (type, name, extra = {}) => {
    const token = document.querySelector('meta[name="csrf-token"]')?.content;

    const res = await fetch('/admin/lookups', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': token,
        },
        body: JSON.stringify({ type, name, ...extra }),
    });

    if (!res.ok) {
        // Pass 422 validation errors back to the caller
        let message = 'lookup create failed';
        try {
            const body = await res.json();
            if (body?.errors) {
                message = Object.values(body.errors).flat().join(' ');
            } else if (body?.message) {
                message = body.message;
            }
        } catch (e) {
            // Not JSON — keep the generic message
        }
        throw new Error(message);
    }

    return res.json(); // { id, name }
};
