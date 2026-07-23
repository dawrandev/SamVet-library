<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A dissertation was originally modeled "like an article" — required to
     * belong to a journal issue. That was wrong: a dissertation is a standalone
     * bound thesis, not a journal publication. Nothing in production has ever
     * used this column meaningfully (0 rows existed when this was written).
     */
    public function up(): void
    {
        Schema::table('dissertations', function (Blueprint $table) {
            $table->dropForeign(['journal_issue_id']);
            $table->dropColumn('journal_issue_id');
        });
    }

    public function down(): void
    {
        Schema::table('dissertations', function (Blueprint $table) {
            $table->foreignId('journal_issue_id')->nullable()->after('id')->constrained('journal_issues')->cascadeOnDelete();
        });
    }
};
