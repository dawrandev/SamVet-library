<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Link a session to a computer from the registry.
     * The legacy free-text `computer_number` column stays (nullable) for old records.
     */
    public function up(): void
    {
        Schema::table('computer_sessions', function (Blueprint $table) {
            $table->foreignId('computer_id')->nullable()->after('computer_number')
                ->constrained('computers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('computer_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('computer_id');
        });
    }
};
