<?php

namespace App\Repositories\Eloquent;

use App\Models\Article;
use App\Repositories\Contracts\ArticleRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ArticleRepository implements ArticleRepositoryInterface
{
    /**
     * Eager loads to avoid N+1 (issue → journal → type/place, plus lookups).
     *
     * @var array<int, string>
     */
    private const RELATIONS = [
        'journalIssue.journal.type',
        'journalIssue.journal.publicationPlace',
        'language',
        'resourceField',
    ];

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Article::query()
            ->with(self::RELATIONS)
            // Search (title or author)
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('author', 'like', "%{$search}%");
                });
            })
            // Filter by journal (through the parent issue)
            ->when($filters['journal_id'] ?? null, function ($query, int $journalId) {
                $query->whereHas('journalIssue', function ($q) use ($journalId) {
                    $q->where('journal_id', $journalId);
                });
            })
            ->when($filters['resource_field_id'] ?? null, function ($query, int $fieldId) {
                $query->where('resource_field_id', $fieldId);
            })
            ->when($filters['kind'] ?? null, function ($query, string $kind) {
                $query->whereHas('journalIssue.journal', fn ($q) => $q->where('kind', $kind));
            })
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function find(int $id): ?Article
    {
        return Article::with(self::RELATIONS)->find($id);
    }

    public function create(array $data): Article
    {
        return Article::create($data);
    }

    public function update(Article $article, array $data): Article
    {
        $article->update($data);

        return $article;
    }

    public function delete(Article $article): void
    {
        $article->delete();
    }
}
