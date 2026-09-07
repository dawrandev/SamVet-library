<?php

namespace App\Services;

use App\Http\Requests\Admin\ImportReadersRequest;

/**
 * Reports the PHP limits the application is actually running under, as seen
 * from the request that renders the page — i.e. FPM's configuration, not the
 * CLI's.
 *
 * That distinction is the whole point of this class. `php -i` on the server
 * reports the CLI SAPI, which usually has a completely different php.ini
 * (frequently memory_limit=-1 and no OPcache at all), so it says nothing
 * useful about what happens during a real upload. Reading the same values
 * inside a web request is the only way to see the numbers that actually
 * apply.
 *
 * Why this exists at all: production has no root/sudo (see CLAUDE.md), so
 * these limits cannot be raised — they are a fixed constraint the code has to
 * fit inside. Knowing them turns "the import dies with a 503" into a concrete
 * budget to design against.
 */
class ServerLimitsService
{
    /**
     * Values PHP accepts with a shorthand suffix ("128M", "1G", "-1"),
     * returned in bytes. -1 (unlimited) and 0 stay as they are.
     */
    public static function toBytes(string $value): int
    {
        $value = trim($value);

        if ($value === '' || $value === '-1') {
            return -1;
        }

        $unit = strtolower(substr($value, -1));
        $number = (int) $value;

        return match ($unit) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => $number,
        };
    }

    public static function format(int $bytes): string
    {
        if ($bytes < 0) {
            return __('Cheksiz');
        }

        if ($bytes === 0) {
            return __('Cheklanmagan (0)');
        }

        foreach (['B', 'KB', 'MB', 'GB'] as $unit) {
            if ($bytes < 1024 || $unit === 'GB') {
                return round($bytes, 1).' '.$unit;
            }
            $bytes /= 1024;
        }

        return $bytes.' B';
    }

    /**
     * The raw PHP settings that decide whether a big upload survives.
     *
     * @return array<int, array{key: string, value: string, note: string}>
     */
    public function uploadLimits(): array
    {
        return [
            [
                'key' => 'upload_max_filesize',
                'value' => (string) ini_get('upload_max_filesize'),
                'note' => __('Bitta faylning eng katta hajmi.'),
            ],
            [
                'key' => 'post_max_size',
                'value' => (string) ini_get('post_max_size'),
                'note' => __('Butun POST so‘rovi hajmi. Fayldan katta bo‘lishi shart — aks holda fayl umuman kelmaydi.'),
            ],
            [
                'key' => 'memory_limit',
                'value' => (string) ini_get('memory_limit'),
                'note' => __('Bitta so‘rov uchun xotira. Excel o‘qishda eng muhim raqam.'),
            ],
            [
                'key' => 'max_execution_time',
                'value' => (string) ini_get('max_execution_time'),
                'note' => __('Soniya. 0 — cheksiz, lekin bu faqat PHP taymeri; Apache/FPM o‘z taymerini baribir qo‘llaydi.'),
            ],
            [
                'key' => 'max_input_time',
                'value' => (string) ini_get('max_input_time'),
                'note' => __('So‘rovni (fayl yuklashni) o‘qish uchun berilgan vaqt.'),
            ],
            [
                'key' => 'max_file_uploads',
                'value' => (string) ini_get('max_file_uploads'),
                'note' => __('Bir so‘rovda nechta fayl.'),
            ],
        ];
    }

    /**
     * OPcache state — answers a separate open question: without sudo the
     * deploy cannot reload php-fpm, so whether new code takes effect at all
     * depends on validate_timestamps.
     *
     * @return array<int, array{key: string, value: string, note: string}>
     */
    public function opcache(): array
    {
        if (! function_exists('opcache_get_configuration')) {
            return [[
                'key' => 'opcache',
                'value' => __('O‘chirilgan'),
                'note' => __('OPcache yo‘q — eski kod xavfi ham yo‘q, php-fpm reload kerak emas.'),
            ]];
        }

        $directives = opcache_get_configuration()['directives'] ?? [];
        $enabled = (bool) ($directives['opcache.enable'] ?? false);
        $validate = $directives['opcache.validate_timestamps'] ?? null;

        return [
            [
                'key' => 'opcache.enable',
                'value' => $enabled ? __('Yoqilgan') : __('O‘chirilgan'),
                'note' => '',
            ],
            [
                'key' => 'opcache.validate_timestamps',
                'value' => $validate ? __('Ha') : __('Yo‘q'),
                'note' => $validate
                    ? __('Yaxshi: PHP fayl o‘zgarganini o‘zi sezadi, deploy’dan keyin php-fpm reload SHART EMAS.')
                    : __('Diqqat: yangi kod php-fpm qayta yuklanmaguncha ishlamaydi. Sizda sudo yo‘q — hosting panelidan “Restart PHP” kerak bo‘ladi.'),
            ],
            [
                'key' => 'opcache.revalidate_freq',
                'value' => (string) ($directives['opcache.revalidate_freq'] ?? '—'),
                'note' => __('Soniya: fayl o‘zgarganini qayta tekshirish oralig‘i.'),
            ],
        ];
    }

    /**
     * The largest upload this server will actually accept, in bytes.
     *
     * Public and static because validation rules read it too: a `max:` rule
     * that promises more than this cannot protect anything — PHP rejects the
     * request before Laravel is reached, so the visitor gets a raw 503 from
     * the web server instead of the rule's friendly message. Deriving the rule
     * from here keeps the two from drifting apart when a host changes a limit.
     */
    public static function effectiveUploadBytes(): int
    {
        return self::effectiveLimit([
            self::toBytes((string) ini_get('upload_max_filesize')),
            self::toBytes((string) ini_get('post_max_size')),
        ]);
    }

    /**
     * The `max:` value (in kilobytes, as Laravel's rule expects) for a file
     * field that is uploaded in ONE request: the smaller of the form's own
     * ceiling and what the server will physically accept.
     *
     * Promising more than the server takes is not a harmless overestimate.
     * PHP discards an oversized request before Laravel runs, so the librarian
     * gets a bare 503 from the web server with nothing in any log, instead of
     * the rule's "file too big" message. That is precisely how the reader
     * import failed on production, where upload_max_filesize was 2M while the
     * rule advertised 100M.
     *
     * Fields that go through ChunkedUploadService must NOT use this — their
     * file never travels as a single request, so the server's per-request
     * limit does not apply to them and clamping would cost real capability.
     *
     * @param  int  $appCapKilobytes  what this form allows regardless of the host
     */
    public static function uploadMaxKilobytes(int $appCapKilobytes): int
    {
        $serverBytes = self::effectiveUploadBytes();

        if ($serverBytes <= 0) {
            return $appCapKilobytes; // unlimited server — the form's own cap stands
        }

        return min($appCapKilobytes, intdiv($serverBytes, 1024));
    }

    /**
     * The practical verdict: what the server really accepts, versus what the
     * application's own validation promises. A rule allowing more than the
     * server does is not a safety net — the request dies before Laravel ever
     * sees it, which is how a friendly "fayl juda katta" turns into a raw 503.
     *
     * @return array{effective_upload: int, app_allows: int, mismatch: bool, memory: int, sapi: string}
     */
    public function verdict(): array
    {
        $effective = self::effectiveUploadBytes();

        return [
            'effective_upload' => $effective,
            'app_allows' => self::appMaxUploadBytes(),
            'mismatch' => $effective > 0 && self::appMaxUploadBytes() > $effective,
            'memory' => self::toBytes((string) ini_get('memory_limit')),
            'sapi' => PHP_SAPI,
        ];
    }

    /**
     * The real ceiling when several limits apply at once: the smallest of
     * them. An unlimited value (-1) is not a ceiling and must be ignored
     * rather than compared — a naive min() would otherwise report the least
     * restrictive setting as the most restrictive one. All-unlimited stays
     * unlimited.
     *
     * @param  array<int, int>  $limits  byte values, -1 for unlimited
     */
    public static function effectiveLimit(array $limits): int
    {
        $bounded = array_filter($limits, static fn (int $v): bool => $v > 0);

        return $bounded === [] ? -1 : min($bounded);
    }

    /**
     * What the reader-import FormRequest itself allows, read from its own
     * `max:` rule so this page can't drift out of step with it.
     */
    private static function appMaxUploadBytes(): int
    {
        $rules = (new ImportReadersRequest)->rules()['file'] ?? [];

        foreach ((array) $rules as $rule) {
            if (is_string($rule) && str_starts_with($rule, 'max:')) {
                return (int) substr($rule, 4) * 1024; // Laravel's `max:` is in kilobytes
            }
        }

        return 0;
    }
}
