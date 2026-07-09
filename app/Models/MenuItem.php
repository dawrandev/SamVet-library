<?php

namespace App\Models;

use App\Enums\MenuItemType;
use App\Observers\MenuItemObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Translatable\HasTranslations;

#[ObservedBy([MenuItemObserver::class])]
class MenuItem extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = [
        'parent_id',
        'title',
        'url',
        'type',
        'sort_order',
        'is_active',
        'target_blank',
    ];

    /** @var array<int, string> */
    public array $translatable = ['title'];

    protected function casts(): array
    {
        return [
            'type' => MenuItemType::class,
            'is_active' => 'boolean',
            'target_blank' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    // --- Relationships ---

    public function parent(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'parent_id')->orderBy('sort_order');
    }

    public function page(): HasOne
    {
        return $this->hasOne(Page::class);
    }

    /**
     * Public-site URL for this menu item, based on its type:
     * external → raw url, module → a named route (url = route name),
     * page/dropdown → the content page renderer.
     */
    public function publicUrl(): string
    {
        return match ($this->type) {
            MenuItemType::External => $this->url ?: '#',
            MenuItemType::Module => $this->url && \Illuminate\Support\Facades\Route::has($this->url)
                ? route($this->url)
                : '#',
            default => route('page.show', $this->id),
        };
    }
}
