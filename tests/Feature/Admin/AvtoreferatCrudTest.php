<?php

use App\Enums\CopyCondition;
use App\Enums\DissertationDegree;
use App\Models\Avtoreferat;
use App\Models\PublicationPlace;
use App\Models\ResourceField;
use Illuminate\Http\UploadedFile;

beforeEach(fn () => actingAsAdmin());

it('creates an avtoreferat with the dissertation-defense fields', function () {
    $place = PublicationPlace::factory()->create();
    $field = ResourceField::factory()->create();

    $this->post(route('admin.avtoreferats.store'), [
        'title' => 'Veterinariya sohasida yangi usullar',
        'author' => 'Aliyev A.',
        'specialty' => '06.02.01 – Veterinariya mikrobiologiyasi',
        'degree' => DissertationDegree::Dsc->value,
        'council_number' => 'DSc.03/30.12.2019.B.02.01',
        'defense_institution' => 'Samarqand davlat universiteti',
        'performed_institution' => 'SamDU aspiranturasi',
        'advisor' => 'Karimov K.',
        'udc' => '619:616.98',
        'registration_number' => 'B24.15',
        'condition' => CopyCondition::New->value,
        'publication_place_id' => $place->id,
        'publication_year' => 2024,
        'inventory_number' => 'AR-00123',
        'resource_field_id' => $field->id,
    ])->assertRedirect();

    $avtoreferat = Avtoreferat::firstWhere('title', 'Veterinariya sohasida yangi usullar');
    expect($avtoreferat)->not->toBeNull()
        ->and($avtoreferat->degree)->toBe(DissertationDegree::Dsc)
        ->and($avtoreferat->condition)->toBe(CopyCondition::New)
        ->and($avtoreferat->advisor)->toBe('Karimov K.')
        ->and($avtoreferat->publication_place_id)->toBe($place->id)
        ->and($avtoreferat->publication_year)->toBe(2024)
        ->and($avtoreferat->slug)->not->toBeEmpty()
        // No longer belongs to a journal issue.
        ->and($avtoreferat->getAttributes())->not->toHaveKey('journal_issue_id');
});

it('requires title, author and advisor', function () {
    $this->from(route('admin.avtoreferats.create'))
        ->post(route('admin.avtoreferats.store'), [])
        ->assertSessionHasErrors(['title', 'author', 'advisor']);
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
