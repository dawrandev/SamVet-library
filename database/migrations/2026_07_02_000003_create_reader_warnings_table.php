<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reader warnings. After 3, blocking is recommended.
     */
    public function up(): void
    {
        Schema::create('reader_warnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reader_id')->constrained('readers')->cascadeOnDelete();
            $table->string('reason');                 // App\Enums\WarningReason
            $table->text('note')->nullable();         // detailed note
            $table->date('warned_at');                // date warned
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); // which admin
            $table->timestamps();

            $table->index('reader_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reader_warnings');
    }
};
