@props([
    'title' => null,          // Modal title ("Book type")
    'parents' => null,        // Parent select for category (Collection|null)
])

{{-- Modal (inside Alpine `lookupTable` state) --}}
<div x-show="open" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4"
     x-init="@if ($errors->any()) open = true; @endif">
    {{-- Backdrop --}}
    <div class="fixed inset-0 bg-gray-900/50" @click="close()"></div>

    {{-- Window --}}
    <div class="relative w-full max-w-lg rounded-2xl border border-gray-200 bg-white p-6 shadow-xl dark:border-gray-800 dark:bg-gray-900">
        <div class="mb-5 flex items-center justify-between">
            <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">
                <span x-show="mode === 'create'">{{ __('Yangi qo‘shish') }}: {{ $title }}</span>
                <span x-show="mode === 'edit'" x-cloak>{{ __('Tahrirlash') }}: {{ $title }}</span>
            </h3>
            <button type="button" @click="close()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">&times;</button>
        </div>

        <form method="POST" :action="action" class="space-y-4">
            @csrf
            <template x-if="mode === 'edit'">
                <input type="hidden" name="_method" value="PUT" />
            </template>

            @isset($parents)
                {{-- Parent category (hierarchy) --}}
                <div>
                    <label for="parent_id" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Ota kategoriya') }}</label>
                    <select name="parent_id" id="parent_id" x-model="form.parent_id"
                            class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">{{ __('Yo‘q (yuqori daraja)') }}</option>
                        @foreach ($parents as $parent)
                            <option value="{{ $parent->id }}" x-bind:disabled="String(editingId) === '{{ $parent->id }}'">
                                {{ $parent->parent ? $parent->parent->name . ' › ' . $parent->name : $parent->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('parent_id')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
                </div>
            @endisset

            {{-- 3 languages (all required) --}}
            <div>
                <label for="name_uz" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                    {{ __('Nomi (o‘zbekcha)') }}<span class="text-error-500">*</span>
                </label>
                <input type="text" name="name[uz]" id="name_uz" x-model="form.uz" required
                       class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:bg-gray-900 dark:text-white/90 @error('name.uz') border-error-500 @else border-gray-300 dark:border-gray-700 @enderror" />
                @error('name.uz')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="name_ru" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                    {{ __('Nomi (ruscha)') }}<span class="text-error-500">*</span>
                </label>
                <input type="text" name="name[ru]" id="name_ru" x-model="form.ru" required
                       class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:bg-gray-900 dark:text-white/90 @error('name.ru') border-error-500 @else border-gray-300 dark:border-gray-700 @enderror" />
                @error('name.ru')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="name_kk" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                    {{ __('Nomi (qoraqalpoqcha)') }}<span class="text-error-500">*</span>
                </label>
                <input type="text" name="name[kk]" id="name_kk" x-model="form.kk" required
                       class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:bg-gray-900 dark:text-white/90 @error('name.kk') border-error-500 @else border-gray-300 dark:border-gray-700 @enderror" />
                @error('name.kk')<p class="mt-1 text-theme-xs text-error-500">{{ $message }}</p>@enderror
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <button type="button" @click="close()"
                        class="h-11 rounded-lg border border-gray-200 px-5 text-sm font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-400 dark:hover:bg-white/5">{{ __('Bekor qilish') }}</button>
                <button type="submit"
                        class="bg-brand-500 hover:bg-brand-600 h-11 rounded-lg px-5 text-sm font-medium text-white transition">{{ __('Saqlash') }}</button>
            </div>
        </form>
    </div>
</div>
