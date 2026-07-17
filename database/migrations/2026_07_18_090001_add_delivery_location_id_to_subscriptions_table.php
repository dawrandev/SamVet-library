<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nullable so existing subscriptions aren't broken — new/edited ones are
     * required to set it via StoreSubscriptionRequest going forward.
     */
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->foreignId('delivery_location_id')->nullable()->after('journal_id')
                ->constrained('delivery_locations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('delivery_location_id');
        });
    }
};
