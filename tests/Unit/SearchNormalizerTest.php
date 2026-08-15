<?php

use App\Support\SearchNormalizer;

it('folds Cyrillic and Latin spellings of the same word onto one form', function (string $cyrillic, string $latin) {
    expect(SearchNormalizer::normalize($cyrillic))
        ->toBe(SearchNormalizer::normalize($latin))
        ->and(SearchNormalizer::normalize($latin))->not->toBe('');
})->with([
    ['Ветеринария', 'veterinariya'],
    ['Умумий ветеринария асослари', 'Umumiy veterinariya asoslari'],
    ['Иқтисодиёт назарияси', 'Iqtisodiyot nazariyasi'],
    ['Биология', 'biologiya'],
    ['А. Ўлмасов', 'A. Oʻlmasov'],
]);

it('collapses every apostrophe variant Uzbek Latin text arrives with', function () {
    $forms = ["O‘zbekiston", "Oʻzbekiston", "O'zbekiston", 'Ozbekiston', 'Ўзбекистон'];

    expect(array_unique(array_map(SearchNormalizer::normalize(...), $forms)))
        ->toHaveCount(1)
        ->and(SearchNormalizer::normalize("O‘zbekiston"))->toBe('ozbekiston');
});

it('is case insensitive across scripts', function () {
    expect(SearchNormalizer::normalize('ВЕТЕРИНАРИЯ'))->toBe('veterinariya')
        ->and(SearchNormalizer::normalize('VETERINARIYA'))->toBe('veterinariya');
});

it('flattens punctuation to single spaces so it cannot glue words together', function () {
    expect(SearchNormalizer::normalize('Мўғулистон — тарих!'))->toBe('moguliston tarix')
        ->and(SearchNormalizer::normalize('  Kitob,   nomi.  '))->toBe('kitob nomi');
});

it('strips LIKE wildcards, so a query cannot smuggle them into a pattern', function () {
    expect(SearchNormalizer::normalize('100%'))->toBe('100')
        ->and(SearchNormalizer::normalize('a_b'))->toBe('a b')
        ->and(SearchNormalizer::normalize('%%%'))->toBe('');
});

it('skips null and empty parts when normalizing several fields at once', function () {
    expect(SearchNormalizer::normalizeAll(['Ветеринария', null, '', 'А. Ўлмасов']))
        ->toBe('veterinariya a olmasov');
});

it('returns words at or above the requested minimum length', function () {
    expect(SearchNormalizer::words('Умумий ветеринария асослари', 3))
        ->toBe(['umumiy', 'veterinariya', 'asoslari'])
        ->and(SearchNormalizer::words('A. Oʻlmasov', 3))->toBe(['olmasov']);
});
