<?php

namespace App\Enums;

/**
 * Book lending (circulation) status.
 */
enum LoanStatus: string
{
    case OnLoan = 'on_loan';    // on loan (not returned)
    case Returned = 'returned'; // returned
    case Lost = 'lost';         // lost

    public function label(): string
    {
        return match ($this) {
            self::OnLoan => __('Berilgan'),
            self::Returned => __('Qaytarilgan'),
            self::Lost => __('Yo‘qotilgan'),
        };
    }
}
