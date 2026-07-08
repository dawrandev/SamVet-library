<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Computers in the electronic reading room (inventory).
     */
    public function up(): void
    {
        Schema::create('computers', function (Blueprint $table) {
            $table->id();
            $table->string('model');                        // Model
            $table->string('type');                         // App\Enums\ComputerType
            $table->string('inventory_number')->unique();   // inventory number (librarian only)
            $table->string('status')->default('working');   // App\Enums\ComputerStatus
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('location_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('computers');
    }
};
