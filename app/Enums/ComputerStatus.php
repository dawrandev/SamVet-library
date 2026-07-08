<?php

namespace App\Enums;

/**
 * Working condition of a computer.
 */
enum ComputerStatus: string
{
    case Working = 'working';
    case Broken = 'broken';
    case InRepair = 'in_repair';

    public function label(): string
    {
        return match ($this) {
            self::Working => __('Ishlayapti'),
            self::Broken => __('Nosoz'),
            self::InRepair => __('Ta’mirda'),
        };
    }

    /** Badge color keyword (success | error | warning). */
    public function color(): string
    {
        return match ($this) {
            self::Working => 'success',
            self::Broken => 'error',
            self::InRepair => 'warning',
        };
    }
}
