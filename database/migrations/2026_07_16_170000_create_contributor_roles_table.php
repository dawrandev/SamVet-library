<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contributor_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // A starting set the librarian asked for — she can add more later, like any other lookup.
        $now = now();
        DB::table('contributor_roles')->insert([
            ['name' => 'Muharrir', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'To‘plovchi', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Tahrirchi', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Tarjimon', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Rassom', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('contributor_roles');
    }
};
