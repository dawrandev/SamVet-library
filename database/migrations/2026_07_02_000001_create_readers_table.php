<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('readers', function (Blueprint $table) {
            $table->id();

            // Identification
            $table->string('id_number')->nullable()->unique();      // ID number (BT0122001)
            $table->string('password')->nullable();
            $table->string('registration_number')->nullable();      // Registration number (№129)
            $table->date('issued_date')->nullable();                // Issued date

            $table->foreignId('reader_type_id')->constrained('reader_types')->restrictOnDelete();
            $table->string('full_name');                            // Full name

            // Student: place of study/specialty/group | Staff: workplace/department/position
            $table->foreignId('affiliation_place_id')->nullable()->constrained('affiliation_places')->nullOnDelete();
            $table->foreignId('affiliation_unit_id')->nullable()->constrained('affiliation_units')->nullOnDelete();
            $table->foreignId('affiliation_group_id')->nullable()->constrained('affiliation_groups')->nullOnDelete();

            // Personal
            $table->string('nationality')->nullable();              // Nationality
            $table->date('birth_date')->nullable();                 // Birth date
            $table->string('passport', 20)->nullable();             // Passport
            $table->string('pinfl', 20)->nullable();                // PINFL
            $table->string('gender')->nullable();                   // App\Enums\Gender
            $table->foreignId('region_id')->nullable()->constrained('regions')->nullOnDelete();
            $table->foreignId('district_id')->nullable()->constrained('districts')->nullOnDelete();
            $table->string('address')->nullable();                  // Address
            $table->string('phone')->nullable();                    // Phone
            $table->unsignedSmallInteger('member_year')->nullable(); // Year of membership

            $table->string('photo')->nullable();                    // Photo (later)
            $table->string('other_library_member')->nullable();     // Membership in other libraries
            $table->text('note')->nullable();                       // Note

            $table->string('status')->default('active');            // App\Enums\ReaderStatus
            $table->date('blocked_until')->nullable();
            $table->string('block_reason')->nullable();
            $table->text('left_reason')->nullable();

            $table->timestamps();

            // Search/filter indexes
            $table->index('full_name');
            $table->index('pinfl');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('readers');
    }
};
