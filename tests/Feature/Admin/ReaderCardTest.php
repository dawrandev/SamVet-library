<?php

use App\Models\AffiliationGroup;
use App\Models\AffiliationPlace;
use App\Models\AffiliationUnit;
use App\Models\Reader;
use App\Models\ReaderType;
use App\Services\ReaderCardService;
use App\Support\ReaderCard\CardPalette;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => actingAsAdmin());

/** The card's HTML exactly as the PDF is built from it. */
function readerCardHtml(Reader $reader, string $view = 'pages.admin.readers.card'): string
{
    return view($view, ['card' => app(ReaderCardService::class)->data($reader)])->render();
}

function pdfPageCount(string $pdf): int
{
    preg_match_all('/\/Count (\d+)/', $pdf, $matches);

    return max(array_map('intval', $matches[1] ?: [0]));
}

it('streams the reader card as a PDF', function () {
    $reader = Reader::factory()->create();

    $res = $this->get(route('admin.readers.card', $reader));

    $res->assertOk();
    expect($res->headers->get('content-type'))->toContain('application/pdf');
});

it('splits full_name into Familyasi/Ismi/Sharifi and shows staff labels', function () {
    $reader = Reader::factory()->create([
        'reader_type_id' => ReaderType::factory()->create(['is_student' => false])->id,
        'full_name' => 'Palensheyev Tólenshe Tólensheyevich',
        'id_number' => 'FX0119001',
        'affiliation_place_id' => AffiliationPlace::factory()->create(['name' => 'Ish joyi nomi'])->id,
        'affiliation_unit_id' => AffiliationUnit::factory()->create(['name' => 'Bo‘limi nomi'])->id,
        'affiliation_group_id' => AffiliationGroup::factory()->create(['name' => 'Lavozim nomi'])->id,
    ]);

    expect(readerCardHtml($reader))->toContain('Palensheyev')
        ->toContain('Tólenshe')
        ->toContain('Tólensheyevich')
        ->toContain('FX0119001')
        ->toContain('Ish joyi')
        ->toContain('Bo‘limi')
        ->toContain('Lavozimi')
        ->not->toContain('O‘qish joyi')
        ->not->toContain('Guruhi');
});

it('keeps a two-word patronymic whole ("Xabibulla o‘g‘li")', function () {
    $reader = Reader::factory()->create(['full_name' => 'Karimov Anvar Xabibulla o‘g‘li']);

    $card = app(ReaderCardService::class)->data($reader);

    expect($card->surname)->toBe('Karimov')
        ->and($card->firstName)->toBe('Anvar')
        ->and($card->patronymic)->toBe('Xabibulla o‘g‘li');
});

it('shows student labels (O‘qish joyi/Mutaxassisligi/Guruhi) for a student reader', function () {
    $reader = Reader::factory()->create([
        'reader_type_id' => ReaderType::factory()->create(['is_student' => true])->id,
    ]);

    expect(readerCardHtml($reader))->toContain('O‘qish joyi')
        ->toContain('Mutaxassisligi')
        ->toContain('Guruhi')
        ->not->toContain('Ish joyi')
        ->not->toContain('Lavozimi');
});

it('colours the card from the reader type\'s own certificate_color', function () {
    foreach (['#2563eb', '#7c3aed', '#dc2626', '#12b76a'] as $color) {
        $reader = Reader::factory()->create([
            'reader_type_id' => ReaderType::factory()->create(['certificate_color' => $color])->id,
        ]);
        $palette = CardPalette::fromAccent($color);

        expect(readerCardHtml($reader))
            ->toContain('background-color: '.strtoupper($color).'; border-radius: 4px;')
            ->toContain('background-color: '.$palette->soft.';')
            ->toContain('background-color: '.$palette->strong.';')
            ->toContain('.ground { background-color: '.$palette->pattern.';');
    }
});

it('leaves the Berilgan sana line blank when issued_date is unknown', function () {
    $reader = Reader::factory()->create(['issued_date' => null]);

    expect(readerCardHtml($reader))->toContain('Berilgan sana')
        ->not->toMatch('/\d{2}\.\d{2}\.\d{4}/');
});

it('writes the actual issued_date on the line once it is known', function () {
    $reader = Reader::factory()->create(['issued_date' => '2026-03-05']);

    expect(readerCardHtml($reader))->toContain('05.03.2026');
});

it('shows 5 registration-year blocks', function () {
    $reader = Reader::factory()->create();

    // Counted by the year blank, not "o‘quv yili" — the rules on the outside
    // use that phrase too.
    expect(substr_count(readerCardHtml($reader), '20__ / 20__'))->toBe(5);
});

it('prints all seven library rules on the outside', function () {
    $html = readerCardHtml(Reader::factory()->create());

    expect($html)->toContain('ARMda kitobxon guvohnomasiz kitob berilmaydi.')
        ->toContain('tasdiqlash zarur.')
        ->toContain('oshiriladi.')
        ->toContain('KITOBXON GUVOHNOMASIDAN BOSHQA SHAXS');
});

it('passes the reader\'s uploaded photo as a local file path, not a data URI', function () {
    Storage::fake('public');
    Storage::disk('public')->put('photos/reader.jpg', 'fake-image-bytes');
    $reader = Reader::factory()->create(['photo' => 'photos/reader.jpg']);

    $html = readerCardHtml($reader);

    expect($html)->toContain('photos/reader.jpg')
        ->not->toContain('data:image');
});

it('renders the card without a photo file present on disk (stale DB value)', function () {
    Storage::fake('public');
    $reader = Reader::factory()->create(['photo' => 'photos/missing.jpg']);

    $this->get(route('admin.readers.card', $reader))->assertOk();
    expect(readerCardHtml($reader))->toContain('Rasm yo‘q');
});

it('is exactly two pages — outside and inside — even with long wrapping affiliation text', function () {
    $reader = Reader::factory()->create([
        'full_name' => 'Abdurahmonova Gulnoraxon Xabibulla qizi',
        'affiliation_unit_id' => AffiliationUnit::factory()->create([
            'name' => '70840302 - Veterinariya mikrobiologiyasi, virusologiyasi, epizootologiyasi, mikologiyasi va immunologiyasi',
        ])->id,
    ]);

    $res = $this->get(route('admin.readers.card', $reader));

    expect(pdfPageCount($res->getContent()))->toBe(2);
});

/*
 * The failure this guards against is silent: if dompdf cannot load a font
 * face — a missing storage/fonts cache directory, a path it cannot read — it
 * quietly falls back to its own default font and the card still renders.
 * Only the embedded font names give it away. Verified by removing that
 * directory: this test and the next one both fail.
 */
it('embeds the design\'s own fonts instead of falling back to a default', function () {
    $pdf = $this->get(route('admin.readers.card', Reader::factory()->create()))->getContent();

    expect($pdf)->toContain('Inter')
        ->toContain('RobotoMono')
        ->not->toContain('DejaVu')
        ->not->toContain('/BaseFont /Times');
});

it('ships the storage/fonts directory dompdf caches font metrics in', function () {
    expect(is_dir(storage_path('fonts')))->toBeTrue()
        ->and(is_file(storage_path('fonts/.gitignore')))->toBeTrue();
});

it('gives a reader type added later the design\'s "Basqalar" orange', function () {
    // Inserted without a colour, the way the admin form leaves it alone.
    $id = DB::table('reader_types')->insertGetId([
        'name' => 'Tashqi tashkilot xodimi',
        'is_student' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(DB::table('reader_types')->where('id', $id)->value('certificate_color'))->toBe('#C58A00');
});

it('streams the formulyar cover as a single-page PDF', function () {
    $reader = Reader::factory()->create();

    $res = $this->get(route('admin.readers.famulyar', $reader));

    $res->assertOk();
    expect($res->headers->get('content-type'))->toContain('application/pdf')
        ->and(pdfPageCount($res->getContent()))->toBe(1);
});

it('shows the id_number on the formulyar spine and front, and no name — as in Figma', function () {
    $reader = Reader::factory()->create([
        'id_number' => 'FX0119001',
        'full_name' => 'Nazarova Madina Baxtiyorovna',
    ]);

    $html = readerCardHtml($reader, 'pages.admin.readers.famulyar');

    expect(substr_count($html, 'FX0119001'))->toBe(2)
        ->and($html)->toContain('KITOBXON FORMULYARI')
        ->not->toContain('Nazarova');
});

it('shows the famulyar download button on the reader show page', function () {
    $reader = Reader::factory()->create();

    $this->get(route('admin.readers.show', $reader))
        ->assertSee(route('admin.readers.famulyar', $reader), false)
        ->assertSee('Famulyar yuklab olish');
});
