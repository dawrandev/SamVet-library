<?php

namespace App\Enums;

/**
 * Hardware type of a computer in the electronic reading room.
 */
enum ComputerType: string
{
    case Monoblock = 'monoblock';
    case Desktop = 'desktop';
    case Laptop = 'laptop';
    case Monitor = 'monitor';

    public function label(): string
    {
        return match ($this) {
            self::Monoblock => __('Monoblok'),
            self::Desktop => __('Desktop'),
            self::Laptop => __('Noutbuk'),
            self::Monitor => __('Monitor'),
        };
    }
}
