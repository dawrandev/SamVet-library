<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * University staff member who subscribes to periodicals.
 * Separate from library readers.
 */
class Subscriber extends Model
{
    use HasFactory;

    protected $fillable = [
        'full_name', 'position', 'department', 'phone',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
