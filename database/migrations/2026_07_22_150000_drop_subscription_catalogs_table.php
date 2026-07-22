<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('subscription_catalogs');
    }

    public function down(): void
    {
        Schema::create('subscription_catalogs', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->foreignId('journal_id')->constrained('journals')->cascadeOnDelete();
            $table->decimal('annual_price', 12, 2);
            $table->boolean('is_selected')->default(false);
            $table->timestamps();

            $table->unique(['year', 'journal_id']);
        });
    }
};
