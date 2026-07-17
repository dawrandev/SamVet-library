<?php

namespace App\Services;

use App\Models\Author;
use App\Models\BookType;
use App\Models\Category;
use App\Models\ContributorRole;
use App\Models\JournalType;
use App\Models\Language;
use App\Models\Location;
use App\Models\NewsCategory;
use App\Models\PublicationPlace;
use App\Models\ResourceField;

/**
 * Creates a lookup (type, language, publication place, author, category, location)
 * "on the fly" from a form. Security: only whitelisted types.
 */
class LookupService
{
    /** @var array<string, class-string> */
    private const MAP = [
        'book_type' => BookType::class,
        'journal_type' => JournalType::class,
        'language' => Language::class,
        'publication_place' => PublicationPlace::class,
        'author' => Author::class,
        'contributor_role' => ContributorRole::class,
        'category' => Category::class,
        'location' => Location::class,
        'news_category' => NewsCategory::class,
        'resource_field' => ResourceField::class,
    ];

    /**
     * Translatable (spatie HasTranslations) lookup types — store a 3-language name.
     *
     * @var list<string>
     */
    private const TRANSLATABLE = ['book_type', 'journal_type', 'language', 'publication_place', 'category', 'location', 'news_category', 'resource_field'];

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
            // In spatie we pass an ['uz'=>, 'ru'=>, 'kk'=>] array to the name column
            $attributes = ['name' => array_map(
                static fn (string $v): string => trim($v),
                (array) $data['name'],
            )];
        } else {
            $attributes = ['name' => trim((string) $data['name'])];
        }

        // Only a category accepts a parent
        if ($type === 'category' && ! empty($data['parent_id'])) {
            $attributes['parent_id'] = $data['parent_id'];
        }

        $record = $model::create($attributes);

        // For a translatable type we return the label in the current locale (getAttribute is automatic)
        return ['id' => $record->id, 'name' => (string) $record->name];
    }
}
