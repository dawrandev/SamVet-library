/**
 * Alpine component for the "Foydalanuvchi turlari" (reader types) lookup page.
 *
 * A dedicated component (not the shared lookupTable) since this lookup carries
 * two extra fields beyond a plain name: is_student and certificate_color.
 *
 * Usage (Blade): x-data="readerTypeTable({ storeUrl })"
 *
 * @param {object} cfg
 * @param {string} cfg.storeUrl  POST URL for a new record
 */
export default function readerTypeTable(cfg = {}) {
    return {
        storeUrl: cfg.storeUrl,

        open: false,
        mode: 'create', // 'create' | 'edit'
        action: cfg.storeUrl,

        form: { name: '', is_student: true, certificate_color: '#2563eb' },
        editingId: null,

        openCreate() {
            this.mode = 'create';
            this.action = this.storeUrl;
            this.editingId = null;
            this.form = { name: '', is_student: true, certificate_color: '#2563eb' };
            this.open = true;
        },

        openEdit(row) {
            this.mode = 'edit';
            this.action = row.update_url;
            this.editingId = row.id;
            this.form = {
                name: row.name ?? '',
                is_student: Boolean(row.is_student),
                certificate_color: row.certificate_color ?? '#2563eb',
            };
            this.open = true;
        },

        close() {
            this.open = false;
        },
    };
}
