<?php

namespace App\Http\Controllers\Admin;

use App\Data\BookData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBookRequest;
use App\Http\Requests\Admin\UpdateBookRequest;
use App\Models\Book;
use App\Services\BookService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookController extends Controller
{
    public function __construct(
        private readonly BookService $bookService,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'category_id', 'language_id']);

        return view('pages.admin.books.index', [
            'books' => $this->bookService->paginate($filters),
            'filters' => $filters,
            ...$this->bookService->filterOptions(),
        ]);
    }

    public function show(Book $book): View
    {
        $book->load(['type', 'language', 'publisher', 'authors', 'categories.parent', 'copies.location', 'work.editions.language']);

        return view('pages.admin.books.show', ['book' => $book]);
    }

    public function create(): View
    {
        return view('pages.admin.books.create', $this->bookService->formOptions());
    }

    public function createTranslation(Book $book): View
    {
        $book->load(['authors', 'categories']);

        return view('pages.admin.books.create', array_merge(
            $this->bookService->formOptions(),
            ['sourceBook' => $book],
        ));
    }

    public function store(StoreBookRequest $request): RedirectResponse
    {
        $translationOf = $request->integer('translation_of') ?: null;

        $book = $this->bookService->create(BookData::fromRequest($request), $translationOf);

        return redirect()
            ->route('admin.books.show', $book)
            ->with('success', __('Kitob yaratildi. Endi nusxalar qo‘shishingiz mumkin.'));
    }

    public function edit(Book $book): View
    {
        $book->load(['authors', 'categories']);

        return view('pages.admin.books.edit', [
            'book' => $book,
            ...$this->bookService->formOptions(),
        ]);
    }

    public function update(UpdateBookRequest $request, Book $book): RedirectResponse
    {
        $this->bookService->update($book, BookData::fromRequest($request));

        return redirect()
            ->route('admin.books.index')
            ->with('success', __('Kitob yangilandi.'));
    }

    public function destroy(Book $book): RedirectResponse
    {
        $this->bookService->delete($book);

        return redirect()
            ->route('admin.books.index')
            ->with('success', __('Kitob o‘chirildi.'));
    }
}
