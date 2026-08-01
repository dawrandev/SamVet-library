<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
        Schema::create('event_locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        $now = now();
        DB::table('event_locations')->insert([
            ['name' => 'ARM o‘qish zali', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'ARM Elektron kutubxona zali', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Talabalar turar joyi', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('event_locations');
    }
};
