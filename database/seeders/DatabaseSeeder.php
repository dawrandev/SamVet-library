<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // First admin user
        User::updateOrCreate(
            ['email' => 'admin@samvet.uz'],
            [
                'name' => 'Administrator',
                'username' => 'admin',
                'password' => Hash::make('password'),
            ]
        );

        // Lookup tables (first), then categories, and books last
        $this->call([
            BookTypeSeeder::class,
            LanguageSeeder::class,
            PublicationPlaceSeeder::class,
            LocationSeeder::class,
            CategorySeeder::class,
            BookSeeder::class,
            MenuSeeder::class,
            NewsCategorySeeder::class,
        ]);
    }
}
