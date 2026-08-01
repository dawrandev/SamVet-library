<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "Ixtisoslik shifri va nomi" for a PhD/DSc dissertation (e.g. "03.00.06-Zoologiya").
     * Stored as one combined code+name string per row, same as the librarian's own list.
     */
    public function up(): void
    {
        Schema::create('doctoral_specialties', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // The exact codes the librarian's own reference mockup listed.
        $now = now();
        foreach ([
            '03.00.06-Zoologiya',
            '06.02.03-Xususiy zootexniya. Chorvachilik mahsulotlarini ishlab chiqarish texnologiyasi',
            '16.00.01-Hayvonlar kasalliklari diagnostikasi, terapiyasi va xirurgiyasi',
            '16.00.02 - Hayvonlar patologiyasi, onkologiyasi va morfologiyasi',
            'Veterinar akusherligi va hayvonlar reproduksiyasi biotexnikasi',
            '16.00.04 - Veterinariya farmakologiyasi va toksikologiyasi. Veterinariya sanitariyasi, ekologiyasi, zoogigiyenasi va veterinar-sanitariya ekspertizasi',
        ] as $name) {
            DB::table('doctoral_specialties')->insert(['name' => $name, 'created_at' => $now, 'updated_at' => $now]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('doctoral_specialties');
    }
};
