<?php

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    public function run(): void
    {
        $languages = [
            ['uz' => 'O‘zbek', 'ru' => 'Узбекский', 'kk' => 'Ózbek'],
            ['uz' => 'Rus', 'ru' => 'Русский', 'kk' => 'Rus'],
            ['uz' => 'Ingliz', 'ru' => 'Английский', 'kk' => 'Ingliz'],
            ['uz' => 'Qoraqalpoq', 'ru' => 'Каракалпакский', 'kk' => 'Qaraqalpaq'],
        ];

        foreach ($languages as $name) {
            if (! Language::where('name->uz', $name['uz'])->exists()) {
                Language::create(['name' => $name]);
            }
        }
    }
}
