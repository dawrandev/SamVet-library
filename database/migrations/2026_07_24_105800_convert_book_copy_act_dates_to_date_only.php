<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The librarian only needs a date for the entry/exit acts, not a time — the
 * DATETIME columns forced her to enter (and ignore) a time on every save.
 * doctrine/dbal is NOT installed, so the column type change uses raw SQL
 * (MODIFY) instead of the Fluent ->change() builder, matching the convention
 * already established in 2026_07_16_065906_drop_legacy_columns_from_computer_sessions_table.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE book_copies MODIFY acquisition_act_at DATE NULL');
        DB::statement('ALTER TABLE book_copies MODIFY disposal_act_at DATE NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE book_copies MODIFY acquisition_act_at DATETIME NULL');
        DB::statement('ALTER TABLE book_copies MODIFY disposal_act_at DATETIME NULL');
    }
};
