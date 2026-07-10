<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Readers sign in to the public site with their ID number and, for now, a
     * single shared password. Storing it hashed per row means switching to
     * per-reader passwords later needs no code change — only new values.
     */
    public function up(): void
    {
        Schema::table('readers', function (Blueprint $table) {
            $table->string('password')->nullable()->after('id_number');
        });

        // Everyone shares the same password today, so the hash is computed once
        // and applied to every existing reader (hashing each row would be slow).
        DB::table('readers')->update([
            'password' => Hash::make(config('arm.reader_default_password')),
        ]);
    }

    public function down(): void
    {
        Schema::table('readers', function (Blueprint $table) {
            $table->dropColumn('password');
        });
    }
};
