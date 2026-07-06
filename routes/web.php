<?php

use App\Http\Controllers\Admin\ArticleController;
use App\Http\Controllers\Admin\BookController;
use App\Http\Controllers\Admin\BookImportController;
use App\Http\Controllers\Admin\ComputerSessionController;
use App\Http\Controllers\Admin\CopyController;
use App\Http\Controllers\Admin\CopyLookupController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\JournalController;
use App\Http\Controllers\Admin\JournalCopyController;
use App\Http\Controllers\Admin\JournalIssueController;
use App\Http\Controllers\Admin\JournalLookupController;
use App\Http\Controllers\Admin\LoanController;
use App\Http\Controllers\Admin\LookupController;
use App\Http\Controllers\Admin\MenuItemController;
use App\Http\Controllers\Admin\NewsController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\ReaderCardController;
use App\Http\Controllers\Admin\ReaderController;
use App\Http\Controllers\Admin\ReaderImportController;
use App\Http\Controllers\Admin\ReaderStatusController;
use App\Http\Controllers\Admin\WarningController;
use App\Http\Controllers\Admin\Lookups\AuthorController;
use App\Http\Controllers\Admin\Lookups\BookTypeController;
use App\Http\Controllers\Admin\Lookups\CategoryController;
use App\Http\Controllers\Admin\Lookups\JournalTypeController;
use App\Http\Controllers\Admin\Lookups\LanguageController;
use App\Http\Controllers\Admin\Lookups\LocationController;
use App\Http\Controllers\Admin\Lookups\NewsCategoryController;
use App\Http\Controllers\Admin\Lookups\PublisherController;
use App\Http\Controllers\Admin\Lookups\ResourceFieldController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\Route;

// No client site yet — root redirects to the admin panel
// (auth: dashboard if logged in, otherwise login). Changes once the client is built.
Route::get('/', fn () => redirect()->route('admin.dashboard'));

// Language switch (for everyone — including the login page)
Route::get('locale/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');

/*
|--------------------------------------------------------------------------
| For guests (not logged in)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'show'])->name('login');
    Route::post('login', [LoginController::class, 'store']);
});

/*
|--------------------------------------------------------------------------
| Admin panel (logged-in users only)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Import books via Excel (BEFORE the resource — so it doesn't clash with `books/{book}`)
    Route::get('books/import', [BookImportController::class, 'create'])->name('books.import.create');
    Route::post('books/import', [BookImportController::class, 'store'])->name('books.import.store');

    // Books CRUD
    Route::get('books/{book}/translations/create', [BookController::class, 'createTranslation'])->name('books.translations.create');
    Route::resource('books', BookController::class);

    // Book copies (modal on the book show page)
    Route::resource('books.copies', CopyController::class)->only(['store', 'update', 'destroy']);

    // Journal live-search + issues (article form autocomplete) — BEFORE the resource
    // so `journals/search` doesn't clash with `journals/{journal}`.
    Route::get('journals/search', [JournalLookupController::class, 'show'])->name('journals.search');
    Route::get('journals/{journal}/issues', [JournalLookupController::class, 'issues'])->name('journals.issues.lookup');

    // Journals CRUD (title level)
    Route::resource('journals', JournalController::class);

    // Journal articles CRUD
    Route::resource('articles', ArticleController::class);

    // Journal issues (modal on the journal show page + issue page)
    Route::resource('journals.issues', JournalIssueController::class)->only(['store', 'show', 'update', 'destroy']);

    // Journal copies (modal on the issue show page)
    Route::resource('journal-issues.copies', JournalCopyController::class)
        ->only(['store', 'update', 'destroy'])
        ->parameters(['journal-issues' => 'journalIssue', 'copies' => 'copy']);

    // Import users via Excel (BEFORE the resource — so it doesn't clash with `readers/{reader}`)
    Route::get('readers/import', [ReaderImportController::class, 'create'])->name('readers.import.create');
    Route::post('readers/import', [ReaderImportController::class, 'store'])->name('readers.import.store');

    // Reader card (ID card) PDF
    Route::get('readers/{reader}/card', [ReaderCardController::class, 'show'])->name('readers.card');

    // Users (library members) CRUD
    Route::resource('readers', ReaderController::class);

    // User warnings (red flags)
    Route::post('readers/{reader}/warnings', [WarningController::class, 'store'])->name('readers.warnings.store');
    Route::delete('readers/{reader}/warnings/{warning}', [WarningController::class, 'destroy'])->name('readers.warnings.destroy');

    // Attended events and contests
    Route::post('readers/{reader}/events', [EventController::class, 'store'])->name('readers.events.store');
    Route::delete('readers/{reader}/events/{event}', [EventController::class, 'destroy'])->name('readers.events.destroy');

    // Computer usage
    Route::post('readers/{reader}/computer-sessions', [ComputerSessionController::class, 'store'])->name('readers.computer-sessions.store');
    Route::delete('readers/{reader}/computer-sessions/{computerSession}', [ComputerSessionController::class, 'destroy'])->name('readers.computer-sessions.destroy');

    // User status (block / finish / restore)
    Route::patch('readers/{reader}/block', [ReaderStatusController::class, 'block'])->name('readers.block');
    Route::patch('readers/{reader}/finish', [ReaderStatusController::class, 'finish'])->name('readers.finish');
    Route::patch('readers/{reader}/restore', [ReaderStatusController::class, 'restore'])->name('readers.restore');

    // Circulation (lending/returning books)
    Route::get('copies/lookup', [CopyLookupController::class, 'show'])->name('copies.lookup');
    Route::get('loans', [LoanController::class, 'index'])->name('loans.index');
    Route::post('readers/{reader}/loans', [LoanController::class, 'store'])->name('readers.loans.store');
    Route::patch('loans/{loan}/return', [LoanController::class, 'return'])->name('loans.return');

    // Site menu (client navbar navigation) — tree-structured CRUD
    Route::get('menu-items/{menuItem}/page', [PageController::class, 'edit'])->name('menu-items.page.edit');
    Route::put('menu-items/{menuItem}/page', [PageController::class, 'update'])->name('menu-items.page.update');
    Route::resource('menu-items', MenuItemController::class)->except(['show']);

    // News CRUD (show is on the client site — not needed here)
    Route::resource('news', NewsController::class)->except(['show'])->parameters(['news' => 'news']);

    // Instant lookup creation (AJAX, in the book form)
    Route::post('lookups', [LookupController::class, 'store'])->name('lookups.store');

    // Lookups management (CRUD)
    Route::prefix('lookups')->name('lookups.')->group(function () {
        Route::resource('categories', CategoryController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('book-types', BookTypeController::class)->only(['index', 'store', 'update', 'destroy'])->parameters(['book-types' => 'bookType']);
        Route::resource('journal-types', JournalTypeController::class)->only(['index', 'store', 'update', 'destroy'])->parameters(['journal-types' => 'journalType']);
        Route::resource('languages', LanguageController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('locations', LocationController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('publishers', PublisherController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('authors', AuthorController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('news-categories', NewsCategoryController::class)->only(['index', 'store', 'update', 'destroy'])->parameters(['news-categories' => 'newsCategory']);
        Route::resource('resource-fields', ResourceFieldController::class)->only(['index', 'store', 'update', 'destroy'])->parameters(['resource-fields' => 'resourceField']);
    });

    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');
});
