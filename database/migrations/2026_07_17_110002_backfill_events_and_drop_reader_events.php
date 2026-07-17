<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * reader_events duplicated the event's own info (name/date/place) on every
     * participating reader's row. Each distinct row becomes its own `events`
     * row plus a single `event_participants` row for that reader — the old
     * free-text `link` (never tied to a news post) is preserved into `note`
     * so nothing is silently dropped.
     */
    public function up(): void
    {
        $locationIds = [];

        DB::table('reader_events')->orderBy('id')->chunkById(200, function ($rows) use (&$locationIds) {
            foreach ($rows as $row) {
                $note = trim((string) $row->note);
                if (filled($row->link)) {
                    $note = trim($note . "\n" . 'Havola: ' . $row->link);
                }

                $eventId = DB::table('events')->insertGetId([
                    'name' => $row->name,
                    'type' => $row->type,
                    'date' => $row->date,
                    'note' => $note !== '' ? $note : null,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);

                if (filled($row->place)) {
                    $place = trim($row->place);
                    if (! isset($locationIds[$place])) {
                        $locationIds[$place] = DB::table('event_locations')->insertGetId([
                            'name' => $place,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    DB::table('event_event_location')->insert([
                        'event_id' => $eventId,
                        'event_location_id' => $locationIds[$place],
                    ]);
                }

                DB::table('event_participants')->insert([
                    'event_id' => $eventId,
                    'reader_id' => $row->reader_id,
                    'role' => $row->role,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);
            }
        });

        Schema::dropIfExists('reader_events');
    }

    public function down(): void
    {
        // One-way data migration — recreating reader_events empty is not a
        // meaningful rollback of the backfill itself.
        Schema::create('reader_events', function ($table) {
            $table->id();
            $table->foreignId('reader_id')->constrained('readers')->cascadeOnDelete();
            $table->date('date');
            $table->string('name');
            $table->string('place')->nullable();
            $table->string('type');
            $table->string('role');
            $table->string('link')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->index('reader_id');
        });
    }
};
