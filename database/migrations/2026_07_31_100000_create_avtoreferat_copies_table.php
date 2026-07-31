<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors book_copies: an avtoreferat (title/metadata) can now have several
 * physical copies, each with its own inventory number — librarian request.
 * Inventory-tracking only, no lending/circulation (unlike BookCopy, no
 * `status`/`loans` here — an avtoreferat is still read only via its
 * title-level electronic_file, never physically issued).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avtoreferat_copies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('avtoreferat_id')->constrained()->cascadeOnDelete();
            $table->string('inventory_number')->nullable()->unique();
            // text, not string — matches the JSON-array condition storage
            // every sibling table (book_copies, journal_copies, dissertations,
            // and avtoreferats itself pre-migration) already uses, see
            // 2026_07_25_171501_convert_condition_columns_to_json_arrays.php.
            $table->text('condition')->nullable();
            $table->string('acquisition_act_number')->nullable();
            $table->date('acquisition_act_at')->nullable();
            $table->string('disposal_act_number')->nullable();
            $table->date('disposal_act_at')->nullable();
            $table->timestamps();

            $table->index('avtoreferat_id');
        });

        // Data migration: every existing avtoreferat's flat inventory/
        // condition/act fields become its first (and, until a librarian adds
        // more, only) copy — nothing is discarded by this restructuring.
        // Wrapped in a transaction: the old avtoreferats.inventory_number was
        // never unique-constrained, but the new avtoreferat_copies one is —
        // if two legacy rows happen to share a value, this fails the whole
        // batch atomically (leaving avtoreferat_copies empty, avtoreferats
        // untouched) instead of leaving a half-migrated table for someone to
        // find later. The failing duplicate value is in the thrown error.
        DB::transaction(function () {
            DB::table('avtoreferats')
                ->whereNotNull('inventory_number')
                ->orWhereNotNull('condition')
                ->orWhereNotNull('acquisition_act_number')
                ->orWhereNotNull('disposal_act_number')
                ->get(['id', 'inventory_number', 'condition', 'acquisition_act_number', 'acquisition_act_at', 'disposal_act_number', 'disposal_act_at'])
                ->each(function ($row) {
                    DB::table('avtoreferat_copies')->insert([
                        'avtoreferat_id' => $row->id,
                        'inventory_number' => $row->inventory_number,
                        'condition' => $row->condition,
                        'acquisition_act_number' => $row->acquisition_act_number,
                        'acquisition_act_at' => $row->acquisition_act_at,
                        'disposal_act_number' => $row->disposal_act_number,
                        'disposal_act_at' => $row->disposal_act_at,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                });
        });

        Schema::table('avtoreferats', function (Blueprint $table) {
            $table->dropColumn([
                'inventory_number', 'condition',
                'acquisition_act_number', 'acquisition_act_at',
                'disposal_act_number', 'disposal_act_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('avtoreferats', function (Blueprint $table) {
            $table->string('inventory_number')->nullable();
            $table->text('condition')->nullable();
            $table->string('acquisition_act_number')->nullable();
            $table->date('acquisition_act_at')->nullable();
            $table->string('disposal_act_number')->nullable();
            $table->date('disposal_act_at')->nullable();
        });

        // Best-effort restore: pulls back the FIRST copy per avtoreferat. If
        // a librarian added more than one copy since, the rest are lost on
        // rollback — acceptable for a down() path, which is a last resort.
        DB::table('avtoreferat_copies')
            ->orderBy('id')
            ->get()
            ->unique('avtoreferat_id')
            ->each(function ($copy) {
                DB::table('avtoreferats')->where('id', $copy->avtoreferat_id)->update([
                    'inventory_number' => $copy->inventory_number,
                    'condition' => $copy->condition,
                    'acquisition_act_number' => $copy->acquisition_act_number,
                    'acquisition_act_at' => $copy->acquisition_act_at,
                    'disposal_act_number' => $copy->disposal_act_number,
                    'disposal_act_at' => $copy->disposal_act_at,
                ]);
            });

        Schema::dropIfExists('avtoreferat_copies');
    }
};
