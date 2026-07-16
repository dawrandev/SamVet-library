<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Computers switch from the open, admin-extendable `locations` lookup
     * table to a fixed, closed set of 3 values (App\Enums\ComputerLocation).
     */
    public function up(): void
    {
        Schema::table('computers', function (Blueprint $table) {
            $table->dropForeign(['location_id']);
            $table->dropIndex(['location_id']);
            $table->dropColumn('location_id');
        });

        Schema::table('computers', function (Blueprint $table) {
            $table->string('location')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('computers', function (Blueprint $table) {
            $table->dropColumn('location');
        });

        Schema::table('computers', function (Blueprint $table) {
            $table->foreignId('location_id')->nullable()->after('status')->constrained('locations')->nullOnDelete();
            $table->index('location_id');
        });
    }
};
