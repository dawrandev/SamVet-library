<?php

/**
 * Rebuilds the committed production vendor/ tree and stages it.
 *
 * Why vendor/ is in this repository at all: the production host (cPanel,
 * arm.sdvunf.uz) has no composer and no outbound network — a deploy there
 * proved it, `copy('https://getcomposer.org/composer-stable.phar', ...)`
 * simply returns false. So `git pull` is the only channel dependencies can
 * arrive through, and a composer.lock change that never reaches the server is
 * not cosmetic: that is exactly how the league/commonmark security update
 * could sit "released" while production kept serving the vulnerable version.
 *
 * Run this after ANY change to composer.json / composer.lock:
 *
 *     php tools/build-vendor.php
 *     git commit -m "chore(deps): ..."
 *     composer install            # put the dev tooling back so tests run
 *
 * That last step matters and is safe: vendor/ is still listed in .gitignore,
 * so the dev-only packages it restores (pest, phpunit, dusk, faker, pint) stay
 * untracked and cannot end up in a release. Only the files this script staged
 * are tracked.
 */

const ROOT = __DIR__.'/..';

function fail(string $message): never
{
    fwrite(STDERR, "XATO: {$message}\n");
    exit(1);
}

function run(string $description, array $command): void
{
    echo "\n-> {$description}\n";

    // Windows needs the whole thing quoted differently than POSIX; passing it
    // through the shell keeps `composer` (a .bat here, a shell script there)
    // resolvable from PATH on both.
    $line = implode(' ', array_map(
        static fn (string $part): string => str_contains($part, ' ') ? '"'.$part.'"' : $part,
        $command
    ));

    passthru($line, $status);

    if ($status !== 0) {
        fail("buyruq muvaffaqiyatsiz tugadi ({$status}): {$line}");
    }
}

chdir(ROOT) || fail('loyiha ildiziga o‘tib bo‘lmadi.');

if (! is_file('composer.lock')) {
    fail('composer.lock topilmadi — bu loyiha ildizi emas.');
}

$composer = getenv('COMPOSER_BIN') ?: 'composer';

run(
    'Production bog‘liqliklari o‘rnatilmoqda (dev paketlarsiz)',
    [$composer, 'install', '--no-dev', '--optimize-autoloader', '--no-interaction']
);

// Sanity check before anything is staged: an aborted install can leave a tree
// that looks fine but has no autoloader, and committing that breaks every
// request on the server with no obvious cause.
if (! is_file('vendor/autoload.php') || ! is_file('vendor/composer/installed.php')) {
    fail('vendor/ to‘liq emas — autoload fayllari yo‘q. Commit qilmang.');
}

$installed = require 'vendor/composer/installed.php';

if (($installed['root']['dev'] ?? true) !== false) {
    fail('vendor/ hali dev paketlar bilan — `--no-dev` ishlamadi.');
}

// Stamp the lock this tree was built from. deploy.sh compares it against the
// composer.lock it just pulled and says so in the deploy log when they differ,
// which is the only way to notice that a dependency change never actually
// reached production — the site keeps working, on the old packages.
file_put_contents('vendor/.lock-sha1', sha1_file('composer.lock')."\n");
// -f overrides the .gitignore entry (kept on purpose, see .gitignore); -A also
// stages deletions, so a package removed from composer.lock actually leaves the
// tree instead of lingering forever.
run('vendor/ git‘ga qo‘shilmoqda', ['git', 'add', '-f', '-A', 'vendor']);

echo "\n";
echo "Tayyor. Paketlar: ".count($installed['versions'])."\n";
echo "Endi:\n";
echo "  git commit -m \"chore(deps): ...\"\n";
echo "  composer install     # dev paketlarni qaytaradi (testlar uchun)\n";
