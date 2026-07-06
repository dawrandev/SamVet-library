<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news', function (Blueprint $table) {
            $table->id();

            $table->foreignId('news_category_id')->nullable()
                ->constrained('news_categories')->nullOnDelete();

            $table->json('title');           // translated: {"uz":..,"ru":..,"kk":..}
            $table->json('excerpt')->nullable(); // short text (translated)
            $table->json('body')->nullable();    // rich HTML (translated)

            $table->string('slug')->unique();
            $table->string('cover_image')->nullable();

            $table->string('status')->default('draft'); // App\Enums\NewsStatus
            $table->dateTime('published_at')->nullable();
            $table->unsignedInteger('views_count')->default(0);

            $table->timestamps();

            $table->index(['status', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news');
    }
};
