<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('readers', function (Blueprint $table) {
            $table->id();

            // Identifikatsiya
            $table->string('id_number')->nullable()->unique();      // ID raqam (BT0122001)
            $table->string('registration_number')->nullable();      // Registratsiya raqami (№129)
            $table->date('issued_date')->nullable();                // Berilgan sana

            $table->string('type');                                 // App\Enums\ReaderType
            $table->string('full_name');                            // To'liq ismi

            // Talaba: o'qish joyi/mutaxassislik/guruh | Xodim: ish joyi/bo'lim/lavozim
            $table->string('affiliation_place')->nullable();
            $table->string('affiliation_unit')->nullable();
            $table->string('affiliation_group')->nullable();

            // Shaxsiy
            $table->string('nationality')->nullable();              // Millati
            $table->date('birth_date')->nullable();                 // Tug'ilgan sanasi
            $table->string('passport', 20)->nullable();             // Pasport
            $table->string('pinfl', 20)->nullable();                // JShShR
            $table->string('gender')->nullable();                   // App\Enums\Gender
            $table->string('district')->nullable();                 // Tuman
            $table->string('address')->nullable();                  // Manzil
            $table->string('phone')->nullable();                    // Telefon
            $table->unsignedSmallInteger('member_year')->nullable(); // A'zo bo'lgan yili

            $table->string('photo')->nullable();                    // Rasm (keyin)
            $table->string('other_library_member')->nullable();     // Boshqa kutubxonalarga a'zolik
            $table->text('note')->nullable();                       // Izoh

            $table->string('status')->default('active');            // App\Enums\ReaderStatus

            $table->timestamps();

            // Qidiruv/filtr indekslari
            $table->index('full_name');
            $table->index('pinfl');
            $table->index('type');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('readers');
    }
};
