<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Newspapers don't have an inventory number per copy — only journals do.
        Schema::table('journal_copies', function (Blueprint $table) {
            $table->string('inventory_number')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('journal_copies', function (Blueprint $table) {
            $table->string('inventory_number')->nullable(false)->change();
        });
    }
};
