<?php

/**
 * Builds the production dependency tree and packs it into vendor-prod.zip for
 * manual upload to the server.
 *
 * The production host (cPanel, arm.sdvunf.uz) has no composer, so vendor/ is
 * put there by hand through File Manager. Run this after ANY change to
 * composer.json / composer.lock:
 *
 *     php tools/build-vendor.php     # writes vendor-prod.zip
 *     ... upload and extract on the server, see CLAUDE.md for the exact order
 *     composer install               # restore the dev tooling locally
 *
 * That last step matters: this script strips pest/phpunit/dusk/faker/pint out
 * of vendor/ to keep them off the server, and the test suite cannot run
 * without them.
 *
 * Neither the tree nor the zip is committed. What keeps the manual upload from
 * being silently skipped is tools/check-vendor.php, which deploy.sh runs on the
 * server and which reports any package whose version differs from
 * composer.lock.
 */
const ZIP_NAME = 'vendor-prod.zip';

function fail(string $message): never
{
    fwrite(STDERR, "XATO: {$message}\n");
    exit(1);
}

function run(string $description, string $command): void
{
    echo "\n-> {$description}\n";

    passthru($command, $status);

    if ($status !== 0) {
        fail("buyruq muvaffaqiyatsiz tugadi ({$status}): {$command}");
    }
}

chdir(dirname(__DIR__)) || fail('loyiha ildiziga o‘tib bo‘lmadi.');

if (! is_file('composer.lock')) {
    fail('composer.lock topilmadi — bu loyiha ildizi emas.');
}

if (! class_exists(ZipArchive::class)) {
    fail('ext-zip yo‘q — zip yasab bo‘lmaydi.');
}

$composer = getenv('COMPOSER_BIN') ?: 'composer';

run(
    'Production bog‘liqliklari o‘rnatilmoqda (dev paketlarsiz)',
    $composer.' install --no-dev --optimize-autoloader --no-interaction'
);

// Sanity checks before anything is packed: an aborted install can leave a tree
// that looks fine but has no autoloader, and uploading that breaks every
// request on the server with no obvious cause.
if (! is_file('vendor/autoload.php') || ! is_file('vendor/composer/installed.php')) {
    fail('vendor/ to‘liq emas — autoload fayllari yo‘q. Yuklamang.');
}

$installed = require 'vendor/composer/installed.php';

if (($installed['root']['dev'] ?? true) !== false) {
    fail('vendor/ hali dev paketlar bilan — `--no-dev` ishlamadi.');
}

echo "\n-> ".ZIP_NAME.' yasalmoqda'."\n";

if (is_file(ZIP_NAME) && ! unlink(ZIP_NAME)) {
    fail('eski '.ZIP_NAME.' o‘chirilmadi.');
}

$zip = new ZipArchive;

if ($zip->open(ZIP_NAME, ZipArchive::CREATE) !== true) {
    fail(ZIP_NAME.' yaratilmadi.');
}

$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator('vendor', FilesystemIterator::SKIP_DOTS)
);

$count = 0;

foreach ($files as $file) {
    if (! $file->isFile()) {
        continue;
    }

    // Entry names keep the "vendor/..." prefix and use forward slashes, so
    // extracting the archive at the application root recreates the directory
    // exactly as it is here — which is what cPanel's File Manager does.
    $entry = str_replace(DIRECTORY_SEPARATOR, '/', $file->getPathname());

    $zip->addFile($file->getPathname(), $entry);
    $count++;
}

$zip->close();

printf(
    "\nTayyor: %s — %d ta fayl, %.1f MB, %d ta paket.\n",
    ZIP_NAME,
    $count,
    filesize(ZIP_NAME) / 1024 / 1024,
    count($installed['versions'])
);

echo "\nKeyingi qadamlar CLAUDE.md da (\"Bogʻliqliklar (vendor)\"), qisqacha:\n";
echo '  1. '.ZIP_NAME." ni serverga yuklang (hali extract qilmang)\n";
echo "  2. Eski vendor'ni vendor_old deb nomlang\n";
echo "  3. Zip'ni extract qiling\n";
echo "  4. bootstrap/cache/ ichidagi .php fayllarni o'chiring  <-- MAJBURIY\n";
echo "  5. Saytni tekshiring, keyin vendor_old'ni o'chiring\n";
echo "  6. Bu yerda: composer install   (dev paketlarni qaytaradi)\n";
