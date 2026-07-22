<?php

namespace App\Models;

use App\Observers\AudiobookObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy([AudiobookObserver::class])]
class Audiobook extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'author', 'annotation', 'cover_image', 'views_count',
    ];

    protected function casts(): array
    {
        return [
            'views_count' => 'integer',
        ];
    }

    public function tracks(): HasMany
    {
        return $this->hasMany(AudioTrack::class)->orderBy('sort_order');
    }
}
