<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Resurs sohasi" was never part of the librarian's own dissertation mockup —
 * it was mistakenly copied in from another module, same class of bug already
 * fixed for avtoreferat (see 2026_07_24_120800_drop_resource_field_and_annotation_from_avtoreferats_table.php).
 * Unlike that fix, dissertation's `annotation` field IS wanted and stays.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dissertations', function (Blueprint $table) {
            $table->dropForeign(['resource_field_id']);
            $table->dropColumn('resource_field_id');
        });
    }

    public function down(): void
    {
        Schema::table('dissertations', function (Blueprint $table) {
            $table->foreignId('resource_field_id')->nullable()->after('id')->constrained('resource_fields')->nullOnDelete();
        });
    }
};
