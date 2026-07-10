<aside
    :class="sidebarToggle ? 'translate-x-0 lg:w-[90px]' : '-translate-x-full'"
    class="sidebar fixed left-0 top-0 z-9999 flex h-screen w-[290px] flex-col overflow-y-hidden border-r border-gray-200 bg-white px-5 dark:border-gray-800 dark:bg-black lg:static lg:translate-x-0"
>
    {{-- Logo --}}
    <div
        :class="sidebarToggle ? 'justify-center' : 'justify-between'"
        class="sidebar-header flex items-center gap-2 pt-8 pb-7"
    >
        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2">
            <img src="{{ asset('images/samvet/logo.png') }}" alt="{{ __('SamVMChBTU logotipi') }}"
                 class="h-9 w-9 shrink-0 object-contain" width="36" height="36" />
            <span class="text-lg font-bold text-gray-900 dark:text-white" :class="sidebarToggle ? 'lg:hidden' : ''">
                {{ config('app.name') }}
            </span>
        </a>
    </div>

    <div class="flex flex-col overflow-y-auto no-scrollbar duration-300 ease-linear">
        <nav>
            <div>
                <h3 class="mb-4 text-xs uppercase leading-[20px] text-gray-400">
                    <span class="menu-group-title" :class="sidebarToggle ? 'lg:hidden' : ''">{{ __('Menyu') }}</span>
                </h3>

                <ul class="mb-6 flex flex-col gap-2">
                    {{-- Bosh sahifa --}}
                    <li>
                        <a href="{{ route('admin.dashboard') }}"
                           class="menu-item group {{ request()->routeIs('admin.dashboard') ? 'menu-item-active' : 'menu-item-inactive' }}">
                            <svg class="{{ request()->routeIs('admin.dashboard') ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}"
                                 width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M5.5 3.25C4.25736 3.25 3.25 4.25736 3.25 5.5V8.99998C3.25 10.2426 4.25736 11.25 5.5 11.25H9C10.2426 11.25 11.25 10.2426 11.25 8.99998V5.5C11.25 4.25736 10.2426 3.25 9 3.25H5.5ZM4.75 5.5C4.75 5.08579 5.08579 4.75 5.5 4.75H9C9.41421 4.75 9.75 5.08579 9.75 5.5V8.99998C9.75 9.41419 9.41421 9.74998 9 9.74998H5.5C5.08579 9.74998 4.75 9.41419 4.75 8.99998V5.5ZM5.5 12.75C4.25736 12.75 3.25 13.7574 3.25 15V18.5C3.25 19.7426 4.25736 20.75 5.5 20.75H9C10.2426 20.75 11.25 19.7427 11.25 18.5V15C11.25 13.7574 10.2426 12.75 9 12.75H5.5ZM4.75 15C4.75 14.5858 5.08579 14.25 5.5 14.25H9C9.41421 14.25 9.75 14.5858 9.75 15V18.5C9.75 18.9142 9.41421 19.25 9 19.25H5.5C5.08579 19.25 4.75 18.9142 4.75 18.5V15ZM12.75 5.5C12.75 4.25736 13.7574 3.25 15 3.25H18.5C19.7426 3.25 20.75 4.25736 20.75 5.5V8.99998C20.75 10.2426 19.7426 11.25 18.5 11.25H15C13.7574 11.25 12.75 10.2426 12.75 8.99998V5.5ZM15 4.75C14.5858 4.75 14.25 5.08579 14.25 5.5V8.99998C14.25 9.41419 14.5858 9.74998 15 9.74998H18.5C18.9142 9.74998 19.25 9.41419 19.25 8.99998V5.5C19.25 5.08579 18.9142 4.75 18.5 4.75H15ZM15 12.75C13.7574 12.75 12.75 13.7574 12.75 15V18.5C12.75 19.7426 13.7574 20.75 15 20.75H18.5C19.7426 20.75 20.75 19.7427 20.75 18.5V15C20.75 13.7574 19.7426 12.75 18.5 12.75H15ZM14.25 15C14.25 14.5858 14.5858 14.25 15 14.25H18.5C18.9142 14.25 19.25 14.5858 19.25 15V18.5C19.25 18.9142 18.9142 19.25 18.5 19.25H15C14.5858 19.25 14.25 18.9142 14.25 18.5V15Z" fill="" />
                            </svg>
                            <span class="menu-item-text" :class="sidebarToggle ? 'lg:hidden' : ''">{{ __('Bosh sahifa') }}</span>
                        </a>
                    </li>

                    {{-- Kitoblar --}}
                    <li>
                        <a href="{{ route('admin.books.index') }}"
                           class="menu-item group {{ request()->routeIs('admin.books.*') ? 'menu-item-active' : 'menu-item-inactive' }}">
                            <svg class="{{ request()->routeIs('admin.books.*') ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M8.50391 4.25C8.50391 3.83579 8.83969 3.5 9.25391 3.5H15.2777C15.4766 3.5 15.6674 3.57902 15.8081 3.71967L18.2807 6.19234C18.4214 6.333 18.5004 6.52376 18.5004 6.72268V16.75C18.5004 17.1642 18.1646 17.5 17.7504 17.5H16.248V17.4993H14.748V17.5H9.25391C8.83969 17.5 8.50391 17.1642 8.50391 16.75V4.25ZM14.748 19H9.25391C8.01126 19 7.00391 17.9926 7.00391 16.75V6.49854H6.24805C5.83383 6.49854 5.49805 6.83432 5.49805 7.24854V19.75C5.49805 20.1642 5.83383 20.5 6.24805 20.5H13.998C14.4123 20.5 14.748 20.1642 14.748 19.75L14.748 19ZM7.00391 4.99854V4.25C7.00391 3.00736 8.01127 2 9.25391 2H15.2777C15.8745 2 16.4468 2.23705 16.8687 2.659L19.3414 5.13168C19.7634 5.55364 20.0004 6.12594 20.0004 6.72268V16.75C20.0004 17.9926 18.9931 19 17.7504 19H16.248L16.248 19.75C16.248 20.9926 15.2407 22 13.998 22H6.24805C5.00541 22 3.99805 20.9926 3.99805 19.75V7.24854C3.99805 6.00589 5.00541 4.99854 6.24805 4.99854H7.00391Z" fill="" />
                            </svg>
                            <span class="menu-item-text" :class="sidebarToggle ? 'lg:hidden' : ''">{{ __('Kitoblar') }}</span>
                        </a>
                    </li>

                    {{-- Jurnallar --}}
                    <li>
                        <a href="{{ route('admin.journals.index') }}"
                           class="menu-item group {{ request()->routeIs('admin.journals.*') ? 'menu-item-active' : 'menu-item-inactive' }}">
                            <svg class="{{ request()->routeIs('admin.journals.*') ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M5.5 3.25C4.25736 3.25 3.25 4.25736 3.25 5.5V18.5C3.25 19.7426 4.25736 20.75 5.5 20.75H18.5C19.7426 20.75 20.75 19.7426 20.75 18.5V5.5C20.75 4.25736 19.7426 3.25 18.5 3.25H5.5ZM4.75 5.5C4.75 5.08579 5.08579 4.75 5.5 4.75H18.5C18.9142 4.75 19.25 5.08579 19.25 5.5V18.5C19.25 18.9142 18.9142 19.25 18.5 19.25H5.5C5.08579 19.25 4.75 18.9142 4.75 18.5V5.5ZM7 7.75C6.58579 7.75 6.25 8.08579 6.25 8.5C6.25 8.91421 6.58579 9.25 7 9.25H17C17.4142 9.25 17.75 8.91421 17.75 8.5C17.75 8.08579 17.4142 7.75 17 7.75H7ZM6.25 12C6.25 11.5858 6.58579 11.25 7 11.25H17C17.4142 11.25 17.75 11.5858 17.75 12C17.75 12.4142 17.4142 12.75 17 12.75H7C6.58579 12.75 6.25 12.4142 6.25 12ZM7 14.75C6.58579 14.75 6.25 15.0858 6.25 15.5C6.25 15.9142 6.58579 16.25 7 16.25H13C13.4142 16.25 13.75 15.9142 13.75 15.5C13.75 15.0858 13.4142 14.75 13 14.75H7Z" fill="" />
                            </svg>
                            <span class="menu-item-text" :class="sidebarToggle ? 'lg:hidden' : ''">{{ __('Jurnallar') }}</span>
                        </a>
                    </li>

                    {{-- Maqolalar --}}
                    <li>
                        <a href="{{ route('admin.articles.index') }}"
                           class="menu-item group {{ request()->routeIs('admin.articles.*') ? 'menu-item-active' : 'menu-item-inactive' }}">
                            <svg class="{{ request()->routeIs('admin.articles.*') ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M5.5 3.25C4.25736 3.25 3.25 4.25736 3.25 5.5V18.5C3.25 19.7426 4.25736 20.75 5.5 20.75H18.5C19.7426 20.75 20.75 19.7426 20.75 18.5V5.5C20.75 4.25736 19.7426 3.25 18.5 3.25H5.5ZM4.75 5.5C4.75 5.08579 5.08579 4.75 5.5 4.75H18.5C18.9142 4.75 19.25 5.08579 19.25 5.5V18.5C19.25 18.9142 18.9142 19.25 18.5 19.25H5.5C5.08579 19.25 4.75 18.9142 4.75 18.5V5.5ZM7.75 8.5C7.33579 8.5 7 8.83579 7 9.25C7 9.66421 7.33579 10 7.75 10H16.25C16.6642 10 17 9.66421 17 9.25C17 8.83579 16.6642 8.5 16.25 8.5H7.75ZM7 13C7 12.5858 7.33579 12.25 7.75 12.25H16.25C16.6642 12.25 17 12.5858 17 13C17 13.4142 16.6642 13.75 16.25 13.75H7.75C7.33579 13.75 7 13.4142 7 13ZM7.75 16C7.33579 16 7 16.3358 7 16.75C7 17.1642 7.33579 17.5 7.75 17.5H12.25C12.6642 17.5 13 17.1642 13 16.75C13 16.3358 12.6642 16 12.25 16H7.75Z" fill="" />
                            </svg>
                            <span class="menu-item-text" :class="sidebarToggle ? 'lg:hidden' : ''">{{ __('Maqolalar') }}</span>
                        </a>
                    </li>

                    {{-- Yangiliklar --}}
                    <li>
                        <a href="{{ route('admin.news.index') }}"
                           class="menu-item group {{ request()->routeIs('admin.news.*') ? 'menu-item-active' : 'menu-item-inactive' }}">
                            <svg class="{{ request()->routeIs('admin.news.*') ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M4 5.5C4 4.53 4.78 3.75 5.75 3.75H15.25C16.22 3.75 17 4.53 17 5.5V18C17 18.69 17.56 19.25 18.25 19.25C18.94 19.25 19.5 18.69 19.5 18V8.5C19.5 8.09 19.84 7.75 20.25 7.75C20.66 7.75 21 8.09 21 8.5V18C21 19.52 19.77 20.75 18.25 20.75H6C4.9 20.75 4 19.85 4 18.75V5.5ZM5.5 5.5V18.75C5.5 19.02 5.73 19.25 6 19.25H15.7C15.57 18.86 15.5 18.44 15.5 18V5.5C15.5 5.36 15.39 5.25 15.25 5.25H5.75C5.61 5.25 5.5 5.36 5.5 5.5ZM7 8C7 7.59 7.34 7.25 7.75 7.25H13.25C13.66 7.25 14 7.59 14 8C14 8.41 13.66 8.75 13.25 8.75H7.75C7.34 8.75 7 8.41 7 8ZM7.75 10.75C7.34 10.75 7 11.09 7 11.5C7 11.91 7.34 12.25 7.75 12.25H13.25C13.66 12.25 14 11.91 14 11.5C14 11.09 13.66 10.75 13.25 10.75H7.75ZM7 15C7 14.59 7.34 14.25 7.75 14.25H11.25C11.66 14.25 12 14.59 12 15C12 15.41 11.66 15.75 11.25 15.75H7.75C7.34 15.75 7 15.41 7 15Z" fill="" />
                            </svg>
                            <span class="menu-item-text" :class="sidebarToggle ? 'lg:hidden' : ''">{{ __('Yangiliklar') }}</span>
                        </a>
                    </li>

                    {{-- Foydalanuvchilar (kutubxona a'zolari) --}}
                    <li>
                        <a href="{{ route('admin.readers.index') }}"
                           class="menu-item group {{ request()->routeIs('admin.readers.*') ? 'menu-item-active' : 'menu-item-inactive' }}">
                            <svg class="{{ request()->routeIs('admin.readers.*') ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M12 4.5C10.4812 4.5 9.25 5.73122 9.25 7.25C9.25 8.76878 10.4812 10 12 10C13.5188 10 14.75 8.76878 14.75 7.25C14.75 5.73122 13.5188 4.5 12 4.5ZM7.75 7.25C7.75 4.90279 9.65279 3 12 3C14.3472 3 16.25 4.90279 16.25 7.25C16.25 9.59721 14.3472 11.5 12 11.5C9.65279 11.5 7.75 9.59721 7.75 7.25ZM7.5 15.25C6.5335 15.25 5.75 16.0335 5.75 17V19.25C5.75 19.6642 5.41421 20 5 20C4.58579 20 4.25 19.6642 4.25 19.25V17C4.25 15.2051 5.70508 13.75 7.5 13.75H16.5C18.2949 13.75 19.75 15.2051 19.75 17V19.25C19.75 19.6642 19.4142 20 19 20C18.5858 20 18.25 19.6642 18.25 19.25V17C18.25 16.0335 17.4665 15.25 16.5 15.25H7.5Z" fill="" />
                            </svg>
                            <span class="menu-item-text" :class="sidebarToggle ? 'lg:hidden' : ''">{{ __('Foydalanuvchilar') }}</span>
                        </a>
                    </li>

                    {{-- Berilgan kitoblar (muddat nazorati) --}}
                    <li>
                        <a href="{{ route('admin.loans.index') }}"
                           class="menu-item group {{ request()->routeIs('admin.loans.*') ? 'menu-item-active' : 'menu-item-inactive' }}">
                            <svg class="{{ request()->routeIs('admin.loans.*') ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M12 3.25C7.16751 3.25 3.25 7.16751 3.25 12C3.25 16.8325 7.16751 20.75 12 20.75C16.8325 20.75 20.75 16.8325 20.75 12C20.75 7.16751 16.8325 3.25 12 3.25ZM4.75 12C4.75 7.99594 7.99594 4.75 12 4.75C16.0041 4.75 19.25 7.99594 19.25 12C19.25 16.0041 16.0041 19.25 12 19.25C7.99594 19.25 4.75 16.0041 4.75 12ZM12.75 7.5C12.75 7.08579 12.4142 6.75 12 6.75C11.5858 6.75 11.25 7.08579 11.25 7.5V12C11.25 12.2508 11.3753 12.485 11.5839 12.6241L14.5839 14.6241C14.9285 14.8539 15.3941 14.7607 15.6239 14.4161C15.8537 14.0715 15.7605 13.6059 15.4159 13.3761L12.75 11.5986V7.5Z" fill="" />
                            </svg>
                            <span class="menu-item-text" :class="sidebarToggle ? 'lg:hidden' : ''">{{ __('Berilgan kitoblar') }}</span>
                            @if (($overdueLoansCount ?? 0) > 0)
                                <span class="ml-auto flex h-5 min-w-5 items-center justify-center rounded-full bg-error-500 px-1 text-[10px] font-semibold text-white"
                                      :class="sidebarToggle ? 'lg:hidden' : ''">
                                    {{ $overdueLoansCount > 99 ? '99+' : $overdueLoansCount }}
                                </span>
                            @endif
                        </a>
                    </li>

                    {{-- Obunalar (nashrlarga obuna) --}}
                    <li>
                        <a href="{{ route('admin.subscriptions.index') }}"
                           class="menu-item group {{ request()->routeIs('admin.subscriptions.*') ? 'menu-item-active' : 'menu-item-inactive' }}">
                            <svg class="{{ request()->routeIs('admin.subscriptions.*') ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M6.25 4C5.00736 4 4 5.00736 4 6.25V17.75C4 18.9926 5.00736 20 6.25 20H17.75C18.9926 20 20 18.9926 20 17.75V6.25C20 5.00736 18.9926 4 17.75 4H6.25ZM5.5 6.25C5.5 5.83579 5.83579 5.5 6.25 5.5H17.75C18.1642 5.5 18.5 5.83579 18.5 6.25V17.75C18.5 18.1642 18.1642 18.5 17.75 18.5H6.25C5.83579 18.5 5.5 18.1642 5.5 17.75V6.25ZM8 8.75C7.58579 8.75 7.25 9.08579 7.25 9.5C7.25 9.91421 7.58579 10.25 8 10.25H16C16.4142 10.25 16.75 9.91421 16.75 9.5C16.75 9.08579 16.4142 8.75 16 8.75H8ZM7.25 13C7.25 12.5858 7.58579 12.25 8 12.25H16C16.4142 12.25 16.75 12.5858 16.75 13C16.75 13.4142 16.4142 13.75 16 13.75H8C7.58579 13.75 7.25 13.4142 7.25 13ZM8 15.75C7.58579 15.75 7.25 16.0858 7.25 16.5C7.25 16.9142 7.58579 17.25 8 17.25H12C12.4142 17.25 12.75 16.9142 12.75 16.5C12.75 16.0858 12.4142 15.75 12 15.75H8Z" fill="" />
                            </svg>
                            <span class="menu-item-text" :class="sidebarToggle ? 'lg:hidden' : ''">{{ __('Obunalar') }}</span>
                        </a>
                    </li>

                    {{-- Kompyuterlar (elektron o'qish zali inventari) --}}
                    <li>
                        <a href="{{ route('admin.computers.index') }}"
                           class="menu-item group {{ request()->routeIs('admin.computers.*') ? 'menu-item-active' : 'menu-item-inactive' }}">
                            <svg class="{{ request()->routeIs('admin.computers.*') ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M2.25 5.25a3 3 0 0 1 3-3h13.5a3 3 0 0 1 3 3V15a3 3 0 0 1-3 3h-3v.257c0 .597.237 1.17.659 1.591l.621.622a.75.75 0 0 1-.53 1.28h-9a.75.75 0 0 1-.53-1.28l.621-.622a2.25 2.25 0 0 0 .659-1.59V18h-3a3 3 0 0 1-3-3V5.25Zm1.5 0v7.5a1.5 1.5 0 0 0 1.5 1.5h13.5a1.5 1.5 0 0 0 1.5-1.5v-7.5a1.5 1.5 0 0 0-1.5-1.5H5.25a1.5 1.5 0 0 0-1.5 1.5Z" fill="" />
                            </svg>
                            <span class="menu-item-text" :class="sidebarToggle ? 'lg:hidden' : ''">{{ __('Kompyuterlar') }}</span>
                        </a>
                    </li>

                    {{-- Ma'lumotnomalar (ochiladigan guruh) --}}
                    <li x-data="{ open: {{ request()->routeIs('admin.lookups.*') ? 'true' : 'false' }} }">
                        <button type="button" @click="open = !open"
                                class="menu-item group w-full {{ request()->routeIs('admin.lookups.*') ? 'menu-item-active' : 'menu-item-inactive' }}">
                            <svg class="{{ request()->routeIs('admin.lookups.*') ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M3.25 5.5C3.25 4.25736 4.25736 3.25 5.5 3.25H18.5C19.7426 3.25 20.75 4.25736 20.75 5.5V18.5C20.75 19.7426 19.7426 20.75 18.5 20.75H5.5C4.25736 20.75 3.25 19.7426 3.25 18.5V5.5ZM5.5 4.75C5.08579 4.75 4.75 5.08579 4.75 5.5V8.58325L19.25 8.58325V5.5C19.25 5.08579 18.9142 4.75 18.5 4.75H5.5ZM19.25 10.0833H15.416V13.9165H19.25V10.0833ZM13.916 10.0833L10.083 10.0833V13.9165L13.916 13.9165V10.0833ZM8.58301 10.0833H4.75V13.9165H8.58301V10.0833ZM4.75 18.5V15.4165H8.58301V19.25H5.5C5.08579 19.25 4.75 18.9142 4.75 18.5ZM10.083 19.25V15.4165L13.916 15.4165V19.25H10.083ZM15.416 19.25V15.4165H19.25V18.5C19.25 18.9142 18.9142 19.25 18.5 19.25H15.416Z" fill="" />
                            </svg>
                            <span class="menu-item-text" :class="sidebarToggle ? 'lg:hidden' : ''">{{ __('Ma’lumotnomalar') }}</span>
                            <svg class="menu-item-arrow absolute right-2.5 top-1/2 -translate-y-1/2 stroke-current"
                                 :class="[open ? 'menu-item-arrow-active' : 'menu-item-arrow-inactive', sidebarToggle ? 'lg:hidden' : '']"
                                 width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M4.79175 7.39584L10.0001 12.6042L15.2084 7.39585" stroke="" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </button>

                        <div x-show="open" x-cloak :class="sidebarToggle ? 'lg:hidden' : ''">
                            <ul class="menu-dropdown mt-2 flex flex-col gap-1 pl-9">
                                @php
                                    $lookupLinks = [
                                        'categories' => __('Kategoriyalar'),
                                        'book-types' => __('Kitob turlari'),
                                        'journal-types' => __('Jurnal turlari'),
                                        'resource-fields' => __('Resurs sohalari'),
                                        'news-categories' => __('Yangilik kategoriyalari'),
                                        'languages' => __('Tillar'),
                                        'locations' => __('Joylashuvlar'),
                                        'publishers' => __('Nashriyotlar'),
                                        'authors' => __('Mualliflar'),
                                    ];
                                @endphp
                                @foreach ($lookupLinks as $slug => $label)
                                    <li>
                                        <a href="{{ route('admin.lookups.' . $slug . '.index') }}"
                                           class="menu-dropdown-item {{ request()->routeIs('admin.lookups.' . $slug . '.*') ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}">
                                            {{ $label }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </li>

                    {{-- Sayt menyusi (client navbar navigatsiyasi) --}}
                    <li>
                        <a href="{{ route('admin.menu-items.index') }}"
                           class="menu-item group {{ request()->routeIs('admin.menu-items.*') ? 'menu-item-active' : 'menu-item-inactive' }}">
                            <svg class="{{ request()->routeIs('admin.menu-items.*') ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M3.25 6.5C3.25 6.08579 3.58579 5.75 4 5.75H20C20.4142 5.75 20.75 6.08579 20.75 6.5C20.75 6.91421 20.4142 7.25 20 7.25H4C3.58579 7.25 3.25 6.91421 3.25 6.5ZM3.25 12C3.25 11.5858 3.58579 11.25 4 11.25H20C20.4142 11.25 20.75 11.5858 20.75 12C20.75 12.4142 20.4142 12.75 20 12.75H4C3.58579 12.75 3.25 12.4142 3.25 12ZM4 16.75C3.58579 16.75 3.25 17.0858 3.25 17.5C3.25 17.9142 3.58579 18.25 4 18.25H20C20.4142 18.25 20.75 17.9142 20.75 17.5C20.75 17.0858 20.4142 16.75 20 16.75H4Z" fill="" />
                            </svg>
                            <span class="menu-item-text" :class="sidebarToggle ? 'lg:hidden' : ''">{{ __('Sayt menyusi') }}</span>
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
    </div>
</aside>
