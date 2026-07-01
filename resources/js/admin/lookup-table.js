/**
 * Ma'lumotnoma (lookup) boshqaruv sahifalari uchun Alpine komponenti.
 *
 * Bitta modal orqali qo'shish ham, tahrirlash ham amalga oshiriladi.
 * Server-render qilingan jadval qatorlari `openEdit(row)` chaqiradi.
 *
 * Ishlatilishi (Blade):
 *   x-data="lookupTable({ storeUrl, translatable, hasParent })"
 *
 * @param {object} cfg
 * @param {string} cfg.storeUrl        Yangi yozuv POST manzili
 * @param {boolean} [cfg.translatable] name = {uz,ru,kk} (aks holda oddiy string)
 * @param {boolean} [cfg.hasParent]    Kategoriya (parent_id) uchun
 */
export default function lookupTable(cfg = {}) {
    return {
        storeUrl: cfg.storeUrl,
        translatable: Boolean(cfg.translatable),
        hasParent: Boolean(cfg.hasParent),

        open: false,
        mode: 'create', // 'create' | 'edit'
        action: cfg.storeUrl, // forma action (create yoki update url)

        // Forma qiymatlari
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
