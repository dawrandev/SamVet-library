<?php

namespace App\Enums;

/**
 * Kitob berish (oldi-berdi) holati.
 */
enum LoanStatus: string
{
    case OnLoan = 'on_loan';    // berilgan (qaytarilmagan)
    case Returned = 'returned'; // qaytarilgan
    case Lost = 'lost';         // yo'qotilgan

    public function label(): string
    {
        return match ($this) {
            self::OnLoan => __('Berilgan'),
            self::Returned => __('Qaytarilgan'),
            self::Lost => __('Yo‘qotilgan'),
        };
    }
}
