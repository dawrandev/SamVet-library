<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Replaces readers.type (a fixed-enum string) with a reader_type_id FK into
 * the new reader_types lookup. Existing "texnikum" readers (student/teacher)
 * have no equivalent row anymore — they're reassigned to the closest
 * remaining bucket by role (student → Bakalavr (kunduzgi), teacher →
 * Professor-o'qituvchi, staff → Filial xodimi) since the librarian asked for
 * texnikum to be dropped entirely, not left dangling.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('readers', function (Blueprint $table) {
            $table->foreignId('reader_type_id')->nullable()->after('type')->constrained()->restrictOnDelete();
        });

        $typeIds = DB::table('reader_types')->pluck('id', 'name');

        $map = [
            'bachelor' => 'Bakalavr talabasi (kunduzgi)',
            'master' => 'Magistr talabasi',
            'doctoral' => 'Doktorant',
            'professor' => 'Professor-o‘qituvchi',
            'branch_staff' => 'Filial xodimi',
            // Dropped technicum cases — reassigned to the closest remaining bucket.
            'technicum_student' => 'Bakalavr talabasi (kunduzgi)',
            'technicum_teacher' => 'Professor-o‘qituvchi',
            'technicum_staff' => 'Filial xodimi',
        ];

        foreach ($map as $oldType => $newTypeName) {
            $newId = $typeIds[$newTypeName] ?? null;

            if ($newId !== null) {
                DB::table('readers')->where('type', $oldType)->update(['reader_type_id' => $newId]);
            }
        }

        // Any row that somehow didn't match a known old value — don't leave
        // it NULL (the column is about to become required); default it to
        // the most common type rather than fail the migration outright.
        $fallbackId = $typeIds['Bakalavr talabasi (kunduzgi)'] ?? null;
        if ($fallbackId !== null) {
            DB::table('readers')->whereNull('reader_type_id')->update(['reader_type_id' => $fallbackId]);
        }

        Schema::table('readers', function (Blueprint $table) {
            $table->foreignId('reader_type_id')->nullable(false)->change();
            $table->dropColumn('type');
        });
    }

    public function down(): void
    {
        Schema::table('readers', function (Blueprint $table) {
            $table->string('type')->nullable()->after('reader_type_id');
        });

        $typeNames = DB::table('reader_types')->pluck('name', 'id');
        $reverseMap = [
            'Bakalavr talabasi (kunduzgi)' => 'bachelor',
            'Bakalavr talabasi (kechki)' => 'bachelor',
            'Bakalavr talabasi (tashqi)' => 'bachelor',
            'Magistr talabasi' => 'master',
            'Doktorant' => 'doctoral',
            'Professor-o‘qituvchi' => 'professor',
            'Filial xodimi' => 'branch_staff',
        ];

        foreach (DB::table('readers')->select('id', 'reader_type_id')->get() as $reader) {
            $name = $typeNames[$reader->reader_type_id] ?? null;
            $old = $reverseMap[$name] ?? 'bachelor';
            DB::table('readers')->where('id', $reader->id)->update(['type' => $old]);
        }

        Schema::table('readers', function (Blueprint $table) {
            $table->string('type')->nullable(false)->change();
            $table->dropConstrainedForeignId('reader_type_id');
        });
    }
};
