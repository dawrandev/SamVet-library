<?php

namespace App\Models;

use App\Enums\CatalogResourceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/** One online read/watch/listen of a resource by a signed-in reader — an append-only log, never updated. */
class OnlineRead extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['reader_id', 'readable_type', 'readable_id', 'read_at'];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function reader(): BelongsTo
    {
        return $this->belongsTo(Reader::class);
    }

    /** The Book, Video, Audiobook, Dissertation or Avtoreferat that was read. */
    public function readable(): MorphTo
    {
        return $this->morphTo();
    }

    /** "Kitob" / "Video" / "Audiokitob" / "Dissertatsiya" / "Avtoreferat" — null if the resource type isn't mapped (shouldn't happen). */
    public function typeLabel(): ?string
    {
        return CatalogResourceType::tryFrom($this->readable_type)?->label();
    }

    /** The read resource's display title — "name" on media types, "title" on the rest. */
    public function readableTitle(): ?string
    {
        $type = CatalogResourceType::tryFrom($this->readable_type);

        return $this->readable?->{$type?->titleColumn() ?? 'title'};
    }

    /** Admin "show" page for the read resource, or null once it's been deleted. */
    public function adminUrl(): ?string
    {
        $type = CatalogResourceType::tryFrom($this->readable_type);

        if ($type === null || $this->readable === null) {
            return null;
        }

        return route("admin.{$this->adminRouteSegment($type)}.show", $this->readable);
    }

    private function adminRouteSegment(CatalogResourceType $type): string
    {
        return match ($type) {
            CatalogResourceType::Book => 'books',
            CatalogResourceType::Video => 'videos',
            CatalogResourceType::Audiobook => 'audiobooks',
            CatalogResourceType::Dissertation => 'dissertations',
            CatalogResourceType::Avtoreferat => 'avtoreferats',
        };
    }
}
