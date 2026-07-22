<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Converts the existing free-text affiliation_place/unit/group/district values
     * on readers into deduplicated lookup rows, and points each reader at the
     * matching lookup row via the new *_id columns added in the previous migration.
     * No data is lost — the old text columns are only dropped in the migration after this one.
     */
    public function up(): void
    {
        $this->backfill('affiliation_places', 'affiliation_place', 'affiliation_place_id');
        $this->backfill('affiliation_units', 'affiliation_unit', 'affiliation_unit_id');
        $this->backfill('affiliation_groups', 'affiliation_group', 'affiliation_group_id');
        $this->backfill('districts', 'district', 'district_id');
    }

    private function backfill(string $lookupTable, string $textColumn, string $fkColumn): void
    {
        $now = now();

        $values = DB::table('readers')
            ->whereNotNull($textColumn)
            ->where($textColumn, '!=', '')
            ->distinct()
            ->pluck($textColumn);

        foreach ($values as $value) {
            $trimmed = trim($value);

            if ($trimmed === '') {
                continue;
            }

            $id = DB::table($lookupTable)->where('name', $trimmed)->value('id');

            if (! $id) {
                $id = DB::table($lookupTable)->insertGetId(['name' => $trimmed, 'created_at' => $now, 'updated_at' => $now]);
            }

            DB::table('readers')->where($textColumn, $value)->update([$fkColumn => $id]);
        }
    }

    /**
     * Irreversible data backfill — the old text columns still hold the original
     * values (dropped only by the next migration), so nothing needs restoring here.
     */
    public function down(): void {}
};
