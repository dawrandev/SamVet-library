<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reader type becomes an admin-editable lookup (like Author/BookType) instead
 * of a fixed enum — the librarian wants to manage the exact set herself
 * (adding shifts like kunduzgi/kechki/tashqi) without a code change each time.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reader_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // Whether this type shows student fields (o'qish joyi/mutaxassislik/guruh)
            // on the reader form, or staff fields (ish joyi/bo'lim/lavozim).
            $table->boolean('is_student')->default(true);
            // Badge color on the printed reader certificate (hex).
            $table->string('certificate_color', 7)->default('#d97706');
            $table->timestamps();
        });

        // Seed in the exact display order the librarian wants — ordered by id,
        // not name, since "Bakalavr (kunduzgi/kechki/tashqi)" should stay together.
        $rows = [
            ['name' => 'Bakalavr talabasi (kunduzgi)', 'is_student' => true, 'certificate_color' => '#2563eb'],
            ['name' => 'Bakalavr talabasi (kechki)', 'is_student' => true, 'certificate_color' => '#2563eb'],
            ['name' => 'Bakalavr talabasi (tashqi)', 'is_student' => true, 'certificate_color' => '#2563eb'],
            ['name' => 'Magistr talabasi', 'is_student' => true, 'certificate_color' => '#7c3aed'],
            ['name' => 'Doktorant', 'is_student' => true, 'certificate_color' => '#dc2626'],
            ['name' => 'Professor-o‘qituvchi', 'is_student' => false, 'certificate_color' => '#12b76a'],
            ['name' => 'Filial xodimi', 'is_student' => false, 'certificate_color' => '#12b76a'],
        ];

        $now = now();
        foreach ($rows as $row) {
            DB::table('reader_types')->insert([...$row, 'created_at' => $now, 'updated_at' => $now]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('reader_types');
    }
};
