/**
 * Alpine component for the lookup management pages.
 *
 * A single modal handles both adding and editing.
 * Server-rendered table rows call `openEdit(row)`.
 *
 * Usage (Blade):
 *   x-data="lookupTable({ storeUrl, translatable, hasParent })"
 *
 * @param {object} cfg
 * @param {string} cfg.storeUrl        POST URL for a new record
 * @param {boolean} [cfg.translatable] name = {uz,ru,kk} (otherwise a plain string)
 * @param {boolean} [cfg.hasParent]    For categories (parent_id)
 */
export default function lookupTable(cfg = {}) {
    return {
        storeUrl: cfg.storeUrl,
        translatable: Boolean(cfg.translatable),
        hasParent: Boolean(cfg.hasParent),

        open: false,
        mode: 'create', // 'create' | 'edit'
        action: cfg.storeUrl, // form action (create or update url)

        // Form values
        form: { uz: '', ru: '', kk: '', name: '', parent_id: '' },
        editingId: null,

        openCreate() {
            this.mode = 'create';
            this.action = this.storeUrl;
            this.editingId = null;
            this.form = { uz: '', ru: '', kk: '', name: '', parent_id: '' };
            this.open = true;
        },

        openEdit(row) {
            this.mode = 'edit';
            this.action = row.update_url;
            this.editingId = row.id;
            this.form = {
                uz: row.uz ?? '',
                ru: row.ru ?? '',
                kk: row.kk ?? '',
                name: row.name ?? '',
                parent_id: row.parent_id != null ? String(row.parent_id) : '',
            };
            this.open = true;
        },

        close() {
            this.open = false;
        },
    };
}
