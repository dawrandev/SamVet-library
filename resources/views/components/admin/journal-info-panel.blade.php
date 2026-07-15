@props(['journal', 'editUrl' => null])

@php
    $isNewspaper = $journal->kind === \App\Enums\PublicationKind::Newspaper;

    $periodicity = match (true) {
        $journal->periodicity && $journal->periodicity_count => "{$journal->periodicity_count} marta / {$journal->periodicity->label()}",
        (bool) $journal->periodicity => $journal->periodicity->label(),
        default => null,
    };

    $details = array_filter([
        ($isNewspaper ? __('Gazeta nomi') : __('Jurnal nomi')) => $journal->name,
        ($isNewspaper ? __('Gazeta turi') : __('Jurnal turi')) => $isNewspaper ? $journal->newspaper_type?->label() : $journal->type?->name,
        __('Indeks') => $journal->index,
        __('Nashr joyi') => $journal->publicationPlace?->name,
        __('Nashriyoti') => $journal->publisher,
        __('Davriyligi') => $periodicity,
        __('Tili') => $journal->language?->name,
        __('ISSN') => $journal->issn,
        __('Muassislar') => $journal->founder,
    ], fn ($v) => filled($v));
@endphp

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]']) }}>
    <div class="mb-4 flex items-center justify-between">
        <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">
            {{ $isNewspaper ? __('Gazeta haqida ma’lumot') : __('Jurnal haqida ma’lumot') }}
        </h3>
        @if ($editUrl)
            <a href="{{ $editUrl }}" class="text-theme-xs text-brand-600 hover:underline dark:text-brand-400">{{ __('Tahrirlash') }}</a>
        @endif
    </div>
    <dl class="space-y-3">
        @forelse ($details as $label => $value)
            <div class="flex justify-between gap-4 border-b border-gray-50 pb-2 dark:border-gray-800/50">
                <dt class="text-theme-sm text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                <dd class="text-theme-sm text-right font-medium text-gray-800 dark:text-white/90">{{ $value }}</dd>
            </div>
        @empty
            <p class="text-theme-sm text-gray-400">{{ __('Ma’lumot yo‘q') }}</p>
        @endforelse
    </dl>
</div>
