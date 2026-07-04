<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use Illuminate\Database\Seeder;

/**
 * Client sayt navbar menyusi (mockup asosida). Idempotent: sarlavha(uz)+ota bo'yicha.
 * Havolalar hozircha bo'sh — kontent sahifalar qurilgach to'ldiriladi.
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
                'title' => ['uz' => 'Yangilik', 'ru' => 'Новости', 'kk' => 'Jańalıqlar'],
                'children' => [
                    ['title' => ['uz' => 'Tanlovlar', 'ru' => 'Конкурсы', 'kk' => 'Tańlawlar']],
                    ['title' => ['uz' => 'Tadbirlar', 'ru' => 'Мероприятия', 'kk' => 'Ilajlar']],
                    ['title' => ['uz' => 'E‘lonlar', 'ru' => 'Объявления', 'kk' => 'Járiyalawlar']],
                    ['title' => ['uz' => 'Ko‘rgazmalar', 'ru' => 'Выставки', 'kk' => 'Kórgizbeler']],
                ],
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
                    'url' => null,
                    'is_active' => true,
                ]);
            }

            if (! empty($node['children'])) {
                $this->createTree($node['children'], $item->id);
            }
        }
    }
}
