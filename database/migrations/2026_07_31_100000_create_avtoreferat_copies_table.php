<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors book_copies: an avtoreferat (title/metadata) can have several
 * physical copies, each with its own inventory number — librarian request.
 * Inventory-tracking only, no lending/circulation (unlike BookCopy, no
 * `status`/`loans` here — an avtoreferat is still read only via its
 * title-level electronic_file, never physically issued).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avtoreferat_copies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('avtoreferat_id')->constrained()->cascadeOnDelete();
            $table->string('inventory_number')->nullable()->unique();
            // text, not string — matches the JSON-array condition storage
            // every sibling table (book_copies, journal_copies, dissertations)
            // uses.
            $table->text('condition')->nullable();
            $table->string('acquisition_act_number')->nullable();
            $table->date('acquisition_act_at')->nullable();
            $table->string('disposal_act_number')->nullable();
            $table->date('disposal_act_at')->nullable();
            $table->timestamps();

            $table->index('avtoreferat_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avtoreferat_copies');
    }
};
