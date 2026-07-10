<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Services\Site\OnlineReaderService;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Protected online reading. PDFs live on the private disk and are never linked
 * directly: they are streamed through these auth-guarded actions, inline only.
 */
class OnlineReaderController extends Controller
{
    public function __construct(
        private readonly OnlineReaderService $reader,
    ) {}

    public function book(string $slug): View
    {
        $book = $this->reader->book($slug);

        return view('pages.site.reader', [
            'title' => $book->title,
            'subtitle' => $book->authors->pluck('name')->join(', '),
            'backUrl' => route('book.show', $book->slug),
            'fileUrl' => route('read.book.file', $book->slug),
        ]);
    }

    public function bookFile(string $slug): StreamedResponse
    {
        return $this->stream($this->reader->book($slug)->electronic_file);
    }

    public function article(string $slug): View
    {
        $article = $this->reader->article($slug);

        return view('pages.site.reader', [
            'title' => $article->title,
            'subtitle' => $article->author,
            'backUrl' => route('article.show', $article->slug),
            'fileUrl' => route('read.article.file', $article->slug),
        ]);
    }

    public function articleFile(string $slug): StreamedResponse
    {
        return $this->stream($this->reader->article($slug)->electronic_file);
    }

    /**
     * Stream a private PDF for in-browser rendering. `inline` plus a private,
     * no-store cache keeps it out of the browser's download flow and disk cache.
     */
    private function stream(string $path): StreamedResponse
    {
        $disk = Storage::disk('local');

        abort_unless($disk->exists($path), 404);

        return $disk->response($path, 'document.pdf', [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ], 'inline');
    }
}
