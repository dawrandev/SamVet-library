<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;

/**
 * Shared save logic for the "other participants" (role + name) list attached
 * to a material (Book, Article, Dissertation, Avtoreferat) — replaces the
 * previous set with the given rows on every save, same as a pivot sync().
 */
class ContributorService
{
    /**
     * @param  Model  $contributable  Any model with a `contributors(): MorphMany` relation
     * @param  array<int, array{contributor_role_id?: mixed, name?: mixed}>  $rows
     */
    public function sync(Model $contributable, array $rows): void
    {
        $contributable->contributors()->delete();

        $order = 0;

        foreach ($rows as $row) {
            $roleId = $row['contributor_role_id'] ?? null;
            $name = trim((string) ($row['name'] ?? ''));

            if (! $roleId || $name === '') {
                continue;
            }

            $contributable->contributors()->create([
                'contributor_role_id' => $roleId,
                'name' => $name,
                'sort_order' => $order++,
            ]);
        }
    }
}
