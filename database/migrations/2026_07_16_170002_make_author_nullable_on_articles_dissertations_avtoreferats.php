<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `author` is now optional — when there's no formal author, the material's
     * "boshqa ishtirokchilar" (contributors) list can identify who compiled it.
     */
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->string('author', 500)->nullable()->change();
        });
        Schema::table('dissertations', function (Blueprint $table) {
            $table->string('author', 500)->nullable()->change();
        });
        Schema::table('avtoreferats', function (Blueprint $table) {
            $table->string('author', 500)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->string('author', 500)->nullable(false)->change();
        });
        Schema::table('dissertations', function (Blueprint $table) {
            $table->string('author', 500)->nullable(false)->change();
        });
        Schema::table('avtoreferats', function (Blueprint $table) {
            $table->string('author', 500)->nullable(false)->change();
        });
    }
};
