<?php

namespace Database\Seeders;

use App\Models\NewsCategory;
use Illuminate\Database\Seeder;

class NewsCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['uz' => 'E‘lonlar', 'ru' => 'Объявления', 'kk' => 'Járiyalawlar'],
            ['uz' => 'Tadbirlar', 'ru' => 'Мероприятия', 'kk' => 'Ilajlar'],
            ['uz' => 'Tanlovlar', 'ru' => 'Конкурсы', 'kk' => 'Tańlawlar'],
        ];

        foreach ($categories as $name) {
            if (! NewsCategory::where('name->uz', $name['uz'])->exists()) {
                NewsCategory::create(['name' => $name]);
            }
        }
    }
}
