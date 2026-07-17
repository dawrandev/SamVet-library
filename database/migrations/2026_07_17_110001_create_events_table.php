<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // App\Enums\EventType
            $table->date('date');
            // Optional: the news post about this event. When set, the event's
            // public link is derived from it instead of being typed by hand.
            $table->foreignId('news_id')->nullable()->constrained('news')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index('date');
        });

        Schema::create('event_event_location', function (Blueprint $table) {
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_location_id')->constrained()->cascadeOnDelete();
            $table->primary(['event_id', 'event_location_id']);
        });

        Schema::create('event_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            // Exactly one of these two is set — enforced in the FormRequest/Service,
            // not the DB, same convention as the subscription reader/budget source.
            $table->foreignId('reader_id')->nullable()->constrained('readers')->cascadeOnDelete();
            $table->string('external_name')->nullable(); // a guest who isn't a registered reader
            $table->string('role'); // App\Enums\EventRole
            $table->timestamps();

            $table->index('event_id');
            $table->index('reader_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_participants');
        Schema::dropIfExists('event_event_location');
        Schema::dropIfExists('events');
    }
};
