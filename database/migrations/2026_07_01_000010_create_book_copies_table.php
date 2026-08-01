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

            // Physical condition (multi-select, JSON array stored as text): new|old|torn|repaired|scribbled — App\Enums\CopyCondition
            $table->text('condition')->nullable();

            // Availability: available|lost|written_off — App\Enums\CopyStatus
            $table->string('status')->default('available');

            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();

            // Acts (librarian only) — plain fields, no file upload
            $table->string('acquisition_act_number')->nullable();
            $table->date('acquisition_act_at')->nullable();
            $table->string('disposal_act_number')->nullable();
            $table->date('disposal_act_at')->nullable();

            $table->timestamps();

            $table->index(['book_id', 'status']); // for availability calculation
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_copies');
    }
};
