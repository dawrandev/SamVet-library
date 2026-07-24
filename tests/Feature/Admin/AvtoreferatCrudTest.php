<?php

use App\Enums\CopyCondition;
use App\Enums\DissertationDegree;
use App\Models\Avtoreferat;
use App\Models\Language;
use App\Models\PublicationPlace;
use App\Models\ScienceField;
use Illuminate\Http\UploadedFile;

beforeEach(fn () => actingAsAdmin());

it('creates an avtoreferat with the dissertation-defense fields', function () {
    $place = PublicationPlace::factory()->create();
    $scienceField = ScienceField::factory()->create();

    $this->post(route('admin.avtoreferats.store'), [
        'title' => 'Veterinariya sohasida yangi usullar',
        'author' => 'Aliyev A.',
        'specialty' => '06.02.01 – Veterinariya mikrobiologiyasi',
        'science_field_id' => $scienceField->id,
        'degree' => DissertationDegree::Dsc->value,
        'council_number' => 'DSc.03/30.12.2019.B.02.01',
        'defense_institution' => 'Samarqand davlat universiteti',
        'performed_institution' => 'SamDU aspiranturasi',
        'advisor' => 'Karimov K.',
        'udc' => '619:616.98',
        'registration_number' => 'B24.15',
        'condition' => CopyCondition::New->value,
        'publication_place_id' => $place->id,
        'defense_year' => 2024,
        'inventory_number' => 'AR-00123',
    ])->assertRedirect();

    $avtoreferat = Avtoreferat::firstWhere('title', 'Veterinariya sohasida yangi usullar');
    expect($avtoreferat)->not->toBeNull()
        ->and($avtoreferat->degree)->toBe(DissertationDegree::Dsc)
        ->and($avtoreferat->condition)->toBe(CopyCondition::New)
        ->and($avtoreferat->advisor)->toBe('Karimov K.')
        ->and($avtoreferat->publication_place_id)->toBe($place->id)
        ->and($avtoreferat->defense_year)->toBe(2024)
        ->and($avtoreferat->science_field_id)->toBe($scienceField->id)
        ->and($avtoreferat->slug)->not->toBeEmpty()
        // No longer belongs to a journal issue.
        ->and($avtoreferat->getAttributes())->not->toHaveKey('journal_issue_id');
});

it('requires title and advisor, but not an author', function () {
    $this->from(route('admin.avtoreferats.create'))
        ->post(route('admin.avtoreferats.store'), [])
        ->assertSessionHasErrors(['title', 'advisor'])
        ->assertSessionDoesntHaveErrors('author');
});

it('rejects an invalid degree or condition', function () {
    $this->from(route('admin.avtoreferats.create'))
        ->post(route('admin.avtoreferats.store'), [
            'title' => 'X',
            'author' => 'Y',
            'advisor' => 'Z',
            'degree' => 'professor', // not a DissertationDegree case
            'condition' => 'destroyed', // not a CopyCondition case
        ])
        ->assertSessionHasErrors(['degree', 'condition']);
});

it('rejects a non-pdf file', function () {
    $this->from(route('admin.avtoreferats.create'))
        ->post(route('admin.avtoreferats.store'), [
            'title' => 'X',
            'author' => 'Y',
            'advisor' => 'Z',
            'electronic_file' => UploadedFile::fake()->create('note.txt', 10, 'text/plain'),
        ])
        ->assertSessionHasErrors('electronic_file');
});

it('updates an avtoreferat', function () {
    $avtoreferat = Avtoreferat::factory()->create(['title' => 'Eski nom']);

    $this->put(route('admin.avtoreferats.update', $avtoreferat), [
        'title' => 'Yangi nom',
        'author' => $avtoreferat->author,
        'advisor' => $avtoreferat->advisor,
    ])->assertRedirect();

    expect($avtoreferat->fresh()->title)->toBe('Yangi nom');
});

it('deletes an avtoreferat', function () {
    $avtoreferat = Avtoreferat::factory()->create();

    $this->delete(route('admin.avtoreferats.destroy', $avtoreferat))->assertRedirect();

    $this->assertDatabaseMissing('avtoreferats', ['id' => $avtoreferat->id]);
});

it('shows the science field and defense year on the show page', function () {
    $scienceField = ScienceField::factory()->create(['name' => 'Veterinariya fanlari']);
    $avtoreferat = Avtoreferat::factory()->create([
        'science_field_id' => $scienceField->id,
        'defense_year' => 2025,
    ]);

    $this->get(route('admin.avtoreferats.show', $avtoreferat))
        ->assertSee('Veterinariya fanlari')
        ->assertSee('Himoya yili')
        ->assertSee('2025');
});

it('saves an avtoreferat written in more than one language', function () {
    $uz = Language::factory()->create(['name' => 'O‘zbek']);
    $ru = Language::factory()->create(['name' => 'Rus']);

    $this->post(route('admin.avtoreferats.store'), [
        'title' => 'Ko‘p tilli avtoreferat',
        'advisor' => 'Aliyev A.',
        'language_ids' => [$uz->id, $ru->id],
    ])->assertRedirect();

    $avtoreferat = Avtoreferat::firstWhere('title', 'Ko‘p tilli avtoreferat');
    expect($avtoreferat->languages->pluck('id')->sort()->values()->all())->toBe([$uz->id, $ru->id]);
});

it('replaces (not appends to) the previous language set on update', function () {
    $uz = Language::factory()->create();
    $ru = Language::factory()->create();
    $kk = Language::factory()->create();

    $avtoreferat = Avtoreferat::factory()->create();
    $avtoreferat->languages()->sync([$uz->id, $ru->id]);

    $this->put(route('admin.avtoreferats.update', $avtoreferat), [
        'title' => $avtoreferat->title,
        'advisor' => $avtoreferat->advisor,
        'language_ids' => [$kk->id],
    ])->assertRedirect();

    expect($avtoreferat->fresh()->languages->pluck('id')->all())->toBe([$kk->id]);
});

it('shows the avtoreferat languages on the show page', function () {
    $uz = Language::factory()->create(['name' => 'Sinov tili']);
    $avtoreferat = Avtoreferat::factory()->create();
    $avtoreferat->languages()->attach($uz->id);

    $this->get(route('admin.avtoreferats.show', $avtoreferat))
        ->assertSee(__('Tillari'))
        ->assertSee('Sinov tili');
});

it('does not show an "ishtirokchi qo‘shish" (contributors) block on the create or edit form', function () {
    $avtoreferat = Avtoreferat::factory()->create();

    $this->get(route('admin.avtoreferats.create'))
        ->assertDontSee(__('Boshqa ishtirokchilar'));

    $this->get(route('admin.avtoreferats.edit', $avtoreferat))
        ->assertDontSee(__('Boshqa ishtirokchilar'));
});

it('does not show a resurs sohasi or annotatsiya field on the create form or show page', function () {
    $avtoreferat = Avtoreferat::factory()->create();

    $this->get(route('admin.avtoreferats.create'))
        ->assertDontSee('resource_field_id', false)
        ->assertDontSee(__('Resurs sohasi'))
        ->assertDontSee(__('Annotatsiya'));

    $this->get(route('admin.avtoreferats.show', $avtoreferat))
        ->assertDontSee(__('Resurs sohasi'))
        ->assertDontSee(__('Annotatsiya'));
});

it('lets the inline lookup-create endpoint add a new science field from the avtoreferat form', function () {
    $this->postJson(route('admin.lookups.store'), [
        'type' => 'science_field',
        'name' => 'Zootexniya fanlari',
    ])->assertCreated()->assertJsonStructure(['id', 'name']);

    expect(ScienceField::where('name', 'Zootexniya fanlari')->exists())->toBeTrue();
});

it('downloads an Excel export of the avtoreferat list', function () {
    Avtoreferat::factory()->count(2)->create();

    $response = $this->get(route('admin.avtoreferats.export'));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('spreadsheet');
});
