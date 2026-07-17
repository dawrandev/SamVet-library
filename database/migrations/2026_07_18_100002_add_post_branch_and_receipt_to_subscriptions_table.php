<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->foreignId('post_branch_id')->nullable()->after('delivery_location_id')
                ->constrained('post_branches')->nullOnDelete();
            $table->string('receipt_file')->nullable()->after('amount'); // proof-of-payment scan/photo
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('post_branch_id');
            $table->dropColumn('receipt_file');
        });
    }
};
