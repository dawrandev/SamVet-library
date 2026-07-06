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

            $table->string('inventory_number')->unique(); // inventory number

            // Format (physical): print | braille — App\Enums\BookFormat
            $table->string('format')->default('print');

            // Physical condition: new|old|torn|repaired|scribbled — App\Enums\CopyCondition
            $table->string('condition')->default('new');

            // Availability: available|lost|written_off — App\Enums\CopyStatus
            $table->string('status')->default('available');

            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();

            $table->decimal('price', 12, 2)->nullable(); // price

            // Acts (librarian only) — protected stream
            $table->string('acquisition_act')->nullable(); // acquisition act (PDF)
            $table->string('disposal_act')->nullable();    // disposal act (PDF)

            $table->timestamps();

            $table->index(['book_id', 'status']); // for availability calculation
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_copies');
    }
};
