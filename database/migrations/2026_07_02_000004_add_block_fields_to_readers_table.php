<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Blocking fields. status=blocked + blocked_until=null → permanently;
     * blocked_until=date → temporarily restricted until that date.
     */
    public function up(): void
    {
        Schema::table('readers', function (Blueprint $table) {
            $table->date('blocked_until')->nullable()->after('status'); // timed block (null=permanent/none)
            $table->string('block_reason')->nullable()->after('blocked_until');
        });
    }

    public function down(): void
    {
        Schema::table('readers', function (Blueprint $table) {
            $table->dropColumn(['blocked_until', 'block_reason']);
        });
    }
};
