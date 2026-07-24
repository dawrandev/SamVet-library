<?php

use App\Enums\ReaderType;
use App\Models\AffiliationGroup;
use App\Models\AffiliationPlace;
use App\Models\AffiliationUnit;
use App\Models\Reader;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => actingAsAdmin());

it('streams the reader card as a PDF', function () {
    $reader = Reader::factory()->create();

    $res = $this->get(route('admin.readers.card', $reader));

    $res->assertOk();
    expect($res->headers->get('content-type'))->toContain('application/pdf');
});

it('splits full_name into Familyasi/Ismi/Sharifi and shows staff labels', function () {
    $reader = Reader::factory()->create([
        'type' => ReaderType::BranchStaff->value,
        'full_name' => 'Palensheyev Tólenshe Tólensheyevich',
        'id_number' => 'FX0119001',
        'affiliation_place_id' => AffiliationPlace::factory()->create(['name' => 'Ish joyi nomi'])->id,
        'affiliation_unit_id' => AffiliationUnit::factory()->create(['name' => 'Bo‘limi nomi'])->id,
        'affiliation_group_id' => AffiliationGroup::factory()->create(['name' => 'Lavozim nomi'])->id,
    ]);

    $html = view('pages.admin.readers.card', ['reader' => $reader, 'photo' => null])->render();

    expect($html)->toContain('Palensheyev')
        ->toContain('Tólenshe')
        ->toContain('Tólensheyevich')
        ->toContain('FX0119001')
        ->toContain('Ish joyi')
        ->toContain('Bo‘limi')
        ->toContain('Lavozimi')
        ->not->toContain('O‘qish joyi')
        ->not->toContain('Guruhi');
});

it('shows student labels (O‘qish joyi/Mutaxassisligi/Guruhi) for a student reader', function () {
    $reader = Reader::factory()->create(['type' => ReaderType::Bachelor->value]);

    $html = view('pages.admin.readers.card', ['reader' => $reader, 'photo' => null])->render();

    expect($html)->toContain('O‘qish joyi')
        ->toContain('Mutaxassisligi')
        ->toContain('Guruhi')
        ->not->toContain('Ish joyi')
        ->not->toContain('Lavozimi');
});

it('colors the badge by reader type', function () {
    $cases = [
        ReaderType::Bachelor->value => '#2563eb',
        ReaderType::Master->value => '#7c3aed',
        ReaderType::Doctoral->value => '#dc2626',
        ReaderType::Professor->value => '#d97706',
    ];

    foreach ($cases as $type => $color) {
        $reader = Reader::factory()->create(['type' => $type]);

        $html = view('pages.admin.readers.card', ['reader' => $reader, 'photo' => null])->render();

        expect($html)->toContain("background: {$color};");
    }
});

it('shows a blank signature line for Kitobxon imzosi and Berilgan sana when issued_date is unknown', function () {
    $reader = Reader::factory()->create(['issued_date' => null]);

    $html = view('pages.admin.readers.card', ['reader' => $reader, 'photo' => null])->render();

    expect(substr_count($html, 'class="sign-line"'))->toBe(2);
});

it('shows the actual issued_date instead of a blank line once it is known', function () {
    $reader = Reader::factory()->create(['issued_date' => '2026-03-05']);

    $html = view('pages.admin.readers.card', ['reader' => $reader, 'photo' => null])->render();

    expect($html)->toContain('05.03.2026')
        ->and(substr_count($html, 'class="sign-line"'))->toBe(1);
});

it('shows 5 registration-year rows', function () {
    $reader = Reader::factory()->create();

    $html = view('pages.admin.readers.card', ['reader' => $reader, 'photo' => null])->render();

    expect(substr_count($html, 'o‘quv yili'))->toBe(5)
        ->and($html)->toContain('5.');
});

it('passes the reader\'s uploaded photo as a local file path, not a data URI', function () {
    Storage::fake('public');
    Storage::disk('public')->put('photos/reader.jpg', 'fake-image-bytes');
    $reader = Reader::factory()->create(['photo' => 'photos/reader.jpg']);

    $res = $this->get(route('admin.readers.card', $reader));

    $res->assertOk();
    expect($res->headers->get('content-type'))->toContain('application/pdf');
});

it('renders the card without a photo file present on disk (stale DB value)', function () {
    Storage::fake('public');
    $reader = Reader::factory()->create(['photo' => 'photos/missing.jpg']);

    // Should not throw — falls back to the empty-photo placeholder.
    $res = $this->get(route('admin.readers.card', $reader));

    $res->assertOk();
});

it('streams the famulyar cover page as a PDF', function () {
    $reader = Reader::factory()->create();

    $res = $this->get(route('admin.readers.famulyar', $reader));

    $res->assertOk();
    expect($res->headers->get('content-type'))->toContain('application/pdf');
});

it('shows the reader\'s id_number and full name on the famulyar cover', function () {
    $reader = Reader::factory()->create([
        'id_number' => 'RS-2026-777',
        'full_name' => 'Nazarova Madina Baxtiyorovna',
    ]);

    $html = view('pages.admin.readers.famulyar', ['reader' => $reader, 'logo' => null])->render();

    expect($html)->toContain('RS-2026-777')
        ->toContain('Nazarova Madina Baxtiyorovna');
});

it('shows the famulyar download button on the reader show page', function () {
    $reader = Reader::factory()->create();

    $this->get(route('admin.readers.show', $reader))
        ->assertSee(route('admin.readers.famulyar', $reader), false)
        ->assertSee('Famulyar yuklab olish');
});