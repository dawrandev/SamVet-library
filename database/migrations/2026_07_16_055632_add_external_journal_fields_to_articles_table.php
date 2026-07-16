<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Some articles (e.g. a teacher's paper in an international journal) aren't
 * held by the library at all — there's no Journal/JournalIssue to link to.
 * journal_issue_id becomes optional; external_journal_name/_year cover the
 * bibliographic info for that case (doi/pages/annotation already exist).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropForeign(['journal_issue_id']);
        });

        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn('journal_issue_id');
        });

        Schema::table('articles', function (Blueprint $table) {
            $table->foreignId('journal_issue_id')->nullable()->after('id')
                ->constrained('journal_issues')->cascadeOnDelete();
            $table->string('external_journal_name')->nullable()->after('journal_issue_id');
            $table->unsignedSmallInteger('external_journal_year')->nullable()->after('external_journal_name');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn(['external_journal_name', 'external_journal_year']);
        });

        Schema::table('articles', function (Blueprint $table) {
            $table->dropForeign(['journal_issue_id']);
        });

        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn('journal_issue_id');
        });

        Schema::table('articles', function (Blueprint $table) {
            $table->foreignId('journal_issue_id')->after('id')
                ->constrained('journal_issues')->cascadeOnDelete();
        });
    }
};
