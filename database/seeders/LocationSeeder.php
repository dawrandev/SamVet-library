<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $locations = ['Kitob berish bo‘limi', 'O‘qish zali', 'Ilmiy adabiyotlar zali', 'Arxiv'];

        foreach ($locations as $name) {
            Location::firstOrCreate(['name' => $name]);
        }
    }
}
