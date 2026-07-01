<?php

namespace Database\Seeders;

use App\Models\Author;
use Illuminate\Database\Seeder;

class AuthorSeeder extends Seeder
{
    public function run(): void
    {
        $authors = ['A. O‘lmasov', 'A. Vahobov', 'B. Xodiyev', 'M. Sharifxo‘jayev', 'N. To‘xliyev'];

        foreach ($authors as $name) {
            Author::firstOrCreate(['name' => $name]);
        }
    }
}
