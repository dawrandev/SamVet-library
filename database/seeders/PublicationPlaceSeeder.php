<?php

namespace Database\Seeders;

use App\Models\PublicationPlace;
use Illuminate\Database\Seeder;

class PublicationPlaceSeeder extends Seeder
{
    public function run(): void
    {
        $places = [
            ['uz' => 'Toshkent', 'ru' => 'Ташкент', 'kk' => 'Tashkent'],
            ['uz' => 'Samarqand', 'ru' => 'Самарканд', 'kk' => 'Samarqand'],
            ['uz' => 'Nukus', 'ru' => 'Нукус', 'kk' => 'Nókis'],
            ['uz' => 'Buxoro', 'ru' => 'Бухара', 'kk' => 'Buxara'],
        ];

        foreach ($places as $name) {
            PublicationPlace::firstOrCreate(['name->uz' => $name['uz']], ['name' => $name]);
        }
    }
}
