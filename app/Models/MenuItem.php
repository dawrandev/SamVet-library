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
use Illuminate\Support\Facades\Route;
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

    /** Active children only, ordered — for the public navbar. */
    public function activeChildren(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'parent_id')
            ->where('is_active', true)
            ->orderBy('sort_order');
    }

    /**
     * Active children eager-loaded recursively (arbitrary depth), so the public
     * navbar can render nested submenus. Loading stops when a level has none.
     */
    public function activeChildrenRecursive(): HasMany
    {
        return $this->activeChildren()->with('activeChildrenRecursive');
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
            MenuItemType::Module => $this->moduleUrl(),
            default => route('page.show', $this->id),
        };
    }

    /**
     * A module item stores a route NAME, but the admin form asks for a
     * "Havola" and offers "/katalog" as its example — so a path is what gets
     * typed, and used to resolve to a silent "#" that looked like a bug in the
     * page rather than a wrong value in the field. Both spellings are accepted
     * now; a route name still wins, so nothing that already worked changes.
     */
    private function moduleUrl(): string
    {
        if (! $this->url) {
            return '#';
        }

        if (Route::has($this->url)) {
            return route($this->url);
        }

        return str_starts_with($this->url, '/') ? $this->url : '#';
    }
}
