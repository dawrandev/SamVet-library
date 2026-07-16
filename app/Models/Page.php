<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class Page extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = [
        'menu_item_id',
        'title',
        'body',
        'cover_image',
    ];

    /** @var array<int, string> */
    public array $translatable = ['title', 'body'];

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(PageImage::class)->orderBy('sort_order');
    }
}
