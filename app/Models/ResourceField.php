<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class ResourceField extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = ['name'];

    /** @var array<int, string> */
    public array $translatable = ['name'];

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }
}
