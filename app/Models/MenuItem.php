<?php

namespace App\Models;

use App\Observers\MenuItemObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

#[ObservedBy([MenuItemObserver::class])]
class MenuItem extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = [
        'parent_id',
        'title',
        'url',
        'sort_order',
        'is_active',
        'target_blank',
    ];

    /** @var array<int, string> */
    public array $translatable = ['title'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'target_blank' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    // --- Bog'lanishlar ---

    public function parent(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'parent_id')->orderBy('sort_order');
    }
}
