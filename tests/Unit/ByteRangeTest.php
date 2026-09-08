<?php

use App\Support\ByteRange;

const FILE_SIZE = 1000;

it('serves the whole file when there is no Range header', function (?string $header) {
    $range = ByteRange::parse($header, FILE_SIZE);

    expect($range)->not->toBeNull()
        ->and($range->isPartial)->toBeFalse()
        ->and($range->status())->toBe(200)
        ->and($range->start)->toBe(0)
        ->and($range->end)->toBe(999)
        ->and($range->length())->toBe(1000);
})->with([null, '', '   ']);

it('reads a closed range', function () {
    $range = ByteRange::parse('bytes=0-499', FILE_SIZE);

    expect($range->isPartial)->toBeTrue()
        ->and($range->status())->toBe(206)
        ->and($range->start)->toBe(0)
        ->and($range->end)->toBe(499)
        ->and($range->length())->toBe(500)
        ->and($range->contentRange(FILE_SIZE))->toBe('bytes 0-499/1000');
});

it('reads an open range as "to the end of the file"', function () {
    $range = ByteRange::parse('bytes=500-', FILE_SIZE);

    expect($range->start)->toBe(500)
        ->and($range->end)->toBe(999)
        ->and($range->length())->toBe(500);
});

it('reads a suffix range as "the last N bytes"', function () {
    $range = ByteRange::parse('bytes=-300', FILE_SIZE);

    expect($range->start)->toBe(700)
        ->and($range->end)->toBe(999)
        ->and($range->length())->toBe(300);
});

it('clamps a suffix longer than the file to the whole file', function () {
    $range = ByteRange::parse('bytes=-99999', FILE_SIZE);

    expect($range->start)->toBe(0)
        ->and($range->end)->toBe(999);
});

it('clamps an end past the file to the last byte', function () {
    $range = ByteRange::parse('bytes=900-99999', FILE_SIZE);

    expect($range->start)->toBe(900)
        ->and($range->end)->toBe(999)
        ->and($range->length())->toBe(100);
});

/**
 * The bug this class was extracted to fix. The video reader clamped only the
 * end, so a start past the file produced end < start and a negative
 * Content-Length — a malformed response where the spec asks for 416.
 */
it('refuses a start past the end of the file', function (string $header) {
    expect(ByteRange::parse($header, FILE_SIZE))->toBeNull();
})->with([
    'bytes=1000-',
    'bytes=999999999-',
    'bytes=1500-2000',
    'bytes=500-499',
    'bytes=-0',
]);

it('never yields a negative or zero length for a satisfiable range', function (string $header) {
    $range = ByteRange::parse($header, FILE_SIZE);

    expect($range)->not->toBeNull()
        ->and($range->length())->toBeGreaterThan(0)
        ->and($range->start)->toBeGreaterThanOrEqual(0)
        ->and($range->end)->toBeLessThan(FILE_SIZE);
})->with([
    'bytes=0-0',
    'bytes=999-999',
    'bytes=0-',
    'bytes=-1',
    'bytes=0-99999',
]);

it('serves the whole file for a multi-range request rather than pretending', function () {
    // Answering with just the first range while reporting it as the complete
    // response would hand the client the wrong bytes for the rest.
    $range = ByteRange::parse('bytes=0-99,200-299', FILE_SIZE);

    expect($range->isPartial)->toBeFalse()
        ->and($range->length())->toBe(1000);
});

it('ignores a unit it does not understand', function (string $header) {
    $range = ByteRange::parse($header, FILE_SIZE);

    expect($range->isPartial)->toBeFalse();
})->with(['items=0-10', 'bytes=abc', 'nonsense', 'bytes=']);

it('serves an empty file without a partial response', function () {
    $range = ByteRange::parse('bytes=0-100', 0);

    expect($range)->not->toBeNull()
        ->and($range->isPartial)->toBeFalse();
});
