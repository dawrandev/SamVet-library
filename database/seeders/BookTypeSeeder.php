<?php

namespace Database\Seeders;

use App\Models\BookType;
use Illuminate\Database\Seeder;

class BookTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = ['Darslik', 'O‘quv qo‘llanma', 'Uslubiy qo‘llanma', 'Monografiya', 'Dissertatsiya', 'To‘plam'];

        foreach ($types as $name) {
            BookType::firstOrCreate(['name' => $name]);
        }
    }
}
