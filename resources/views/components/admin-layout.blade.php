@props(['title' => 'Administration'])
@php
    // Navigation : modifie ici pour ajouter ou retirer un lien
    $nav = [
        'Pilotage' => [
            ['label' => 'Tableau de bord', 'route' => 'admin.dashboard',          'match' => 'admin.dashboard',      'icon' => 'squares'],
        ],
        'Carte' => [
            ['label' => 'Plats',           'route' => 'admin.menus.index',        'match' => 'admin.menus.*',        'icon' => 'book'],
            ['label' => 'Catégories',      'route' => 'admin.categories.index',   'match' => 'admin.categories.*',   'icon' => 'tag'],
        ],
        'Clientèle' => [
            ['label' => 'Réservations',    'route' => 'admin.reservations.index', 'match' => 'admin.reservations.*', 'icon' => 'calendar'],
            ['label' => 'Commentaires',    'route' => 'admin.comments.index',     'match' => 'admin.comments.*',     'icon' => 'chat'],
            ['label' => 'Clients',         'route' => 'admin.users.index',        'match' => 'admin.users.*',        'icon' => 'users'],
        ],
        'Salle' => [
            ['label' => 'Tables',          'route' => 'admin.tables.index',       'match' => 'admin.tables.*',       'icon' => 'table'],
        ],
    ];

    $pageTitle = $title;
    $initials  = collect(explode(' ', trim(Auth::user()->name)))
                    ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))
                    ->take(2)->implode('');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle }} · {{ config('app.name', 'Café Resto') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Serif+Display&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-cream font-sans text-charcoal antialiased"
      x-data="{ sidebar: false }"
      @keydown.escape.window="sidebar = false">

    {{-- Fond sombre derrière le menu mobile --}}
    <div x-show="sidebar" x-transition.opacity.duration.300ms @click="sidebar = false"
         class="fixed inset-0 z-30 bg-coffee/40 lg:hidden" style="display: none;"></div>

    {{-- ========== SIDEBAR ========== --}}
    <aside class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col border-r border-coffee/10 bg-white
                  transition-transform duration-300 ease-out lg:translate-x-0"
           :class="sidebar ? 'translate-x-0' : '-translate-x-full'">

        {{-- Logo --}}
        <div class="flex h-16 items-center justify-between border-b border-coffee/10 px-5">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3">
                <img src="{{ asset('images/logo.png') }}" alt="" class="h-10 w-auto">
                <span class="font-display text-xl leading-none text-coffee">Café Resto</span>
            </a>
            <button type="button" @click="sidebar = false" class="rounded-md p-1.5 text-charcoal/50 hover:bg-coffee/5 lg:hidden" aria-label="Fermer le menu">
                <x-icon name="close" class="h-5 w-5" />
            </button>
        </div>

        {{-- Liens --}}
        <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-6">
            @foreach($nav as $group => $items)
                <div>
                    <p class="mb-2 px-3 text-[11px] font-semibold uppercase tracking-widest text-charcoal/40">{{ $group }}</p>
                    <div class="space-y-1">
                        @foreach($items as $item)
                            @php $active = request()->routeIs($item['match']); @endphp
                            <a href="{{ route($item['route']) }}"
                               @if($active) aria-current="page" @endif
                               class="group relative flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors duration-200
                                      {{ $active ? 'bg-amber/10 text-coffee' : 'text-charcoal/70 hover:bg-coffee/5 hover:text-coffee' }}">
                                {{-- Repère de la page active --}}
                                <span class="absolute left-0 top-1/2 h-5 w-1 -translate-y-1/2 rounded-r-full bg-amber transition duration-300
                                             {{ $active ? '' : 'scale-y-0 group-hover:scale-y-50' }}"></span>
                                <x-icon :name="$item['icon']"
                                        class="h-5 w-5 shrink-0 transition-colors duration-200 {{ $active ? 'text-amber-dark' : 'text-charcoal/40 group-hover:text-coffee' }}" />
                                {{ $item['label'] }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </nav>

        {{-- Profil + déconnexion --}}
        <div class="border-t border-coffee/10 p-3">
            <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 rounded-lg p-2 transition-colors hover:bg-coffee/5">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-coffee text-xs font-semibold text-cream">{{ $initials }}</span>
                <span class="min-w-0">
                    <span class="block truncate text-sm font-semibold text-coffee">{{ Auth::user()->name }}</span>
                    <span class="block text-xs text-charcoal/50">Administrateur</span>
                </span>
            </a>
            <form method="POST" action="{{ route('logout') }}" class="mt-1">
                @csrf
                <button type="submit"
                        class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-charcoal/70 transition-colors hover:bg-red-50 hover:text-red-700">
                    <x-icon name="logout" class="h-5 w-5" />
                    Déconnexion
                </button>
            </form>
        </div>
    </aside>

    {{-- ========== ZONE PRINCIPALE ========== --}}
    <div class="lg:pl-64">

        {{-- Barre du haut --}}
        <header class="sticky top-0 z-20 flex h-16 items-center gap-4 border-b border-coffee/10 bg-cream px-4 sm:px-6 lg:px-8">
            <button type="button" @click="sidebar = true"
                    class="rounded-md p-2 text-coffee hover:bg-coffee/5 lg:hidden" aria-label="Ouvrir le menu">
                <x-icon name="bars" class="h-6 w-6" />
            </button>

            <div class="flex items-center gap-2 text-sm">
                <span class="hidden text-charcoal/50 sm:inline">Administration</span>
                <span class="hidden text-charcoal/30 sm:inline">/</span>
                <span class="font-semibold text-coffee">{{ $pageTitle }}</span>
            </div>

            <a href="{{ route('home') }}" target="_blank"
               class="ml-auto inline-flex items-center gap-2 rounded-lg border border-coffee/15 px-3 py-1.5 text-sm font-medium text-coffee transition hover:border-amber hover:text-amber-dark">
                Voir le site
                <x-icon name="external" class="h-4 w-4" />
            </a>
        </header>

        {{-- Contenu --}}
        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-6 flex items-start gap-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M20 6 9 17l-5-5" />
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-6 flex items-start gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-red-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="10" />
                        <path d="M12 8v4M12 16h.01" />
                    </svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            {{ $slot }}
        </main>
    </div>
</body>
</html>