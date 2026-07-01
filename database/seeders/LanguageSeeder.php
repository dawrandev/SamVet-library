<?php

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    public function run(): void
    {
        $languages = ['O‘zbek', 'Rus', 'Ingliz', 'Qoraqalpoq'];

        foreach ($languages as $name) {
            Language::firstOrCreate(['name' => $name]);
        }
    }
}
