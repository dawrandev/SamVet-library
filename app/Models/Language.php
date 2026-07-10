<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class Language extends Model
{
    use HasFactory, HasTranslations;

    /** `locale` maps this language to a translation key (uz/ru/kk); null = unknown. */
    protected $fillable = ['name', 'locale'];

    /** @var array<int, string> */
    public array $translatable = ['name'];

    public function books(): HasMany
    {
        return $this->hasMany(Book::class);
    }
}
