<?php

use App\Models\DepartmentCoverage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Seeds the university departments the librarian tracks for the public
     * "Kafedralar kesimida ta'minganlik darajasi" statistic. Percentage starts
     * at 0 — the librarian fills in the real coverage figure from the admin
     * lookup page.
     */
    public function up(): void
    {
        Schema::create('department_coverages', function (Blueprint $table) {
            $table->id();
            $table->json('name'); // translated: {"uz":..,"ru":..,"kk":..}
            $table->unsignedTinyInteger('percentage')->default(0);
            $table->timestamps();
        });

        $names = [
            ['uz' => 'Veterinariya meditsinasi va farmakalogiyasi kafedrasi', 'ru' => 'Кафедра ветеринарной медицины и фармакологии', 'kk' => 'Veterinariya medicinası hám farmakologiya kafedrası'],
            ['uz' => 'Veterinariya diagnostikasi va oziq-ovqat xavfsizligi kafedrasi', 'ru' => 'Кафедра ветеринарной диагностики и безопасности пищевых продуктов', 'kk' => 'Veterinariya diagnostikası hám azıq-áwqat qáwipsizligi kafedrası'],
            ['uz' => 'Zooinjeneriya kafedrasi', 'ru' => 'Кафедра зооинженерии', 'kk' => 'Zooinjeneriya kafedrası'],
            ['uz' => 'Iqtisodiyot va gumanitar fanlar kafedrasi', 'ru' => 'Кафедра экономики и гуманитарных наук', 'kk' => 'Ekonomika hám gumanitar pánler kafedrası'],
        ];

        foreach ($names as $name) {
            $exists = DepartmentCoverage::where('name->uz', $name['uz'])->exists();

            if (! $exists) {
                DepartmentCoverage::create(['name' => $name, 'percentage' => 0]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('department_coverages');
    }
};
