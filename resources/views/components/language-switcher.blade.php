@php $supported = config('locale.supported'); $current = app()->getLocale(); @endphp

<div x-data="{ open: false }" @click.outside="open = false" class="relative">
    <button @click="open = !open"
        class="flex h-11 items-center gap-2 rounded-full border border-gray-200 bg-white px-3 text-sm font-medium text-gray-600 transition hover:bg-gray-100 hover:text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white">
        <svg class="fill-current" width="18" height="18" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path fill-rule="evenodd" clip-rule="evenodd" d="M10 2.5C5.85786 2.5 2.5 5.85786 2.5 10C2.5 14.1421 5.85786 17.5 10 17.5C14.1421 17.5 17.5 14.1421 17.5 10C17.5 5.85786 14.1421 2.5 10 2.5ZM1 10C1 5.02944 5.02944 1 10 1C14.9706 1 19 5.02944 19 10C19 14.9706 14.9706 19 10 19C5.02944 19 1 14.9706 1 10ZM10 2.5C9.8 2.5 9.4 2.7 8.98 3.55C8.62 4.29 8.34 5.35 8.19 6.6H11.81C11.66 5.35 11.38 4.29 11.02 3.55C10.6 2.7 10.2 2.5 10 2.5ZM13.32 6.6C13.15 5.11 12.8 3.78 12.3 2.83C13.88 3.35 15.22 4.4 16.12 5.78C15.7 6.11 15.16 6.38 14.53 6.6H13.32ZM14.9 8.1C14.96 8.72 15 9.35 15 10C15 10.65 14.96 11.28 14.9 11.9H16.9C17.1 11.28 17.2 10.65 17.2 10C17.2 9.35 17.1 8.72 16.9 8.1H14.9ZM13.39 8.1H6.61C6.54 8.71 6.5 9.35 6.5 10C6.5 10.65 6.54 11.29 6.61 11.9H13.39C13.46 11.29 13.5 10.65 13.5 10C13.5 9.35 13.46 8.71 13.39 8.1ZM5.1 11.9C5.04 11.28 5 10.65 5 10C5 9.35 5.04 8.72 5.1 8.1H3.1C2.9 8.72 2.8 9.35 2.8 10C2.8 10.65 2.9 11.28 3.1 11.9H5.1ZM3.88 13.4C4.3 13.73 4.84 14 5.47 14.22H6.68C6.85 14.89 7.2 16.22 7.7 17.17C6.12 16.65 4.78 15.6 3.88 14.22V13.4ZM8.19 13.4C8.34 14.65 8.62 15.71 8.98 16.45C9.4 17.3 9.8 17.5 10 17.5C10.2 17.5 10.6 17.3 11.02 16.45C11.38 15.71 11.66 14.65 11.81 13.4H8.19ZM12.3 17.17C12.8 16.22 13.15 14.89 13.32 13.4H14.53C15.16 14 15.7 14 16.12 14.22C15.22 15.6 13.88 16.65 12.3 17.17ZM5.47 6.6H6.68C6.85 5.11 7.2 3.78 7.7 2.83C6.12 3.35 4.78 4.4 3.88 5.78C4.3 6.11 4.84 6.38 5.47 6.6Z" fill=""/>
        </svg>
        <span class="hidden sm:inline">{{ $supported[$current] ?? strtoupper($current) }}</span>
        <span class="text-gray-400">&#9662;</span>
    </button>

    <div x-show="open" x-cloak
        class="absolute right-0 mt-2 w-44 overflow-hidden rounded-lg border border-gray-200 bg-white py-1 shadow-lg dark:border-gray-800 dark:bg-gray-900">
        @foreach ($supported as $code => $label)
            <a href="{{ route('locale.switch', $code) }}"
               class="flex items-center justify-between px-4 py-2 text-sm hover:bg-gray-50 dark:hover:bg-white/5 {{ $current === $code ? 'font-medium text-brand-500' : 'text-gray-700 dark:text-gray-300' }}">
                {{ $label }}
                @if ($current === $code)<span>&#10003;</span>@endif
            </a>
        @endforeach
    </div>
</div>
