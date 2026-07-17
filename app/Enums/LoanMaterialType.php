<?php

namespace App\Enums;

/**
 * What kind of material a loan is for — derived from the loanable copy
 * (BookCopy, or a JournalCopy whose issue's journal is a journal/newspaper).
 * Not stored — purely a display/filter value.
 */
enum LoanMaterialType: string
{
    case Book = 'book';
    case Newspaper = 'newspaper';
    case Journal = 'journal';

    public function label(): string
    {
        return match ($this) {
            self::Book => __('Kitob'),
            self::Newspaper => __('Gazeta'),
            self::Journal => __('Jurnal'),
        };
    }
}
