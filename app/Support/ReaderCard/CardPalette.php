<?php

namespace App\Support\ReaderCard;

/**
 * Every colour on the printed reader card, from the one accent the librarian
 * sets per reader type (reader_types.certificate_color).
 *
 * The Figma file draws five variants — Student, Workers, Magistr, Doktarant,
 * Basqalar — and their warning-box tints are hand-picked, not one formula:
 * Workers' box is a saturated #CFF7D3 where Student's is a soft #D9E5F7, and
 * only Student and Workers darken the "!" square away from the accent. So the
 * five are stored as measured, and anything else the librarian picks is
 * derived — the card still works, it just won't be a Figma variant.
 *
 * The warning text is derived in every case: the design's #022354 on Student
 * is its square's hue at 94% saturation and 17% lightness, which reproduces
 * that colour to within a shade and carries over to the rest.
 */
final readonly class CardPalette
{
    /** The design's "Basqalar" orange: any type the five variants do not name. */
    public const FALLBACK = '#C58A00';

    /** The panel ground the pattern sits on (Figma "Gray 6"). */
    public const GROUND = '#F2F2F2';

    /** Measured from the Figma variants: accent => [warning box, "!" square]. */
    private const VARIANTS = [
        '#349AED' => ['#D9E5F7', '#2E73DA'],  // Student
        '#27AE60' => ['#CFF7D3', '#219653'],  // Workers
        '#3F2B96' => ['#E0DAF5', '#3F2B96'],  // Magistr
        '#8B0000' => ['#F2D7D5', '#8B0000'],  // Doktarant
        '#C58A00' => ['#FFE89C', '#C58A00'],  // Basqalar
    ];

    private function __construct(
        public string $accent,
        public string $soft,
        public string $strong,
        public string $ink,
        public string $pattern,
    ) {}

    public static function fromAccent(?string $hex): self
    {
        $accent = self::format(self::parse($hex) ?? self::parse(self::FALLBACK));
        $rgb = self::parse($accent);

        [$soft, $strong] = self::VARIANTS[$accent] ?? [
            self::format(self::mix($rgb, [255, 255, 255], 0.19)),
            // Three of the five variants leave the square at the accent.
            $accent,
        ];

        return new self(
            accent: $accent,
            soft: $soft,
            strong: $strong,
            ink: self::darken($strong),
            // Pattern strokes, shown through the stencil's cut-outs.
            pattern: self::format(self::mix($rgb, self::parse(self::GROUND), 0.15)),
        );
    }

    /** @return array{int, int, int}|null */
    private static function parse(?string $hex): ?array
    {
        if ($hex === null || ! preg_match('/^#([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})$/i', trim($hex), $m)) {
            return null;
        }

        return [hexdec($m[1]), hexdec($m[2]), hexdec($m[3])];
    }

    /**
     * @param  array{int, int, int}  $color
     * @param  array{int, int, int}  $base
     * @return array{int, int, int}
     */
    private static function mix(array $color, array $base, float $weight): array
    {
        return array_map(
            static fn (int $c, int $b): int => (int) round($c * $weight + $b * (1 - $weight)),
            $color,
            $base,
        );
    }

    /** The colour's own hue, taken down to near-black for small print. */
    private static function darken(string $hex): string
    {
        [$r, $g, $b] = array_map(static fn (int $c): float => $c / 255, self::parse($hex));
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $delta = $max - $min;

        $hue = match (true) {
            $delta == 0.0 => 0.0,
            $max === $r => 60 * fmod(($g - $b) / $delta, 6),
            $max === $g => 60 * (($b - $r) / $delta + 2),
            default => 60 * (($r - $g) / $delta + 4),
        };

        return self::format(self::fromHsl($hue < 0 ? $hue + 360 : $hue, 0.94, 0.17));
    }

    /** @return array{int, int, int} */
    private static function fromHsl(float $hue, float $saturation, float $lightness): array
    {
        $c = (1 - abs(2 * $lightness - 1)) * $saturation;
        $x = $c * (1 - abs(fmod($hue / 60, 2) - 1));
        $m = $lightness - $c / 2;

        [$r, $g, $b] = match (true) {
            $hue < 60 => [$c, $x, 0],
            $hue < 120 => [$x, $c, 0],
            $hue < 180 => [0, $c, $x],
            $hue < 240 => [0, $x, $c],
            $hue < 300 => [$x, 0, $c],
            default => [$c, 0, $x],
        };

        return [
            (int) round(($r + $m) * 255),
            (int) round(($g + $m) * 255),
            (int) round(($b + $m) * 255),
        ];
    }

    /** @param array{int, int, int} $rgb */
    private static function format(array $rgb): string
    {
        return sprintf('#%02X%02X%02X', ...$rgb);
    }
}
