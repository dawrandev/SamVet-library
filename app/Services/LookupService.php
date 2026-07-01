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
     * @return list<string>
     */
    public static function types(): array
    {
        return array_keys(self::MAP);
    }

    /**
     * @param  array{name: string, parent_id?: int|null}  $data
     * @return array{id: int, name: string}
     */
    public function create(string $type, array $data): array
    {
        /** @var class-string<\Illuminate\Database\Eloquent\Model> $model */
        $model = self::MAP[$type];

        $attributes = ['name' => trim($data['name'])];

        // Faqat kategoriya ota-ona qabul qiladi
        if ($type === 'category' && ! empty($data['parent_id'])) {
            $attributes['parent_id'] = $data['parent_id'];
        }

        $record = $model::create($attributes);

        return ['id' => $record->id, 'name' => $record->name];
    }
}
