#!/bin/bash
#
# One-time: install composer.phar into the application root.
#
# This account has no terminal, so run it from a cPanel cron entry:
#
#   /bin/bash /home/sdvunf/arm.sdvunf.uz/tools/install-composer.sh
#
# then delete the cron entry again. Everything is appended to
# storage/logs/composer-install.log, which is the only place to watch it.
#
# It touches nothing but composer.phar: no vendor/, no cache, no config. If it
# fails, the site keeps running exactly as it did before.
#
# Why this is possible at all, since an earlier attempt concluded it was not:
# copy() on an https URL returned false, which was read as "no outbound
# network". It returns false just as readily when allow_url_fopen is off, and
# that is what was actually the case here — curl reaches packagist fine, and
# proc_open/exec/ext-zip, the things composer really needs, are all available.

set -uo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$APP_DIR" || exit 1

LOG="$APP_DIR/storage/logs/composer-install.log"
mkdir -p "$(dirname "$LOG")"

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*" | tee -a "$LOG"
}

# Same selection as deploy.sh, deliberately repeated rather than shared: this
# script has to be able to run on an account where deploy.sh is broken or not
# yet updated, and cron's `php` is usually an old default here.
find_php() {
    local candidate
    for candidate in \
        /usr/local/bin/ea-php83 /opt/cpanel/ea-php83/root/usr/bin/php \
        /usr/local/bin/ea-php82 /opt/cpanel/ea-php82/root/usr/bin/php \
        /usr/local/bin/php "$(command -v php 2>/dev/null)"
    do
        [ -x "$candidate" ] || continue
        if "$candidate" -r 'exit(PHP_VERSION_ID >= 80200 ? 0 : 1);' 2>/dev/null; then
            echo "$candidate"
            return 0
        fi
    done

    return 1
}

PHP="$(find_php)" || {
    log "XATO: PHP 8.2+ topilmadi."
    exit 1
}

log "=== composer o'rnatish boshlandi ($APP_DIR) — PHP $($PHP -r 'echo PHP_VERSION;') ==="

if [ -f composer.phar ]; then
    log "composer.phar allaqachon mavjud — versiya: $($PHP -d allow_url_fopen=1 composer.phar --version 2>&1 | head -1)"
    log "Qayta o'rnatish uchun avval uni o'chiring."
    exit 0
fi

command -v curl >/dev/null 2>&1 || {
    log "XATO: curl topilmadi."
    exit 1
}

SETUP="$APP_DIR/composer-setup.php"
SIGFILE="$APP_DIR/composer-setup.sig"

cleanup() {
    rm -f "$SETUP" "$SIGFILE"
}

trap cleanup EXIT

log "-> installer yuklanmoqda"
if ! curl -sSfL --max-time 60 -o "$SETUP" https://getcomposer.org/installer 2>>"$LOG"; then
    log "XATO: installer yuklanmadi."
    exit 1
fi

log "-> imzo (sha384) yuklanmoqda"
if ! curl -sSfL --max-time 60 -o "$SIGFILE" https://composer.github.io/installer.sig 2>>"$LOG"; then
    log "XATO: imzo yuklanmadi."
    exit 1
fi

# The whole point of the signature: this file is about to be executed with the
# account's own privileges, straight off the network. A corrupted or swapped
# download must stop here, not run.
EXPECTED="$(tr -d ' \t\n\r' < "$SIGFILE")"
ACTUAL="$("$PHP" -r 'echo hash_file("sha384", $argv[1]);' "$SETUP")"

if [ "$EXPECTED" != "$ACTUAL" ]; then
    log "XATO: imzo mos kelmadi — installer ishga TUSHIRILMADI."
    log "      kutilgan: $EXPECTED"
    log "      olingan : $ACTUAL"
    exit 1
fi

log "imzo to'g'ri"

# -d allow_url_fopen=1: the installer refuses to run without it, and this
# account cannot edit the CLI ini. The setting is off here for a reason and
# stays off — the override lives and dies with this one process, and composer
# itself does its downloading through curl anyway.
log "-> composer.phar yasalmoqda (installer)"
if "$PHP" -d allow_url_fopen=1 "$SETUP" --install-dir="$APP_DIR" --filename=composer.phar >>"$LOG" 2>&1 && [ -f composer.phar ]; then
    log "installer muvaffaqiyatli"
else
    # Fallback: fetch the built phar directly and check it against the
    # published SHA-256. Same guarantee as the installer's own signature check,
    # one fewer moving part — worth having, because a host that blocks
    # something else the installer wants would otherwise leave no way forward.
    log "DIQQAT: installer ishlamadi — to'g'ridan-to'g'ri yuklashga o'tilmoqda"
    rm -f composer.phar

    if ! curl -sSfL --max-time 120 -o composer.phar https://getcomposer.org/download/latest-stable/composer.phar 2>>"$LOG"; then
        log "XATO: composer.phar yuklanmadi."
        exit 1
    fi

    if ! curl -sSfL --max-time 60 -o composer.phar.sha256sum https://getcomposer.org/download/latest-stable/composer.phar.sha256sum 2>>"$LOG"; then
        log "XATO: sha256sum yuklanmadi — tekshirilmagan fayl QOLDIRILMADI."
        rm -f composer.phar
        exit 1
    fi

    EXPECTED_SHA="$(cut -d' ' -f1 < composer.phar.sha256sum)"
    ACTUAL_SHA="$("$PHP" -r 'echo hash_file("sha256", "composer.phar");')"
    rm -f composer.phar.sha256sum

    if [ "$EXPECTED_SHA" != "$ACTUAL_SHA" ]; then
        log "XATO: sha256 mos kelmadi — fayl o'chirildi."
        log "      kutilgan: $EXPECTED_SHA"
        log "      olingan : $ACTUAL_SHA"
        rm -f composer.phar
        exit 1
    fi

    log "sha256 to'g'ri"
fi

if [ ! -f composer.phar ]; then
    log "XATO: composer.phar yaratilmadi."
    exit 1
fi

# 128M is not always enough for a dependency resolve, and the ini cannot be
# changed from here — so the limit is lifted for composer only, per invocation.
log "versiya: $(COMPOSER_MEMORY_LIMIT=-1 "$PHP" -d allow_url_fopen=1 composer.phar --version 2>&1 | head -1)"
log "=== Tayyor: $APP_DIR/composer.phar ==="
