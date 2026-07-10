<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

/**
 * City a book or periodical was published in — a shared, translatable lookup.
 */
class PublicationPlace extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = ['name'];

    /** @var array<int, string> */
    public array $translatable = ['name'];

    public function books(): HasMany
    {
        return $this->hasMany(Book::class);
    }

    public function journals(): HasMany
    {
        return $this->hasMany(Journal::class);
    }
}
