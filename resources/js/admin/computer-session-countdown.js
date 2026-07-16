/**
 * Live countdown/elapsed display for a computer session.
 *
 * Reads the shared `clock` store (ticked once per second in app.js) instead
 * of running its own setInterval, so a page with many rows (the cross-reader
 * index) doesn't spawn one timer per row. The server-side truth of "is this
 * session expired" is always the expires_at < now() query/accessor — this
 * timer is purely a live visual, not the authoritative source.
 *
 * @param {object} config
 * @param {string|null} config.expiresAt   ISO 8601 string, or null (legacy/no duration tracked)
 * @param {string|null} config.returnedAt  ISO 8601 string, or null (not finished)
 */
export default function computerSessionCountdown(config) {
    return {
        expiresAt: config.expiresAt ? new Date(config.expiresAt) : null,
        finished: config.returnedAt !== null,

        get remainingLabel() {
            if (this.finished || this.expiresAt === null) return null;

            const diffMs = this.expiresAt.getTime() - this.$store.clock.now;
            const totalSeconds = Math.floor(Math.abs(diffMs) / 1000);
            const hh = Math.floor(totalSeconds / 3600);
            const mm = String(Math.floor((totalSeconds % 3600) / 60)).padStart(2, '0');
            const ss = String(totalSeconds % 60).padStart(2, '0');
            const label = hh > 0 ? `${hh}:${mm}:${ss}` : `${mm}:${ss}`;

            return diffMs <= 0 ? `-${label}` : label;
        },

        get isExpired() {
            return !this.finished && this.expiresAt !== null && this.expiresAt.getTime() <= this.$store.clock.now;
        },
    };
}
