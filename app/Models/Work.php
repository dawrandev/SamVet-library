<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Work — a group of editions (books) of a single work in different languages.
 */
class Work extends Model
{
    protected $fillable = [];

    /**
     * All editions of this work (in different languages).
     */
    public function editions(): HasMany
    {
        return $this->hasMany(Book::class);
    }
}
