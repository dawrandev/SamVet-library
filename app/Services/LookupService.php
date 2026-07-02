<?php

namespace App\Services;

use App\Models\Author;
use App\Models\BookType;
use App\Models\Category;
use App\Models\Language;
use App\Models\Location;
use App\Models\Publisher;

/**
 * Formadan turib lookup (tur, til, nashriyot, muallif, kategoriya, joylashuv)
 * "shu zahoti" yaratish. Xavfsizlik: faqat whitelist'dagi turlar.
 */
class LookupService
{
    /** @var array<string, class-string> */
    private const MAP = [
        'book_type' => BookType::class,
        'language' => Language::class,
        'publisher' => Publisher::class,
        'author' => Author::class,
        'category' => Category::class,
        'location' => Location::class,
    ];

    /**
     * Tarjimali (spatie HasTranslations) lookup turlari — 3 tilli nom saqlaydi.
     *
     * @var list<string>
     */
    private const TRANSLATABLE = ['book_type', 'language', 'category', 'location'];

    /**
     * @return list<string>
     */
    public static function types(): array
    {
        return array_keys(self::MAP);
    }

    public static function isTranslatable(string $type): bool
    {
        return in_array($type, self::TRANSLATABLE, true);
    }

    /**
     * @param  array{name: array<string, string>|string, parent_id?: int|null}  $data
     * @return array{id: int, name: string}
     */
    public function create(string $type, array $data): array
    {
        /** @var class-string<\Illuminate\Database\Eloquent\Model> $model */
        $model = self::MAP[$type];

        if (self::isTranslatable($type)) {
            // spatie'da name ustuniga ['uz'=>, 'ru'=>, 'kk'=>] massivini uzatamiz
            $attributes = ['name' => array_map(
                static fn (string $v): string => trim($v),
                (array) $data['name'],
            )];
        } else {
            $attributes = ['name' => trim((string) $data['name'])];
        }

        // Faqat kategoriya ota-ona qabul qiladi
        if ($type === 'category' && ! empty($data['parent_id'])) {
            $attributes['parent_id'] = $data['parent_id'];
        }

        $record = $model::create($attributes);

        // Tarjimali turda joriy locale bo'yicha label qaytaramiz (getAttribute avtomatik)
        return ['id' => $record->id, 'name' => (string) $record->name];
    }
}
