<?php

namespace App\Enums;

enum AdminActivityAction: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Deleted = 'deleted';

    public function label(): string
    {
        return match ($this) {
            self::Created => __('Yaratildi'),
            self::Updated => __('Yangilandi'),
            self::Deleted => __('O‘chirildi'),
        };
    }
}
