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

    public function create(): View
    {
        return view('pages.admin.books.create', $this->bookService->formOptions());
    }

    public function store(StoreBookRequest $request): RedirectResponse
    {
        $this->bookService->create(BookData::fromRequest($request));

        return redirect()
            ->route('admin.books.index')
            ->with('success', __('Kitob muvaffaqiyatli qo‘shildi.'));
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
