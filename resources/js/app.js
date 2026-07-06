import './bootstrap';

import Alpine from 'alpinejs';
import persist from '@alpinejs/persist';
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.css';

import { initCharts } from './admin/charts';
import lookupTable from './admin/lookup-table';

// Alpine.js (persist plugin — remembers sidebar/dark mode state)
Alpine.plugin(persist);
window.Alpine = Alpine;

// Lookup management table (add/edit modal)
Alpine.data('lookupTable', lookupTable);

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

// Dashboard charts (only start on the matching page)
document.addEventListener('DOMContentLoaded', initCharts);

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
