<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // Ierarxik daraxt: [nom => [bolalar...]]
        $tree = [
            'O‘quv-uslubiy adabiyotlar' => [
                'Iqtisodiyot' => [
                    'Iqtisodiyot nazariyasi' => [],
                ],
                'Veterinariya' => [
                    'Umumiy veterinariya' => [],
                    'Epizootologiya' => [],
                ],
            ],
            'Ilmiy adabiyotlar' => [
                'Monografiyalar' => [],
            ],
        ];

        $this->createTree($tree, null);
    }

    /**
     * Daraxtni rekursiv yaratish.
     */
    private function createTree(array $nodes, ?int $parentId): void
    {
        foreach ($nodes as $name => $children) {
            $category = Category::firstOrCreate([
                'name' => $name,
                'parent_id' => $parentId,
            ]);

            if (! empty($children)) {
                $this->createTree($children, $category->id);
            }
        }
    }
}
