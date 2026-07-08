<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Subscriptions now belong to a reader (Foydalanuvchi) instead of a separate
 * `subscribers` table — the subscribing staff already exist among the readers.
 * The standalone subscribers table/module is removed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['subscriber_id']);
            $table->dropColumn('subscriber_id');
            // Nullable so any pre-existing rows survive; new records require it (FormRequest).
            $table->foreignId('reader_id')->nullable()->after('id')->constrained('readers')->cascadeOnDelete();
        });

        Schema::dropIfExists('subscribers');
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['reader_id']);
            $table->dropColumn('reader_id');
            // subscribers table is not recreated here — re-run its create migration if needed.
            $table->unsignedBigInteger('subscriber_id')->nullable()->after('id');
        });
    }
};
