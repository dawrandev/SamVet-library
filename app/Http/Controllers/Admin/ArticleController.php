<?php

namespace App\Http\Controllers\Admin;

use App\Data\ArticleData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreArticleRequest;
use App\Http\Requests\Admin\UpdateArticleRequest;
use App\Models\Article;
use App\Services\ArticleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function __construct(
        private readonly ArticleService $articleService,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'journal_id', 'resource_field_id', 'kind']);

        return view('pages.admin.articles.index', [
            'articles' => $this->articleService->paginate($filters),
            'filters' => $filters,
            ...$this->articleService->filterOptions($filters['kind'] ?? null),
        ]);
    }

    public function create(Request $request): View
    {
        // After a validation error, re-select the journal/issue the user had chosen.
        $selection = $this->articleService->formSelection(
            $this->intOldInput($request, 'journal_id'),
            $this->intOldInput($request, 'journal_issue_id'),
        );

        return view('pages.admin.articles.create', [
            'kind' => $request->string('kind')->toString() ?: null,
            ...$this->articleService->formOptions(),
            ...$selection,
        ]);
    }

    public function store(StoreArticleRequest $request): RedirectResponse
    {
        $article = $this->articleService->create(ArticleData::fromRequest($request));

        return redirect()
            ->route('admin.articles.show', $article)
            ->with('success', __('Maqola yaratildi.'));
    }

    public function show(Article $article): View
    {
        $article->load([
            'journalIssue.journal.type',
            'journalIssue.journal.publicationPlace',
            'language',
            'resourceField',
        ]);

        return view('pages.admin.articles.show', ['article' => $article]);
    }

    public function edit(Request $request, Article $article): View
    {
        $article->load('journalIssue.journal.type');

        // Old input (after a validation error) wins over the stored value.
        $selection = $this->articleService->formSelection(
            $this->intOldInput($request, 'journal_id') ?? $article->journalIssue?->journal_id,
            $this->intOldInput($request, 'journal_issue_id') ?? $article->journal_issue_id,
        );

        return view('pages.admin.articles.edit', [
            'article' => $article,
            ...$this->articleService->formOptions(),
            ...$selection,
        ]);
    }

    public function update(UpdateArticleRequest $request, Article $article): RedirectResponse
    {
        $this->articleService->update($article, ArticleData::fromRequest($request));

        return redirect()
            ->route('admin.articles.show', $article)
            ->with('success', __('Maqola yangilandi.'));
    }

    public function destroy(Article $article): RedirectResponse
    {
        $kind = $article->journalIssue?->journal?->kind?->value;

        $this->articleService->delete($article);

        return redirect()
            ->route('admin.articles.index', array_filter(['kind' => $kind]))
            ->with('success', __('Maqola o‘chirildi.'));
    }

    /**
     * Read a flashed old-input value as a positive int, or null when absent.
     */
    private function intOldInput(Request $request, string $key): ?int
    {
        $value = $request->old($key);

        return ($value === null || $value === '') ? null : (int) $value;
    }
}
