<?php

use App\Enums\BookFormat;
use App\Enums\Gender;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\DepartmentCoverage;
use App\Models\Reader;
use App\Models\ReaderType;

it('includes a fund breakdown by Shakli (format), alongside type/language/category', function () {
    $book = Book::factory()->create();
    BookCopy::factory()->create(['book_id' => $book->id, 'format' => BookFormat::Print->value]);

    $res = $this->get(route('statistics'));

    $res->assertOk();
    $byFormat = $res->viewData('byFormat');
    expect($byFormat->firstWhere('label', __('Bosma'))['count'])->toBe(1);
});

it('includes reader demographics: turi, jinsi, yoshi', function () {
    $type = ReaderType::factory()->create(['name' => 'Talaba']);
    Reader::factory()->create(['reader_type_id' => $type->id, 'gender' => Gender::Male->value, 'birth_date' => now()->subYears(20)]);

    $res = $this->get(route('statistics'));

    $res->assertOk();
    expect($res->viewData('readersByType')->firstWhere('label', 'Talaba')['count'])->toBe(1)
        ->and($res->viewData('readersByGender')->firstWhere('label', Gender::Male->label())['count'])->toBe(1)
        ->and($res->viewData('readersByAgeGroup')->firstWhere('label', '18-25')['count'])->toBe(1);
});

it('keeps age groups in chronological order, not sorted by size', function () {
    Reader::factory()->create(['birth_date' => now()->subYears(70)]); // 60+
    Reader::factory()->count(3)->create(['birth_date' => now()->subYears(20)]); // 18-25, larger bucket

    $res = $this->get(route('statistics'));

    $labels = $res->viewData('readersByAgeGroup')->pluck('label')->all();
    $eighteenTo25 = array_search('18-25', $labels, true);
    $sixtyPlus = array_search('60+', $labels, true);

    expect($eighteenTo25)->not->toBeFalse()
        ->and($sixtyPlus)->not->toBeFalse()
        ->and($eighteenTo25)->toBeLessThan($sixtyPlus);
});

it('shows the fund and reader statistics section headings', function () {
    $this->get(route('statistics'))
        ->assertOk()
        ->assertSee(__('Fond statistikasi'))
        ->assertSee(__('Foydalanuvchilar statistikasi'));
});

it('shows each department\'s own percentage as its bar share, not relative to the largest one', function () {
    DepartmentCoverage::factory()->create(['name' => ['uz' => 'Kichik kafedra', 'ru' => 'x', 'kk' => 'x'], 'percentage' => 20]);
    DepartmentCoverage::factory()->create(['name' => ['uz' => 'Katta kafedra', 'ru' => 'x', 'kk' => 'x'], 'percentage' => 80]);

    $res = $this->get(route('statistics'));

    $res->assertOk()->assertSee(__('Kafedralar kesimida ta’minganlik darajasi'));

    $byDepartment = $res->viewData('byDepartment');
    expect($byDepartment->firstWhere('label', 'Kichik kafedra')['share'])->toBe(20)
        ->and($byDepartment->firstWhere('label', 'Katta kafedra')['share'])->toBe(80);
});
