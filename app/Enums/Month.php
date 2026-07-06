<?php

namespace App\Enums;

/**
 * Calendar month (1..12) — used for subscription periods.
 */
enum Month: int
{
    case January = 1;
    case February = 2;
    case March = 3;
    case April = 4;
    case May = 5;
    case June = 6;
    case July = 7;
    case August = 8;
    case September = 9;
    case October = 10;
    case November = 11;
    case December = 12;

    public function label(): string
    {
        return match ($this) {
            self::January => __('Yanvar'),
            self::February => __('Fevral'),
            self::March => __('Mart'),
            self::April => __('Aprel'),
            self::May => __('May'),
            self::June => __('Iyun'),
            self::July => __('Iyul'),
            self::August => __('Avgust'),
            self::September => __('Sentabr'),
            self::October => __('Oktabr'),
            self::November => __('Noyabr'),
            self::December => __('Dekabr'),
        };
    }
}
