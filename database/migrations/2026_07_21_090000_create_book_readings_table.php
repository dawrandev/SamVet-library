<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per online read — logged each time a signed-in reader opens a
     * book's PDF reader on the client site. Lets the librarian see, alongside
     * physical loans, who read what electronically and exactly when.
     */
    public function up(): void
    {
        Schema::create('book_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reader_id')->constrained('readers')->cascadeOnDelete();
            $table->foreignId('book_id')->constrained('books')->cascadeOnDelete();
            $table->timestamp('read_at');

            $table->index(['reader_id', 'read_at']);
            $table->index(['book_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_readings');
    }
};
