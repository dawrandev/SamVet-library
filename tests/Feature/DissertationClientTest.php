<?php

use App\Models\Dissertation;
use App\Models\OnlineRead;
use App\Models\ScienceField;
use Illuminate\Support\Facades\Storage;

it('renders the public dissertation catalog', function () {
    Dissertation::factory()->create(['title' => 'Ochiq dissertatsiya']);

    $this->get(route('dissertations.index'))
        ->assertOk()
        ->assertSee('Ochiq dissertatsiya');
});

it('filters the dissertation catalog by search', function () {
    Dissertation::factory()->create(['title' => 'Veterinariya sohasida tadqiqot']);
    Dissertation::factory()->create(['title' => 'Boshqa mavzu']);

    $this->get(route('dissertations.index', ['search' => 'Veterinariya']))
        ->assertOk()
        ->assertSee('Veterinariya sohasida tadqiqot')
        ->assertDontSee('Boshqa mavzu');
});

it('shows the dissertation detail page with its public bibliographic fields', function () {
    $scienceField = ScienceField::factory()->create(['name' => 'Veterinariya fanlari']);
    $dissertation = Dissertation::factory()->create([
        'title' => 'Ochiq dissertatsiya',
        'science_field_id' => $scienceField->id,
        'advisor' => 'Ochiq rahbar',
    ]);

    $this->get(route('dissertation.show', $dissertation->slug))
        ->assertOk()
        ->assertSee('Ochiq dissertatsiya')
        ->assertSee('Veterinariya fanlari')
        ->assertSee('Ochiq rahbar');
});

it('never shows a dissertation’s inventory number or condition on the client page', function () {
    $dissertation = Dissertation::factory()->create([
        'inventory_number' => 'INV-SECRET-99',
    ]);

    $this->get(route('dissertation.show', $dissertation->slug))
        ->assertOk()
        ->assertDontSee('INV-SECRET-99');
});

it('has no resurs sohasi field at all — it was never in the librarian’s mockup', function () {
    $dissertation = Dissertation::factory()->create();

    $this->get(route('dissertation.show', $dissertation->slug))
        ->assertOk()
        ->assertDontSee(__('Resurs sohasi'));
});

it('shows the online-reading link only when an electronic file is attached', function () {
    $withPdf = Dissertation::factory()->withPdf()->create();
    $withoutPdf = Dissertation::factory()->create();

    $this->get(route('dissertation.show', $withPdf->slug))->assertSee(route('read.dissertation', $withPdf->slug), false);
    $this->get(route('dissertation.show', $withoutPdf->slug))->assertDontSee(route('read.dissertation', $withoutPdf->slug), false);
});

it('increments the dissertation view count on each visit', function () {
    $dissertation = Dissertation::factory()->create(['views_count' => 0]);

    $this->get(route('dissertation.show', $dissertation->slug));

    expect($dissertation->fresh()->views_count)->toBe(1);
});

it('redirects a guest from the dissertation reader page to the reader login', function () {
    $dissertation = Dissertation::factory()->withPdf()->create();

    $this->get(route('read.dissertation', $dissertation->slug))->assertRedirect(route('reader.login'));
});

it('lets a signed-in reader open the dissertation reader page', function () {
    actingAsReader();
    $dissertation = Dissertation::factory()->withPdf()->create();

    $this->get(route('read.dissertation', $dissertation->slug))->assertOk();
});

it('streams the dissertation pdf inline, never as a download', function () {
    Storage::fake('local');
    $path = 'dissertations/electronic/x.pdf';
    Storage::disk('local')->put($path, '%PDF-1.7 fake');

    actingAsReader();
    $dissertation = Dissertation::factory()->withPdf($path)->create();

    $res = $this->get(route('read.dissertation.file', $dissertation->slug));

    $res->assertOk();
    expect($res->headers->get('content-type'))->toContain('application/pdf')
        ->and($res->headers->get('content-disposition'))->toContain('inline')
        ->and($res->headers->get('content-disposition'))->not->toContain('attachment');
});

it('404s when a reader opens a dissertation that has no stored pdf', function () {
    actingAsReader();
    $dissertation = Dissertation::factory()->create();

    $this->get(route('read.dissertation', $dissertation->slug))->assertNotFound();
    $this->get(route('read.dissertation.file', $dissertation->slug))->assertNotFound();
});

it('logs an online read when a reader opens the dissertation', function () {
    $reader = actingAsReader();
    $dissertation = Dissertation::factory()->withPdf()->create();

    $this->get(route('read.dissertation', $dissertation->slug))->assertOk();

    $reading = OnlineRead::where('reader_id', $reader->id)
        ->where('readable_type', 'dissertation')
        ->where('readable_id', $dissertation->id)
        ->first();
    expect($reading)->not->toBeNull()
        ->and($reading->read_at->diffInSeconds(now()))->toBeLessThan(5);
});
