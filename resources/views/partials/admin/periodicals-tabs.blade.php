{{--
    Shared tab nav for the two periodical-related index pages (journals,
    articles) — lets the librarian jump between them without needing separate
    sidebar links. Expects $activeTab = 'journals'|'articles' from the caller.
--}}
@php
    $activeTab ??= 'journals';
@endphp
<div class="mb-5 flex gap-2 border-b border-gray-200 dark:border-gray-800">
    <a href="{{ route('admin.journals.index') }}"
       class="border-b-2 px-1 pb-3 text-sm font-medium {{ $activeTab === 'journals' ? 'border-brand-500 text-brand-600 dark:text-brand-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' }}">
        {{ __('Davriy nashrlar') }}
    </a>
    <a href="{{ route('admin.articles.index') }}"
       class="border-b-2 px-1 pb-3 text-sm font-medium {{ $activeTab === 'articles' ? 'border-brand-500 text-brand-600 dark:text-brand-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' }}">
        {{ __('Maqolalar') }}
    </a>
</div>
