<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The Excel import created script variants ("O'zbek (Kirill)", "Qoraqalpoq
     * (Lotin)", ...) as separate languages. A script is not a language, so they
     * are merged back into the canonical rows.
     *
     * A `locale` then ties a language to the translation key used by the
     * translatable lookups (book types, categories, ...), which lets the book
     * form label a type in the language the book itself is written in.
     */
    private const MERGE = [
        // imported duplicate (uz name) => canonical (uz name)
        "O'zbek (Kirill)" => 'O‘zbek',
        "O'zbek (Lotin)" => 'O‘zbek',
        'Qoraqalpoq (Lotin)' => 'Qoraqalpoq',
    ];

    /** Canonical language (uz name) => translation locale. */
    private const LOCALES = [
        'O‘zbek' => 'uz',
        'Rus' => 'ru',
        'Qoraqalpoq' => 'kk',
    ];

    /** Tables whose rows point at a language. */
    private const REFERENCING_TABLES = ['books', 'journals', 'articles'];

    public function up(): void
    {
        Schema::table('languages', function (Blueprint $table) {
            $table->string('locale', 5)->nullable()->after('name')->index();
        });

        DB::transaction(function () {
            foreach (self::MERGE as $duplicate => $canonical) {
                $duplicateId = $this->languageId($duplicate);
                $canonicalId = $this->languageId($canonical);

                if ($duplicateId === null || $canonicalId === null) {
                    continue; // nothing imported here — fresh install
                }

                foreach (self::REFERENCING_TABLES as $table) {
                    if (Schema::hasTable($table) && Schema::hasColumn($table, 'language_id')) {
                        DB::table($table)
                            ->where('language_id', $duplicateId)
                            ->update(['language_id' => $canonicalId]);
                    }
                }

                DB::table('languages')->where('id', $duplicateId)->delete();
            }

            foreach (self::LOCALES as $name => $locale) {
                $id = $this->languageId($name);

                if ($id !== null) {
                    DB::table('languages')->where('id', $id)->update(['locale' => $locale]);
                }
            }
        });
    }

    public function down(): void
    {
        // The merge cannot be undone — the duplicates carried no extra data.
        Schema::table('languages', function (Blueprint $table) {
            $table->dropIndex(['locale']);
            $table->dropColumn('locale');
        });
    }

    /** Find a language by its Uzbek name (the translatable `name` is JSON). */
    private function languageId(string $uzName): ?int
    {
        $id = DB::table('languages')
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.uz')) = ?", [$uzName])
            ->value('id');

        return $id !== null ? (int) $id : null;
    }
};
