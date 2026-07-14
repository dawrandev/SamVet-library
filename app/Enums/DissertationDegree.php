<?php

namespace App\Enums;

/**
 * Academic degree a dissertation (and its avtoreferat) was defended for.
 */
enum DissertationDegree: string
{
    case Phd = 'phd';
    case Dsc = 'dsc';

    public function label(): string
    {
        return match ($this) {
            self::Phd => __('Falsafa doktori (PhD)'),
            self::Dsc => __('Fan doktori (DSc)'),
        };
    }
}
