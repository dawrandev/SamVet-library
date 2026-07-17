<?php

use App\Models\Event;
use App\Models\EventLocation;
use App\Models\News;
use App\Models\Reader;

beforeEach(fn () => actingAsAdmin());

// --- EventLocation lookup CRUD ---

it('creates an event location', function () {
    $this->post(route('admin.lookups.event-locations.store'), [
        'name' => 'ARM o‘qish zali',
    ])->assertRedirect(route('admin.lookups.event-locations.index'));

    expect(EventLocation::where('name', 'ARM o‘qish zali')->exists())->toBeTrue();
});

it('deletes an unused event location', function () {
    $location = EventLocation::factory()->create();

    $this->delete(route('admin.lookups.event-locations.destroy', $location))->assertRedirect();

    $this->assertDatabaseMissing('event_locations', ['id' => $location->id]);
});

// --- Event create/update with participants ---

it('creates an event with a reader participant and a location', function () {
    $reader = Reader::factory()->create();
    $location = EventLocation::factory()->create();

    $this->post(route('admin.events.store'), [
        'name' => 'Kitobxonlar bayrami',
        'type' => 'contest',
        'date' => '2026-07-10',
        'location_ids' => [$location->id],
        'participants' => [
            ['is_external' => '0', 'reader_id' => $reader->id, 'role' => 'participant'],
        ],
    ])->assertRedirect(route('admin.events.index'));

    $event = Event::firstWhere('name', 'Kitobxonlar bayrami');
    expect($event)->not->toBeNull()
        ->and($event->locations)->toHaveCount(1)
        ->and($event->locations->first()->id)->toBe($location->id)
        ->and($event->participants)->toHaveCount(1)
        ->and($event->participants->first()->reader_id)->toBe($reader->id)
        ->and($event->participants->first()->external_name)->toBeNull();
});

it('creates an event with an external (non-reader) participant', function () {
    $this->post(route('admin.events.store'), [
        'name' => 'She’riyat kechasi',
        'type' => 'event',
        'date' => '2026-07-12',
        'participants' => [
            ['is_external' => '1', 'external_name' => 'Shoir Tashqi I.', 'role' => 'host'],
        ],
    ])->assertRedirect();

    $event = Event::firstWhere('name', 'She’riyat kechasi');
    expect($event->participants)->toHaveCount(1)
        ->and($event->participants->first()->reader_id)->toBeNull()
        ->and($event->participants->first()->external_name)->toBe('Shoir Tashqi I.')
        ->and($event->participants->first()->displayName())->toBe('Shoir Tashqi I.');
});

it('rejects a reader-mode participant row with no reader selected', function () {
    $this->from(route('admin.events.create'))
        ->post(route('admin.events.store'), [
            'name' => 'X',
            'type' => 'event',
            'date' => '2026-07-12',
            'participants' => [
                ['is_external' => '0', 'reader_id' => '', 'role' => 'participant'],
            ],
        ])
        ->assertSessionHasErrors('participants.0.reader_id');
});

it('rejects an external-mode participant row with no name', function () {
    $this->from(route('admin.events.create'))
        ->post(route('admin.events.store'), [
            'name' => 'X',
            'type' => 'event',
            'date' => '2026-07-12',
            'participants' => [
                ['is_external' => '1', 'external_name' => '', 'role' => 'participant'],
            ],
        ])
        ->assertSessionHasErrors('participants.0.external_name');
});

it('derives the event link from its linked news post, never typed by hand', function () {
    $news = News::factory()->create(['title' => ['uz' => 'Tadbir haqida yangilik']]);

    $this->post(route('admin.events.store'), [
        'name' => 'Konferensiya',
        'type' => 'meeting',
        'date' => '2026-07-14',
        'news_id' => $news->id,
    ])->assertRedirect();

    $event = Event::firstWhere('name', 'Konferensiya');
    expect($event->link())->toBe(route('news.show', $news->slug));
});

it('has no link when no news post is attached', function () {
    $this->post(route('admin.events.store'), [
        'name' => 'Oddiy tadbir',
        'type' => 'meeting',
        'date' => '2026-07-14',
    ])->assertRedirect();

    expect(Event::firstWhere('name', 'Oddiy tadbir')->link())->toBeNull();
});

it('accepts more than one location for a single event', function () {
    $locationA = EventLocation::factory()->create();
    $locationB = EventLocation::factory()->create();

    $this->post(route('admin.events.store'), [
        'name' => 'Ko‘p zalli tadbir',
        'type' => 'exhibition',
        'date' => '2026-07-16',
        'location_ids' => [$locationA->id, $locationB->id],
    ])->assertRedirect();

    expect(Event::firstWhere('name', 'Ko‘p zalli tadbir')->locations)->toHaveCount(2);
});

it('replaces the previous participant set on update, not appends', function () {
    $event = Event::factory()->create();
    $readerA = Reader::factory()->create();
    $readerB = Reader::factory()->create();

    $this->put(route('admin.events.update', $event), [
        'name' => $event->name,
        'type' => $event->type->value,
        'date' => $event->date->format('Y-m-d'),
        'participants' => [
            ['is_external' => '0', 'reader_id' => $readerA->id, 'role' => 'participant'],
        ],
    ])->assertRedirect();

    $this->put(route('admin.events.update', $event), [
        'name' => $event->name,
        'type' => $event->type->value,
        'date' => $event->date->format('Y-m-d'),
        'participants' => [
            ['is_external' => '0', 'reader_id' => $readerB->id, 'role' => 'jury'],
        ],
    ])->assertRedirect();

    $event->refresh();
    expect($event->participants)->toHaveCount(1)
        ->and($event->participants->first()->reader_id)->toBe($readerB->id);
});

it('deletes an event', function () {
    $event = Event::factory()->create();

    $this->delete(route('admin.events.destroy', $event))->assertRedirect();

    $this->assertDatabaseMissing('events', ['id' => $event->id]);
});

// --- Reader show page: read-only participation list ---

it('shows a reader’s event participations read-only, without an add form', function () {
    $reader = Reader::factory()->create();
    $event = Event::factory()->create(['name' => 'Ko‘rgazma sinov tadbiri']);
    $event->participants()->create(['reader_id' => $reader->id, 'role' => 'participant']);

    $this->get(route('admin.readers.show', $reader))
        ->assertSee('Ko‘rgazma sinov tadbiri')
        ->assertDontSee('name="date"', false);
});
