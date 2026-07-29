/**
 * Alpine component for the lookup management pages.
 *
 * A single modal handles both adding and editing.
 * Server-rendered table rows call `openEdit(row)`.
 *
 * Usage (Blade):
 *   x-data="lookupTable({ storeUrl, translatable, hasParent, hasPercentage })"
 *
 * @param {object} cfg
 * @param {string} cfg.storeUrl        POST URL for a new record
 * @param {boolean} [cfg.translatable] name = {uz,ru,kk} (otherwise a plain string)
 * @param {boolean} [cfg.hasParent]    For categories (parent_id)
 * @param {boolean} [cfg.hasPercentage] For department coverage (percentage, 0-100)
 */
export default function lookupTable(cfg = {}) {
    return {
        storeUrl: cfg.storeUrl,
        translatable: Boolean(cfg.translatable),
        hasParent: Boolean(cfg.hasParent),
        hasPercentage: Boolean(cfg.hasPercentage),

        open: false,
        mode: 'create', // 'create' | 'edit'
        action: cfg.storeUrl, // form action (create or update url)

        // Form values
        form: { uz: '', ru: '', kk: '', name: '', parent_id: '', percentage: '' },
        editingId: null,

        openCreate() {
            this.mode = 'create';
            this.action = this.storeUrl;
            this.editingId = null;
            this.form = { uz: '', ru: '', kk: '', name: '', parent_id: '', percentage: '' };
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
                percentage: row.percentage != null ? String(row.percentage) : '',
            };
            this.open = true;
        },

        close() {
            this.open = false;
        },
    };
}
