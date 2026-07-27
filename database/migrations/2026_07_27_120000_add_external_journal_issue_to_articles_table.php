<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * An external (not held by the library) article had no way to record which
 * issue/son it appeared in — only the journal name and year. Free text (not
 * a JournalIssue link, since there's no real Issue record for it).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->string('external_journal_issue')->nullable()->after('external_journal_year');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn('external_journal_issue');
        });
    }
};
