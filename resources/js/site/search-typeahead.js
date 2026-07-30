/**
 * Live typeahead for the homepage hero search + catalog filter search box.
 * Debounced fetch to the quick-search JSON endpoint; Enter/submit still
 * goes to the full /katalog results page as before.
 */
export default function searchTypeahead({ url, initialQuery = '' }) {
    return {
        query: initialQuery,
        results: [],
        open: false,
        loading: false,
        debounceTimer: null,

        search() {
            clearTimeout(this.debounceTimer);
            const term = this.query.trim();

            if (term.length < 2) {
                this.results = [];
                this.open = false;
                return;
            }

            this.debounceTimer = setTimeout(() => this.fetchResults(term), 250);
        },

        async fetchResults(term) {
            this.loading = true;
            try {
                const res = await fetch(`${url}?q=${encodeURIComponent(term)}`, {
                    headers: { Accept: 'application/json' },
                });
                const body = await res.json();
                this.results = body.data ?? [];
                this.open = this.results.length > 0;
            } catch (e) {
                this.results = [];
                this.open = false;
            }
            this.loading = false;
        },

        openIfHasResults() {
            this.open = this.results.length > 0;
        },
    };
}
