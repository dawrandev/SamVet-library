<?php

namespace App\Http\Controllers\Admin;

use App\Data\CopyData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCopyRequest;
use App\Http\Requests\Admin\UpdateCopyRequest;
use App\Models\Book;
use App\Models\BookCopy;
use App\Services\CopyService;
use Illuminate\Http\RedirectResponse;

class CopyController extends Controller
{
    public function __construct(
        private readonly CopyService $copyService,
    ) {}

    public function store(StoreCopyRequest $request, Book $book): RedirectResponse
    {
        $this->copyService->create($book, CopyData::fromRequest($request));

        return redirect()
            ->route('admin.books.show', $book)
            ->with('success', __('Nusxa qo‘shildi.'));
    }

    public function update(UpdateCopyRequest $request, Book $book, BookCopy $copy): RedirectResponse
    {
        $this->ensureCopyBelongsToBook($book, $copy);

        $this->copyService->update($copy, CopyData::fromRequest($request));

        return redirect()
            ->route('admin.books.show', $book)
            ->with('success', __('Nusxa yangilandi.'));
    }

    public function destroy(Book $book, BookCopy $copy): RedirectResponse
    {
        $this->ensureCopyBelongsToBook($book, $copy);

        $this->copyService->delete($copy);

        return redirect()
            ->route('admin.books.show', $book)
            ->with('success', __('Nusxa o‘chirildi.'));
    }

    /**
     * Xavfsizlik: nusxa aynan shu kitobga tegishli bo'lishi shart.
     */
    private function ensureCopyBelongsToBook(Book $book, BookCopy $copy): void
    {
        abort_unless($copy->book_id === $book->id, 404);
    }
}
