<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add-only step (all nullable) of the computer_sessions redesign: replaces
 * the split date+issued_time/returned_time columns with single datetimes,
 * and adds the allotted-duration/expiry pair a countdown needs. The next two
 * migrations backfill existing rows and then drop the legacy columns.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('computer_sessions', function (Blueprint $table) {
            $table->dateTime('issued_at')->nullable()->after('reader_id');
            $table->dateTime('returned_at')->nullable()->after('issued_at');
            $table->unsignedInteger('duration_minutes')->nullable()->after('returned_at');
            $table->dateTime('expires_at')->nullable()->after('duration_minutes');
            $table->index('expires_at');
            $table->index('returned_at');
        });
    }

    public function down(): void
    {
        Schema::table('computer_sessions', function (Blueprint $table) {
            $table->dropIndex(['expires_at']);
            $table->dropIndex(['returned_at']);
            $table->dropColumn(['issued_at', 'returned_at', 'duration_minutes', 'expires_at']);
        });
    }
};
