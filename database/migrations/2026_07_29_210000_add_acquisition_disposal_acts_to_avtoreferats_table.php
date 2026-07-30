<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kirish/chiqish akti (acquisition/disposal act) — same admin-only pair of
 * plain fields BookCopy already has (see 2026_07_17_100000_convert_book_copy_acts_to_plain_fields.php).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('avtoreferats', function (Blueprint $table) {
            $table->string('acquisition_act_number')->nullable()->after('inventory_number');
            $table->date('acquisition_act_at')->nullable()->after('acquisition_act_number');
            $table->string('disposal_act_number')->nullable()->after('acquisition_act_at');
            $table->date('disposal_act_at')->nullable()->after('disposal_act_number');
        });
    }

    public function down(): void
    {
        Schema::table('avtoreferats', function (Blueprint $table) {
            $table->dropColumn(['acquisition_act_number', 'acquisition_act_at', 'disposal_act_number', 'disposal_act_at']);
        });
    }
};
