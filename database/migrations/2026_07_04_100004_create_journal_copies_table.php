<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_copies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_issue_id')->constrained('journal_issues')->cascadeOnDelete();

            $table->string('inventory_number')->unique(); // inventar raqami

            // Jismoniy holat: new|old|torn|repaired|scribbled — App\Enums\CopyCondition
            $table->string('condition')->nullable();

            // Mavjudlik: available|borrowed|lost|written_off — App\Enums\CopyStatus
            $table->string('status')->default('available');

            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();

            $table->date('arrival_date')->nullable();     // kelgan vaqti
            $table->decimal('price', 12, 2)->nullable();  // narx

            $table->timestamps();

            $table->index(['journal_issue_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_copies');
    }
};
