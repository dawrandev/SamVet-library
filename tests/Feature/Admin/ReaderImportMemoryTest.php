<?php

use App\Models\Reader;
use App\Services\ReaderImportService;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * A resource-budget test, not a correctness one.
 *
 * The rest of the suite checks that the import produces the right rows, and it
 * always passed — including while the importer was loading every photo in the
 * sheet into one array up front and setting memory_limit to -1 so nothing
 * would ever complain about it. That combination is invisible to a correctness
 * test built on a one-row, image-free fixture, and it is exactly what killed
 * the PHP worker on the production host: no PHP error, no Laravel log entry,
 * just a bare 503 from Apache.
 *
 * So this asserts the property that actually failed in production — the
 * importer's memory use must not scale with the number of images in the file.
 */
/**
 * Enough rows that "all photos at once" and "one photo at a time" are an
 * order of magnitude apart. The row count only moves the first of those — the
 * streaming peak is flat in the number of images, which is the whole property
 * under test — so raising it buys headroom against a CI machine allocating
 * slightly differently, without weakening the assertion.
 */
const PHOTO_ROWS = 40;

/**
 * One PNG of pure random pixels. Noise is the point: a flat or geometric
 * image compresses down to a few KB, which would make the budget assertion
 * below pass no matter how the importer behaves.
 */
function makeNoisyImage(string $path): void
{
    $size = 420;
    $image = imagecreatetruecolor($size, $size);
    $noise = random_bytes($size * $size * 3);
    $i = 0;

    for ($y = 0; $y < $size; $y++) {
        for ($x = 0; $x < $size; $x++) {
            imagesetpixel($image, $x, $y, imagecolorallocate(
                $image,
                ord($noise[$i++]),
                ord($noise[$i++]),
                ord($noise[$i++])
            ));
        }
    }

    imagepng($image, $path, 1);
    imagedestroy($image);
}

/**
 * A sheet of $rows readers, each with a photo anchored to their row.
 *
 * Header sits in row 1 and data starts at row 2, which is what the importer
 * expects: the drawing anchored to spreadsheet row N lands on absolute
 * (0-based) row N-1, i.e. data row N-2.
 *
 * @return array{path: string, photoBytes: int}
 */
function makePhotoImportFixture(int $rows): array
{
    $imagePath = tempnam(sys_get_temp_dir(), 'reader_photo_').'.png';
    makeNoisyImage($imagePath);
    $imageBytes = (int) filesize($imagePath);

    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('BT');
    $sheet->fromArray([['ID raqam', "To'liq ismi"]], null, 'A1');

    for ($i = 1; $i <= $rows; $i++) {
        $row = $i + 1;
        $sheet->setCellValue("A{$row}", sprintf('BT2099%03d', $i));
        $sheet->setCellValue("B{$row}", "Xotira Testi {$i}");

        $drawing = new Drawing;
        $drawing->setPath($imagePath);
        $drawing->setCoordinates("C{$row}");
        $drawing->setWorksheet($sheet);
    }

    $path = tempnam(sys_get_temp_dir(), 'reader_import_photos_').'.xlsx';
    (new Xlsx($spreadsheet))->save($path);
    $spreadsheet->disconnectWorksheets();
    @unlink($imagePath);

    // What the old "load every photo first" importer would have had to hold.
    // The archive itself stores the image once (the writer de-duplicates
    // identical media), but the importer read it back once per row.
    return ['path' => $path, 'photoBytes' => $imageBytes * $rows];
}

it('does not hold every photo of a sheet in memory at once', function () {
    Storage::fake('public');

    $fixture = makePhotoImportFixture(PHOTO_ROWS);

    // PEAK, not the delta at the end. Measuring after import() returns proves
    // nothing: whatever it held has already been freed by then, so an importer
    // that loads every photo at once looks identical to one that streams them.
    // Resetting the peak first excludes the fixture writer's own cost.
    gc_collect_cycles();
    memory_reset_peak_usage();
    $before = memory_get_usage();

    app(ReaderImportService::class)->import($fixture['path']);

    $used = memory_get_peak_usage() - $before;
    $wouldHaveHeld = $fixture['photoBytes'];

    @unlink($fixture['path']);

    // Measured on this fixture: ~2.5MB streaming versus ~23MB holding them
    // all, against a 5MB bound — roughly 2x headroom below, 4x above, so the
    // assertion survives a CI machine allocating a little differently.
    //
    // Sanity-check the fixture first: if the images compressed away, the
    // budget below would pass for the wrong reason.
    expect($wouldHaveHeld)->toBeGreaterThan(4_000_000);

    // The bound guards an order of magnitude, not a byte count: reading one
    // photo at a time cannot approach the total, holding them all cannot stay
    // under it.
    expect($used)->toBeLessThan((int) ($wouldHaveHeld / 4));
})->skip(
    fn () => ! function_exists('imagecreatetruecolor'),
    'Requires the GD extension to build an image-bearing fixture.'
);

it('still imports every reader and attaches their photo', function () {
    Storage::fake('public');

    $fixture = makePhotoImportFixture(5);

    $stats = app(ReaderImportService::class)->import($fixture['path']);

    @unlink($fixture['path']);

    $photo = Reader::where('id_number', 'BT2099001')->value('photo');

    expect(Reader::whereIn('id_number', ['BT2099001', 'BT2099005'])->count())->toBe(2)
        ->and($photo)->not->toBeNull()
        ->and(array_sum(array_column($stats, 'photos')))->toBe(5);

    Storage::disk('public')->assertExists($photo);
})->skip(
    fn () => ! function_exists('imagecreatetruecolor'),
    'Requires the GD extension to build an image-bearing fixture.'
);
