<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Exact publication date of the issue (newspapers date each issue —
     * `year` alone isn't precise enough), distinct from a copy's arrival_date.
     */
    public function up(): void
    {
        Schema::table('journal_issues', function (Blueprint $table) {
            $table->date('issue_date')->nullable()->after('year');
        });
    }

    public function down(): void
    {
        Schema::table('journal_issues', function (Blueprint $table) {
            $table->dropColumn('issue_date');
        });
    }
};
