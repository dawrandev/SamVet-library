<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The librarian's own mockup for the avtoreferat card never had a "Resurs
 * sohasi" or "Annotatsiya" field — they were mistakenly copied in from
 * another module. 0 rows exist in production, so this is a clean drop.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('avtoreferats', function (Blueprint $table) {
            $table->dropForeign(['resource_field_id']);
            $table->dropColumn(['resource_field_id', 'annotation']);
        });
    }

    public function down(): void
    {
        Schema::table('avtoreferats', function (Blueprint $table) {
            $table->foreignId('resource_field_id')->nullable()->after('id')->constrained('resource_fields')->nullOnDelete();
            $table->text('annotation')->nullable();
        });
    }
};
