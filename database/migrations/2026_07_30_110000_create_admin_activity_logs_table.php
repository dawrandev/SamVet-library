<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Who changed what, and when — for admin actions on sensitive records
     * (readers' PII, inventory, subscriptions, loans).
     */
    public function up(): void
    {
        Schema::create('admin_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action'); // App\Enums\AdminActivityAction
            $table->string('subject_type'); // model basename, e.g. "Reader", "Book"
            $table->unsignedBigInteger('subject_id');
            $table->json('changes')->nullable(); // {field: [old, new]}, sensitive values redacted
            $table->timestamps();

            $table->index(['subject_type', 'subject_id']);
            $table->index('admin_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_activity_logs');
    }
};
