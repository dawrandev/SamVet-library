<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Controlled subscription delivery destinations (library/branch names) —
     * a subscription must pick one of these instead of a free-typed address,
     * so newspapers stop being mailed to a subscriber's home by mistake.
     */
    public function up(): void
    {
        Schema::create('delivery_locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // Verbatim from the librarian's own tracking sheet — she can rename/add more later.
        DB::table('delivery_locations')->insert([
            'name' => 'SamMVShBU NF',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_locations');
    }
};
