<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Why a reader's membership was finished (graduated, left the job, maternity
 * leave, etc.) — the librarian types this when clicking "Foydalanishni
 * tugatish". Mirrors block_reason's role for blocking.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('readers', function (Blueprint $table) {
            $table->text('left_reason')->nullable()->after('block_reason');
        });
    }

    public function down(): void
    {
        Schema::table('readers', function (Blueprint $table) {
            $table->dropColumn('left_reason');
        });
    }
};
