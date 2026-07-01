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
        // Birinchi admin foydalanuvchi
        User::updateOrCreate(
            ['email' => 'admin@samvet.uz'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('password'),
            ]
        );

        // Lookup jadvallar (avval), so'ng kategoriya, oxirida kitoblar
        $this->call([
            BookTypeSeeder::class,
            LanguageSeeder::class,
            PublisherSeeder::class,
            LocationSeeder::class,
            AuthorSeeder::class,
            CategorySeeder::class,
            BookSeeder::class,
        ]);
    }
}
