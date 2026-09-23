<?php

namespace App\Support\ReaderCard;

/**
 * Converts Figma text positions into the CSS dompdf needs to reproduce them.
 *
 * dompdf does not lay out line-height the way browsers do, and positioning
 * the card from Figma's numbers as-is put "ESLATMA!" half out of its bar and
 * ran the rules list into the warning box. What it actually does, read from
 * its frame tree (Inter, Roboto Mono, DejaVu and Helvetica, at 96/127/180 dpi,
 * px and unitless line-heights alike):
 *
 *   line box height = 1.1 x (ascender - descender) x line-height
 *   baseline        = 1.1 x ascender x line-height, from the line box's top
 *
 * with ascender/descender as fractions of the em. For Inter that makes a
 * 31px line-height space lines 41.3px apart. So this class works backwards
 * from the design: the line spacing Figma shows, and where its capitals start.
 */
final class Layout
{
    /** dompdf's own multiplier on every line box. */
    private const LINE_FACTOR = 1.1;

    /**
     * Per font, as fractions of the em: ascender and descender as dompdf reads
     * them (its .ufm metrics), cap height from the OS/2 table.
     */
    private const METRICS = [
        'inter' => ['ascender' => 0.969, 'descender' => 0.241, 'cap' => 0.7275],
        'mono' => ['ascender' => 1.048, 'descender' => 0.271, 'cap' => 0.711],
    ];

    /** The CSS line-height (px) that makes lines $spacing px apart. */
    public static function lineHeight(float $spacing, string $font = 'inter'): float
    {
        $m = self::METRICS[$font];

        return round($spacing / (self::LINE_FACTOR * ($m['ascender'] + $m['descender'])), 3);
    }

    /**
     * The `top` at which a block's first line has its capitals at $capTop.
     * $lineHeight is the CSS value; omitted, it is the font size — what the
     * `.line` class sets for single lines.
     */
    public static function top(float $capTop, float $fontSize, ?float $lineHeight = null, string $font = 'inter'): float
    {
        $m = self::METRICS[$font];

        return self::onBaseline($capTop + $m['cap'] * $fontSize, $fontSize, $lineHeight, $font);
    }

    /**
     * The `top` that puts a line's baseline at $baseline — for fields whose
     * size is fitted to the text, so a shrunken name still sits on the same
     * line, above the same divider.
     */
    public static function onBaseline(float $baseline, float $fontSize, ?float $lineHeight = null, string $font = 'inter'): float
    {
        $m = self::METRICS[$font];

        return round($baseline - self::LINE_FACTOR * $m['ascender'] * ($lineHeight ?? $fontSize), 1);
    }

    /** The `top` that centres a single line's capitals on $centerY. */
    public static function centered(float $centerY, float $fontSize, string $font = 'inter'): float
    {
        $m = self::METRICS[$font];

        return self::top($centerY - $m['cap'] * $fontSize / 2, $fontSize, null, $font);
    }
}
