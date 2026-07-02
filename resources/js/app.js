import './bootstrap';

import Alpine from 'alpinejs';
import persist from '@alpinejs/persist';
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.css';

import { initCharts } from './admin/charts';
import lookupTable from './admin/lookup-table';

// Alpine.js (persist plugin — sidebar/dark mode holatini eslab qoladi)
Alpine.plugin(persist);
window.Alpine = Alpine;

// Ma'lumotnoma boshqaruv jadvali (qo'shish/tahrirlash modali)
Alpine.data('lookupTable', lookupTable);

// O'chirishni tasdiqlash uchun umumiy modal (native confirm o'rniga)
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
 * Admin formalaridagi modal quick-create ishlatadi.
 *
 * @param {string} type   LookupService turi (masalan 'book_type')
 * @param {object|string} name  Tarjimali: {uz, ru, kk}; oddiy: string
 * @param {object} extra  Qo'shimcha (masalan {parent_id})
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
        // 422 validatsiya xatolarini chaqiruvchiga yetkazamiz
        let message = 'lookup create failed';
        try {
            const body = await res.json();
            if (body?.errors) {
                message = Object.values(body.errors).flat().join(' ');
            } else if (body?.message) {
                message = body.message;
            }
        } catch (e) {
            // JSON emas — umumiy xabar qoladi
        }
        throw new Error(message);
    }

    return res.json(); // { id, name }
};
