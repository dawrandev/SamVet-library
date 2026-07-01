@props([
    'name',
    'label' => null,
    'checked' => false,
    'help' => null,
])

@php $isOn = (bool) old($name, $checked); @endphp

<div x-data="{ on: {{ $isOn ? 'true' : 'false' }} }">
    <div class="flex items-center gap-3">
        <button type="button" role="switch" :aria-checked="on ? 'true' : 'false'" @click="on = !on"
            :class="on ? 'bg-brand-500' : 'bg-gray-300 dark:bg-gray-700'"
            class="relative h-6 w-11 flex-shrink-0 rounded-full transition-colors">
            <span :class="on ? 'translate-x-5' : 'translate-x-0.5'"
                  class="absolute top-0.5 left-0 h-5 w-5 rounded-full bg-white shadow transition-transform"></span>
        </button>

        <input type="hidden" name="{{ $name }}" :value="on ? 1 : 0" />

        @if ($label)
            <span class="cursor-pointer text-sm font-medium text-gray-700 select-none dark:text-gray-300" @click="on = !on">{{ $label }}</span>
        @endif
    </div>

    @if ($help)
        <p class="mt-1 text-theme-xs text-gray-400">{{ $help }}</p>
    @endif
</div>
