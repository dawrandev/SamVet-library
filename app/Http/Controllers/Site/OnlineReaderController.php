<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Concerns\StreamsPrivateFiles;
use App\Http\Controllers\Controller;
use App\Services\OnlineReadService;
use App\Services\Site\OnlineReaderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Protected online reading. PDFs live on the private disk and are never linked
 * directly: they are streamed through these auth-guarded actions, inline only.
 */
class OnlineReaderController extends Controller
{
    use StreamsPrivateFiles;

    public function __construct(
        private readonly OnlineReaderService $reader,
        private readonly OnlineReadService $onlineReads,
    ) {}

    public function book(string $slug): View
    {
        $book = $this->reader->book($slug);

        // One log row per opened reading session — lets the librarian see who
        // read what electronically and exactly when, alongside physical loans.
        $this->onlineReads->log(Auth::guard('reader')->user(), $book);

        return view('pages.site.reader', [
            'title' => $book->title,
            'subtitle' => $book->authors,
            'backUrl' => route('book.show', $book->slug),
            'fileUrl' => route('read.book.file', $book->slug),
        ]);
    }

    public function bookFile(Request $request, string $slug): StreamedResponse
    {
        return $this->streamPdf($request, $this->reader->book($slug)->electronic_file);
    }

    public function article(string $slug): View
    {
        $article = $this->reader->article($slug);

        $this->onlineReads->log(Auth::guard('reader')->user(), $article);

        return view('pages.site.reader', [
            'title' => $article->title,
            'subtitle' => $article->author,
            'backUrl' => route('article.show', $article->slug),
            'fileUrl' => route('read.article.file', $article->slug),
        ]);
    }

    public function articleFile(Request $request, string $slug): StreamedResponse
    {
        return $this->streamPdf($request, $this->reader->article($slug)->electronic_file);
    }

    public function dissertation(string $slug): View
    {
        $dissertation = $this->reader->dissertation($slug);

        $this->onlineReads->log(Auth::guard('reader')->user(), $dissertation);

        return view('pages.site.reader', [
            'title' => $dissertation->title,
            'subtitle' => $dissertation->author,
            'backUrl' => route('dissertation.show', $dissertation->slug),
            'fileUrl' => route('read.dissertation.file', $dissertation->slug),
        ]);
    }

    public function dissertationFile(Request $request, string $slug): StreamedResponse
    {
        return $this->streamPdf($request, $this->reader->dissertation($slug)->electronic_file);
    }

    public function avtoreferat(string $slug): View
    {
        $avtoreferat = $this->reader->avtoreferat($slug);

        $this->onlineReads->log(Auth::guard('reader')->user(), $avtoreferat);

        return view('pages.site.reader', [
            'title' => $avtoreferat->title,
            'subtitle' => $avtoreferat->author,
            'backUrl' => route('avtoreferat.show', $avtoreferat->slug),
            'fileUrl' => route('read.avtoreferat.file', $avtoreferat->slug),
        ]);
    }

    public function avtoreferatFile(Request $request, string $slug): StreamedResponse
    {
        return $this->streamPdf($request, $this->reader->avtoreferat($slug)->electronic_file);
    }

    public function journalIssue(int $id): View
    {
        $issue = $this->reader->journalIssue($id);

        return view('pages.site.reader', [
            'title' => $issue->journal->name,
            'subtitle' => __(':y yil, №:n', ['y' => $issue->year, 'n' => $issue->issue_number]),
            'backUrl' => route('journal.show', $issue->journal->slug, ['son' => $issue->id]),
            'fileUrl' => route('read.journal-issue.file', $issue->id),
        ]);
    }

    public function journalIssueFile(Request $request, int $id): StreamedResponse
    {
        return $this->streamPdf($request, $this->reader->journalIssue($id)->electronic_file);
    }

    /**
     * Stream a private PDF for in-browser rendering. `inline` plus a private,
     * no-store cache keeps it out of the browser's download flow and disk
     * cache; the shared trait adds Range support, which is what lets PDF.js
     * fetch only the pages being read instead of the entire file.
     */
    private function streamPdf(Request $request, string $path): StreamedResponse
    {
        return $this->streamPrivateFile($request, $path, 'document.pdf', 'application/pdf');
    }
}
