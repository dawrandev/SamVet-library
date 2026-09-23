<?php

namespace App\Support\ReaderCard;

use FontLib\Font;

/**
 * Picks the largest font size at which a line of text fits a given width.
 *
 * The card places names and the ID in fixed boxes, as the design does. A long
 * patronymic or place name would otherwise wrap into the field below it or run
 * off the edge. Widths come from the TTF's own advance table, which is exactly
 * what dompdf lays out with — it applies no kerning — so a size chosen here is
 * the size that fits there, not an estimate.
 */
final class TextFitter
{
    /** @var array<string, array{widths: array<int, int>, cmap: array<int, int>, upm: int}> */
    private static array $metrics = [];

    public function fit(string $text, string $fontFile, float $maxWidth, int $maxSize, int $minSize, float $letterSpacingEm = 0.0): int
    {
        $em = $this->widthInEm($text, $fontFile, $letterSpacingEm);

        if ($em <= 0.0) {
            return $maxSize;
        }

        return max($minSize, min($maxSize, (int) floor($maxWidth / $em)));
    }

    /** Width of $text at a font size of 1px, letter-spacing included. */
    public function widthInEm(string $text, string $fontFile, float $letterSpacingEm = 0.0): float
    {
        $metrics = self::$metrics[$fontFile] ??= $this->load($fontFile);
        $units = 0;
        $chars = mb_str_split($text);

        foreach ($chars as $char) {
            $glyph = $metrics['cmap'][mb_ord($char)] ?? 0;
            $units += $metrics['widths'][$glyph] ?? 0;
        }

        // Spacing counted after every character, the last one included: a
        // slight overestimate is a font a pixel smaller, never an overflow.
        return $units / $metrics['upm'] + $letterSpacingEm * count($chars);
    }

    /** @return array{widths: array<int, int>, cmap: array<int, int>, upm: int} */
    private function load(string $fontFile): array
    {
        $font = Font::load($fontFile);
        $font->parse();

        $widths = [];

        foreach ((array) $font->getData('hmtx') as $glyph => $entry) {
            $widths[$glyph] = (int) (is_array($entry) ? $entry[0] : $entry);
        }

        $metrics = [
            'widths' => $widths,
            'cmap' => $font->getUnicodeCharMap(),
            'upm' => (int) $font->getData('head', 'unitsPerEm'),
        ];

        $font->close();

        return $metrics;
    }
}
