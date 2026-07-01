<?php

namespace App\Enums;

/**
 * Nusxaning mavjudlik (hayotiy) holati.
 * Kelajakda 'borrowed' (talabada) qo'shilishi mumkin — loans moduli.
 */
enum CopyStatus: string
{
    case Available = 'available';
    case Lost = 'lost';
    case WrittenOff = 'written_off';

    public function label(): string
    {
        return match ($this) {
            self::Available => __('Mavjud'),
            self::Lost => __('Yo‘qotilgan'),
            self::WrittenOff => __('Hisobdan chiqarilgan'),
        };
    }
}
