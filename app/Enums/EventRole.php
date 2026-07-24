<?php

namespace App\Enums;

/**
 * The reader's purpose/role of participation in the event.
 */
enum EventRole: string
{
    case Participant = 'participant'; // Participant
    case Host = 'host';               // Host
    case Spectator = 'spectator';     // Spectator
    case Jury = 'jury';               // Jury
    case Guest = 'guest';             // Guest

    public function label(): string
    {
        return match ($this) {
            self::Participant => __('Ishtirokchi'),
            self::Host => __('Boshlovchi'),
            self::Spectator => __('Tomoshabin'),
            self::Jury => __('Hakam'),
            self::Guest => __('Mehmon'),
        };
    }
}
