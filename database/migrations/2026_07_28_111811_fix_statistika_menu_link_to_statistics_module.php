<?php

use App\Models\MenuItem;
use Illuminate\Database\Migrations\Migration;

/**
 * The "Statistika" item under "ARM haqida" was seeded with its default type
 * (dropdown, no url, no Page body — see MenuSeeder), so it fell through to
 * the empty content-page renderer ("Sahifa matni tez orada qo'shiladi")
 * instead of the real, live statistics page (route `statistics`, now built
 * with fund + reader breakdowns). This links it directly to that module —
 * but only if an admin never customized it (still default dropdown/no-url),
 * matching the same guarded pattern as the earlier "Yangilik" menu fix
 * (2026_07_14_113100).
 */
return new class extends Migration
{
    public function up(): void
    {
        $armHaqida = MenuItem::whereNull('parent_id')->where('title->uz', 'ARM haqida')->first();

        if (! $armHaqida) {
            return;
        }

        $statistika = $armHaqida->children()
            ->where('title->uz', 'Statistika')
            ->where('type', 'dropdown')
            ->whereNull('url')
            ->whereDoesntHave('page')
            ->first();

        if (! $statistika) {
            return;
        }

        $statistika->update(['type' => 'module', 'url' => 'statistics']);
    }

    public function down(): void
    {
        $statistika = MenuItem::where('title->uz', 'Statistika')
            ->where('type', 'module')
            ->where('url', 'statistics')
            ->first();

        if (! $statistika) {
            return;
        }

        $statistika->update(['type' => 'dropdown', 'url' => null]);
    }
};
