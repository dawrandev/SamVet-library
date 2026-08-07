<?php

use App\Models\AffiliationGroup;
use App\Models\AffiliationUnit;
use App\Models\ComputerSession;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Loan;
use App\Models\OnlineRead;
use App\Models\Reader;
use App\Models\ReaderType;
use App\Services\Site\HomeService;

it('excludes a reader with no activity at all from the active-readers wall', function () {
    Reader::factory()->create(['status' => 'active']);

    $data = app(HomeService::class)->homeData();

    expect($data['activeReaders'])->toHaveCount(0);
});

it('includes a reader who has read books online', function () {
    $reader = Reader::factory()->create(['status' => 'active']);
    OnlineRead::factory()->create(['reader_id' => $reader->id]);

    $data = app(HomeService::class)->homeData();

    expect($data['activeReaders']->pluck('id'))->toContain($reader->id);
});

it('includes a reader who has borrowed a physical copy', function () {
    $reader = Reader::factory()->create(['status' => 'active']);
    Loan::factory()->returned()->create(['reader_id' => $reader->id]);

    $data = app(HomeService::class)->homeData();

    expect($data['activeReaders']->pluck('id'))->toContain($reader->id);
});

it('includes a reader who has used a library computer', function () {
    $reader = Reader::factory()->create(['status' => 'active']);
    ComputerSession::factory()->finished()->create(['reader_id' => $reader->id]);

    $data = app(HomeService::class)->homeData();

    expect($data['activeReaders']->pluck('id'))->toContain($reader->id);
});

it('includes a reader who has participated in an event', function () {
    $reader = Reader::factory()->create(['status' => 'active']);
    $event = Event::factory()->create();
    EventParticipant::create(['event_id' => $event->id, 'reader_id' => $reader->id, 'role' => 'participant']);

    $data = app(HomeService::class)->homeData();

    expect($data['activeReaders']->pluck('id'))->toContain($reader->id);
});

it('excludes a blocked reader even with plenty of activity', function () {
    $reader = Reader::factory()->create(['status' => 'blocked']);
    OnlineRead::factory()->count(5)->create(['reader_id' => $reader->id]);

    $data = app(HomeService::class)->homeData();

    expect($data['activeReaders']->pluck('id'))->not->toContain($reader->id);
});

it('orders active readers by total activity, most active first', function () {
    $quiet = Reader::factory()->create(['status' => 'active']);
    OnlineRead::factory()->create(['reader_id' => $quiet->id]);

    $busy = Reader::factory()->create(['status' => 'active']);
    OnlineRead::factory()->count(4)->create(['reader_id' => $busy->id]);

    $data = app(HomeService::class)->homeData();

    expect($data['activeReaders']->pluck('id')->take(2)->all())->toBe([$busy->id, $quiet->id]);
});

it('shows a student’s group and specialty, and a staff reader’s position', function () {
    $studentType = ReaderType::factory()->create(['is_student' => true]);
    $staffType = ReaderType::factory()->create(['is_student' => false]);
    $group = AffiliationGroup::factory()->create(['name' => '2-kurs, 14-guruh']);
    $unit = AffiliationUnit::factory()->create(['name' => 'Veterinariya']);
    $position = AffiliationGroup::factory()->create(['name' => 'Bosh kutubxonachi']);

    $student = Reader::factory()->create([
        'status' => 'active',
        'reader_type_id' => $studentType->id,
        'affiliation_group_id' => $group->id,
        'affiliation_unit_id' => $unit->id,
    ]);
    OnlineRead::factory()->create(['reader_id' => $student->id]);

    $staff = Reader::factory()->create([
        'status' => 'active',
        'reader_type_id' => $staffType->id,
        'affiliation_group_id' => $position->id,
    ]);
    OnlineRead::factory()->create(['reader_id' => $staff->id]);

    expect($student->fresh()->load('type', 'affiliationUnit', 'affiliationGroup')->affiliationLine())
        ->toBe('2-kurs, 14-guruh · Veterinariya')
        ->and($staff->fresh()->load('type', 'affiliationGroup')->affiliationLine())->toBe('Bosh kutubxonachi');
});

it('renders the active readers section on the home page', function () {
    $reader = Reader::factory()->create(['status' => 'active', 'full_name' => 'Eng Faol Test Reader']);
    OnlineRead::factory()->create(['reader_id' => $reader->id]);

    $this->get('/')
        ->assertOk()
        ->assertSee('Eng faol kitobxonlar')
        ->assertSee('Eng Faol Test Reader');
});
