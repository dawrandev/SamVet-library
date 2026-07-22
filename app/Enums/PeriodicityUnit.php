<?php

namespace App\Enums;

/**
 * The base time unit a journal/newspaper's periodicity is expressed in.
 * Paired with an interval ("necha birlikda") and a count ("necha marta") on
 * the Journal model so any real schedule can be expressed dynamically —
 * "2 haftada 3 marta", "3 oyda bir" — not just a fixed list of named
 * frequencies (which could not represent every N-units/M-times combination).
 */
enum PeriodicityUnit: string
{
    case Day = 'day';
    case Week = 'week';
    case Month = 'month';
    case Year = 'year';
    case Irregular = 'irregular';

    public function label(): string
    {
        return match ($this) {
            self::Day => __('Kun'),
            self::Week => __('Hafta'),
            self::Month => __('Oy'),
            self::Year => __('Yil'),
            self::Irregular => __('Muntazam emas'),
        };
    }

    /** Locative form used when composing a sentence, e.g. "2 haftada 3 marta". */
    public function locative(): string
    {
        return match ($this) {
            self::Day => __('kunda'),
            self::Week => __('haftada'),
            self::Month => __('oyda'),
            self::Year => __('yilda'),
            self::Irregular => '',
        };
    }

    /** Named singular form for the common "once every 1 unit" case, e.g. "Haftalik". */
    public function singularLabel(): string
    {
        return match ($this) {
            self::Day => __('Kunlik'),
            self::Week => __('Haftalik'),
            self::Month => __('Oylik'),
            self::Year => __('Yillik'),
            self::Irregular => __('Muntazam emas'),
        };
    }
}
