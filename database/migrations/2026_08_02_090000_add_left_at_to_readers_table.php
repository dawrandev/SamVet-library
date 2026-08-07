<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * When a reader was marked "tugatildi" (finished/left) — left_reason already
 * existed but had no matching timestamp, so the admin show page couldn't say
 * WHEN it happened (only updated_at, which any later edit would overwrite).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('readers', function (Blueprint $table) {
            $table->timestamp('left_at')->nullable()->after('left_reason');
        });
    }

    public function down(): void
    {
        Schema::table('readers', function (Blueprint $table) {
            $table->dropColumn('left_at');
        });
    }
};
