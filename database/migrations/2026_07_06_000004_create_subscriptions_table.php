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

            $table->foreignId('reader_id')->nullable()->constrained('readers')->cascadeOnDelete();
            $table->string('source')->default('reader'); // App\Enums\SubscriptionSource
            // Subscribed publication (journal or newspaper — both live in `journals`)
            $table->foreignId('journal_id')->constrained('journals')->cascadeOnDelete();
            $table->foreignId('delivery_location_id')->nullable()->constrained('delivery_locations')->nullOnDelete();
            $table->foreignId('post_branch_id')->nullable()->constrained('post_branches')->nullOnDelete();

            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('start_month'); // 1..12 — App\Enums\Month
            $table->unsignedTinyInteger('end_month');   // 1..12 — App\Enums\Month
            $table->decimal('amount', 12, 2);           // subscription total
            $table->string('receipt_file')->nullable();

            $table->timestamps();

            $table->index('journal_id');
            $table->index('year');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
