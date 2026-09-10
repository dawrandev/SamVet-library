@props(['active' => 'all'])

{{--
    Shared by /jurnallar and /maqolalar.

    An article is part of a periodical in the data model too —
    Journal → JournalIssue → Article — so to a reader these are one section
    even though they are separate routes. The split exists because an article
    published in a journal the library does not hold has no issue to sit under:
    it carries the journal's name as free text instead, and without a tab here
    there is no path to it from the periodicals page at all.
--}}
@php
    $tabs = [
        ['key' => 'all', 'label' => __('Barchasi'), 'url' => route('periodicals.index')],
        ['key' => \App\Enums\PublicationKind::Journal->value, 'label' => __('Jurnallar'), 'url' => route('periodicals.index', ['kind' => \App\Enums\PublicationKind::Journal->value])],
        ['key' => \App\Enums\PublicationKind::Newspaper->value, 'label' => __('Gazetalar'), 'url' => route('periodicals.index', ['kind' => \App\Enums\PublicationKind::Newspaper->value])],
        ['key' => 'articles', 'label' => __('Maqolalar'), 'url' => route('articles.index')],
    ];
@endphp

<div class="mt-6 flex flex-wrap gap-2">
    @foreach ($tabs as $tab)
        <a href="{{ $tab['url'] }}"
           @class([
               'rounded-full px-4 py-2 text-sm font-medium transition',
               'bg-blue-700 text-white' => $active === $tab['key'],
               'bg-white text-gray-600 ring-1 ring-gray-200 hover:bg-gray-50' => $active !== $tab['key'],
           ])>{{ $tab['label'] }}</a>
    @endforeach
</div>
