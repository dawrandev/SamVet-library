<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A large file (PDF/video/audio) being uploaded in chunks, before the
     * admin submits the rest of the form it belongs to. See
     * App\Services\ChunkedUploadService.
     */
    public function up(): void
    {
        Schema::create('upload_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('token')->unique();
            $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
            $table->string('kind'); // App\Enums\ChunkedUploadKind
            $table->string('original_filename');
            $table->unsignedBigInteger('total_size'); // bytes, declared at start(), capped by kind's max
            $table->unsignedInteger('total_chunks');
            $table->unsignedInteger('next_chunk_index')->default(0); // sequential — resumes from here
            $table->string('status')->default('uploading'); // uploading | assembled
            $table->string('assembled_path')->nullable();
            $table->timestamps();

            $table->index('admin_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('upload_sessions');
    }
};
