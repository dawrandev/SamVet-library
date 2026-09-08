<?php

/**
 * READ-ONLY server diagnostic. Changes nothing. Safe to run repeatedly.
 *
 * Production has no terminal, so this is how the account's actual state gets
 * looked at: run it once from cron and read the file it writes.
 *
 *   /usr/local/bin/ea-php83 /home/sdvunf/arm.sdvunf.uz/tools/check-server.php > /home/sdvunf/arm.sdvunf.uz/server-report.txt 2>&1
 *
 * Then open server-report.txt in File Manager.
 */

// Works whether the file sits in tools/ (where it belongs) or was uploaded
// straight into the application root, which is the likelier thing to happen
// when someone is copying it in through File Manager under pressure.
$root = is_file(__DIR__.'/artisan') ? __DIR__ : dirname(__DIR__);

if (! is_file($root.'/artisan')) {
    fwrite(STDERR, "Ilova ildizi topilmadi (artisan yo'q). Skriptni loyiha ildiziga yoki tools/ ichiga qo'ying.\n");
    exit(1);
}

function h(string $title): void
{
    echo "\n".str_repeat('=', 60)."\n".$title."\n".str_repeat('=', 60)."\n";
}

function kv(string $key, $value): void
{
    if (is_bool($value)) {
        $value = $value ? 'HA' : 'YOQ';
    }

    printf("%-32s %s\n", $key.':', (string) $value);
}

function canShell(): bool
{
    if (! function_exists('shell_exec')) {
        return false;
    }

    $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));

    return ! in_array('shell_exec', $disabled, true);
}

function sh(string $command): string
{
    if (! canShell()) {
        return '(shell_exec ochirilgan)';
    }

    return trim((string) shell_exec($command.' 2>&1'));
}

function tailFile(string $path, int $lines): string
{
    if (! is_file($path)) {
        return '(fayl yoq)';
    }

    $all = file($path, FILE_IGNORE_NEW_LINES);

    return $all === false ? '(oqib bolmadi)' : implode("\n", array_slice($all, -$lines));
}

function human(int $bytes): string
{
    return $bytes > 1048576
        ? round($bytes / 1048576, 1).' MB'
        : round($bytes / 1024, 1).' KB';
}

h('1. UMUMIY');
kv('Sana', date('Y-m-d H:i:s'));
kv('Host', php_uname('n'));
kv('Foydalanuvchi', sh('whoami'));
kv('Ilova papkasi', $root);
kv('PHP', PHP_VERSION.' ('.PHP_SAPI.')');
kv('memory_limit', ini_get('memory_limit'));
kv('upload_max_filesize', ini_get('upload_max_filesize'));
kv('post_max_size', ini_get('post_max_size'));
kv('opcache.enable', ini_get('opcache.enable') ? 'ON' : 'OFF');
kv('opcache.validate_timestamps', ini_get('opcache.validate_timestamps') ? 'ON' : 'OFF');
kv('shell_exec', canShell());
kv('disk', sh('df -h '.escapeshellarg($root).' | tail -1'));

h('2. .env (faqat xavfsiz kalitlar)');
$envPath = $root.'/.env';
kv('.env mavjud', is_file($envPath));

if (is_file($envPath)) {
    $safe = ['APP_ENV', 'APP_DEBUG', 'APP_URL', 'DB_CONNECTION', 'DB_DATABASE', 'DB_HOST', 'FILESYSTEM_DISK', 'CACHE_STORE', 'SESSION_DRIVER', 'QUEUE_CONNECTION'];

    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);

        if ($line === '' || $line[0] === '#' || ! str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);

        if (in_array(trim($key), $safe, true)) {
            kv(trim($key), trim($value));
        }
    }
}

h('3. VENDOR PAPKALARI');

foreach (scandir($root) ?: [] as $entry) {
    if (! str_starts_with($entry, 'vendor') || ! is_dir($root.'/'.$entry)) {
        continue;
    }

    $dir = $root.'/'.$entry;
    echo "\n--- {$entry}/ ---\n";
    kv('  autoload.php', is_file($dir.'/autoload.php'));

    $installedPath = $dir.'/composer/installed.php';

    if (is_file($installedPath)) {
        $installed = require $installedPath;
        kv('  dev rejimi', ($installed['root']['dev'] ?? null) ? 'HA (dev paketlar bor)' : 'YOQ (production)');
        kv('  paketlar', count($installed['versions'] ?? []));
        kv('  league/commonmark', $installed['versions']['league/commonmark']['pretty_version'] ?? '(yoq)');
        kv('  laravel/framework', $installed['versions']['laravel/framework']['pretty_version'] ?? '(yoq)');
        kv('  laravel/dusk (dev)', $installed['versions']['laravel/dusk']['pretty_version'] ?? '(yoq)');
    } else {
        kv('  composer/installed.php', 'YOQ');
    }

    kv('  olchami', sh('du -sh '.escapeshellarg($dir).' | cut -f1'));
    kv('  ozgargan', date('Y-m-d H:i', (int) filemtime($dir)));
}

h('4. composer.lock BILAN SOLISHTIRISH (joriy vendor/)');
$lockPath = $root.'/composer.lock';
$installedPath = $root.'/vendor/composer/installed.php';

if (! is_file($lockPath) || ! is_file($installedPath)) {
    echo "composer.lock yoki vendor/composer/installed.php yoq.\n";
} else {
    $lock = json_decode((string) file_get_contents($lockPath), true);
    $installed = require $installedPath;
    $problems = [];

    foreach ($lock['packages'] ?? [] as $package) {
        $actual = $installed['versions'][$package['name']]['pretty_version'] ?? null;

        if ($actual === null) {
            $problems[] = $package['name'].": lock {$package['version']}, vendorda YOQ";
        } elseif ($actual !== $package['version']) {
            $problems[] = $package['name'].": lock {$package['version']}, vendor {$actual}";
        }
    }

    kv('lock paketlari', count($lock['packages'] ?? []));
    kv('farqlar', count($problems));

    foreach (array_slice($problems, 0, 25) as $problem) {
        echo "  - {$problem}\n";
    }
}

h('5. bootstrap/cache/');
$cacheFiles = glob($root.'/bootstrap/cache/*') ?: [];

if ($cacheFiles === []) {
    echo "  (bosh)\n";
}

foreach ($cacheFiles as $file) {
    printf("  %-22s %9s   %s\n", basename($file), human((int) filesize($file)), date('Y-m-d H:i', (int) filemtime($file)));
}

h('6. public/storage');
$link = $root.'/public/storage';
kv('mavjud', file_exists($link));
kv('symlink', is_link($link));

if (is_link($link)) {
    kv('korsatadi', readlink($link));
}

h('7. GIT');
$gitBin = sh('command -v git');
kv('git binari', $gitBin !== '' ? $gitBin : 'TOPILMADI');
kv('.git papkasi', is_dir($root.'/.git'));

if ($gitBin !== '' && is_dir($root.'/.git')) {
    $g = 'cd '.escapeshellarg($root).' && '.escapeshellarg($gitBin).' --no-optional-locks';
    kv('branch', sh($g.' rev-parse --abbrev-ref HEAD'));
    kv('HEAD', sh($g.' log -1 --format="%h %ad %s" --date=short'));
    kv('remote', sh($g.' config --get remote.origin.url'));
    kv('vendor kuzatilyaptimi', sh($g.' ls-files vendor | head -1') !== '' ? 'HA' : 'YOQ');

    $status = sh($g.' status --porcelain');
    $lines = $status === '' ? [] : explode("\n", $status);
    $vendorLines = array_values(array_filter($lines, static fn ($l) => str_contains($l, 'vendor/')));
    $otherLines = array_values(array_filter($lines, static fn ($l) => ! str_contains($l, 'vendor/')));

    kv('ozgargan fayllar (jami)', count($lines));
    kv('  vendor/ ichida', count($vendorLines));
    kv('  vendordan tashqarida', count($otherLines));

    foreach (array_slice($otherLines, 0, 25) as $line) {
        echo "    {$line}\n";
    }
}

h('8. TARMOQ (internet bormi?)');
kv('allow_url_fopen', ini_get('allow_url_fopen') ? 'ON' : 'OFF');
kv('openssl kengaytmasi', extension_loaded('openssl'));
kv('curl kengaytmasi', extension_loaded('curl'));
kv('DNS repo.packagist.org', gethostbyname('repo.packagist.org'));

if (extension_loaded('curl')) {
    $ch = curl_init('https://repo.packagist.org/packages.json');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_NOBODY => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_CONNECTTIMEOUT => 10,
    ]);
    curl_exec($ch);
    kv('curl -> packagist HTTP', (string) curl_getinfo($ch, CURLINFO_HTTP_CODE));
    kv('curl xatosi', curl_error($ch) ?: '(yoq)');
    curl_close($ch);
}

kv('curl CLI', sh('curl -sS -m 20 -o /dev/null -w "%{http_code}" https://repo.packagist.org/packages.json'));

h('9. COMPOSER');

foreach ([$root.'/composer.phar', '/opt/cpanel/composer/bin/composer', '/usr/local/bin/composer', getenv('HOME').'/composer.phar'] as $candidate) {
    kv($candidate, is_file($candidate));
}

kv('command -v composer', sh('command -v composer') ?: 'topilmadi');

h('10. storage/logs/laravel.log (oxirgi 40 qator)');
$laravelLog = $root.'/storage/logs/laravel.log';
kv('olchami', is_file($laravelLog) ? human((int) filesize($laravelLog)) : '(yoq)');
echo "\n".tailFile($laravelLog, 40)."\n";

h('11. storage/logs/deploy.log (oxirgi 15 qator)');
echo tailFile($root.'/storage/logs/deploy.log', 15)."\n";

h('12. CRON');
echo sh('crontab -l')."\n";

h('TUGADI');
