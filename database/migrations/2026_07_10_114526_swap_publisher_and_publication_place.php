<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The two publication fields swap roles.
     *
     * Publisher was a single-value lookup; it becomes translatable free text on
     * the record itself (the same house is written differently per language).
     * Place of publication was translatable free text; it becomes a shared
     * translatable lookup, since a handful of cities repeat across the fund.
     */
    private const TABLES = ['books', 'journals'];

    /** Cache of place (uz name) => publication_places.id while migrating. */
    private array $placeIds = [];

    public function up(): void
    {
        Schema::create('publication_places', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->timestamps();
        });

        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->json('publisher')->nullable()->after('language_id');
                $t->foreignId('publication_place_id')->nullable()->after('publisher')
                    ->constrained('publication_places')->nullOnDelete();
            });
        }

        foreach (self::TABLES as $table) {
            $this->moveData($table);
        }

        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropForeign(['publisher_id']);
                $t->dropColumn(['publisher_id', 'publication_place']);
            });
        }

        Schema::dropIfExists('publishers');
    }

    /**
     * Carry each row's publisher name into the new translatable column and turn
     * its place of publication into a shared lookup row.
     */
    private function moveData(string $table): void
    {
        DB::table($table)
            ->select('id', 'publisher_id', 'publication_place')
            ->orderBy('id')
            ->chunkById(200, function ($rows) use ($table) {
                foreach ($rows as $row) {
                    $attributes = [];

                    if ($row->publisher_id !== null) {
                        $name = DB::table('publishers')->where('id', $row->publisher_id)->value('name');

                        // The old value had no language; keep it as the Uzbek one.
                        if (filled($name)) {
                            $attributes['publisher'] = json_encode(['uz' => $name], JSON_UNESCAPED_UNICODE);
                        }
                    }

                    $placeId = $this->placeId($row->publication_place);

                    if ($placeId !== null) {
                        $attributes['publication_place_id'] = $placeId;
                    }

                    if ($attributes !== []) {
                        DB::table($table)->where('id', $row->id)->update($attributes);
                    }
                }
            });
    }

    /** Find or create the lookup row for a stored place translation. */
    private function placeId(?string $translations): ?int
    {
        $decoded = $translations !== null ? json_decode($translations, true) : null;

        if (! is_array($decoded) || $decoded === []) {
            return null;
        }

        // Rows are identified by their Uzbek name (the language always present).
        $key = $decoded['uz'] ?? reset($decoded);

        if (blank($key)) {
            return null;
        }

        if (! isset($this->placeIds[$key])) {
            $existing = DB::table('publication_places')
                ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.uz')) = ?", [$key])
                ->value('id');

            $this->placeIds[$key] = $existing ?: DB::table('publication_places')->insertGetId([
                'name' => json_encode($decoded, JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return (int) $this->placeIds[$key];
    }

    /**
     * Restores the previous shape. The values themselves are not restored —
     * publisher names and places would have to be split back apart by hand.
     */
    public function down(): void
    {
        Schema::create('publishers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->foreignId('publisher_id')->nullable()->after('language_id')
                    ->constrained('publishers')->nullOnDelete();
                $t->json('publication_place')->nullable()->after('publisher_id');
            });

            Schema::table($table, function (Blueprint $t) {
                $t->dropForeign(['publication_place_id']);
                $t->dropColumn(['publisher', 'publication_place_id']);
            });
        }

        Schema::dropIfExists('publication_places');
    }
};
