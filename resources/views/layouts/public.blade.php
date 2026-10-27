<!DOCTYPE HTML>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>@yield('title', 'Café Resto')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-cream font-sans text-charcoal antialiased">

    @php
        $restaurant = config('restaurant');
        $mapQuery = trim((string) data_get($restaurant, 'address.map_query', ''));
        $directionsUrl = $mapQuery !== ''
            ? 'https://www.google.com/maps/dir/?api=1&destination='.rawurlencode($mapQuery)
            : null;

        $links = [
            ['label' => 'Accueil', 'url' => route('home'), 'active' => request()->routeIs('home')],
            ['label' => 'Menu', 'url' => route('home') . '#menu', 'active' => false],
            ['label' => 'Contact', 'url' => route('contact.create'), 'active' => request()->routeIs('contact.create')],
        ];
    @endphp

    {{-- ========== EN-TÊTE ========== --}}
    <header class="sticky top-0 z-50 border-b border-coffee/10 bg-cream/90 backdrop-blur-md"
            x-data="{ open: false }"
            @keydown.escape.window="open = false">

        <nav class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">

            {{-- Marque --}}
            <a href="{{ route('home') }}" class="group flex shrink-0 items-center gap-3">
                <img src="{{ asset('images/logo.png') }}" alt="Logo Café Resto"
                     class="h-10 w-auto transition-transform duration-200 group-hover:scale-105">
                <span class="font-display text-xl leading-none text-coffee">Café Resto</span>
            </a>

            {{-- Liens — bureau --}}
            <div class="hidden items-center gap-8 lg:flex">
                @foreach($links as $link)
                    <a href="{{ $link['url'] }}"
                       @if($link['active']) aria-current="page" @endif
                       class="group relative py-2 text-sm font-medium transition-colors duration-200
                              {{ $link['active'] ? 'text-coffee' : 'text-charcoal/70 hover:text-coffee' }}">
                        {{ $link['label'] }}
                        <span class="absolute inset-x-0 -bottom-0.5 h-0.5 origin-left scale-x-0 rounded-full bg-amber transition-transform duration-300 group-hover:scale-x-100
                                     {{ $link['active'] ? 'scale-x-100' : '' }}"></span>
                    </a>
                @endforeach
            </div>

            {{-- Authentification — bureau --}}
            <div class="hidden items-center gap-3 lg:flex">
                @guest
                    <a href="{{ route('login') }}"
                       class="rounded-lg px-3 py-2 text-sm font-medium text-charcoal/70 transition-colors hover:bg-coffee/5 hover:text-coffee">
                        Connexion
                    </a>
                    <a href="{{ route('register') }}"
                       class="rounded-lg bg-amber px-4 py-2 text-sm font-semibold text-white shadow-sm transition duration-200 hover:-translate-y-0.5 hover:bg-amber-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-light focus-visible:ring-offset-2">
                        Inscription
                    </a>
                @endguest

                @auth
                    <a href="{{ route('reservations.index') }}"
                       class="rounded-lg px-3 py-2 text-sm font-medium text-charcoal/70 transition-colors hover:bg-coffee/5 hover:text-coffee">
                        Mes réservations
                    </a>

                    @if(Auth::user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}"
                           class="rounded-lg px-3 py-2 text-sm font-medium text-charcoal/70 transition-colors hover:bg-coffee/5 hover:text-coffee">
                            Admin
                        </a>
                    @endif

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center gap-2 self-start rounded-lg bg-coffee px-4 py-2.5 text-sm font-semibold text-cream shadow-sm transition hover:bg-amber-dark">
                            <x-lucide-log-out class="h-4 w-4" />
                            Déconnexion
                        </button>
                    </form>
                @endauth
            </div>

            {{-- Bouton — mobile --}}
            <button type="button" @click="open = !open"
                    class="rounded-lg p-2 text-coffee transition-colors hover:bg-coffee/5 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber lg:hidden"
                    :aria-expanded="open ? 'true' : 'false'" aria-controls="mobile-menu"
                    :aria-label="open ? 'Fermer le menu' : 'Ouvrir le menu'">
                <x-lucide-menu class="h-6 w-6" x-show="!open" />
                <x-lucide-x class="h-6 w-6" x-show="open" style="display: none;" />
            </button>
        </nav>

        {{-- Fond sombre derrière le menu mobile --}}
        <div x-show="open" x-transition.opacity.duration.200ms @click="open = false"
             class="fixed inset-0 top-16 z-40 bg-coffee/40 lg:hidden" style="display: none;"></div>

        {{-- Panneau — mobile --}}
        <div id="mobile-menu" x-show="open" x-transition.duration.200ms
             class="absolute inset-x-0 top-full z-50 origin-top border-b border-coffee/10 bg-cream shadow-xl lg:hidden"
             style="display: none;">
            <nav class="mx-auto max-w-7xl space-y-1 px-4 py-4 sm:px-6">
                @foreach($links as $link)
                    <a href="{{ $link['url'] }}" @click="open = false"
                       class="block rounded-lg px-4 py-3 text-[15px] font-medium transition-colors
                              {{ $link['active'] ? 'bg-amber/10 text-coffee' : 'text-charcoal/80 hover:bg-coffee/5 hover:text-coffee' }}">
                        {{ $link['label'] }}
                    </a>
                @endforeach

                <div class="my-3 border-t border-coffee/10"></div>

                @guest
                    <a href="{{ route('login') }}"
                       class="block rounded-lg px-4 py-3 text-[15px] font-medium text-charcoal/80 transition-colors hover:bg-coffee/5 hover:text-coffee">
                        Connexion
                    </a>
                    <a href="{{ route('register') }}"
                       class="mt-2 flex items-center justify-center rounded-lg bg-amber px-4 py-3 text-[15px] font-semibold text-white transition-colors hover:bg-amber-dark">
                        Inscription
                    </a>
                @endguest

                @auth
                    <a href="{{ route('reservations.index') }}"
                       class="block rounded-lg px-4 py-3 text-[15px] font-medium text-charcoal/80 transition-colors hover:bg-coffee/5 hover:text-coffee">
                        Mes réservations
                    </a>

                    @if(Auth::user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}"
                           class="block rounded-lg px-4 py-3 text-[15px] font-medium text-charcoal/80 transition-colors hover:bg-coffee/5 hover:text-coffee">
                            Admin
                        </a>
                    @endif

                    <form method="POST" action="{{ route('logout') }}" class="mt-2">
                        @csrf
                        <button type="submit"
                               class="inline-flex items-center gap-2 self-start rounded-lg bg-coffee px-4 py-2.5 text-sm font-semibold text-cream shadow-sm transition hover:bg-amber-dark">
                            <x-lucide-log-out class="h-4 w-4" />
                            Déconnexion
                        </button>
                    </form>
                @endauth
            </nav>
        </div>
    </header>

    <main>
        @yield('content')
    </main>

    {{-- ========== PIED DE PAGE ========== --}}
    <footer class="bg-coffee text-cream/70">
        <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
            <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-4">

                {{-- Marque --}}
                <div class="sm:col-span-2 lg:col-span-1">
                    <a href="{{ route('home') }}" class="flex items-center gap-3">
                        <img src="{{ asset('images/logo.png') }}" alt="Logo Café Resto" class="h-10 w-auto">
                        <span class="font-display text-2xl text-cream">Café Resto</span>
                    </a>
                    <p class="mt-4 max-w-xs text-sm leading-relaxed text-cream/60">
                        Un café de quartier et une cuisine soignée, du matin au soir.
                        Bon goût, bons moments.
                    </p>
                </div>

                {{-- Navigation --}}
                <div>
                    <h2 class="font-display text-lg text-cream">Navigation</h2>
                    <ul class="mt-4 space-y-2.5 text-sm">
                        <li><a href="{{ route('home') }}" class="transition-colors hover:text-amber-light">Accueil</a></li>
                        <li><a href="{{ route('home') }}#menu" class="transition-colors hover:text-amber-light">Menu</a></li>
                        <li><a href="{{ route('contact.create') }}" class="transition-colors hover:text-amber-light">Contact</a></li>
                        <li><a href="{{ route('reservations.index') }}" class="transition-colors hover:text-amber-light">Réservations</a></li>
                    </ul>
                </div>

                {{-- Horaires --}}
                <div>
                    <h2 class="font-display text-lg text-cream">Horaires</h2>
                    <ul class="mt-4 space-y-2.5 text-sm">
                        <li class="flex items-center justify-between gap-4">
                            <span>Tous les jours</span>
                            <span class="font-medium text-cream/90">8h – 22h</span>
                        </li>
                    </ul>
                    <p class="mt-3 text-xs text-cream/50">Réservations en ligne de 8h à 20h.</p>
                </div>

                {{-- Contact --}}
                <div>
                    <h2 class="font-display text-lg text-cream">Contact</h2>
                    <ul class="mt-4 space-y-3 text-sm">
                        <li class="flex items-start gap-3">
                            {{-- Localisation --}}
                            <svg class="mt-0.5 h-[18px] w-[18px] shrink-0 text-amber-light" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0Z" />
                                <circle cx="12" cy="10" r="3" />
                            </svg>
                            @if ($directionsUrl)
                                <a href="{{ $directionsUrl }}" target="_blank" rel="noopener noreferrer"
                                   class="leading-6 transition-colors hover:text-amber-light">
                                    {{ $restaurant['address']['line1'] }}<br>
                                    <span class="text-cream/50">{{ $restaurant['address']['district'] }}, {{ $restaurant['address']['postal_code'] }} {{ $restaurant['address']['city'] }}</span>
                                </a>
                            @else
                                <span class="leading-6">
                                    {{ $restaurant['address']['line1'] }}<br>
                                    <span class="text-cream/50">{{ $restaurant['address']['district'] }}, {{ $restaurant['address']['postal_code'] }} {{ $restaurant['address']['city'] }}</span>
                                </span>
                            @endif
                        </li>
                        <li class="flex items-center gap-3">
                            {{-- Téléphone --}}
                            <svg class="h-[18px] w-[18px] shrink-0 text-amber-light" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92Z" />
                            </svg>
                            @if (($restaurant['phone'] ?? '') !== '' && $restaurant['phone'] !== 'XXXXXX')
                                <a href="tel:{{ preg_replace('/\s+/', '', $restaurant['phone']) }}" class="transition-colors hover:text-amber-light">{{ $restaurant['phone'] }}</a>
                            @else
                                <span>{{ $restaurant['phone'] ?? 'XXXXXX' }} <span class="text-xs text-cream/40">(à confirmer)</span></span>
                            @endif
                        </li>
                        <li class="flex items-center gap-3">
                            {{-- E-mail --}}
                            <svg class="h-[18px] w-[18px] shrink-0 text-amber-light" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="2" y="4" width="20" height="16" rx="2" />
                                <path d="m22 7-10 6L2 7" />
                            </svg>
                            @if (($restaurant['email'] ?? '') !== '' && $restaurant['email'] !== 'XXXXXX')
                                <a href="mailto:{{ $restaurant['email'] }}" class="break-all transition-colors hover:text-amber-light">{{ $restaurant['email'] }}</a>
                            @else
                                <span>{{ $restaurant['email'] ?? 'XXXXXX' }} <span class="text-xs text-cream/40">(à confirmer)</span></span>
                            @endif
                        </li>
                    </ul>
                </div>

            </div>
        </div>

        {{-- Barre inférieure --}}
        <div class="border-t border-cream/10">
            <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2 px-4 py-5 text-xs text-cream/50 sm:flex-row sm:px-6 lg:px-8">
                <p>© {{ now()->year }} Café Resto. Tous droits réservés.</p>
                <p class="font-display italic text-cream/60">Bon goût. Bons moments.</p>
            </div>
        </div>
    </footer>

    @stack('scripts')

</body>
</html>
