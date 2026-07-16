<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Data-only migration: folds the legacy date + issued_time/returned_time
 * columns into the new issued_at/returned_at datetimes for any pre-existing
 * rows. duration_minutes/expires_at are left null for these rows — there's
 * no real allotted-duration data to backfill, so they're simply excluded
 * from expiry tracking (ComputerSession::isExpired() returns false when
 * expires_at is null) rather than guessed at.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('computer_sessions')
            ->whereNull('issued_at')
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    $issuedAt = Carbon::parse($row->date)
                        ->setTimeFromTimeString($row->issued_time ?? '00:00:00');
                    $returnedAt = $row->returned_time
                        ? Carbon::parse($row->date)->setTimeFromTimeString($row->returned_time)
                        : null;

                    DB::table('computer_sessions')->where('id', $row->id)->update([
                        'issued_at' => $issuedAt,
                        'returned_at' => $returnedAt,
                    ]);
                }
            });
    }

    public function down(): void
    {
        // Data-only forward migration — the columns themselves are dropped/
        // restored by the surrounding schema migrations' own down().
    }
};
