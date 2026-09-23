<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Moves each reader type onto its colour from the Figma card design.
 *
 * The printed card takes its whole palette from reader_types.certificate_color
 * (see App\Support\ReaderCard\CardPalette), and the design names five exact
 * accents. The seeded defaults were close but not those, so a live card came
 * out a shade off — this brings the rows the seeder created in line, and makes
 * the design's "Basqalar" orange the default any other type gets.
 *
 * Rows are only touched while they still hold a seeded default: a librarian
 * who has already chosen a colour in "Ma'lumotnomalar → Kitobxon turlari"
 * keeps it.
 */
return new class extends Migration
{
    /** The variant every type outside the list below is drawn in. */
    private const OTHERS = '#C58A00';

    private const OTHERS_WAS = '#d97706';

    /** Type name => [seeded default, Figma accent]. */
    private const COLORS = [
        'Bakalavr talabasi (kunduzgi)' => ['#2563eb', '#349AED'],
        'Bakalavr talabasi (kechki)' => ['#2563eb', '#349AED'],
        'Bakalavr talabasi (tashqi)' => ['#2563eb', '#349AED'],
        'Magistr talabasi' => ['#7c3aed', '#3F2B96'],
        'Doktorant' => ['#dc2626', '#8B0000'],
        'Professor-o‘qituvchi' => ['#12b76a', '#27AE60'],
        'Filial xodimi' => ['#12b76a', '#27AE60'],
    ];

    public function up(): void
    {
        foreach (self::COLORS as $name => [$old, $new]) {
            DB::table('reader_types')
                ->where('name', $name)
                ->where('certificate_color', $old)
                ->update(['certificate_color' => $new, 'updated_at' => now()]);
        }

        // Types the librarian added themselves and left at the old default.
        DB::table('reader_types')
            ->where('certificate_color', self::OTHERS_WAS)
            ->update(['certificate_color' => self::OTHERS, 'updated_at' => now()]);

        Schema::table('reader_types', function (Blueprint $table) {
            $table->string('certificate_color', 7)->default(self::OTHERS)->change();
        });
    }

    public function down(): void
    {
        Schema::table('reader_types', function (Blueprint $table) {
            $table->string('certificate_color', 7)->default(self::OTHERS_WAS)->change();
        });

        DB::table('reader_types')
            ->where('certificate_color', self::OTHERS)
            ->update(['certificate_color' => self::OTHERS_WAS, 'updated_at' => now()]);

        foreach (self::COLORS as $name => [$old, $new]) {
            DB::table('reader_types')
                ->where('name', $name)
                ->where('certificate_color', $new)
                ->update(['certificate_color' => $old, 'updated_at' => now()]);
        }
    }
};
