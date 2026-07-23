<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Seeds the science-field/specialty lookups with the exact codes the
     * librarian's own reference mockup listed — otherwise these tables start
     * empty and every single code has to be hand-typed one at a time through
     * the "+ Yangi" inline creator. Idempotent (skips a name that already exists),
     * so re-running this migration (or a librarian adding the same code by hand
     * first) never creates a duplicate row.
     */
    public function up(): void
    {
        $now = now();

        $this->seed('science_fields', [
            'Biologiya fanlari',
            'Veterinariya fanlari',
            'Qishloq xo‘jaligi fanlari',
            'Filologiya fanlari',
        ], $now);

        $this->seed('doctoral_specialties', [
            '03.00.06-Zoologiya',
            '06.02.03-Xususiy zootexniya. Chorvachilik mahsulotlarini ishlab chiqarish texnologiyasi',
            '16.00.01-Hayvonlar kasalliklari diagnostikasi, terapiyasi va xirurgiyasi',
            '16.00.02 - Hayvonlar patologiyasi, onkologiyasi va morfologiyasi',
            'Veterinar akusherligi va hayvonlar reproduksiyasi biotexnikasi',
            '16.00.04 - Veterinariya farmakologiyasi va toksikologiyasi. Veterinariya sanitariyasi, ekologiyasi, zoogigiyenasi va veterinar-sanitariya ekspertizasi',
        ], $now);

        $this->seed('master_specialties', [
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
        ], $now);
    }

    /**
     * @param  array<int, string>  $names
     */
    private function seed(string $table, array $names, \DateTimeInterface $now): void
    {
        foreach ($names as $name) {
            $exists = DB::table($table)->where('name', $name)->exists();

            if (! $exists) {
                DB::table($table)->insert(['name' => $name, 'created_at' => $now, 'updated_at' => $now]);
            }
        }
    }

    /**
     * Data-only seed — leaves the rows in place on rollback (they may already
     * be in real use by a saved dissertation by the time anyone rolls back).
     */
    public function down(): void {}
};
