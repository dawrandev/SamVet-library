<?php

namespace App\Enums;

/**
 * Reader tadbirдаги qatnashish maqsadi/roli.
 */
enum EventRole: string
{
    case Participant = 'participant'; // Ishtirokchi
    case Host = 'host';               // Boshlovchi
    case Spectator = 'spectator';     // Tomoshabin
    case Jury = 'jury';               // Juri

    public function label(): string
    {
        return match ($this) {
            self::Participant => __('Ishtirokchi'),
            self::Host => __('Boshlovchi'),
            self::Spectator => __('Tomoshabin'),
            self::Jury => __('Juri'),
        };
    }
}
