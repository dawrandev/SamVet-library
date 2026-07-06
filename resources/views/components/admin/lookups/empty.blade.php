@props([
    'colspan' => 2,
    'message' => null,
])

<tr>
    <td colspan="{{ $colspan }}" class="px-5 py-12 text-center">
        <x-admin.icon name="folder" class="mx-auto h-10 w-10 text-gray-300 dark:text-gray-600" />
        <p class="mt-2 text-theme-sm text-gray-500 dark:text-gray-400">{{ $message ?? __('Ma’lumot topilmadi.') }}</p>
    </td>
</tr>
