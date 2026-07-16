/**
 * Reader-show "computer usage" modal.
 *
 * Owns the modal open/close state (matching the plain-object x-data every
 * other "add X" modal on this page uses) plus a live, non-editable location
 * preview derived from the selected computer — the librarian never types a
 * location, it's always read off the chosen computer's own fixed location.
 *
 * @param {object} config
 * @param {boolean} config.hasErrors               open the modal pre-filled on a validation error
 * @param {Object<string, string>} config.locations computer_id (string) → location label
 */
export default function computerSessionForm(config) {
    return {
        computerOpen: config.hasErrors ?? false,
        computerId: '',
        locations: config.locations ?? {},

        get locationPreview() {
            return this.computerId && this.locations[this.computerId]
                ? this.locations[this.computerId]
                : '—';
        },
    };
}
