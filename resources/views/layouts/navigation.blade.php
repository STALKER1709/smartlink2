<nav x-data="{ open: false }" class="bg-surface-container-lowest border-b border-outline-variant">
    <!-- Primary Navigation Menu -->
    <div class="max-w-container mx-auto px-margin-mobile md:px-margin-desktop">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center gap-2">
                    <a href="{{ route('home') }}" class="flex items-center gap-2">
                        <x-application-logo class="block h-8 w-auto fill-current text-primary" />
                        <span class="hidden sm:inline font-headline-md text-headline-md text-primary">SmartLink</span>
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-6 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('services.index')" :active="request()->routeIs('services.*')">
                        {{ __('Services') }}
                    </x-nav-link>
                    <x-nav-link :href="route('providers.index')" :active="request()->routeIs('providers.*')">
                        {{ __('Prestataires') }}
                    </x-nav-link>

                    @auth
                        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                            {{ __('Tableau de bord') }}
                        </x-nav-link>
                        <x-nav-link :href="route('requests.index')" :active="request()->routeIs('requests.*')">
                            {{ __('Demandes') }}
                        </x-nav-link>
                        @if (Auth::user()->isProvider())
                            <x-nav-link :href="route('provider.services.index')" :active="request()->routeIs('provider.services.*')">
                                {{ __('Mes services') }}
                            </x-nav-link>
                            @if (Auth::user()->currentPlan()?->has_stats)
                                <x-nav-link :href="route('provider.statistics.index')" :active="request()->routeIs('provider.statistics.*')">
                                    {{ __('ui.nav.statistics') }}
                                </x-nav-link>
                            @endif
                            <x-nav-link :href="route('provider.subscription.show')" :active="request()->routeIs('provider.subscription.*')">
                                {{ __('ui.nav.subscription') }}
                            </x-nav-link>
                        @endif
                        <x-nav-link :href="route('conversations.index')" :active="request()->routeIs('conversations.*')">
                            {{ __('Messages') }}
                        </x-nav-link>
                        @if (Auth::user()->isAdmin())
                            <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.*')">
                                {{ __('Administration') }}
                            </x-nav-link>
                        @endif
                    @endauth
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:ms-6 gap-3">
                {{-- Language switcher --}}
                @php $currentLocale = app()->getLocale(); @endphp
                <div class="flex rounded-full border border-outline-variant overflow-hidden text-xs font-semibold">
                    <a href="{{ route('locale.switch', 'fr') }}"
                       class="px-3 py-1.5 {{ $currentLocale === 'fr' ? 'bg-primary text-on-primary' : 'text-on-surface-variant hover:bg-surface-container-low' }}">FR</a>
                    <a href="{{ route('locale.switch', 'en') }}"
                       class="px-3 py-1.5 {{ $currentLocale === 'en' ? 'bg-primary text-on-primary' : 'text-on-surface-variant hover:bg-surface-container-low' }}">EN</a>
                </div>

                @auth
                    <!-- Notifications -->
                    <a href="{{ route('notifications.index') }}" class="relative inline-flex items-center p-2 text-on-surface-variant hover:text-on-surface rounded-full hover:bg-surface-container-low transition-colors">
                        <span class="material-symbols-outlined" style="font-size: 22px;">notifications</span>
                        @php $unreadCount = Auth::user()->unreadNotifications()->count(); @endphp
                        @if ($unreadCount > 0)
                            <span class="absolute top-0.5 right-0.5 inline-flex items-center justify-center h-4 w-4 rounded-full bg-error text-[10px] font-bold text-on-error">
                                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                            </span>
                        @endif
                    </a>

                    <!-- Settings Dropdown -->
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="inline-flex items-center gap-1 px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-full text-on-surface-variant bg-surface-container-lowest hover:text-on-surface hover:bg-surface-container-low focus:outline-none transition ease-in-out duration-150">
                                <div>{{ Auth::user()->name }}</div>

                                <span class="material-symbols-outlined" style="font-size: 18px;">expand_more</span>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            @if (Auth::user()->isClient())
                                <x-dropdown-link :href="route('client.profile.edit')">
                                    {{ __('Mon profil client') }}
                                </x-dropdown-link>
                            @elseif (Auth::user()->isProvider())
                                <x-dropdown-link :href="route('provider.profile.edit')">
                                    {{ __('Mon profil prestataire') }}
                                </x-dropdown-link>
                            @endif

                            <x-dropdown-link :href="route('profile.edit')">
                                {{ __('Paramètres du compte') }}
                            </x-dropdown-link>

                            <!-- Authentication -->
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf

                                <x-dropdown-link :href="route('logout')"
                                        onclick="event.preventDefault();
                                                    this.closest('form').submit();">
                                    {{ __('Log Out') }}
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                @else
                    <a href="{{ route('login') }}" class="text-sm font-medium text-on-surface-variant hover:text-on-surface">{{ __('Se connecter') }}</a>
                    <a href="{{ route('register') }}" class="rounded-full bg-primary px-5 py-2 text-sm font-button-text font-semibold text-on-primary hover:bg-primary-container transition-colors">{{ __("S'inscrire") }}</a>
                @endauth
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-full text-on-surface-variant hover:text-on-surface hover:bg-surface-container-low focus:outline-none transition duration-150 ease-in-out">
                    <span class="material-symbols-outlined" x-show="! open">menu</span>
                    <span class="material-symbols-outlined" x-show="open" x-cloak>close</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('services.index')" :active="request()->routeIs('services.*')">
                {{ __('Services') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('providers.index')" :active="request()->routeIs('providers.*')">
                {{ __('Prestataires') }}
            </x-responsive-nav-link>

            @auth
                <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                    {{ __('Tableau de bord') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('requests.index')" :active="request()->routeIs('requests.*')">
                    {{ __('Demandes') }}
                </x-responsive-nav-link>
                @if (Auth::user()->isProvider())
                    <x-responsive-nav-link :href="route('provider.services.index')" :active="request()->routeIs('provider.services.*')">
                        {{ __('Mes services') }}
                    </x-responsive-nav-link>
                    @if (Auth::user()->currentPlan()?->has_stats)
                        <x-responsive-nav-link :href="route('provider.statistics.index')" :active="request()->routeIs('provider.statistics.*')">
                            {{ __('ui.nav.statistics') }}
                        </x-responsive-nav-link>
                    @endif
                    <x-responsive-nav-link :href="route('provider.subscription.show')" :active="request()->routeIs('provider.subscription.*')">
                        {{ __('ui.nav.subscription') }}
                    </x-responsive-nav-link>
                @endif
                <x-responsive-nav-link :href="route('conversations.index')" :active="request()->routeIs('conversations.*')">
                    {{ __('Messages') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('notifications.index')" :active="request()->routeIs('notifications.*')">
                    {{ __('Notifications') }}
                    @php $unreadCount = Auth::user()->unreadNotifications()->count(); @endphp
                    @if ($unreadCount > 0)
                        <span class="ms-1 inline-flex items-center justify-center h-4 w-4 rounded-full bg-error text-[10px] font-bold text-on-error">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
                    @endif
                </x-responsive-nav-link>
                @if (Auth::user()->isAdmin())
                    <x-responsive-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.*')">
                        {{ __('Administration') }}
                    </x-responsive-nav-link>
                @endif
            @endauth
        </div>

        <!-- Mobile language switcher -->
        <div class="pt-2 pb-2 px-4 border-t border-outline-variant flex gap-2">
            @php $currentLocale = app()->getLocale(); @endphp
            <a href="{{ route('locale.switch', 'fr') }}"
               class="rounded-full px-3 py-1.5 text-sm font-medium {{ $currentLocale === 'fr' ? 'bg-primary text-on-primary' : 'text-on-surface-variant hover:bg-surface-container-low' }}">Français</a>
            <a href="{{ route('locale.switch', 'en') }}"
               class="rounded-full px-3 py-1.5 text-sm font-medium {{ $currentLocale === 'en' ? 'bg-primary text-on-primary' : 'text-on-surface-variant hover:bg-surface-container-low' }}">English</a>
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-outline-variant">
            @auth
                <div class="px-4">
                    <div class="font-medium text-base text-on-surface">{{ Auth::user()->name }}</div>
                    <div class="font-medium text-sm text-on-surface-variant">{{ Auth::user()->email }}</div>
                </div>

                <div class="mt-3 space-y-1">
                    @if (Auth::user()->isClient())
                        <x-responsive-nav-link :href="route('client.profile.edit')">
                            {{ __('Mon profil client') }}
                        </x-responsive-nav-link>
                    @elseif (Auth::user()->isProvider())
                        <x-responsive-nav-link :href="route('provider.profile.edit')">
                            {{ __('Mon profil prestataire') }}
                        </x-responsive-nav-link>
                    @endif

                    <x-responsive-nav-link :href="route('profile.edit')">
                        {{ __('Paramètres du compte') }}
                    </x-responsive-nav-link>

                    <!-- Authentication -->
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf

                        <x-responsive-nav-link :href="route('logout')"
                                onclick="event.preventDefault();
                                            this.closest('form').submit();">
                            {{ __('Log Out') }}
                        </x-responsive-nav-link>
                    </form>
                </div>
            @else
                <div class="space-y-1">
                    <x-responsive-nav-link :href="route('login')">{{ __('Se connecter') }}</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('register')">{{ __("S'inscrire") }}</x-responsive-nav-link>
                </div>
            @endauth
        </div>
    </div>
</nav>
