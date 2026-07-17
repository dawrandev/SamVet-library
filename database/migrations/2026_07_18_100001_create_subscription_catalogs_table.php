<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The official yearly subscription catalog (all publications + their
     * annual price) that arrives each year, narrowed down by `is_selected`
     * to the library's own shortlist — only those are offered when creating
     * an actual Subscription. `annual_price` is what drives the per-period
     * amount auto-calculation (the real catalog only ever states a yearly figure).
     */
    public function up(): void
    {
        Schema::create('subscription_catalogs', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->foreignId('journal_id')->constrained('journals')->cascadeOnDelete();
            $table->decimal('annual_price', 12, 2);
            $table->boolean('is_selected')->default(false); // part of the library's own shortlist
            $table->timestamps();

            $table->unique(['year', 'journal_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_catalogs');
    }
};
