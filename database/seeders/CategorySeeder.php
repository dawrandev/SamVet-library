<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $tree = [
            [
                'name' => ['uz' => 'O‘quv-uslubiy adabiyotlar', 'ru' => 'Учебно-методическая литература', 'kk' => 'Oqıw-uslubiy ádebiyatlar'],
                'children' => [
                    [
                        'name' => ['uz' => 'Iqtisodiyot', 'ru' => 'Экономика', 'kk' => 'Ekonomika'],
                        'children' => [
                            ['name' => ['uz' => 'Iqtisodiyot nazariyasi', 'ru' => 'Экономическая теория', 'kk' => 'Ekonomika teoriyası']],
                        ],
                    ],
                    [
                        'name' => ['uz' => 'Veterinariya', 'ru' => 'Ветеринария', 'kk' => 'Veterinariya'],
                        'children' => [
                            ['name' => ['uz' => 'Umumiy veterinariya', 'ru' => 'Общая ветеринария', 'kk' => 'Ulıwma veterinariya']],
                            ['name' => ['uz' => 'Epizootologiya', 'ru' => 'Эпизоотология', 'kk' => 'Epizootologiya']],
                        ],
                    ],
                ],
            ],
            [
                'name' => ['uz' => 'Ilmiy adabiyotlar', 'ru' => 'Научная литература', 'kk' => 'Ilimiy ádebiyatlar'],
                'children' => [
                    ['name' => ['uz' => 'Monografiyalar', 'ru' => 'Монографии', 'kk' => 'Monografiyalar']],
                ],
            ],
        ];

        $this->createTree($tree, null);
    }

    /**
     * @param  array<int, array{name: array, children?: array}>  $nodes
     */
    private function createTree(array $nodes, ?int $parentId): void
    {
        foreach ($nodes as $node) {
            $category = Category::where('name->uz', $node['name']['uz'])
                ->where('parent_id', $parentId)
                ->first();

            if (! $category) {
                $category = Category::create([
                    'name' => $node['name'],
                    'parent_id' => $parentId,
                ]);
            }

            if (! empty($node['children'])) {
                $this->createTree($node['children'], $category->id);
            }
        }
    }
}
