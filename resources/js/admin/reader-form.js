import uploadForm from './upload-form';

/**
 * Reader form Alpine component.
 *
 * Swaps student/staff field labels based on the selected reader type.
 * Also mixes in uploadForm so the photo upload shows progress, and drives
 * the region -> district dependent select (same cascading-fetch shape as
 * articleForm's journal -> issue select).
 *
 * @param {object} config
 * @param {string[]} config.studentTypes  ReaderType values that count as "student"
 * @param {string} config.type            Currently selected reader type
 * @param {string} config.districtsUrlTemplate  Has a `__RID__` placeholder for the region id
 * @param {?number} config.initialRegionId
 * @param {?number} config.initialDistrictId
 */
export default function readerForm(config) {
    return {
        ...uploadForm(),

        studentTypes: config.studentTypes,
        type: config.type,
        get isStudent() {
            return this.studentTypes.includes(this.type);
        },

        districtsUrlTemplate: config.districtsUrlTemplate,
        regionId: config.initialRegionId ?? null,
        districtId: config.initialDistrictId ? String(config.initialDistrictId) : '',
        districts: [],
        loadingDistricts: false,

        initDistricts() {
            if (this.regionId !== null) this.loadDistricts(this.districtId);
        },

        pickRegion(regionId) {
            this.regionId = regionId ? Number(regionId) : null;
            this.loadDistricts(null);
        },

        async loadDistricts(preselectId) {
            if (this.regionId === null) {
                this.districts = [];
                this.districtId = '';

                return;
            }

            this.loadingDistricts = true;
            try {
                const url = this.districtsUrlTemplate.replace('__RID__', this.regionId);
                const res = await fetch(url, { headers: { Accept: 'application/json' } });
                const json = await res.json();
                this.districts = json.districts ?? [];
            } catch (e) {
                this.districts = [];
            }
            this.loadingDistricts = false;

            const keep = preselectId && this.districts.some((d) => String(d.id) === String(preselectId)) ? String(preselectId) : '';
            this.districtId = keep;

            this.$nextTick(() => { if (this.$refs.districtSelect) this.$refs.districtSelect.value = keep; });
        },
    };
}
