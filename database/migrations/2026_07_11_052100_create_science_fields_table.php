<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "Fan nomi" for a dissertation (e.g. Biologiya fanlari, Veterinariya fanlari).
     * A separate list from ResourceField — not reused, per the librarian's request.
     */
    public function up(): void
    {
        Schema::create('science_fields', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // The exact codes the librarian's own reference mockup listed.
        $now = now();
        foreach ([
            'Biologiya fanlari',
            'Veterinariya fanlari',
            'Qishloq xo‘jaligi fanlari',
            'Filologiya fanlari',
        ] as $name) {
            DB::table('science_fields')->insert(['name' => $name, 'created_at' => $now, 'updated_at' => $now]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('science_fields');
    }
};
