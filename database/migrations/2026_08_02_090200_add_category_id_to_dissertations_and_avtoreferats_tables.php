<?php

use App\Models\Category;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Dissertations/avtoreferats had no link to the categories tree at all —
 * "Ilmiy adabiyotlar" (scientific literature) on the homepage only ever
 * counted books, so dissertations/avtoreferats got their own separate
 * homepage tiles instead of folding into it. Every existing row is
 * backfilled to "Ilmiy adabiyotlar" itself, since that's what they already
 * conceptually are — the admin can re-categorize individual ones later.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dissertations', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('title')->constrained('categories')->nullOnDelete();
        });

        Schema::table('avtoreferats', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('title')->constrained('categories')->nullOnDelete();
        });

        $ilmiyId = Category::where('name->uz', 'Ilmiy adabiyotlar')->whereNull('parent_id')->value('id');

        if ($ilmiyId !== null) {
            DB::table('dissertations')->whereNull('category_id')->update(['category_id' => $ilmiyId]);
            DB::table('avtoreferats')->whereNull('category_id')->update(['category_id' => $ilmiyId]);
        }
    }

    public function down(): void
    {
        Schema::table('dissertations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
        });

        Schema::table('avtoreferats', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
        });
    }
};
