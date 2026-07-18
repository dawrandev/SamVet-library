<?php

namespace App\Enums;

/**
 * Academic type of a dissertation record — drives which field set (PhD/DSc's
 * science field + doctoral specialty, vs Magistrlik's master specialty) applies.
 * Kept separate from DissertationDegree (Avtoreferat-only) to avoid coupling.
 */
enum DissertationType: string
{
    case Phd = 'phd';
    case Dsc = 'dsc';
    case Master = 'master';

    public function label(): string
    {
        return match ($this) {
            self::Phd => __('Falsafa doktori (PhD)'),
            self::Dsc => __('Fan doktori (DSc)'),
            self::Master => __('Magistrlik dissertatsiyasi'),
        };
    }

    /** PhD and DSc share the same field set (Fan nomi + Ixtisoslik). */
    public function isDoctoral(): bool
    {
        return $this === self::Phd || $this === self::Dsc;
    }

    public function isMaster(): bool
    {
        return $this === self::Master;
    }
}
