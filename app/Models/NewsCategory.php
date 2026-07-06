<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class NewsCategory extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = ['name'];

    /** @var array<int, string> */
    public array $translatable = ['name'];

    public function news(): HasMany
    {
        return $this->hasMany(News::class);
    }
}
