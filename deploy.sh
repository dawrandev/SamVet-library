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


# Laravel caches the list of discovered packages in bootstrap/cache/. Those
# files are written by composer's post-autoload-dump hook, so they belong to
# whichever vendor/ was in place when they were written — and they are not in
# the repository, so they outlive a vendor/ that gets replaced underneath them,
# still naming providers from the tree that is gone.
#
# That is not a subtle failure. A production vendor/ has no laravel/dusk, the
# stale manifest still lists DuskServiceProvider, and every request and every
# artisan command then dies with "Class not found" before the framework
# finishes booting. It took this site down on 2026-09-08.
#
# It also has to happen BEFORE composer runs, not just before artisan: the
# post-autoload-dump hook is itself an artisan command, so a stale manifest
# would take down the very install that was going to fix it. rm is the right
# tool precisely because it works when nothing can boot.
log "bootstrap/cache tozalanmoqda"
rm -f bootstrap/cache/packages.php \
      bootstrap/cache/services.php \
      bootstrap/cache/config.php \
      bootstrap/cache/routes-*.php

# Composer is not on cron's PATH here — it lives in the application root,
# installed once by tools/install-composer.sh — so `command -v composer` finds
# nothing and dependencies would silently never update. That is not cosmetic:
# it is how a release that bumped a package (the league/commonmark security
# update) can look successful while production keeps running the vulnerable
# version, for weeks.
find_composer() {
    local candidate
    for candidate in \
        "$APP_DIR/composer.phar" \
        /opt/cpanel/composer/bin/composer \
        /usr/local/bin/composer \
        "${HOME:-}/composer.phar" \
        "$(command -v composer 2>/dev/null)"
    do
        [ -n "$candidate" ] && [ -f "$candidate" ] && { echo "$candidate"; return 0; }
    done

    return 1
}

COMPOSER="$(find_composer)"

if [ -n "${COMPOSER:-}" ]; then
    log "composer: $COMPOSER"

    # -d allow_url_fopen=1: off on this host, and composer's own bootstrap
    # refuses to start without it even though it downloads through curl. The
    # override lives and dies with this process; the ini is left as the host
    # set it.
    #
    # COMPOSER_MEMORY_LIMIT: the CLI limit here is 128M, which a resolve can
    # exceed, and this account cannot edit the ini.
    #
    # COMPOSER_HOME: without it composer falls back to guessing a cache
    # directory, which under cron can land somewhere unwritable and turn every
    # deploy into a full re-download.
    log "-> composer install --no-dev --optimize-autoloader"
    if ! COMPOSER_MEMORY_LIMIT=-1 COMPOSER_HOME="${HOME:-/tmp}/.composer" \
        "$PHP" -d allow_url_fopen=1 "$COMPOSER" install \
        --no-dev --optimize-autoloader --no-interaction >>"$LOG" 2>&1
    then
        log "XATO: composer install muvaffaqiyatsiz tugadi — yuqoridagi logga qarang."
        log "      Deploy to'xtatildi: yarim yangilangan vendor/ bilan migratsiya qilish xavfli."
        exit 1
    fi
else
    # Fallback for a host without composer: vendor/ is uploaded by hand as
    # vendor-prod.zip (built by tools/build-vendor.php). Nothing here can
    # install anything then — it can only refuse to deploy against a missing
    # tree, and complain about an outdated one.
    log "DIQQAT: composer topilmadi — vendor/ qo'lda yuklanishi kerak."
    log "        O'rnatish: /bin/bash $APP_DIR/tools/install-composer.sh"
fi

if [ ! -f vendor/autoload.php ]; then
    log "XATO: vendor/autoload.php yo'q — bog'liqliklar o'rnatilmagan."
    exit 1
fi

# Belt and braces after composer has run, and the only check there is when it
# has not. The failure mode of a manual upload is that it gets skipped, and a
# skipped upload breaks nothing visibly: the site keeps serving the packages it
# already has. That is precisely how the league/commonmark security update sat
# in composer.lock, passed CI, and never reached production. Non-fatal on
# purpose — an outdated tree still runs — but it must not pass in silence.
if ! "$PHP" tools/check-vendor.php >>"$LOG" 2>&1; then
    log "DIQQAT: vendor/ composer.lock ga mos emas — yuqorida ro'yxati bor."
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
