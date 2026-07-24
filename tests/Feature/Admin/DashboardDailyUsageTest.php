<?php

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\BookReading;
use App\Models\Computer;
use App\Models\ComputerSession;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Language;
use App\Models\Loan;
use App\Models\Reader;

beforeEach(fn () => actingAsAdmin());

it('defaults the daily usage range to the last 7 days', function () {
    $res = $this->get(route('admin.dashboard'));

    $res->assertOk();
    expect($res->viewData('statsFrom')->format('Y-m-d'))->toBe(today()->subDays(6)->format('Y-m-d'))
        ->and($res->viewData('statsTo')->format('Y-m-d'))->toBe(today()->format('Y-m-d'))
        ->and($res->viewData('dailyUsage')['dates'])->toHaveCount(7);
});

it('counts each metric on its own day and sums them into a daily total', function () {
    $day = '2026-06-10';

    $reader = Reader::factory()->create();
    Loan::factory()->create(['reader_id' => $reader->id, 'issued_at' => "{$day} 09:00:00"]);

    $book = Book::factory()->create();
    BookReading::factory()->create(['reader_id' => $reader->id, 'book_id' => $book->id, 'read_at' => "{$day} 10:00:00"]);

    ComputerSession::factory()->create(['reader_id' => $reader->id, 'computer_id' => Computer::factory(), 'issued_at' => "{$day} 11:00:00"]);

    $event = Event::factory()->create(['date' => $day]);
    EventParticipant::create(['event_id' => $event->id, 'reader_id' => $reader->id, 'role' => 'participant']);

    $res = $this->get(route('admin.dashboard', ['stats_from' => $day, 'stats_to' => $day]));

    $daily = $res->viewData('dailyUsage');
    expect($daily['dates'])->toBe([$day])
        ->and($daily['loans'])->toBe([1])
        ->and($daily['onlineReadings'])->toBe([1])
        ->and($daily['computerSessions'])->toBe([1])
        ->and($daily['eventParticipations'])->toBe([1])
        ->and($daily['total'])->toBe([4]);
});

it('zero-fills days with no activity, and excludes activity outside the range', function () {
    $reader = Reader::factory()->create();
    Loan::factory()->create(['reader_id' => $reader->id, 'issued_at' => '2026-06-10 09:00:00']);
    Loan::factory()->create(['reader_id' => $reader->id, 'issued_at' => '2026-06-01 09:00:00']); // outside range

    $res = $this->get(route('admin.dashboard', ['stats_from' => '2026-06-09', 'stats_to' => '2026-06-11']));

    $daily = $res->viewData('dailyUsage');
    expect($daily['dates'])->toBe(['2026-06-09', '2026-06-10', '2026-06-11'])
        ->and($daily['loans'])->toBe([0, 1, 0]);
});

it('counts books by language both by title (nomi) and by copy (nusxa)', function () {
    $uz = Language::factory()->create(['name' => 'Bir tilli kitob']);
    $ru = Language::factory()->create(['name' => 'Ko‘p nusxali kitob']);

    Book::factory()->create(['language_id' => $uz->id]); // 1 title, 0 copies

    $ruBook = Book::factory()->create(['language_id' => $ru->id]);
    BookCopy::factory()->count(3)->create(['book_id' => $ruBook->id]); // 1 title, 3 copies

    $res = $this->get(route('admin.dashboard'));

    $byTitle = $res->viewData('booksByLanguage');
    $byCopy = $res->viewData('copiesByLanguage');

    expect((int) $byTitle[$uz->id])->toBe(1)
        ->and((int) ($byCopy[$uz->id] ?? 0))->toBe(0)
        ->and((int) $byTitle[$ru->id])->toBe(1)
        ->and((int) $byCopy[$ru->id])->toBe(3);
});

it('shows the daily usage chart and the language bar toggle on the dashboard', function () {
    $this->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Kunlik statistika')
        ->assertSee('Nusxa')
        ->assertSee('Nomi');
});
