<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Acts are no longer uploaded PDFs — just the act number and its date/time.
        Schema::table('book_copies', function (Blueprint $table) {
            $table->dropColumn(['acquisition_act', 'disposal_act']);

            $table->string('acquisition_act_number')->nullable()->after('price');
            $table->dateTime('acquisition_act_at')->nullable()->after('acquisition_act_number');
            $table->string('disposal_act_number')->nullable()->after('acquisition_act_at');
            $table->dateTime('disposal_act_at')->nullable()->after('disposal_act_number');
        });
    }

    public function down(): void
    {
        Schema::table('book_copies', function (Blueprint $table) {
            $table->dropColumn(['acquisition_act_number', 'acquisition_act_at', 'disposal_act_number', 'disposal_act_at']);

            $table->string('acquisition_act')->nullable();
            $table->string('disposal_act')->nullable();
        });
    }
};
