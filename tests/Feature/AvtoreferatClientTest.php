<?php

use App\Models\Avtoreferat;
use Illuminate\Support\Facades\Storage;

it('renders the public avtoreferat catalog', function () {
    Avtoreferat::factory()->create(['title' => 'Ochiq avtoreferat']);

    $this->get(route('avtoreferats.index'))
        ->assertOk()
        ->assertSee('Ochiq avtoreferat');
});

it('filters the avtoreferat catalog by search', function () {
    Avtoreferat::factory()->create(['title' => 'Veterinariya sohasida tadqiqot']);
    Avtoreferat::factory()->create(['title' => 'Boshqa mavzu']);

    $this->get(route('avtoreferats.index', ['search' => 'Veterinariya']))
        ->assertOk()
        ->assertSee('Veterinariya sohasida tadqiqot')
        ->assertDontSee('Boshqa mavzu');
});

it('shows the avtoreferat detail page with its public bibliographic fields', function () {
    $avtoreferat = Avtoreferat::factory()->create([
        'title' => 'Ochiq avtoreferat',
        'advisor' => 'Ochiq rahbar',
    ]);

    $this->get(route('avtoreferat.show', $avtoreferat->slug))
        ->assertOk()
        ->assertSee('Ochiq avtoreferat')
        ->assertSee('Ochiq rahbar');
});

it('never shows an avtoreferat’s inventory number or condition on the client page', function () {
    $avtoreferat = Avtoreferat::factory()->create(['inventory_number' => 'INV-SECRET-77']);

    $this->get(route('avtoreferat.show', $avtoreferat->slug))
        ->assertOk()
        ->assertDontSee('INV-SECRET-77');
});

it('shows the online-reading link only when an electronic file is attached', function () {
    $withPdf = Avtoreferat::factory()->withPdf()->create();
    $withoutPdf = Avtoreferat::factory()->create();

    $this->get(route('avtoreferat.show', $withPdf->slug))->assertSee(route('read.avtoreferat', $withPdf->slug), false);
    $this->get(route('avtoreferat.show', $withoutPdf->slug))->assertDontSee(route('read.avtoreferat', $withoutPdf->slug), false);
});

it('increments the avtoreferat view count on each visit', function () {
    $avtoreferat = Avtoreferat::factory()->create(['views_count' => 0]);

    $this->get(route('avtoreferat.show', $avtoreferat->slug));

    expect($avtoreferat->fresh()->views_count)->toBe(1);
});

it('redirects a guest from the avtoreferat reader page to the reader login', function () {
    $avtoreferat = Avtoreferat::factory()->withPdf()->create();

    $this->get(route('read.avtoreferat', $avtoreferat->slug))->assertRedirect(route('reader.login'));
});

it('lets a signed-in reader open the avtoreferat reader page', function () {
    actingAsReader();
    $avtoreferat = Avtoreferat::factory()->withPdf()->create();

    $this->get(route('read.avtoreferat', $avtoreferat->slug))->assertOk();
});

it('streams the avtoreferat pdf inline, never as a download', function () {
    Storage::fake('local');
    $path = 'avtoreferats/electronic/x.pdf';
    Storage::disk('local')->put($path, '%PDF-1.7 fake');

    actingAsReader();
    $avtoreferat = Avtoreferat::factory()->withPdf($path)->create();

    $res = $this->get(route('read.avtoreferat.file', $avtoreferat->slug));

    $res->assertOk();
    expect($res->headers->get('content-type'))->toContain('application/pdf')
        ->and($res->headers->get('content-disposition'))->toContain('inline')
        ->and($res->headers->get('content-disposition'))->not->toContain('attachment');
});

it('404s when a reader opens an avtoreferat that has no stored pdf', function () {
    actingAsReader();
    $avtoreferat = Avtoreferat::factory()->create();

    $this->get(route('read.avtoreferat', $avtoreferat->slug))->assertNotFound();
    $this->get(route('read.avtoreferat.file', $avtoreferat->slug))->assertNotFound();
});
