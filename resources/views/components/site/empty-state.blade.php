@props(['title', 'description' => null])

{{--
    The "nothing here yet" card, shared by every public listing.

    Deliberately tall: as a p-12 box it sat as a thin strip under the heading
    with the rest of the viewport empty below it, which looked like the page had
    failed to load rather than like a section with nothing in it. Giving it real
    height — together with the layout's own flex-1 main — makes an empty page
    read as a deliberate state.

    Slots: `icon` for an illustration above the text, the default slot for
    actions underneath (the catalog uses it for "clear filters").
--}}
<div {{ $attributes->merge(['class' => 'mt-8 flex min-h-[45vh] flex-col items-center justify-center rounded-2xl border border-dashed border-gray-300 bg-white p-12 text-center']) }}>
    @isset($icon)
        <div class="mb-4">{{ $icon }}</div>
    @endisset

    <p class="text-base font-semibold text-gray-900">{{ $title }}</p>

    @if ($description)
        <p class="mt-1.5 max-w-md text-sm text-gray-500">{{ $description }}</p>
    @endif

    {{ $slot }}
</div>
