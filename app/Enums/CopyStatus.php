<?php

namespace App\Enums;

/**
 * Availability (lifecycle) status of the copy.
 * 'borrowed' (on loan) may be added in the future — loans module.
 */
enum CopyStatus: string
{
    case Available = 'available';
    case Borrowed = 'borrowed';
    case Lost = 'lost';
    case WrittenOff = 'written_off';

    public function label(): string
    {
        return match ($this) {
            self::Available => __('Mavjud'),
            self::Borrowed => __('Berilgan'),
            self::Lost => __('Yo‘qotilgan'),
            self::WrittenOff => __('Hisobdan chiqarilgan'),
        };
    }
}
