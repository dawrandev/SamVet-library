<?php

namespace App\Models;

use App\Observers\NewsObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

#[ObservedBy([NewsObserver::class])]
class News extends Model
{
    use HasFactory, HasTranslations;

    protected $table = 'news';

    protected $fillable = [
        'news_category_id',
        'title',
        'excerpt',
        'body',
        'slug',
        'cover_image',
        'published_at',
        'views_count',
    ];

    /** @var array<int, string> */
    public array $translatable = ['title', 'excerpt', 'body'];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'views_count' => 'integer',
        ];
    }

    // --- Relationships ---

    public function category(): BelongsTo
    {
        return $this->belongsTo(NewsCategory::class, 'news_category_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(NewsImage::class)->orderBy('sort_order');
    }
}
