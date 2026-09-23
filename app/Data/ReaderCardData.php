<?php

namespace App\Data;

use App\Models\Reader;
use App\Support\ReaderCard\CardPalette;

/** Everything the reader card and formulyar templates render from. */
final readonly class ReaderCardData
{
    /**
     * @param  array<string, int>  $sizes  fitted font sizes, keyed by field
     */
    public function __construct(
        public Reader $reader,
        public CardPalette $palette,
        public string $fontDir,
        public string $logoPath,
        public string $stencilPath,
        public ?string $photoPath,
        public string $surname,
        public string $firstName,
        public string $patronymic,
        public string $idNumber,
        public array $sizes,
    ) {}
}
