<?php

namespace App\Models;

use App\Enums\EventType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'type', 'date', 'news_id', 'note'];

    protected function casts(): array
    {
        return [
            'type' => EventType::class,
            'date' => 'date',
        ];
    }

    public function news(): BelongsTo
    {
        return $this->belongsTo(News::class);
    }

    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(EventLocation::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(EventParticipant::class);
    }

    /**
     * The event's public link — always derived from its linked news post
     * (never typed by hand). Null when no news post is linked.
     */
    public function link(): ?string
    {
        return $this->news ? route('news.show', $this->news->slug) : null;
    }
}
