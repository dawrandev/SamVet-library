#!/bin/bash
#
# Applies a release on the production host.
#
# This exists because production is a cPanel account with no terminal, no root
# and no working CI deploy: the only ways to run anything are a cron entry or
# cPanel's Git "Deploy HEAD Commit" button. Both call this one script, so the
# steps live in a single place instead of being retyped into a cron box (where
# a forgotten `search:reindex` silently empties every search on the site).
#
# Run it either way:
#   cron       :  /bin/bash /home/USER/PATH-TO-APP/deploy.sh
#   cPanel Git :  handled by .cpanel.yml, which just calls this file
#
# Everything is appended to storage/logs/deploy.log, readable from cPanel's
# File Manager — there is no console here to watch it happen.
#
# Safe to run twice: every step is idempotent.

set -uo pipefail

# Locate the application by where this script lives, so no path has to be
# configured anywhere. Both cron and cPanel run it from an unpredictable
# working directory.
APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$APP_DIR" || exit 1

LOG="$APP_DIR/storage/logs/deploy.log"
mkdir -p "$(dirname "$LOG")"

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*" | tee -a "$LOG"
}

# cPanel accounts usually have several PHP builds installed, and cron's PATH
# often resolves `php` to an old default (5.x/7.x) rather than the one the
# site runs on. Picking explicitly avoids a confusing "syntax error" that is
# really a version mismatch.
find_php() {
    local candidate
    for candidate in \
        /usr/local/bin/ea-php83 /opt/cpanel/ea-php83/root/usr/bin/php \
        /usr/local/bin/ea-php82 /opt/cpanel/ea-php82/root/usr/bin/php \
        /usr/local/bin/php "$(command -v php 2>/dev/null)"
    do
        [ -x "$candidate" ] || continue
        # Laravel 12 needs 8.2+; anything older is the wrong binary.
        if "$candidate" -r 'exit(PHP_VERSION_ID >= 80200 ? 0 : 1);' 2>/dev/null; then
            echo "$candidate"
            return 0
        fi
    done

    return 1
}

PHP="$(find_php)" || {
    log "XATO: PHP 8.2+ topilmadi. Hosting'dagi PHP yo'lini shu skriptdagi find_php ro'yxatiga qo'shing."
    exit 1
}

log "=== Deploy boshlandi ($APP_DIR) — $($PHP -r 'echo PHP_VERSION;') ==="

run() {
    log "-> $*"
    if ! "$@" >>"$LOG" 2>&1; then
        log "XATO: buyruq muvaffaqiyatsiz tugadi — yuqoridagi logga qarang."
        exit 1
    fi
}

# Composer is rarely on cron's PATH on a cPanel account — it ships at a fixed
# location outside it — so the usual `command -v composer` finds nothing and
# dependencies silently never update. That is not a cosmetic miss: it is how a
# release that bumped a package (the league/commonmark security update) can
# look successful while production keeps running the vulnerable version.
find_composer() {
    local candidate
    for candidate in \
        "$APP_DIR/composer.phar" \
        /opt/cpanel/composer/bin/composer \
        /usr/local/bin/composer \
        "$HOME/composer.phar" \
        "$(command -v composer 2>/dev/null)"
    do
        [ -n "$candidate" ] && [ -f "$candidate" ] && { echo "$candidate"; return 0; }
    done

    return 1
}

COMPOSER="$(find_composer)"

if [ -n "${COMPOSER:-}" ]; then
    log "composer: $COMPOSER"
    # Always invoked through the PHP chosen above: cPanel's composer wrapper
    # picks its own PHP otherwise, which is not necessarily the site's.
    run "$PHP" "$COMPOSER" install --no-dev --optimize-autoloader --no-interaction
else
    log "DIQQAT: composer topilmadi — paketlar yangilanmadi. composer.lock o'zgargan bo'lsa, buni qo'lda hal qiling."
fi

# --force: there is no TTY here to answer the confirmation prompt.
run "$PHP" artisan migrate --force

# NOT optional. Search matches on the derived search_text columns, and a
# migration that adds them leaves every existing row empty — skipping this
# does not error, it just makes every search on the site and in the admin
# panel return nothing at all. Also run it after any bulk import: rows written
# outside the app's own models are never indexed by the observers.
run "$PHP" artisan search:reindex

# public/storage -> storage/app/public. Without it every file on the public
# disk 404s: reader photos from the Excel import land there and are served as
# /storage/readers/photos/..., so the records import fine and every picture is
# broken, which looks like an import bug rather than a missing symlink.
#
# Guarded on absence rather than run with --force: storage:link fails when the
# path already exists, which would abort the whole deploy, and --force deletes
# whatever sits at that path first — the wrong tool if it is ever a real
# directory holding files.
#
# Not fatal: a host that forbids symlinks should not block a release over
# broken thumbnails, but it must say so in the log rather than pass silently.
if [ -e public/storage ]; then
    log "storage:link: public/storage allaqachon mavjud — o'tkazib yuborildi"
elif "$PHP" artisan storage:link >>"$LOG" 2>&1; then
    log "storage:link: yaratildi"
else
    log "DIQQAT: storage:link bajarilmadi — public diskdagi fayllar (kitobxon suratlari) ko'rinmaydi."
fi

# Cleared before rebuilding so a stale compiled view or config from the
# previous release cannot survive.
run "$PHP" artisan config:clear
run "$PHP" artisan view:clear
run "$PHP" artisan config:cache

# No php-fpm reload: that needs root, which this account does not have. Whether
# new code takes effect at once depends on opcache.validate_timestamps —
# /admin/server-limits reports it. If it is off, use the hosting panel's
# "Restart PHP" after deploying.
log "=== Deploy tugadi ==="
