<?php

namespace App\Services;

use App\Enums\Gender;
use App\Models\Reader;
use App\Models\ReaderType;
use Illuminate\Support\Carbon;

/**
 * Reader demographic breakdowns (gender / age / reader type) — shared by the
 * admin dashboard and the public "Statistika" page, so both stay in sync.
 */
class ReaderStatsService
{
    /** Age buckets in display order — internal to this breakdown, not a shared domain concept. */
    private const AGE_BUCKETS = [
        ['max' => 17, 'label' => '<18'],
        ['max' => 25, 'label' => '18-25'],
        ['max' => 35, 'label' => '26-35'],
        ['max' => 45, 'label' => '36-45'],
        ['max' => 60, 'label' => '46-60'],
        ['max' => null, 'label' => '60+'],
    ];

    /**
     * Reader count per gender, label => count. Only Erkak/Ayol — readers with
     * no gender on file are simply excluded, not bucketed as "Noma'lum".
     *
     * @return array<string, int>
     */
    public function byGender(): array
    {
        $counts = Reader::query()->selectRaw('gender, COUNT(*) as c')->groupBy('gender')->pluck('c', 'gender');

        $result = [];
        foreach (Gender::cases() as $gender) {
            $c = (int) ($counts[$gender->value] ?? 0);
            if ($c > 0) {
                $result[$gender->label()] = $c;
            }
        }

        return $result;
    }

    /**
     * Reader count per age bucket, label => count, in bucket order. Readers
     * without a birth date are bucketed under "Noma'lum" at the end.
     *
     * @return array<string, int>
     */
    public function byAgeGroup(): array
    {
        $result = array_fill_keys(array_column(self::AGE_BUCKETS, 'label'), 0);

        Reader::query()->whereNotNull('birth_date')->pluck('birth_date')->each(function ($birthDate) use (&$result): void {
            $age = Carbon::parse($birthDate)->age;
            foreach (self::AGE_BUCKETS as $bucket) {
                if ($bucket['max'] === null || $age <= $bucket['max']) {
                    $result[$bucket['label']]++;
                    break;
                }
            }
        });

        $result = array_filter($result);

        $unknown = Reader::query()->whereNull('birth_date')->count();
        if ($unknown > 0) {
            $result[__('Noma’lum')] = $unknown;
        }

        return $result;
    }

    /**
     * Reader count per reader type, label => count. Types with zero readers
     * are excluded.
     *
     * @return array<string, int>
     */
    public function byType(): array
    {
        $counts = Reader::query()->selectRaw('reader_type_id, COUNT(*) as c')->groupBy('reader_type_id')->pluck('c', 'reader_type_id');

        $result = [];
        foreach (ReaderType::query()->orderBy('id')->get() as $type) {
            $c = (int) ($counts[$type->id] ?? 0);
            if ($c > 0) {
                $result[$type->name] = $c;
            }
        }

        return $result;
    }
}
