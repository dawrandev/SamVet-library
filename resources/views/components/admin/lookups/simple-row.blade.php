@props([
    'row',   // ['id','name','update_url','destroy_url']
])

<tr class="border-b border-gray-100 last:border-0 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-white/[0.02]">
    <td class="px-5 py-4">
        <p class="text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $row['name'] ?: '—' }}</p>
    </td>
    <td class="px-5 py-4">
        <div class="flex items-center justify-end gap-2">
            <button type="button" @click='openEdit(@json($row))'
                    class="text-theme-xs rounded-lg border border-gray-200 px-3 py-1.5 font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-400 dark:hover:bg-white/5">{{ __('Tahrirlash') }}</button>
            <button type="button"
                    @click="$store.confirm.ask('{{ $row['destroy_url'] }}', '{{ __('O‘chirishni tasdiqlaysizmi?') }}')"
                    class="text-theme-xs rounded-lg border border-red-200 px-3 py-1.5 font-medium text-red-600 hover:bg-red-50 dark:border-red-500/30 dark:hover:bg-red-500/10">{{ __('O‘chirish') }}</button>
        </div>
    </td>
</tr>
