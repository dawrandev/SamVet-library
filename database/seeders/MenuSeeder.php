<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use Illuminate\Database\Seeder;

/**
 * Client site navbar menu (based on the mockup). Idempotent: by title(uz)+parent.
 * Links are empty for now — filled in once the content pages are built.
 */
class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $tree = [
            [
                'title' => ['uz' => 'ARM haqida', 'ru' => 'Об ИРЦ', 'kk' => 'ARM haqqında'],
                'children' => [
                    ['title' => ['uz' => 'ARM nizomi', 'ru' => 'Устав ИРЦ', 'kk' => 'ARM nızamı']],
                    ['title' => ['uz' => 'Foydalanish qoidasi', 'ru' => 'Правила пользования', 'kk' => 'Paydalanıw qaǵıydası']],
                    ['title' => ['uz' => 'Tuzilma', 'ru' => 'Структура', 'kk' => 'Dúzilis']],
                    ['title' => ['uz' => 'Statistika', 'ru' => 'Статистика', 'kk' => 'Statistika']],
                    ['title' => ['uz' => 'Me‘yoriy huquqiy hujjatlar', 'ru' => 'Нормативно-правовые документы', 'kk' => 'Normativ-huqıqıy hújjetler']],
                    ['title' => ['uz' => 'Rejalar', 'ru' => 'Планы', 'kk' => 'Rejeler']],
                ],
            ],
            [
                'title' => ['uz' => 'Ilmiy-innovatsion faoliyat', 'ru' => 'Научно-инновационная деятельность', 'kk' => 'Ilimiy-innovaciyalıq is-háreket'],
                'children' => [
                    ['title' => ['uz' => 'Dissertatsiya', 'ru' => 'Диссертации', 'kk' => 'Dissertaciya']],
                    ['title' => ['uz' => 'Avtoreferatlar', 'ru' => 'Авторефераты', 'kk' => 'Avtoreferatlar']],
                    ['title' => ['uz' => 'Monografiyalar', 'ru' => 'Монографии', 'kk' => 'Monografiyalar']],
                    ['title' => ['uz' => 'Konferensiya materiallari', 'ru' => 'Материалы конференций', 'kk' => 'Konferenciya materialları']],
                    ['title' => ['uz' => 'Maqolalar', 'ru' => 'Статьи', 'kk' => 'Maqalalar']],
                ],
            ],
            [
                // Links straight to the real News module — it already has its
                // own category tabs (E'lonlar/Tadbirlar/Tanlovlar), so this is
                // NOT a dropdown with sub-pages (that duplicated/broke it before).
                'title' => ['uz' => 'Yangilik', 'ru' => 'Новости', 'kk' => 'Jańalıqlar'],
                'type' => 'module',
                'url' => 'news.index',
            ],
            [
                'title' => ['uz' => 'Elektron kutubxona', 'ru' => 'Электронная библиотека', 'kk' => 'Elektron kitapxana'],
            ],
        ];

        $this->createTree($tree, null);
    }

    /**
     * @param  array<int, array{title: array<string,string>, children?: array}>  $nodes
     */
    private function createTree(array $nodes, ?int $parentId): void
    {
        foreach ($nodes as $node) {
            $item = MenuItem::where('title->uz', $node['title']['uz'])
                ->where('parent_id', $parentId)
                ->first();

            if (! $item) {
                $item = MenuItem::create([
                    'title' => $node['title'],
                    'parent_id' => $parentId,
                    'type' => $node['type'] ?? 'dropdown',
                    'url' => $node['url'] ?? null,
                    'is_active' => true,
                ]);
            } elseif (isset($node['type']) && $item->type->value === 'dropdown' && $item->url === null) {
                // Only touch still-default items — never overwrite an admin's manual edit.
                $item->update(['type' => $node['type'], 'url' => $node['url'] ?? null]);
            }

            if (! empty($node['children'])) {
                $this->createTree($node['children'], $item->id);
            }
        }
    }
}
