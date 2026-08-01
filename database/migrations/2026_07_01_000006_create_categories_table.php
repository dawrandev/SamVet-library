<?php

use App\Models\Category;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->json('name'); // translated: {"uz":..,"ru":..,"kk":..}
            // Hierarchy: parent category (null = top level)
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Top-level categories the librarian needs for the dashboard's
        // literature-type breakdown chart to have real buckets to count
        // books into (alongside the ones CategorySeeder creates).
        $names = [
            ['uz' => 'Xorijiy adabiyotlar', 'ru' => 'Зарубежная литература', 'kk' => 'Shet el ádebiyatlar'],
            ['uz' => 'Badiiy adabiyotlar', 'ru' => 'Художественная литература', 'kk' => 'Kórkem ádebiyat'],
        ];

        foreach ($names as $name) {
            $exists = Category::where('name->uz', $name['uz'])->whereNull('parent_id')->exists();

            if (! $exists) {
                Category::create(['name' => $name, 'parent_id' => null]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
