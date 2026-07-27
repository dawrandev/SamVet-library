@props([
    'row',          // ['id','uz','ru','kk','incomplete','update_url','destroy_url', ...]
    'hasParent' => false,
    'sortable' => false,   // expects 'move_up_url'/'move_down_url' (null at a sibling-group boundary) in $row
])

<tr class="border-b border-gray-100 last:border-0 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-white/[0.02]">
    {{-- Name (uz — primary) --}}
    <td class="px-5 py-4">
        <p class="text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $row['uz'] ?: '—' }}</p>
    </td>

    {{-- Parent category (only for category) --}}
    @if ($hasParent)
        <td class="px-5 py-4 text-theme-sm text-gray-600 dark:text-gray-400">{{ $row['parent'] ?? '—' }}</td>
    @endif

    {{-- Translation status --}}
    <td class="px-5 py-4">
        @if ($row['incomplete'])
            <span class="text-theme-xs inline-flex items-center gap-1 rounded-full bg-warning-50 px-2.5 py-0.5 font-medium text-warning-600 dark:bg-warning-500/15 dark:text-warning-500">
                ⚠️ {{ __('Tarjima to‘liq emas') }}
            </span>
        @else
            <span class="text-theme-xs inline-flex rounded-full bg-success-50 px-2.5 py-0.5 font-medium text-success-600 dark:bg-success-500/15 dark:text-success-500">
                {{ __('To‘liq') }}
            </span>
        @endif
    </td>

    {{-- Actions --}}
    <td class="px-5 py-4">
        <div class="flex items-center justify-end gap-2">
            @if ($sortable)
                <form method="POST" action="{{ $row['move_up_url'] ?? '#' }}">
                    @csrf @method('PATCH')
                    <button type="submit" {{ $row['move_up_url'] ? '' : 'disabled' }}
                            title="{{ __('Yuqoriga') }}"
                            class="flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-30 dark:border-gray-800 dark:text-gray-400 dark:hover:bg-white/5">↑</button>
                </form>
                <form method="POST" action="{{ $row['move_down_url'] ?? '#' }}">
                    @csrf @method('PATCH')
                    <button type="submit" {{ $row['move_down_url'] ? '' : 'disabled' }}
                            title="{{ __('Pastga') }}"
                            class="flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-30 dark:border-gray-800 dark:text-gray-400 dark:hover:bg-white/5">↓</button>
                </form>
            @endif
            <button type="button" @click='openEdit(@json($row))'
                    class="text-theme-xs rounded-lg border border-gray-200 px-3 py-1.5 font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-400 dark:hover:bg-white/5">{{ __('Tahrirlash') }}</button>
            <button type="button"
                    @click="$store.confirm.ask('{{ $row['destroy_url'] }}', '{{ __('O‘chirishni tasdiqlaysizmi?') }}')"
                    class="text-theme-xs rounded-lg border border-red-200 px-3 py-1.5 font-medium text-red-600 hover:bg-red-50 dark:border-red-500/30 dark:hover:bg-red-500/10">{{ __('O‘chirish') }}</button>
        </div>
    </td>
</tr>
