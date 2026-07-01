<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Lookup (ma'lumotnoma) uchun umumiy repozitoriy shartnomasi.
 * Barcha DB so'rovlari faqat shu qatlamda.
 */
interface LookupRepositoryInterface
{
    /**
     * Ro'yxat (index sahifasi uchun).
     *
     * @return Collection<int, Model>
     */
    public function all(): Collection;

    public function find(int $id): ?Model;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Model;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Model $model, array $attributes): Model;

    public function delete(Model $model): void;
}
