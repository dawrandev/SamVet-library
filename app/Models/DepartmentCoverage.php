<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

/**
 * A university department's fund coverage percentage — admin-managed,
 * shown on the public "Statistika" page as "Kafedralar kesimida
 * ta'minganlik darajasi".
 */
class DepartmentCoverage extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = ['name', 'percentage'];

    /** @var array<int, string> */
    public array $translatable = ['name'];

    protected function casts(): array
    {
        return [
            'percentage' => 'integer',
        ];
    }
}
