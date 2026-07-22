<?php

use App\Models\AffiliationGroup;
use App\Models\AffiliationPlace;
use App\Models\AffiliationUnit;
use App\Models\District;
use App\Models\Reader;
use App\Services\ReaderImportService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * The Excel import used to write affiliation_place/unit/group and district as
 * raw strings straight onto the reader. Since those became lookup FKs, the
 * importer must now resolve (find-or-create) the matching lookup row instead.
 */
function makeReaderImportFixture(): string
{
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('BT');

    $sheet->fromArray([
        ['ID raqam', "To'liq ismi", 'Ish joyi', 'Mutaxassisligi', 'Guruhi', 'Tuman'],
        ['BT2026001', 'Import Test Talaba', 'Veterinariya fakulteti', 'Veterinariya', '2-21', 'Chimboy'],
    ]);

    $path = tempnam(sys_get_temp_dir(), 'reader_import_').'.xlsx';
    (new Xlsx($spreadsheet))->save($path);

    return $path;
}

it('resolves imported affiliation/district text into lookup rows instead of raw strings', function () {
    $path = makeReaderImportFixture();

    try {
        app(ReaderImportService::class)->import($path);
    } finally {
        @unlink($path);
    }

    $reader = Reader::where('id_number', 'BT2026001')->first();
    expect($reader)->not->toBeNull()
        ->and($reader->affiliation_place_id)->not->toBeNull()
        ->and($reader->affiliationPlace->name)->toBe('Veterinariya fakulteti')
        ->and($reader->affiliationUnit->name)->toBe('Veterinariya')
        ->and($reader->affiliationGroup->name)->toBe('2-21')
        ->and($reader->district->name)->toBe('Chimboy');

    expect(AffiliationPlace::where('name', 'Veterinariya fakulteti')->count())->toBe(1)
        ->and(AffiliationUnit::where('name', 'Veterinariya')->count())->toBe(1)
        ->and(AffiliationGroup::where('name', '2-21')->count())->toBe(1)
        ->and(District::where('name', 'Chimboy')->count())->toBe(1);
});

it('reuses an existing lookup row on a second import instead of creating a duplicate', function () {
    AffiliationPlace::factory()->create(['name' => 'Veterinariya fakulteti']);

    $path = makeReaderImportFixture();

    try {
        app(ReaderImportService::class)->import($path);
    } finally {
        @unlink($path);
    }

    expect(AffiliationPlace::where('name', 'Veterinariya fakulteti')->count())->toBe(1);
});
