<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $locations = [
            ['uz' => 'Kitob berish bo‘limi', 'ru' => 'Отдел выдачи книг', 'kk' => 'Kitap beriw bólimi'],
            ['uz' => 'O‘qish zali', 'ru' => 'Читальный зал', 'kk' => 'Oqıw zalı'],
            ['uz' => 'Ilmiy adabiyotlar zali', 'ru' => 'Зал научной литературы', 'kk' => 'Ilimiy ádebiyatlar zalı'],
            ['uz' => 'Arxiv', 'ru' => 'Архив', 'kk' => 'Arxiv'],
        ];

        foreach ($locations as $name) {
            if (! Location::where('name->uz', $name['uz'])->exists()) {
                Location::create(['name' => $name]);
            }
        }
    }
}
