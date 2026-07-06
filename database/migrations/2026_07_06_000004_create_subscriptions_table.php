<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('subscriber_id')->constrained('subscribers')->cascadeOnDelete();
            // Subscribed publication (journal or newspaper — both live in `journals`)
            $table->foreignId('journal_id')->constrained('journals')->cascadeOnDelete();

            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('start_month'); // 1..12 — App\Enums\Month
            $table->unsignedTinyInteger('end_month');   // 1..12 — App\Enums\Month
            $table->decimal('amount', 12, 2);           // subscription total

            $table->timestamps();

            $table->index('subscriber_id');
            $table->index('journal_id');
            $table->index('year');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
