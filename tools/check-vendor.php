<?php

/**
 * Compares the installed vendor/ tree against composer.lock and reports every
 * package that is missing or at a different version.
 *
 * Run from deploy.sh on the production host. That host has no composer, so
 * vendor/ is uploaded by hand as a zip after any dependency change — and the
 * failure mode of a manual step is that it gets skipped. When it is skipped
 * nothing breaks and nothing complains: the site keeps serving the old
 * packages. That is precisely how the league/commonmark security update (6
 * advisories) sat in composer.lock, passed CI, and never reached production.
 *
 * So the step stays manual, but it stops being silent: this prints the exact
 * drift into the deploy log.
 *
 * Exit code: 0 when the tree matches the lock, 1 when it does not (or cannot
 * be read at all).
 */
$root = dirname(__DIR__);

$lockPath = $root.'/composer.lock';
$installedPath = $root.'/vendor/composer/installed.php';

if (! is_file($lockPath)) {
    fwrite(STDERR, "composer.lock topilmadi.\n");
    exit(1);
}

if (! is_file($installedPath)) {
    fwrite(STDERR, "vendor/composer/installed.php topilmadi — vendor/ yuklanmagan.\n");
    exit(1);
}

$lock = json_decode((string) file_get_contents($lockPath), true);

if (! is_array($lock) || ! isset($lock['packages'])) {
    fwrite(STDERR, "composer.lock o'qib bo'lmadi.\n");
    exit(1);
}

$installed = require $installedPath;

/** @var array<string, string> $expected */
$expected = [];

// Only `packages` — `packages-dev` is deliberately absent from a production
// tree, so comparing it would report drift on every single deploy.
foreach ($lock['packages'] as $package) {
    $expected[$package['name']] = $package['version'];
}

$problems = [];

foreach ($expected as $name => $version) {
    $actual = $installed['versions'][$name]['pretty_version'] ?? null;

    if ($actual === null) {
        $problems[] = "{$name}: lock {$version}, vendor'da YO'Q";

        continue;
    }

    if ($actual !== $version) {
        $problems[] = "{$name}: lock {$version}, vendor {$actual}";
    }
}

if ($problems === []) {
    echo 'vendor/ composer.lock ga mos ('.count($expected)." ta paket).\n";
    exit(0);
}

echo 'vendor/ composer.lock ga MOS EMAS — '.count($problems)." ta farq:\n";

foreach ($problems as $problem) {
    echo "  - {$problem}\n";
}

echo "Yangi vendor-prod.zip yuklang (loyihada: php tools/build-vendor.php).\n";

exit(1);
