<?php

/**
 * READ-ONLY disk report. Changes nothing, deletes nothing. Safe to repeat.
 *
 * The account has one 10 GB quota shared by every site on it, and the library
 * is the only one that grows on its own — every catalogued PDF lands in
 * storage/app. Before asking the host for more, the question is what actually
 * holds the space today, because "public_html is 7.8 GB" is not something a
 * hosting provider can act on.
 *
 * Production has no terminal, so run it from cron and read the file:
 *
 *   /usr/local/bin/ea-php83 /home/sdvunf/arm.sdvunf.uz/tools/check-disk.php > /home/sdvunf/arm.sdvunf.uz/disk-report.txt 2>&1
 *
 * Then open disk-report.txt in File Manager and delete the cron entry.
 */

// Same tolerance as check-server.php: the file may end up in the application
// root instead of tools/ when someone copies it in by hand under pressure.
$root = is_file(__DIR__.'/artisan') ? __DIR__ : dirname(__DIR__);

if (! is_file($root.'/artisan')) {
    fwrite(STDERR, "Ilova ildizi topilmadi (artisan yo'q). Skriptni loyiha ildiziga yoki tools/ ichiga qo'ying.\n");
    exit(1);
}

$home = dirname($root);

function h(string $title): void
{
    echo "\n".str_repeat('=', 64)."\n".$title."\n".str_repeat('=', 64)."\n";
}

function kv(string $key, $value): void
{
    if (is_bool($value)) {
        $value = $value ? 'HA' : 'YOQ';
    }

    printf("%-30s %s\n", $key.':', (string) $value);
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

function human(float $bytes): string
{
    foreach (['B', 'KB', 'MB', 'GB'] as $unit) {
        if ($bytes < 1024 || $unit === 'GB') {
            return round($bytes, $bytes < 10 ? 1 : 0).' '.$unit;
        }

        $bytes /= 1024;
    }

    return (string) $bytes;
}

/**
 * Size and file count of a directory tree.
 *
 * du is used when available because it is far cheaper than walking the tree
 * from PHP; the PHP fallback exists because shell_exec is the first thing a
 * shared host disables. The fallback is capped — an answer that took four
 * minutes and timed the cron job out would be worth nothing.
 *
 * @return array{bytes: int, files: int, capped: bool}
 */
function measure(string $dir, int $maxFiles = 200000): array
{
    if (! is_dir($dir)) {
        return ['bytes' => 0, 'files' => 0, 'capped' => false];
    }

    if (canShell()) {
        $out = sh('du -sb '.escapeshellarg($dir).' | cut -f1');

        if (ctype_digit($out)) {
            $count = sh('find '.escapeshellarg($dir).' -type f | wc -l');

            return [
                'bytes' => (int) $out,
                'files' => ctype_digit($count) ? (int) $count : 0,
                'capped' => false,
            ];
        }
    }

    $bytes = 0;
    $files = 0;
    $capped = false;

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST,
        RecursiveIteratorIterator::CATCH_GET_CHILD
    );

    foreach ($iterator as $file) {
        if (! $file->isFile() || $file->isLink()) {
            continue;
        }

        $bytes += $file->getSize();
        $files++;

        if ($files >= $maxFiles) {
            $capped = true;
            break;
        }
    }

    return ['bytes' => $bytes, 'files' => $files, 'capped' => $capped];
}

function reportDir(string $label, string $dir): int
{
    if (! is_dir($dir)) {
        printf("%-30s %10s\n", $label.':', '(yoq)');

        return 0;
    }

    $m = measure($dir);

    printf(
        "%-30s %10s  %7s ta fayl%s\n",
        $label.':',
        human((float) $m['bytes']),
        number_format($m['files'], 0, '.', ' '),
        $m['capped'] ? '  (hisob chegaralandi)' : ''
    );

    return $m['bytes'];
}

h('1. AKKAUNT KVOTASI');
kv('Sana', date('Y-m-d H:i:s'));
kv('Foydalanuvchi', sh('whoami'));
kv('Home', $home);
kv('Ilova papkasi', $root);
kv('shell_exec', canShell());

echo "\n-- quota -s --\n".sh('quota -s')."\n";
echo "\n-- df -h (ilova papkasi) --\n".sh('df -h '.escapeshellarg($root))."\n";

// disk_free_space() reports the filesystem, not the account quota. On a shared
// host those are different numbers and the quota is the one that bites first,
// so both are printed rather than either being called "free space".
$free = @disk_free_space($root);
$total = @disk_total_space($root);

if ($free !== false && $total !== false) {
    kv("Fayl tizimi (bo'sh/jami)", human((float) $free).' / '.human((float) $total));
    echo "  (diqqat: bu FAYL TIZIMI, akkaunt kvotasi emas — yuqoridagi quota qatoriga qarang)\n";
}

h('2. HOME OSTIDAGI SAYTLAR');
echo "Akkauntda bir nechta sayt bor — qaysi biri joyni yeyayotgani shu yerda ko'rinadi.\n\n";

$sites = [];

foreach (@scandir($home) ?: [] as $entry) {
    if ($entry === '.' || $entry === '..') {
        continue;
    }

    $path = $home.'/'.$entry;

    if (! is_dir($path) || is_link($path)) {
        continue;
    }

    $sites[$entry] = measure($path)['bytes'];
}

arsort($sites);

foreach ($sites as $name => $bytes) {
    printf("%-30s %10s\n", $name.':', human((float) $bytes));
}

printf("\n%-30s %10s\n", 'JAMI (home)', human((float) array_sum($sites)));

h('3. LOYIHA ICHIDAGI TAQSIMOT');

$parts = [
    'storage/app (yuklangan)' => $root.'/storage/app',
    'storage/logs' => $root.'/storage/logs',
    'storage/framework' => $root.'/storage/framework',
    'public' => $root.'/public',
    'vendor' => $root.'/vendor',
    'node_modules' => $root.'/node_modules',
    '.git' => $root.'/.git',
];

$projectTotal = 0;

foreach ($parts as $label => $dir) {
    $projectTotal += reportDir($label, $dir);
}

printf("\n%-30s %10s\n", 'JAMI (loyiha)', human((float) $projectTotal));

h("4. storage/app — TUR BO'YICHA");
echo "Kutubxona o'z-o'zidan o'sadigan yagona joy. Kelajakni shu raqamlar bashorat qiladi.\n\n";

$appDir = $root.'/storage/app';
$kinds = [];

foreach (@scandir($appDir) ?: [] as $entry) {
    if ($entry === '.' || $entry === '..') {
        continue;
    }

    $path = $appDir.'/'.$entry;

    if (! is_dir($path) || is_link($path)) {
        continue;
    }

    $kinds[$entry] = measure($path);
}

uasort($kinds, static fn (array $a, array $b): int => $b['bytes'] <=> $a['bytes']);

foreach ($kinds as $name => $m) {
    printf(
        "%-30s %10s  %7s ta fayl  o'rtacha %s\n",
        $name.':',
        human((float) $m['bytes']),
        number_format($m['files'], 0, '.', ' '),
        $m['files'] > 0 ? human($m['bytes'] / $m['files']) : '—'
    );
}

h('5. ENG KATTA 25 FAYL (butun home)');

if (canShell()) {
    $find = 'find '.escapeshellarg($home).' -type f -printf '.escapeshellarg('%s\t%p\n')
        .' | sort -rn | head -25';

    foreach (explode("\n", sh($find)) as $line) {
        if (! str_contains($line, "\t")) {
            continue;
        }

        [$bytes, $path] = explode("\t", $line, 2);
        printf("%10s  %s\n", human((float) $bytes), str_replace($home.'/', '', $path));
    }
} else {
    echo "(shell_exec o'chirilgan — bu bo'lim uchun find kerak)\n";
}

h("6. TOZALASH MUMKIN BO'LGAN NOMZODLAR");
echo "Skript hech narsani o'chirmaydi — faqat ko'rsatadi. Qaror sizniki.\n\n";

$candidates = [
    $root.'/vendor_old' => 'eski vendor (deploy orqaga qaytish nuqtasi)',
    $root.'/vendor-prod.zip' => "zaxira yo'l uchun yuklangan arxiv",
    $root.'/server-report.txt' => 'diagnostika natijasi',
    $root.'/disk-report.txt' => 'shu skriptning natijasi',
    $root.'/error_log' => 'PHP xato jurnali',
    $root.'/public/error_log' => 'PHP xato jurnali (public)',
    $root.'/storage/app/private/chunk-uploads' => "tashlab ketilgan chunked upload bo'laklari (24 soatdan eskisi)",
    $home.'/.trash' => "File Manager savati — o'chirilgan fayllar hamon kvotaga kiradi",
    $root.'/node_modules' => 'faqat build uchun — productionda kerak emas',
];

$reclaimable = 0;

foreach ($candidates as $path => $why) {
    if (! file_exists($path)) {
        continue;
    }

    $bytes = is_dir($path) ? measure($path)['bytes'] : (int) filesize($path);
    $reclaimable += $bytes;

    printf("%10s  %s\n            %s\n", human((float) $bytes), str_replace($home.'/', '', $path), $why);
}

foreach (glob($root.'/storage/logs/*.log') ?: [] as $log) {
    $bytes = (int) filesize($log);

    if ($bytes < 1048576) {
        continue;
    }

    $reclaimable += $bytes;
    printf("%10s  %s\n            %s\n", human((float) $bytes), str_replace($home.'/', '', $log), 'log fayli (kesish mumkin)');
}

printf("\n%-30s %10s\n", 'JAMI (taxminiy)', human((float) $reclaimable));

h('7. BAZA HAJMI');

// Read-only, information_schema only. Credentials come from .env and are never
// printed — only the database name and the sizes.
$env = [];

if (is_file($root.'/.env')) {
    foreach (file($root.'/.env', FILE_IGNORE_NEW_LINES) as $line) {
        $line = trim($line);

        if ($line === '' || $line[0] === '#' || ! str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $env[trim($key)] = trim(trim($value), "\"'");
    }
}

$database = $env['DB_DATABASE'] ?? null;

if ($database === null || ! extension_loaded('pdo_mysql')) {
    kv('Baza', $database ?? "(.env dan o'qib bo'lmadi)");
    echo "(pdo_mysql yo'q yoki .env topilmadi — bu bo'lim o'tkazib yuborildi)\n";
} else {
    try {
        $pdo = new PDO(
            sprintf('mysql:host=%s;port=%s;dbname=%s', $env['DB_HOST'] ?? '127.0.0.1', $env['DB_PORT'] ?? '3306', $database),
            $env['DB_USERNAME'] ?? '',
            $env['DB_PASSWORD'] ?? '',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        kv('Baza', $database);

        $totalBytes = (int) $pdo->query(
            'SELECT COALESCE(SUM(data_length + index_length), 0)
             FROM information_schema.tables
             WHERE table_schema = DATABASE()'
        )->fetchColumn();

        kv('Jami hajmi', human((float) $totalBytes));

        $rows = $pdo->query(
            'SELECT table_name, data_length + index_length AS bytes, table_rows
             FROM information_schema.tables
             WHERE table_schema = DATABASE()
             ORDER BY bytes DESC
             LIMIT 12'
        )->fetchAll(PDO::FETCH_ASSOC);

        echo "\nEng katta jadvallar:\n";

        foreach ($rows as $row) {
            printf(
                "%-30s %10s  ~%s qator\n",
                '  '.$row['table_name'],
                human((float) $row['bytes']),
                number_format((int) $row['table_rows'], 0, '.', ' ')
            );
        }
    } catch (Throwable $e) {
        echo "Bazaga ulanib bo'lmadi: ".$e->getMessage()."\n";
    }
}

h('8. PROGNOZ');
echo "Bugungi o'rtacha fayl hajmiga qarab, bo'sh joyga yana nechta resurs sig'adi.\n\n";

$appTotal = array_sum(array_column($kinds, 'bytes'));
$appFiles = array_sum(array_column($kinds, 'files'));
$average = $appFiles > 0 ? $appTotal / $appFiles : 0.0;

kv('storage/app jami', human((float) $appTotal));
kv('Fayllar soni', number_format($appFiles, 0, '.', ' '));
kv("O'rtacha fayl", $average > 0 ? human($average) : '—');

if ($average > 0) {
    echo "\n";

    foreach ([1, 5, 10, 20, 50] as $gb) {
        printf("  %2d GB bo'sh joy  →  ~%s ta fayl\n", $gb, number_format((int) (($gb * 1073741824) / $average), 0, '.', ' '));
    }
}

echo "\nTugadi. Bu skript hech narsani o'zgartirmadi.\n";
