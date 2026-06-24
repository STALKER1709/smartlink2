<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('home') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-indigo-600" />
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
                @auth
                    <!-- Notifications -->
                    <a href="{{ route('notifications.index') }}" class="relative inline-flex items-center p-2 text-gray-500 hover:text-gray-700">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2c0 .53-.21 1.04-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        @php $unreadCount = Auth::user()->unreadNotifications()->count(); @endphp
                        @if ($unreadCount > 0)
                            <span class="absolute top-0 right-0 inline-flex items-center justify-center h-4 w-4 rounded-full bg-red-600 text-[10px] font-bold text-white">
                                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                            </span>
                        @endif
                    </a>

                    <!-- Settings Dropdown -->
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                                <div>{{ Auth::user()->name }}</div>

                                <div class="ms-1">
                                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </div>
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
                    <a href="{{ route('login') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900">{{ __('Se connecter') }}</a>
                    <a href="{{ route('register') }}" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">{{ __("S'inscrire") }}</a>
                @endauth
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
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
                @endif
                <x-responsive-nav-link :href="route('conversations.index')" :active="request()->routeIs('conversations.*')">
                    {{ __('Messages') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('notifications.index')" :active="request()->routeIs('notifications.*')">
                    {{ __('Notifications') }}
                    @php $unreadCount = Auth::user()->unreadNotifications()->count(); @endphp
                    @if ($unreadCount > 0)
                        <span class="ms-1 inline-flex items-center justify-center h-4 w-4 rounded-full bg-red-600 text-[10px] font-bold text-white">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
                    @endif
                </x-responsive-nav-link>
                @if (Auth::user()->isAdmin())
                    <x-responsive-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.*')">
                        {{ __('Administration') }}
                    </x-responsive-nav-link>
                @endif
            @endauth
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            @auth
                <div class="px-4">
                    <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                    <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
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
