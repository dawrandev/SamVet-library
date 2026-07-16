<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Final step of the computer_sessions redesign. Refuses to run (throws,
 * migration fails loudly) if any row still has no computer_id — rather than
 * silently making the column NOT NULL and losing/corrupting those rows.
 * doctrine/dbal is NOT installed in this project, so column nullability is
 * changed via raw SQL (MODIFY) instead of the Fluent ->change() builder.
 */
return new class extends Migration
{
    public function up(): void
    {
        $orphans = DB::table('computer_sessions')->whereNull('computer_id')->count();

        if ($orphans > 0) {
            throw new RuntimeException(
                "{$orphans} computer_sessions row(s) have no computer_id. ".
                'Resolve them manually (assign a computer or delete the row) before re-running this migration.'
            );
        }

        Schema::table('computer_sessions', function (Blueprint $table) {
            $table->dropColumn(['date', 'issued_time', 'returned_time', 'computer_number']);
        });

        DB::statement('ALTER TABLE computer_sessions MODIFY issued_at DATETIME NOT NULL');

        Schema::table('computer_sessions', function (Blueprint $table) {
            $table->dropForeign(['computer_id']);
        });

        DB::statement('ALTER TABLE computer_sessions MODIFY computer_id BIGINT UNSIGNED NOT NULL');

        Schema::table('computer_sessions', function (Blueprint $table) {
            $table->foreign('computer_id')->references('id')->on('computers')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('computer_sessions', function (Blueprint $table) {
            $table->dropForeign(['computer_id']);
        });

        DB::statement('ALTER TABLE computer_sessions MODIFY computer_id BIGINT UNSIGNED NULL');

        Schema::table('computer_sessions', function (Blueprint $table) {
            $table->foreign('computer_id')->references('id')->on('computers')->nullOnDelete();
        });

        DB::statement('ALTER TABLE computer_sessions MODIFY issued_at DATETIME NULL');

        Schema::table('computer_sessions', function (Blueprint $table) {
            $table->date('date')->nullable()->after('reader_id');
            $table->time('issued_time')->nullable()->after('date');
            $table->time('returned_time')->nullable()->after('issued_time');
            $table->string('computer_number')->nullable()->after('computer_id');
        });
    }
};
