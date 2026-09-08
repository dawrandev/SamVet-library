<?php

use App\Models\Reader;
use App\Services\ReaderImportService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Re-importing the same workbook is a normal thing to do: a few rows get fixed
 * in Excel and the file goes back in. What must not happen is the other rows
 * losing anything in the process.
 *
 * @param  array<int, array<int, string>>  $rows  header row first
 */
function makeReimportSheet(array $rows): string
{
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('BT');
    $sheet->fromArray($rows);

    $path = tempnam(sys_get_temp_dir(), 'reader_reimport_').'.xlsx';
    (new Xlsx($spreadsheet))->save($path);

    return $path;
}

/**
 * @param  array<int, array<int, string>>  $rows
 * @return array<string, mixed>
 */
function importSheet(array $rows): array
{
    $path = makeReimportSheet($rows);

    try {
        return app(ReaderImportService::class)->import($path)['BT'];
    } finally {
        @unlink($path);
    }
}

it('updates rather than duplicates when the same file is imported twice', function () {
    $rows = [
        ['ID raqam', 'F.I.Sh.', 'JShShR', 'Telefon'],
        ['BT2027001', 'Takror Talaba', '30000000000001', '901234567'],
    ];

    expect(importSheet($rows)['imported'])->toBe(1);

    $second = importSheet($rows);

    expect($second['imported'])->toBe(0)
        ->and($second['updated'])->toBe(1)
        ->and(Reader::where('id_number', 'BT2027001')->count())->toBe(1);
});

it('does not erase a stored value that the workbook leaves blank', function () {
    importSheet([
        ['ID raqam', 'F.I.Sh.', 'JShShR', 'Telefon', 'Manzil'],
        ['BT2027002', 'Qoʻlda Tahrirlangan', '30000000000002', '901234567', 'Nukus'],
    ]);

    // What a librarian would do in the admin panel afterwards.
    $reader = Reader::where('id_number', 'BT2027002')->firstOrFail();
    $reader->update(['phone' => '998901112233', 'note' => 'Panelda kiritilgan']);

    // The same file again, this time with those cells empty.
    importSheet([
        ['ID raqam', 'F.I.Sh.', 'JShShR', 'Telefon', 'Manzil'],
        ['BT2027002', 'Qoʻlda Tahrirlangan', '30000000000002', '', 'Nukus'],
    ]);

    $reader->refresh();

    expect($reader->phone)->toBe('998901112233')
        ->and($reader->note)->toBe('Panelda kiritilgan')
        // Still authoritative for what it does state.
        ->and($reader->address)->toBe('Nukus');
});

it('overwrites a stored value the workbook does state', function () {
    importSheet([
        ['ID raqam', 'F.I.Sh.', 'JShShR', 'Manzil'],
        ['BT2027003', 'Manzil Talabasi', '30000000000003', 'Eski manzil'],
    ]);

    importSheet([
        ['ID raqam', 'F.I.Sh.', 'JShShR', 'Manzil'],
        ['BT2027003', 'Manzil Talabasi', '30000000000003', 'Yangi manzil'],
    ]);

    expect(Reader::where('id_number', 'BT2027003')->value('address'))->toBe('Yangi manzil');
});

/**
 * pinfl is only indexed, not unique, so before this the second import created
 * a second row for the same person and nothing complained.
 */
it('merges a reader first seen without an ID number when one later appears', function () {
    // A sheet row carrying only a PINFL — what the ST sheet produces.
    importSheet([
        ['ID raqam', 'F.I.Sh.', 'JShShR'],
        ['', 'Jshshr Bilan Kelgan', '30000000000004'],
    ]);

    expect(Reader::where('pinfl', '30000000000004')->count())->toBe(1)
        ->and(Reader::where('pinfl', '30000000000004')->value('id_number'))->toBeNull();

    // The same person, now with an ID number.
    $second = importSheet([
        ['ID raqam', 'F.I.Sh.', 'JShShR'],
        ['BT2027004', 'Jshshr Bilan Kelgan', '30000000000004'],
    ]);

    expect($second['imported'])->toBe(0)
        ->and($second['updated'])->toBe(1)
        ->and(Reader::where('pinfl', '30000000000004')->count())->toBe(1)
        ->and(Reader::where('pinfl', '30000000000004')->value('id_number'))->toBe('BT2027004');
});

it('keeps an ID number a later sheet does not mention', function () {
    importSheet([
        ['ID raqam', 'F.I.Sh.', 'JShShR'],
        ['BT2027005', 'Id Saqlanadi', '30000000000005'],
    ]);

    importSheet([
        ['ID raqam', 'F.I.Sh.', 'JShShR'],
        ['', 'Id Saqlanadi', '30000000000005'],
    ]);

    expect(Reader::where('pinfl', '30000000000005')->value('id_number'))->toBe('BT2027005')
        ->and(Reader::where('pinfl', '30000000000005')->count())->toBe(1);
});

it('lets the ID number decide when the two identifiers point at different rows', function () {
    Reader::factory()->create(['id_number' => 'BT2027006', 'pinfl' => '30000000000006']);
    Reader::factory()->create(['id_number' => 'BT2027007', 'pinfl' => '30000000000007']);

    // Row carries the first reader's ID and the second one's PINFL. The unique,
    // authoritative key wins; nothing new is created.
    $stats = importSheet([
        ['ID raqam', 'F.I.Sh.', 'JShShR'],
        ['BT2027006', 'Ziddiyatli Qator', '30000000000007'],
    ]);

    expect($stats['updated'])->toBe(1)
        ->and($stats['imported'])->toBe(0)
        ->and(Reader::where('id_number', 'BT2027006')->value('full_name'))->toBe('Ziddiyatli Qator');
});
