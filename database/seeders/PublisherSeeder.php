<?php

namespace Database\Seeders;

use App\Models\Publisher;
use Illuminate\Database\Seeder;

class PublisherSeeder extends Seeder
{
    public function run(): void
    {
        $publishers = ['O‘zbekiston', 'Fan', 'Yangi asr avlodi', 'Iqtisod-moliya', 'Toshkent davlat agrar universiteti'];

        foreach ($publishers as $name) {
            Publisher::firstOrCreate(['name' => $name]);
        }
    }
}
