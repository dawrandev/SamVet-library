<?php

use App\Http\Controllers\Admin\ArticleController;
use App\Http\Controllers\Admin\AudiobookController;
use App\Http\Controllers\Admin\AudioTrackController;
use App\Http\Controllers\Admin\AvtoreferatController;
use App\Http\Controllers\Admin\BookController;
use App\Http\Controllers\Admin\BookImportController;
use App\Http\Controllers\Admin\ComputerSessionController;
use App\Http\Controllers\Admin\ComputerController;
use App\Http\Controllers\Admin\CopyController;
use App\Http\Controllers\Admin\CopyLookupController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DissertationController;
use App\Http\Controllers\Admin\DistrictLookupController;
use App\Http\Controllers\Admin\EditorImageController;
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
use App\Http\Controllers\Admin\ReaderLookupController;
use App\Http\Controllers\Admin\ReaderStatusController;
use App\Http\Controllers\Admin\SubscriptionController;
use App\Http\Controllers\Admin\VideoController;
use App\Http\Controllers\Admin\VideoTrackController;
use App\Http\Controllers\Admin\WarningController;
use App\Http\Controllers\Admin\Lookups\AffiliationGroupController;
use App\Http\Controllers\Admin\Lookups\AffiliationPlaceController;
use App\Http\Controllers\Admin\Lookups\AffiliationUnitController;
use App\Http\Controllers\Admin\Lookups\BookTypeController;
use App\Http\Controllers\Admin\Lookups\CategoryController;
use App\Http\Controllers\Admin\Lookups\ContributorRoleController;
use App\Http\Controllers\Admin\Lookups\DeliveryLocationController;
use App\Http\Controllers\Admin\Lookups\DistrictController;
use App\Http\Controllers\Admin\Lookups\EventLocationController;
use App\Http\Controllers\Admin\Lookups\JournalTypeController;
use App\Http\Controllers\Admin\Lookups\LanguageController;
use App\Http\Controllers\Admin\Lookups\LocationController;
use App\Http\Controllers\Admin\Lookups\NewsCategoryController;
use App\Http\Controllers\Admin\Lookups\DoctoralSpecialtyController;
use App\Http\Controllers\Admin\Lookups\MasterSpecialtyController;
use App\Http\Controllers\Admin\Lookups\PostBranchController;
use App\Http\Controllers\Admin\Lookups\ScienceFieldController;
use App\Http\Controllers\Admin\Lookups\PublicationPlaceController;
use App\Http\Controllers\Admin\Lookups\RegionController;
use App\Http\Controllers\Admin\Lookups\ResourceFieldController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Site\ArticleController as SiteArticleController;
use App\Http\Controllers\Site\AudiobookController as SiteAudiobookController;
use App\Http\Controllers\Site\AudioReaderController;
use App\Http\Controllers\Site\BookController as SiteBookController;
use App\Http\Controllers\Site\CatalogController;
use App\Http\Controllers\Site\HomeController;
use App\Http\Controllers\Site\JournalController as SiteJournalController;
use App\Http\Controllers\Site\NewsController as SiteNewsController;
use App\Http\Controllers\Site\OnlineReaderController;
use App\Http\Controllers\Site\PageController as SitePageController;
use App\Http\Controllers\Site\PeriodicalController;
use App\Http\Controllers\Site\ReaderAuthController;
use App\Http\Controllers\Site\SectionController;
use App\Http\Controllers\Site\StatisticsController as SiteStatisticsController;
use App\Http\Controllers\Site\VideoController as SiteVideoController;
use App\Http\Controllers\Site\VideoReaderController;
use App\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public site (library portal)
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/katalog', [CatalogController::class, 'index'])->name('catalog');
Route::get('/kitob/{slug}', [SiteBookController::class, 'show'])->name('book.show');
Route::get('/bolimlar', [SectionController::class, 'index'])->name('sections');
Route::get('/jurnallar', [PeriodicalController::class, 'index'])->name('periodicals.index');
Route::get('/jurnal/{slug}', [SiteJournalController::class, 'show'])->name('journal.show');
Route::get('/maqola/{slug}', [SiteArticleController::class, 'show'])->name('article.show');
Route::get('/audiokitoblar', [SiteAudiobookController::class, 'index'])->name('audiobooks.index');
Route::get('/audiokitob/{slug}', [SiteAudiobookController::class, 'show'])->name('audiobook.show');
Route::get('/videolar', [SiteVideoController::class, 'index'])->name('videos.index');
Route::get('/video/{slug}', [SiteVideoController::class, 'show'])->name('video.show');
Route::get('/yangiliklar', [SiteNewsController::class, 'index'])->name('news.index');
Route::get('/yangiliklar/{slug}', [SiteNewsController::class, 'show'])->name('news.show');
Route::get('/sahifa/{id}', [SitePageController::class, 'show'])->whereNumber('id')->name('page.show');
Route::get('/statistika', [SiteStatisticsController::class, 'index'])->name('statistics');

/*
|--------------------------------------------------------------------------
| Reader sign-in (public site) — reading online requires an account
|--------------------------------------------------------------------------
*/
Route::get('/kirish', [ReaderAuthController::class, 'create'])->name('reader.login');
Route::post('/kirish', [ReaderAuthController::class, 'store'])->middleware('throttle:10,1');
Route::post('/chiqish', [ReaderAuthController::class, 'destroy'])->middleware('reader.auth')->name('reader.logout');

/*
|--------------------------------------------------------------------------
| Protected online reading — signed-in readers only, no download
|--------------------------------------------------------------------------
*/
Route::middleware('reader.auth')->prefix('oqish')->group(function () {
    Route::get('kitob/{slug}', [OnlineReaderController::class, 'book'])->name('read.book');
    Route::get('kitob/{slug}/fayl', [OnlineReaderController::class, 'bookFile'])->name('read.book.file');
    Route::get('maqola/{slug}', [OnlineReaderController::class, 'article'])->name('read.article');
    Route::get('maqola/{slug}/fayl', [OnlineReaderController::class, 'articleFile'])->name('read.article.file');
});

/*
|--------------------------------------------------------------------------
| Protected online listening — signed-in readers only, no download
|--------------------------------------------------------------------------
*/
Route::middleware('reader.auth')->prefix('tinglash')->group(function () {
    Route::get('audiokitob/{slug}', [AudioReaderController::class, 'show'])->name('listen.audiobook');
    Route::get('audiokitob/{slug}/{track}/fayl', [AudioReaderController::class, 'trackFile'])->name('listen.audiobook.file');
});

/*
|--------------------------------------------------------------------------
| Protected online watching — signed-in readers only, no download
|--------------------------------------------------------------------------
*/
Route::middleware('reader.auth')->prefix('tomosha')->group(function () {
    Route::get('video/{slug}', [VideoReaderController::class, 'show'])->name('watch.video');
    Route::get('video/{slug}/{track}/fayl', [VideoReaderController::class, 'trackFile'])->name('watch.video.file');
});

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

    // Export books to Excel (BEFORE the resource — so it doesn't clash with `books/{book}`)
    Route::get('books/export', [BookController::class, 'export'])->name('books.export');

    // Books CRUD
    Route::get('books/{book}/translations/create', [BookController::class, 'createTranslation'])->name('books.translations.create');
    Route::resource('books', BookController::class);

    // Book copies (modal on the book show page)
    Route::resource('books.copies', CopyController::class)->only(['store', 'update', 'destroy']);

    // Journal live-search + issues (article form autocomplete) — BEFORE the resource
    // so `journals/search` doesn't clash with `journals/{journal}`.
    Route::get('journals/search', [JournalLookupController::class, 'show'])->name('journals.search');
    Route::get('journals/{journal}/issues', [JournalLookupController::class, 'issues'])->name('journals.issues.lookup');

    // Export journals to Excel (BEFORE the resource — so it doesn't clash with `journals/{journal}`)
    Route::get('journals/export', [JournalController::class, 'export'])->name('journals.export');

    // Journals CRUD (title level)
    Route::resource('journals', JournalController::class);

    // Journal articles CRUD
    Route::resource('articles', ArticleController::class);

    // Export dissertations to Excel (BEFORE the resource — so it doesn't clash with `dissertations/{dissertation}`)
    Route::get('dissertations/export', [DissertationController::class, 'export'])->name('dissertations.export');

    // Dissertations CRUD (catalogued like an article — belongs to a journal issue)
    Route::resource('dissertations', DissertationController::class);

    // Export avtoreferats to Excel (BEFORE the resource — so it doesn't clash with `avtoreferats/{avtoreferat}`)
    Route::get('avtoreferats/export', [AvtoreferatController::class, 'export'])->name('avtoreferats.export');

    // Avtoreferats CRUD (same shape as a dissertation)
    Route::resource('avtoreferats', AvtoreferatController::class);

    // Export audiobooks to Excel (BEFORE the resource — so it doesn't clash with `audiobooks/{audiobook}`)
    Route::get('audiobooks/export', [AudiobookController::class, 'export'])->name('audiobooks.export');

    // Audiobooks CRUD (title level)
    Route::resource('audiobooks', AudiobookController::class);

    // Audio tracks (modal on the audiobook show page)
    Route::resource('audiobooks.tracks', AudioTrackController::class)->only(['store', 'update', 'destroy']);

    // Export videos to Excel (BEFORE the resource — so it doesn't clash with `videos/{video}`)
    Route::get('videos/export', [VideoController::class, 'export'])->name('videos.export');

    // Videos CRUD (title level)
    Route::resource('videos', VideoController::class);

    // Video tracks (modal on the video show page)
    Route::resource('videos.tracks', VideoTrackController::class)->only(['store', 'update', 'destroy']);

    // Journal issues (modal on the journal show page + issue page)
    Route::resource('journals.issues', JournalIssueController::class)->only(['store', 'show', 'update', 'destroy']);

    // Journal copies (modal on the issue show page)
    Route::resource('journal-issues.copies', JournalCopyController::class)
        ->only(['store', 'update', 'destroy'])
        ->parameters(['journal-issues' => 'journalIssue', 'copies' => 'copy']);

    // Districts of a region (dependent select in the reader form)
    Route::get('regions/{region}/districts', [DistrictLookupController::class, 'byRegion'])->name('regions.districts.lookup');

    // Import users via Excel (BEFORE the resource — so it doesn't clash with `readers/{reader}`)
    Route::get('readers/import', [ReaderImportController::class, 'create'])->name('readers.import.create');
    Route::post('readers/import', [ReaderImportController::class, 'store'])->name('readers.import.store');

    // Export users to Excel (BEFORE the resource — so it doesn't clash with `readers/{reader}`)
    Route::get('readers/export', [ReaderController::class, 'export'])->name('readers.export');

    // Reader card (ID card) PDF
    Route::get('readers/{reader}/card', [ReaderCardController::class, 'show'])->name('readers.card');

    // Famulyar (practicum booklet) cover page PDF
    Route::get('readers/{reader}/famulyar', [ReaderCardController::class, 'famulyar'])->name('readers.famulyar');

    // Reader lookup by ID number (before the resource's {reader} show route, or "lookup" gets bound as an id)
    Route::get('readers/lookup', [ReaderLookupController::class, 'show'])->name('readers.lookup');

    // Users (library members) CRUD
    Route::resource('readers', ReaderController::class);

    // User warnings (red flags)
    Route::post('readers/{reader}/warnings', [WarningController::class, 'store'])->name('readers.warnings.store');
    Route::delete('readers/{reader}/warnings/{warning}', [WarningController::class, 'destroy'])->name('readers.warnings.destroy');

    // Events and contests (own module — a reader's show page only displays them read-only)
    Route::resource('events', EventController::class)->except(['show']);

    // Computer usage
    Route::get('computer-sessions', [ComputerSessionController::class, 'index'])->name('computer-sessions.index');
    Route::post('readers/{reader}/computer-sessions', [ComputerSessionController::class, 'store'])->name('readers.computer-sessions.store');
    Route::delete('readers/{reader}/computer-sessions/{computerSession}', [ComputerSessionController::class, 'destroy'])->name('readers.computer-sessions.destroy');
    Route::patch('computer-sessions/{computerSession}/finish', [ComputerSessionController::class, 'finish'])->name('computer-sessions.finish');
    Route::patch('computer-sessions/{computerSession}/extend', [ComputerSessionController::class, 'extend'])->name('computer-sessions.extend');

    // User status (block / finish / restore)
    Route::patch('readers/{reader}/block', [ReaderStatusController::class, 'block'])->name('readers.block');
    Route::patch('readers/{reader}/finish', [ReaderStatusController::class, 'finish'])->name('readers.finish');
    Route::patch('readers/{reader}/restore', [ReaderStatusController::class, 'restore'])->name('readers.restore');

    // Circulation (lending/returning books)
    Route::get('copies/lookup', [CopyLookupController::class, 'show'])->name('copies.lookup');
    Route::get('loans', [LoanController::class, 'index'])->name('loans.index');
    Route::post('readers/{reader}/loans', [LoanController::class, 'store'])->name('readers.loans.store');
    Route::patch('loans/{loan}/return', [LoanController::class, 'return'])->name('loans.return');

    // Periodical subscriptions — attached to a reader (Foydalanuvchi)
    Route::get('subscriptions/{subscription}/receipt', [SubscriptionController::class, 'receipt'])->name('subscriptions.receipt');
    Route::resource('subscriptions', SubscriptionController::class)->except(['show']);

    // Computers (electronic reading room inventory)
    Route::resource('computers', ComputerController::class);

    // Site menu (client navbar navigation) — tree-structured CRUD
    Route::get('menu-items/{menuItem}/page', [PageController::class, 'edit'])->name('menu-items.page.edit');
    Route::put('menu-items/{menuItem}/page', [PageController::class, 'update'])->name('menu-items.page.update');
    Route::get('menu-items/{menuItem}/page/show', [PageController::class, 'show'])->name('menu-items.page.show');
    Route::resource('menu-items', MenuItemController::class)->except(['show']);

    // News CRUD (admin preview page included)
    Route::resource('news', NewsController::class)->parameters(['news' => 'news']);

    // Instant lookup creation (AJAX, in the book form)
    Route::post('lookups', [LookupController::class, 'store'])->name('lookups.store');

    // Inline image upload for the TinyMCE rich-text editor (news/page body)
    Route::post('editor/images', [EditorImageController::class, 'store'])->name('editor-images.store');

    // Lookups management (CRUD)
    Route::prefix('lookups')->name('lookups.')->group(function () {
        Route::resource('categories', CategoryController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('book-types', BookTypeController::class)->only(['index', 'store', 'update', 'destroy'])->parameters(['book-types' => 'bookType']);
        Route::resource('journal-types', JournalTypeController::class)->only(['index', 'store', 'update', 'destroy'])->parameters(['journal-types' => 'journalType']);
        Route::resource('languages', LanguageController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('locations', LocationController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('publication-places', PublicationPlaceController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('contributor-roles', ContributorRoleController::class)->only(['index', 'store', 'update', 'destroy'])->parameters(['contributor-roles' => 'contributorRole']);
        Route::resource('news-categories', NewsCategoryController::class)->only(['index', 'store', 'update', 'destroy'])->parameters(['news-categories' => 'newsCategory']);
        Route::resource('resource-fields', ResourceFieldController::class)->only(['index', 'store', 'update', 'destroy'])->parameters(['resource-fields' => 'resourceField']);
        Route::resource('event-locations', EventLocationController::class)->only(['index', 'store', 'update', 'destroy'])->parameters(['event-locations' => 'eventLocation']);
        Route::resource('delivery-locations', DeliveryLocationController::class)->only(['index', 'store', 'update', 'destroy'])->parameters(['delivery-locations' => 'deliveryLocation']);
        Route::resource('post-branches', PostBranchController::class)->only(['index', 'store', 'update', 'destroy'])->parameters(['post-branches' => 'postBranch']);
        Route::resource('science-fields', ScienceFieldController::class)->only(['index', 'store', 'update', 'destroy'])->parameters(['science-fields' => 'scienceField']);
        Route::resource('doctoral-specialties', DoctoralSpecialtyController::class)->only(['index', 'store', 'update', 'destroy'])->parameters(['doctoral-specialties' => 'doctoralSpecialty']);
        Route::resource('master-specialties', MasterSpecialtyController::class)->only(['index', 'store', 'update', 'destroy'])->parameters(['master-specialties' => 'masterSpecialty']);
        Route::resource('affiliation-places', AffiliationPlaceController::class)->only(['index', 'store', 'update', 'destroy'])->parameters(['affiliation-places' => 'affiliationPlace']);
        Route::resource('affiliation-units', AffiliationUnitController::class)->only(['index', 'store', 'update', 'destroy'])->parameters(['affiliation-units' => 'affiliationUnit']);
        Route::resource('affiliation-groups', AffiliationGroupController::class)->only(['index', 'store', 'update', 'destroy'])->parameters(['affiliation-groups' => 'affiliationGroup']);
        Route::resource('regions', RegionController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('districts', DistrictController::class)->only(['index', 'store', 'update', 'destroy']);
    });

    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');
});
