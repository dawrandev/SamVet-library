<?php

use App\Models\MenuItem;
use Illuminate\Database\Migrations\Migration;

/**
 * The "Yangilik" navbar item was seeded as an empty dropdown whose 4 children
 * (Tanlovlar/Tadbirlar/E'lonlar/Ko'rgazmalar) had no url and no page content,
 * so they rendered an empty placeholder instead of the real, working News
 * catalog (route `news.index`, which already has its own category tabs).
 * This links "Yangilik" directly to that module and drops the dead children —
 * but only if an admin never customized them (still default dropdown/no-url).
 */
return new class extends Migration
{
    public function up(): void
    {
        $yangilik = MenuItem::whereNull('parent_id')
            ->where('title->uz', 'Yangilik')
            ->where('type', 'dropdown')
            ->whereNull('url')
            ->first();

        if (! $yangilik) {
            return;
        }

        $yangilik->children()
            ->where('type', 'dropdown')
            ->whereNull('url')
            ->whereDoesntHave('page')
            ->delete();

        $yangilik->update(['type' => 'module', 'url' => 'news.index']);
    }

    public function down(): void
    {
        $yangilik = MenuItem::whereNull('parent_id')
            ->where('title->uz', 'Yangilik')
            ->where('type', 'module')
            ->where('url', 'news.index')
            ->first();

        if (! $yangilik) {
            return;
        }

        $yangilik->update(['type' => 'dropdown', 'url' => null]);

        foreach ([
            ['uz' => 'Tanlovlar', 'ru' => 'Конкурсы', 'kk' => 'Tańlawlar'],
            ['uz' => 'Tadbirlar', 'ru' => 'Мероприятия', 'kk' => 'Ilajlar'],
            ['uz' => 'E‘lonlar', 'ru' => 'Объявления', 'kk' => 'Járiyalawlar'],
            ['uz' => 'Ko‘rgazmalar', 'ru' => 'Выставки', 'kk' => 'Kórgizbeler'],
        ] as $title) {
            MenuItem::create(['title' => $title, 'parent_id' => $yangilik->id, 'is_active' => true]);
        }
    }
};
