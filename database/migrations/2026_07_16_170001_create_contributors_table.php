<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Other people involved in a material (book/article/dissertation/avtoreferat)
     * beyond its formal author — each tagged with a role (muharrir, tarjimon, ...).
     */
    public function up(): void
    {
        Schema::create('contributors', function (Blueprint $table) {
            $table->id();
            $table->string('contributable_type');
            $table->unsignedBigInteger('contributable_id');
            $table->foreignId('contributor_role_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['contributable_type', 'contributable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contributors');
    }
};
