<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContributorRole extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function contributors(): HasMany
    {
        return $this->hasMany(Contributor::class);
    }
}
