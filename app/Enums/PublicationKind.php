<?php

namespace App\Enums;

/**
 * Publication kind of a periodical — journal or newspaper.
 */
enum PublicationKind: string
{
    case Journal = 'journal';
    case Newspaper = 'newspaper';

    public function label(): string
    {
        return match ($this) {
            self::Journal => __('Jurnal'),
            self::Newspaper => __('Gazeta'),
        };
    }
}
