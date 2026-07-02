<?php

use App\Http\Controllers\Admin\BookController;
use App\Http\Controllers\Admin\CopyController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LookupController;
use App\Http\Controllers\Admin\Lookups\AuthorController;
use App\Http\Controllers\Admin\Lookups\BookTypeController;
use App\Http\Controllers\Admin\Lookups\CategoryController;
use App\Http\Controllers\Admin\Lookups\LanguageController;
use App\Http\Controllers\Admin\Lookups\LocationController;
use App\Http\Controllers\Admin\Lookups\PublisherController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

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

    // Kitoblar CRUD
    Route::get('books/{book}/translations/create', [BookController::class, 'createTranslation'])->name('books.translations.create');
    Route::resource('books', BookController::class);

    // Kitob nusxalari (kitob show sahifasida modal)
    Route::resource('books.copies', CopyController::class)->only(['store', 'update', 'destroy']);

    // Lookup "shu zahoti" qo'shish (AJAX, kitob formasida)
    Route::post('lookups', [LookupController::class, 'store'])->name('lookups.store');

    // Ma'lumotnomalar boshqaruvi (CRUD)
    Route::prefix('lookups')->name('lookups.')->group(function () {
        Route::resource('categories', CategoryController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('book-types', BookTypeController::class)->only(['index', 'store', 'update', 'destroy'])->parameters(['book-types' => 'bookType']);
        Route::resource('languages', LanguageController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('locations', LocationController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('publishers', PublisherController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('authors', AuthorController::class)->only(['index', 'store', 'update', 'destroy']);
    });

    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');
});
