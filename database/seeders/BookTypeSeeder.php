<?php

namespace Database\Seeders;

use App\Models\BookType;
use Illuminate\Database\Seeder;

class BookTypeSeeder extends Seeder
{
    public function run(): void
    {
        // [uz, ru, kk] translations (kk — placeholder, to be finalized later)
        $types = [
            ['uz' => 'Darslik', 'ru' => 'Учебник', 'kk' => 'Sabaqlıq'],
            ['uz' => 'O‘quv qo‘llanma', 'ru' => 'Учебное пособие', 'kk' => 'Oqıw qollanba'],
            ['uz' => 'Uslubiy qo‘llanma', 'ru' => 'Методическое пособие', 'kk' => 'Uslubiy qollanba'],
            ['uz' => 'Monografiya', 'ru' => 'Монография', 'kk' => 'Monografiya'],
            ['uz' => 'Dissertatsiya', 'ru' => 'Диссертация', 'kk' => 'Dissertatsiya'],
            ['uz' => 'To‘plam', 'ru' => 'Сборник', 'kk' => 'Jıynaq'],
        ];

        foreach ($types as $name) {
            if (! BookType::where('name->uz', $name['uz'])->exists()) {
                BookType::create(['name' => $name]);
            }
        }
    }
}
