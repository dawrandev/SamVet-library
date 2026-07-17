<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The names the librarian gave for certain (two others — "Aktiv zal" and
     * "Ruhiylik" — she's still confirming the exact wording for, so they're
     * left for her to add herself via the lookup admin UI, same as any other
     * lookup entry).
     */
    public function up(): void
    {
        $now = now();
        DB::table('event_locations')->insert([
            ['name' => 'ARM o‘qish zali', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'ARM Elektron kutubxona zali', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Talabalar turar joyi', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        DB::table('event_locations')->whereIn('name', [
            'ARM o‘qish zali',
            'ARM Elektron kutubxona zali',
            'Talabalar turar joyi',
        ])->delete();
    }
};
