<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScienceField extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function dissertations(): HasMany
    {
        return $this->hasMany(Dissertation::class);
    }
}
