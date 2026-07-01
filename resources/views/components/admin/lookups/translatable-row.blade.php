@props([
    'row',          // ['id','uz','ru','kk','incomplete','update_url','destroy_url', ...]
    'hasParent' => false,
])

<tr class="border-b border-gray-100 last:border-0 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-white/[0.02]">
    {{-- Nomi (uz — asosiy) --}}
    <td class="px-5 py-4">
        <p class="text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $row['uz'] ?: '—' }}</p>
    </td>

    {{-- Ota kategoriya (faqat kategoriyada) --}}
    @if ($hasParent)
        <td class="px-5 py-4 text-theme-sm text-gray-600 dark:text-gray-400">{{ $row['parent'] ?? '—' }}</td>
    @endif

    {{-- Tarjima holati --}}
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

    {{-- Amallar --}}
    <td class="px-5 py-4">
        <div class="flex items-center justify-end gap-2">
            <button type="button" @click='openEdit(@json($row))'
                    class="text-theme-xs rounded-lg border border-gray-200 px-3 py-1.5 font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-400 dark:hover:bg-white/5">{{ __('Tahrirlash') }}</button>
            <form method="POST" action="{{ $row['destroy_url'] }}"
                  onsubmit="return confirm('{{ __('O‘chirishni tasdiqlaysizmi?') }}')">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-theme-xs rounded-lg border border-red-200 px-3 py-1.5 font-medium text-red-600 hover:bg-red-50 dark:border-red-500/30 dark:hover:bg-red-500/10">{{ __('O‘chirish') }}</button>
            </form>
        </div>
    </td>
</tr>
