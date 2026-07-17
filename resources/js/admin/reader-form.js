import uploadForm from './upload-form';

/**
 * Reader form Alpine component.
 *
 * Swaps student/staff field labels based on the selected reader type.
 * Also mixes in uploadForm so the photo upload shows progress.
 *
 * @param {object} config
 * @param {string[]} config.studentTypes  ReaderType values that count as "student"
 * @param {string} config.type            Currently selected reader type
 */
export default function readerForm(config) {
    return {
        ...uploadForm(),

        studentTypes: config.studentTypes,
        type: config.type,
        get isStudent() {
            return this.studentTypes.includes(this.type);
        },
    };
}
