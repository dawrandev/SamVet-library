<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Which post office branch handles a subscription's mail (e.g. Toshkent,
     * Nukus). Exact branch names aren't known yet — the librarian adds them
     * herself via the lookup admin UI, same as any other lookup.
     */
    public function up(): void
    {
        Schema::create('post_branches', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_branches');
    }
};
