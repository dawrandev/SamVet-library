<?php

use App\Enums\ReaderImportOutcome;
use App\Models\Reader;
use App\Models\User;
use App\Services\ReaderImportService;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * A real 563-row import reported "563 o'tkazildi" and nothing else. The sheet
 * headed its name column "F.I.Sh.", the alias table only knew "To'liq ismi", so
 * every row arrived with an empty name and was dropped exactly like a blank
 * line would be. The count was accurate and told nobody anything.
 *
 * Two things are covered here: that heading now maps, and a skip carries a
 * reason all the way to the screen.
 */

/**
 * @param  array<int, array<int, string>>  $rows  header row first
 */
function makeSheet(array $rows, string $title = 'BT'): string
{
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle($title);
    $sheet->fromArray($rows);

    $path = tempnam(sys_get_temp_dir(), 'reader_diag_').'.xlsx';
    (new Xlsx($spreadsheet))->save($path);

    return $path;
}

it('imports a sheet whose name column is headed "F.I.Sh."', function () {
    $path = makeSheet([
        ['ID raqam', 'F.I.Sh.', 'JShShR'],
        ['BT2026777', 'Fish Sarlavha Talabasi', '12345678901234'],
    ]);

    try {
        $stats = app(ReaderImportService::class)->import($path);
    } finally {
        @unlink($path);
    }

    expect($stats['BT']['imported'])->toBe(1)
        ->and($stats['BT']['skipped'])->toBe(0)
        ->and($stats['BT']['missing_columns'])->toBe([])
        ->and(Reader::where('id_number', 'BT2026777')->value('full_name'))
        ->toBe('Fish Sarlavha Talabasi');
});

it('reports the missing column instead of silently skipping every row', function () {
    // "Talabaning ismi" is not an alias the mapper knows, so full_name cannot
    // be found — the exact shape of the original failure.
    $path = makeSheet([
        ['ID raqam', 'Talabaning ismi', 'JShShR'],
        ['BT2026778', 'Nomalum Ustun Talabasi', '12345678901235'],
        ['BT2026779', 'Ikkinchi Talaba', '12345678901236'],
    ]);

    try {
        $stats = app(ReaderImportService::class)->import($path);
    } finally {
        @unlink($path);
    }

    expect($stats['BT']['imported'])->toBe(0)
        ->and($stats['BT']['skipped'])->toBe(2)
        ->and($stats['BT']['missing_columns'])->not->toBe([])
        // The sheet's own headings travel with the report, so the screen can
        // show what the file has next to what was needed.
        ->and($stats['BT']['headers'])->toContain('Talabaning ismi')
        ->and($stats['BT']['issues'][ReaderImportOutcome::SkippedNoName->value])->toBe(2);
});

it('separates rows with nothing to identify from rows that lost their name', function () {
    // The filler in "Izoh" is what keeps these rows in the file at all:
    // PhpSpreadsheet drops wholly empty trailing rows when writing, while the
    // real workbooks keep hundreds of them because the cells carry formatting.
    // What matters to the importer is the same either way — no name, no ID, no
    // PINFL, nothing to identify a person by.
    $path = makeSheet([
        ['ID raqam', 'F.I.Sh.', 'JShShR', 'Izoh'],
        ['BT2026780', 'Haqiqiy Talaba', '12345678901237', ''],
        ['', '', '', '-'],
        ['', '', '', '-'],
    ]);

    try {
        $stats = app(ReaderImportService::class)->import($path);
    } finally {
        @unlink($path);
    }

    // Blank padding must not be reported as a problem — nagging about it is
    // how a real warning gets ignored.
    expect($stats['BT']['imported'])->toBe(1)
        ->and($stats['BT']['issues'][ReaderImportOutcome::SkippedEmptyRow->value] ?? 0)->toBe(2)
        ->and($stats['BT']['issues'][ReaderImportOutcome::SkippedNoName->value] ?? 0)->toBe(0);
});

it('puts the skip reasons in front of the librarian after an import', function () {
    $path = makeSheet([
        ['ID raqam', 'Talabaning ismi', 'JShShR'],
        ['BT2026781', 'Ustun Topilmadi', '12345678901238'],
    ]);

    $upload = new UploadedFile($path, 'readers.xlsx', null, null, true);

    $response = $this->actingAs(User::factory()->create(), 'web')
        ->post(route('admin.readers.import.store'), ['file' => $upload]);

    $response->assertRedirect();

    $stats = session('import_stats');

    expect($stats['needs_attention'])->toBeTrue()
        ->and($stats['problems'])->toHaveCount(1)
        ->and($stats['problems'][0]['sheet'])->toBe('BT')
        ->and($stats['problems'][0]['missing_columns'])->not->toBe([]);

    // And the page actually renders it, rather than the data merely existing.
    $this->actingAs(User::factory()->create(), 'web')
        ->get(route('admin.readers.import.create'))
        ->assertOk();
});

it('answers an over-sized upload with a message on the form, not a bare 413', function () {
    // PHP discards the body before Laravel when post_max_size is exceeded, so
    // the form's own max: rule never runs and the visitor used to get a raw
    // 413 error page with no explanation.
    $this->actingAs(User::factory()->create(), 'web');

    // CONTENT_LENGTH has to go in as a server variable: passed as a header it
    // would arrive as HTTP_CONTENT_LENGTH, which ValidatePostSize does not read.
    $response = $this->call(
        'POST',
        route('admin.readers.import.store'),
        ['_token' => csrf_token()],
        [],
        [],
        ['CONTENT_LENGTH' => (string) (8 * 1024 * 1024 * 1024)],
    );

    $response->assertRedirect()->assertSessionHasErrors('file');

    expect(session('errors')->first('file'))->toContain('juda katta');
});
