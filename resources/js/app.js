import './bootstrap';

import Alpine from 'alpinejs';
import persist from '@alpinejs/persist';
import flatpickr from 'flatpickr';

import { initCharts } from './admin/charts';

// Alpine.js (persist plugin — sidebar/dark mode holatini eslab qoladi)
Alpine.plugin(persist);
window.Alpine = Alpine;
Alpine.start();

// Sana tanlash (dashboard filtri)
flatpickr('.datepicker', {
    mode: 'range',
    static: true,
    monthSelectorType: 'static',
    dateFormat: 'M j',
});

// Dashboard grafiklari (faqat mos sahifada ishga tushadi)
document.addEventListener('DOMContentLoaded', initCharts);

/**
 * Lookup (tur, til, nashriyot, muallif, kategoriya) "shu zahoti" yaratish.
 * Admin formalarida <x-admin.form.select creatable> ishlatadi.
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
        throw new Error('lookup create failed');
    }

    return res.json(); // { id, name }
};
