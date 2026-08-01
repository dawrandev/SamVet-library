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

            $table->string('inventory_number')->nullable()->unique(); // inventory number

            // Physical condition: new|old|torn|repaired|scribbled — App\Enums\CopyCondition
            $table->text('condition')->nullable();

            // Availability: available|borrowed|lost|written_off — App\Enums\CopyStatus
            $table->string('status')->default('available');

            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();

            $table->date('arrival_date')->nullable();     // arrival date

            $table->timestamps();

            $table->index(['journal_issue_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_copies');
    }
};
