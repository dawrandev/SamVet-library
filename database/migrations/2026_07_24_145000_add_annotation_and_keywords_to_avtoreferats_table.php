<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Annotatsiya" was dropped earlier this session, then the librarian said it's
 * actually needed after all — re-added, plus a new "Tayanch so'zlar" (keywords)
 * field. 0 avtoreferats exist in production, so this is a clean addition.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('avtoreferats', function (Blueprint $table) {
            $table->text('annotation')->nullable()->after('inventory_number');
            $table->string('keywords', 500)->nullable()->after('annotation');
        });
    }

    public function down(): void
    {
        Schema::table('avtoreferats', function (Blueprint $table) {
            $table->dropColumn(['annotation', 'keywords']);
        });
    }
};
