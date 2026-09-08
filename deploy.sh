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
# files are written by composer's post-autoload-dump hook, which never runs on
# this host, so they are absent from the repository and they outlive any
# replacement of vendor/ — still naming providers from the tree that is gone.
#
# That is not a subtle failure. A production vendor/ has no laravel/dusk, the
# stale manifest still lists DuskServiceProvider, and every request and every
# artisan command then dies with "Class not found" before the framework
# finishes booting. It took this site down on 2026-09-08.
#
# Deleting them is the entire fix — Laravel rebuilds them on the next run — and
# rm works even when the application cannot boot at all, which is exactly the
# state it has to recover from. So it runs BEFORE any artisan call below.
log "bootstrap/cache tozalanmoqda"
rm -f bootstrap/cache/packages.php \
      bootstrap/cache/services.php \
      bootstrap/cache/config.php \
      bootstrap/cache/routes-*.php

# Dependencies do not arrive with the code. This host has no composer, so
# vendor/ is uploaded by hand as vendor-prod.zip (built by
# tools/build-vendor.php). Nothing here can install anything; it can only
# refuse to deploy against a tree that is missing, and complain about one that
# is out of date.
if [ ! -f vendor/autoload.php ]; then
    log "XATO: vendor/autoload.php yo'q — vendor/ yuklanmagan yoki to'liq emas."
    log "      Loyihada 'php tools/build-vendor.php' ishlating, so'ng vendor-prod.zip'ni serverga yuklang."
    exit 1
fi

# The failure mode of a manual step is that it gets skipped, and a skipped
# vendor upload breaks nothing visibly: the site keeps serving the packages it
# already has. That is precisely how the league/commonmark security update sat
# in composer.lock, passed CI, and never reached production. Non-fatal on
# purpose — an outdated tree still runs — but it must not pass in silence.
if ! "$PHP" tools/check-vendor.php >>"$LOG" 2>&1; then
    log "DIQQAT: vendor/ composer.lock ga mos emas — yuqorida ro'yxati bor."
    log "        Yangi vendor-prod.zip tayyorlab, serverga yuklash kerak."
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
