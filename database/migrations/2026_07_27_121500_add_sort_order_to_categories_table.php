<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Top-level categories drive the public site's "Elektron kutubxona bo'limlari"
 * tile order — the librarian needs to control that order from the admin
 * panel, which the previous implicit `id` ordering didn't allow.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('parent_id');
        });

        // Backfill: preserve the current (id-based) order as the starting
        // sort_order, separately within each sibling group (top-level
        // categories among themselves, each parent's children among theirs).
        DB::table('categories')->orderBy('id')->get(['id', 'parent_id'])
            ->groupBy('parent_id')
            ->each(function ($siblings) {
                foreach ($siblings->values() as $order => $row) {
                    DB::table('categories')->where('id', $row->id)->update(['sort_order' => $order]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
