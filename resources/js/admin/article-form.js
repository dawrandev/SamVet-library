import uploadForm from './upload-form';

/**
 * Article form Alpine component.
 *
 * Journal live-search autocomplete + a dependent issue select:
 * pick a journal (fetched by name), then choose one of its issues.
 * Also mixes in uploadForm so its (possibly large) PDF shows upload progress.
 *
 * @param {object} config
 * @param {string} config.searchUrl          Journal live-search endpoint
 * @param {string} config.issuesUrlTemplate  Issues endpoint with a `__JID__` placeholder
 * @param {string} config.newJournalUrl      Link to create a new journal
 * @param {string|null} config.kind          Restrict search to 'journal'|'newspaper' (or null for both)
 * @param {object} config.initial            Pre-selected {journalId, journalName, issueId}
 */
export default function articleForm(config) {
    return {
        ...uploadForm(),

        searchUrl: config.searchUrl,
        issuesUrlTemplate: config.issuesUrlTemplate,
        newJournalUrl: config.newJournalUrl,
        kind: config.kind ?? null,

        journalId: config.initial.journalId,
        journalName: config.initial.journalName,
        issueId: config.initial.issueId ? String(config.initial.issueId) : '',

        results: [],
        showResults: false,
        searching: false,
        issues: [],
        loadingIssues: false,

        init() {
            // Pre-populate the issue list when editing (or redisplaying after a validation error).
            if (this.journalId !== null) {
                this.loadIssues(this.issueId);
            }
        },

        async search() {
            const term = this.journalName.trim();
            // Typing a new name invalidates the previously chosen journal.
            this.journalId = null;
            this.issues = [];
            this.issueId = '';

            if (term === '') {
                this.results = [];
                this.showResults = false;
                return;
            }

            this.searching = true;
            this.showResults = true;
            try {
                const kindParam = this.kind ? `&kind=${encodeURIComponent(this.kind)}` : '';
                const res = await fetch(`${this.searchUrl}?q=${encodeURIComponent(term)}${kindParam}`, {
                    headers: { Accept: 'application/json' },
                });
                const json = await res.json();
                this.results = json.data ?? [];
            } catch (e) {
                this.results = [];
            }
            this.searching = false;
        },

        pickJournal(journal) {
            this.journalId = journal.id;
            this.journalName = journal.name;
            this.showResults = false;
            this.results = [];
            this.loadIssues(null);
        },

        async loadIssues(preselectId) {
            if (this.journalId === null) return;
            this.loadingIssues = true;
            try {
                const url = this.issuesUrlTemplate.replace('__JID__', this.journalId);
                const res = await fetch(url, { headers: { Accept: 'application/json' } });
                const json = await res.json();
                this.issues = json.issues ?? [];
            } catch (e) {
                this.issues = [];
            }
            this.loadingIssues = false;

            // Keep the previously selected issue if it still exists in the loaded list.
            const keep = preselectId && this.issues.some((i) => String(i.id) === String(preselectId))
                ? String(preselectId)
                : '';
            this.issueId = keep;

            // x-model may set the native value before x-for has painted the matching
            // <option>, leaving the select empty on edit — force it once options exist.
            this.$nextTick(() => {
                if (this.$refs.issueSelect) this.$refs.issueSelect.value = keep;
            });
        },
    };
}
