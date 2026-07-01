<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Asar — bir asarning turli tildagi nashrlari (books) guruhi.
 */
class Work extends Model
{
    protected $fillable = [];

    /**
     * Shu asarning barcha nashrlari (turli tilda).
     */
    public function editions(): HasMany
    {
        return $this->hasMany(Book::class);
    }
}
