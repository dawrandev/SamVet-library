<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_copies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained('books')->cascadeOnDelete();

            $table->string('inventory_number')->unique(); // inventar raqami

            // Format (jismoniy): print | braille — App\Enums\BookFormat
            $table->string('format')->default('print');

            // Jismoniy holat: new|old|torn|repaired|scribbled — App\Enums\CopyCondition
            $table->string('condition')->default('new');

            // Mavjudlik: available|lost|written_off — App\Enums\CopyStatus
            $table->string('status')->default('available');

            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();

            $table->decimal('price', 12, 2)->nullable(); // narx

            // Aktlar (faqat kutubxonachi) — himoyalangan stream
            $table->string('acquisition_act')->nullable(); // kirish akti (PDF)
            $table->string('disposal_act')->nullable();    // chiqish akti (PDF)

            $table->timestamps();

            $table->index(['book_id', 'status']); // mavjudlik hisobi uchun
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_copies');
    }
};
