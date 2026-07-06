<?php

namespace App\Enums;

/**
 * News status (publication stage).
 */
enum NewsStatus: string
{
    case Draft = 'draft';
    case Published = 'published';

    public function label(): string
    {
        return match ($this) {
            self::Draft => __('Qoralama'),
            self::Published => __('E‘lon qilingan'),
        };
    }
}
