<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The librarian catalogs far too many distinct authors to maintain as a
 * curated lookup — picking (or creating) each one from a dropdown is slow.
 * Authors become a plain comma-separated text field on the book itself,
 * same idea as Article/Dissertation/Avtoreferat's free-text author column.
 * Existing book_author rows are backfilled into the new column before the
 * pivot and the authors lookup table are dropped.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->string('authors', 1000)->nullable()->after('title');
        });

        DB::table('books')->orderBy('id')->select('id')->chunkById(200, function ($books) {
            foreach ($books as $book) {
                $names = DB::table('book_author')
                    ->join('authors', 'authors.id', '=', 'book_author.author_id')
                    ->where('book_author.book_id', $book->id)
                    ->orderBy('authors.id')
                    ->pluck('authors.name');

                if ($names->isNotEmpty()) {
                    DB::table('books')->where('id', $book->id)->update(['authors' => $names->implode(', ')]);
                }
            }
        });

        Schema::dropIfExists('book_author');
        Schema::dropIfExists('authors');
    }

    public function down(): void
    {
        Schema::create('authors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('book_author', function (Blueprint $table) {
            $table->foreignId('book_id')->constrained('books')->cascadeOnDelete();
            $table->foreignId('author_id')->constrained('authors')->cascadeOnDelete();
            $table->primary(['book_id', 'author_id']);
        });

        Schema::table('books', function (Blueprint $table) {
            $table->dropColumn('authors');
        });
    }
};
