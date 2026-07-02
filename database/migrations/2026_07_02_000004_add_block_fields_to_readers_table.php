<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bloklash maydonlari. status=blocked + blocked_until=null → butunlay;
     * blocked_until=sana → shu sanagacha vaqtincha cheklangan.
     */
    public function up(): void
    {
        Schema::table('readers', function (Blueprint $table) {
            $table->date('blocked_until')->nullable()->after('status'); // muddatли blok (null=butunlay/yo'q)
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
