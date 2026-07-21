<?php

use App\Enums\ReaderType;
use App\Models\Reader;

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
        'affiliation_place' => 'Ish joyi nomi',
        'affiliation_unit' => 'Bo‘limi nomi',
        'affiliation_group' => 'Lavozim nomi',
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

it('shows 5 registration-year rows', function () {
    $reader = Reader::factory()->create();

    $html = view('pages.admin.readers.card', ['reader' => $reader, 'photo' => null])->render();

    expect(substr_count($html, 'o‘quv yili'))->toBe(5)
        ->and($html)->toContain('5.');
});