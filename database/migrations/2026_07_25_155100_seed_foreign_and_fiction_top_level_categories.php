<?php

use App\Models\Category;
use Illuminate\Database\Migrations\Migration;

/**
 * Adds the two top-level categories the librarian needs for the dashboard's
 * literature-type breakdown — "Xorijiy adabiyotlar" and "Badiiy adabiyotlar"
 * didn't exist alongside the seeded "O‘quv-uslubiy adabiyotlar" / "Ilmiy
 * adabiyotlar" — so the chart has real buckets to count books into.
 */
return new class extends Migration
{
    public function up(): void
    {
        $names = [
            ['uz' => 'Xorijiy adabiyotlar', 'ru' => 'Зарубежная литература', 'kk' => 'Shet el ádebiyatlar'],
            ['uz' => 'Badiiy adabiyotlar', 'ru' => 'Художественная литература', 'kk' => 'Kórkem ádebiyat'],
        ];

        foreach ($names as $name) {
            $exists = Category::where('name->uz', $name['uz'])->whereNull('parent_id')->exists();

            if (! $exists) {
                Category::create(['name' => $name, 'parent_id' => null]);
            }
        }
    }

    public function down(): void
    {
        Category::whereIn('name->uz', ['Xorijiy adabiyotlar', 'Badiiy adabiyotlar'])
            ->whereNull('parent_id')
            ->delete();
    }
};
