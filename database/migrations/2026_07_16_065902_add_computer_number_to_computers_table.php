<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The librarian hands computers out to readers using a different number than
 * the formal `inventory_number` (a library-internal asset tag) — this is that
 * number, used by the checkout picker instead.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('computers', function (Blueprint $table) {
            $table->string('computer_number')->nullable()->unique()->after('inventory_number');
        });
    }

    public function down(): void
    {
        Schema::table('computers', function (Blueprint $table) {
            $table->dropUnique(['computer_number']);
            $table->dropColumn('computer_number');
        });
    }
};
