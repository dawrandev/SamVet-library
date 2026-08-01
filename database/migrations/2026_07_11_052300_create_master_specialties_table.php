<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "Mutaxassislik shifri va nomi" for a Magistrlik dissertation (e.g. "70710201 - Biotexnologiya").
     * Stored as one combined code+name string per row, same as the librarian's own list.
     */
    public function up(): void
    {
        Schema::create('master_specialties', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // The exact codes the librarian's own reference mockup listed.
        $now = now();
        foreach ([
            '70710201 - Biotexnologiya',
            '70810802 - Qishloq xo‘jalik hayvonlarining seleksiyasi va naslchilik',
            '70810803 - Chorvachilik',
            '70810804 - Chorvachilik mahsulotlarini qayta ishlash texnologiyasi',
            '70840102 - Veterinariya jarrohligi',
            '70840103 - Veterinariya farmakologiyasi va toksikologiyasi',
            '70840302 - Veterinariya mikrobiologiyasi, virusologiyasi, epizootologiyasi, mikologiyasi va immunologiyasi',
            '70840303 - Hayvonlarning parazitar va yuqumli kasalliklari',
            '70710201 - Biotexnologiya [mahsulot turlari bo‘yicha]',
            '70811505 - Naslchilik',
            '70840302 - Hayvonlar parazitli va yuqumli kasalliklari',
        ] as $name) {
            DB::table('master_specialties')->insert(['name' => $name, 'created_at' => $now, 'updated_at' => $now]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('master_specialties');
    }
};
