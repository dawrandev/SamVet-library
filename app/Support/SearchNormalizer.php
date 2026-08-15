<?php

namespace App\Support;

/**
 * Folds every script this catalog is written in (Latin Uzbek, Cyrillic Uzbek,
 * Russian) down to a single canonical ASCII-Latin form, so a reader typing
 * "ветеринария" and one typing "veterinariya" both hit the same indexed text.
 *
 * Both sides go through here: the stored text (once, at save time, into the
 * `search_text`/`title_normalized` columns) and the incoming query (on every
 * search). Matching then happens entirely in normalized space, which is why
 * cross-script search needs no per-script duplicate columns or query rewriting.
 *
 * Scope, stated honestly: this is TRANSLITERATION, not translation. It folds
 * scripts, so Cyrillic "ветеринария" reaches the Latin-script record and vice
 * versa — which covers the international/loan vocabulary that dominates a
 * veterinary fund (veterinariya, biologiya, anatomiya, fiziologiya, ...).
 * It cannot bridge genuinely different words across languages: Russian
 * "Экономическая теория" will never normalize onto Uzbek "Iqtisodiyot
 * nazariyasi". Linking those is what books.work_id (editions of one work in
 * several languages) is for, and that depends on cataloguer data entry, not
 * on search.
 *
 * Residual one- and two-character spelling gaps this deliberately does NOT
 * try to fold (Uzbek "х"/"ҳ" vs Russian "х", "ts"/"s" for ц, word-initial
 * "е"/"ye") are left to SearchSuggestionService's edit-distance pass, which
 * runs over this same normalized vocabulary. Folding them here instead would
 * cost precision on every search to fix a case a distance-1 correction
 * already handles.
 */
final class SearchNormalizer
{
    /**
     * Every apostrophe-like character Uzbek Latin text arrives with — the
     * proper ʻ/ʼ (U+02BB/U+02BC), the typographic ‘/’, a plain ASCII quote,
     * and the backtick/acute people type when their keyboard has neither.
     * All are dropped outright rather than mapped to one character, so
     * "o‘quv", "oʻquv", "o'quv" and "oquv" collapse together — and so do
     * their Cyrillic counterparts, since ў/ғ below transliterate bare.
     */
    private const APOSTROPHES = ['‘', '’', 'ʻ', 'ʼ', '`', '´', '′', '‛', "'"];

    /**
     * Cyrillic → Latin, using the standard Uzbek correspondence. Uzbek-only
     * letters (ў қ ғ ҳ) and Russian-only ones (ы ь щ ъ) are both covered, so
     * one table serves Uzbek Cyrillic and Russian alike.
     *
     * ў→o and ғ→g intentionally drop the apostrophe that formal Uzbek Latin
     * writes (o‘, g‘) — APOSTROPHES above strips it from the Latin side too,
     * so both scripts land on the same bare letter.
     *
     * @var array<string, string>
     */
    private const CYRILLIC = [
        'а' => 'a',  'б' => 'b',  'в' => 'v',  'г' => 'g',  'д' => 'd',
        'е' => 'e',  'ё' => 'yo', 'ж' => 'j',  'з' => 'z',  'и' => 'i',
        'й' => 'y',  'к' => 'k',  'л' => 'l',  'м' => 'm',  'н' => 'n',
        'о' => 'o',  'п' => 'p',  'р' => 'r',  'с' => 's',  'т' => 't',
        'у' => 'u',  'ф' => 'f',  'х' => 'x',  'ц' => 'ts', 'ч' => 'ch',
        'ш' => 'sh', 'щ' => 'sh', 'ъ' => '',   'ы' => 'i',  'ь' => '',
        'э' => 'e',  'ю' => 'yu', 'я' => 'ya',
        // Uzbek/Karakalpak-specific
        'ў' => 'o',  'қ' => 'q',  'ғ' => 'g',  'ҳ' => 'h',
        // Karakalpak Cyrillic extras
        'ә' => 'a',  'ө' => 'o',  'ү' => 'u',  'ң' => 'n',
    ];

    /**
     * Canonical form of a single piece of text: lowercased, transliterated to
     * Latin, apostrophes removed, punctuation flattened to single spaces.
     * Returns '' for text that normalizes away to nothing.
     */
    public static function normalize(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = str_replace(self::APOSTROPHES, '', $text);
        $text = strtr($text, self::CYRILLIC);

        // Anything left that isn't a plain Latin letter or digit (punctuation,
        // stray Cyrillic outside the table, em dashes in titles) becomes a
        // separator — FULLTEXT tokenizes on those anyway, and it keeps the
        // edit-distance corpus free of punctuation-glued pseudo-words.
        $text = preg_replace('/[^a-z0-9]+/u', ' ', $text) ?? '';

        return trim(preg_replace('/\s+/', ' ', $text) ?? '');
    }

    /**
     * Normalizes several fields into one indexable blob (the `search_text`
     * column). Null/empty parts are skipped; duplicate words are NOT removed,
     * since a word repeated across title and annotation is a genuine
     * relevance signal for FULLTEXT.
     *
     * @param  array<int, string|null>  $parts
     */
    public static function normalizeAll(array $parts): string
    {
        $normalized = array_filter(array_map(
            static fn (?string $part): string => $part === null ? '' : self::normalize($part),
            $parts
        ), static fn (string $part): bool => $part !== '');

        return implode(' ', $normalized);
    }

    /**
     * Distinct normalized words of at least $minLength characters — the
     * vocabulary edit-distance correction draws from.
     *
     * @return array<int, string>
     */
    public static function words(string $text, int $minLength = 1): array
    {
        $normalized = self::normalize($text);

        if ($normalized === '') {
            return [];
        }

        return array_values(array_filter(
            explode(' ', $normalized),
            static fn (string $word): bool => mb_strlen($word) >= $minLength
        ));
    }
}
