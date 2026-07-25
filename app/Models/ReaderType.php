<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReaderType extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'is_student', 'certificate_color'];

    protected function casts(): array
    {
        return [
            'is_student' => 'boolean',
        ];
    }

    public function readers(): HasMany
    {
        return $this->hasMany(Reader::class);
    }
}
