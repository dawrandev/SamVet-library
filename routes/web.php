<?php

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
use App\Http\Controllers\Admin\LoanController;
use App\Http\Controllers\Admin\LookupController;
use App\Http\Controllers\Admin\MenuItemController;
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
use App\Http\Controllers\Admin\Lookups\PublisherController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\Route;

// Hozircha client sayt yo'q — root admin panelga yo'naltiriladi
// (auth: kirgan bo'lsa dashboard, aks holda login). Client qurilganда o'zgaradi.
Route::get('/', fn () => redirect()->route('admin.dashboard'));

// Til almashtirish (hamma uchun — login sahifada ham)
Route::get('locale/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');

/*
|--------------------------------------------------------------------------
| Mehmonlar uchun (login qilmagan)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'show'])->name('login');
    Route::post('login', [LoginController::class, 'store']);
});

/*
|--------------------------------------------------------------------------
| Admin panel (faqat login qilganlar)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Kitoblarni Excel orqali import qilish (resource'dan OLDIN — `books/{book}` bilan to'qnashmasin)
    Route::get('books/import', [BookImportController::class, 'create'])->name('books.import.create');
    Route::post('books/import', [BookImportController::class, 'store'])->name('books.import.store');

    // Kitoblar CRUD
    Route::get('books/{book}/translations/create', [BookController::class, 'createTranslation'])->name('books.translations.create');
    Route::resource('books', BookController::class);

    // Kitob nusxalari (kitob show sahifasida modal)
    Route::resource('books.copies', CopyController::class)->only(['store', 'update', 'destroy']);

    // Jurnallar CRUD (nom darajasi)
    Route::resource('journals', JournalController::class);

    // Jurnal sonlari (jurnal show sahifasida modal + son sahifasi)
    Route::resource('journals.issues', JournalIssueController::class)->only(['store', 'show', 'update', 'destroy']);

    // Jurnal nusxalari (son show sahifasida modal)
    Route::resource('journal-issues.copies', JournalCopyController::class)
        ->only(['store', 'update', 'destroy'])
        ->parameters(['journal-issues' => 'journalIssue', 'copies' => 'copy']);

    // Foydalanuvchilarni Excel orqali import qilish (resource'dan OLDIN — `readers/{reader}` bilan to'qnashmasin)
    Route::get('readers/import', [ReaderImportController::class, 'create'])->name('readers.import.create');
    Route::post('readers/import', [ReaderImportController::class, 'store'])->name('readers.import.store');

    // Kitobxon guvohnomasi (ID-karta) PDF
    Route::get('readers/{reader}/card', [ReaderCardController::class, 'show'])->name('readers.card');

    // Foydalanuvchilar (kutubxona a'zolari) CRUD
    Route::resource('readers', ReaderController::class);

    // Foydalanuvchi ogohlantirishlari (qizil qoidalar)
    Route::post('readers/{reader}/warnings', [WarningController::class, 'store'])->name('readers.warnings.store');
    Route::delete('readers/{reader}/warnings/{warning}', [WarningController::class, 'destroy'])->name('readers.warnings.destroy');

    // Qatnashgan tadbir va tanlovlar
    Route::post('readers/{reader}/events', [EventController::class, 'store'])->name('readers.events.store');
    Route::delete('readers/{reader}/events/{event}', [EventController::class, 'destroy'])->name('readers.events.destroy');

    // Kompyuterdan foydalanish
    Route::post('readers/{reader}/computer-sessions', [ComputerSessionController::class, 'store'])->name('readers.computer-sessions.store');
    Route::delete('readers/{reader}/computer-sessions/{computerSession}', [ComputerSessionController::class, 'destroy'])->name('readers.computer-sessions.destroy');

    // Foydalanuvchi holati (bloklash / tugatish / tiklash)
    Route::patch('readers/{reader}/block', [ReaderStatusController::class, 'block'])->name('readers.block');
    Route::patch('readers/{reader}/finish', [ReaderStatusController::class, 'finish'])->name('readers.finish');
    Route::patch('readers/{reader}/restore', [ReaderStatusController::class, 'restore'])->name('readers.restore');

    // Oldi-berdi (kitob berish/qaytarish)
    Route::get('copies/lookup', [CopyLookupController::class, 'show'])->name('copies.lookup');
    Route::get('loans', [LoanController::class, 'index'])->name('loans.index');
    Route::post('readers/{reader}/loans', [LoanController::class, 'store'])->name('readers.loans.store');
    Route::patch('loans/{loan}/return', [LoanController::class, 'return'])->name('loans.return');

    // Sayt menyusi (client navbar navigatsiyasi) — daraxtsimon CRUD
    Route::resource('menu-items', MenuItemController::class)->except(['show']);

    // Lookup "shu zahoti" qo'shish (AJAX, kitob formasida)
    Route::post('lookups', [LookupController::class, 'store'])->name('lookups.store');

    // Ma'lumotnomalar boshqaruvi (CRUD)
    Route::prefix('lookups')->name('lookups.')->group(function () {
        Route::resource('categories', CategoryController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('book-types', BookTypeController::class)->only(['index', 'store', 'update', 'destroy'])->parameters(['book-types' => 'bookType']);
        Route::resource('journal-types', JournalTypeController::class)->only(['index', 'store', 'update', 'destroy'])->parameters(['journal-types' => 'journalType']);
        Route::resource('languages', LanguageController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('locations', LocationController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('publishers', PublisherController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('authors', AuthorController::class)->only(['index', 'store', 'update', 'destroy']);
    });

    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');
});
