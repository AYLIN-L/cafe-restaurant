@extends('layouts.public')
@section('content')

    @php
        // Libellés français affichés pour chaque slug de catégorie.
        $labels = [
            'breakfast' => 'Petit-déjeuner',
            'lunch' => 'Déjeuner',
            'dinner' => 'Dîner',
            'desserts' => 'Desserts',
            'drinks' => 'Boissons',
            'coffee' => 'Cafés',
            'starters' => 'Entrées',
            'mains' => 'Plats principaux',
            'juices' => 'Jus',
        ];

        // Accents secondaires par catégorie (le brun/ambré reste dominant).
        $accents = [
            'desserts' => 'bg-pink-100 text-pink-700 ring-pink-200',
            'drinks' => 'bg-orange-100 text-orange-700 ring-orange-200',
            'juices' => 'bg-orange-100 text-orange-700 ring-orange-200',
            'coffee' => 'bg-amber-100 text-amber-800 ring-amber-200',
            'starters' => 'bg-emerald-100 text-emerald-700 ring-emerald-200',
            'mains' => 'bg-red-100 text-red-700 ring-red-200',
        ];

        $activeCategory = request('category');
        $searchTerm = request('q');

        $visibleCategories = $categories->take(10);
        $hiddenCategories = $categories->slice(10);
    @endphp

    <div class="min-h-screen bg-cream py-12 sm:py-16">
        <div class="mx-auto max-w-7xl px-4">

            {{-- ========== EN-TÊTE ========== --}}
            <div class="reveal max-w-3xl">
                <a href="{{ route('home') }}#menu" class="text-sm font-medium text-amber-dark hover:underline">
                    ← Retour à l'accueil
                </a>

                <h1 class="mt-5 font-display text-4xl leading-tight text-coffee sm:text-5xl">
                    Notre carte
                </h1>

                <p class="mt-4 text-lg leading-relaxed text-gray-500">
                    Découvrez l'ensemble de nos plats et boissons. Filtrez par catégorie
                    ou recherchez un plat par son nom.
                </p>
            </div>

            {{-- ========== RECHERCHE ========== --}}
            <form
                method="GET"
                action="{{ route('menu.index') }}"
                x-data="{ search: @js($searchTerm) }"
                class="mt-8 max-w-xl"
            >
                {{-- Conserve la catégorie active lors d'une recherche --}}
                @if ($activeCategory)
                    <input type="hidden" name="category" value="{{ $activeCategory }}">
                @endif

                <div class="relative">
                    <svg class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="11" cy="11" r="7" />
                        <path d="m21 21-4.3-4.3" />
                    </svg>

                    <input
                        type="text"
                        name="q"
                        x-model="search"
                        @input.debounce.500ms="$el.form.submit()"
                        @if ($searchTerm) autofocus @endif
                        value="{{ $searchTerm }}"
                        placeholder="Rechercher un plat..."
                        class="w-full rounded-xl border border-coffee/15 bg-white py-3.5 pl-12 pr-11 text-[15px] text-coffee placeholder:text-gray-400 transition duration-200 focus:border-amber focus:ring-2 focus:ring-amber/30"
                    >

                    {{-- Bouton effacer --}}
                    <button
                        type="button"
                        x-show="search && search.length"
                        @click="search = ''; $el.form.submit()"
                        class="absolute right-3 top-1/2 flex h-7 w-7 -translate-y-1/2 items-center justify-center rounded-full text-gray-400 transition hover:bg-coffee/5 hover:text-coffee"
                        aria-label="Effacer la recherche"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M18 6 6 18M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </form>

            {{-- ========== FILTRES CATÉGORIES ========== --}}
            <div class="mt-6" x-data="{ expanded: false }">

                <div class="flex flex-wrap gap-2.5">

                    {{-- Filtre "Toutes" --}}
                    <a
                        href="{{ route('menu.index', array_filter(['q' => $searchTerm])) }}"
                        class="inline-flex items-center rounded-full px-4 py-2 text-sm font-semibold ring-1 transition duration-200
                            {{ $activeCategory
                                ? 'border border-coffee/10 bg-white text-charcoal hover:border-amber/40 hover:text-amber-dark'
                                : 'bg-coffee text-cream ring-coffee' }}"
                    >
                        Toutes
                    </a>

                    {{-- 10 premières catégories --}}
                    @foreach ($visibleCategories as $category)
                        @php
                            $isActive = $activeCategory === $category->slug;
                            $accent = $accents[$category->slug] ?? 'bg-cream text-coffee ring-coffee/20';
                            $label = $labels[$category->slug] ?? $category->name;
                        @endphp

                        <a
                            href="{{ route('menu.index', array_filter(['category' => $category->slug, 'q' => $searchTerm])) }}"
                            class="inline-flex items-center rounded-full px-4 py-2 text-sm font-semibold ring-1 transition duration-200
                                {{ $isActive
                                    ? $accent
                                    : 'border border-coffee/10 bg-white text-charcoal hover:border-amber/40 hover:text-amber-dark' }}"
                        >
                            {{ $label }}
                        </a>
                    @endforeach

                    {{-- Catégories supplémentaires (masquées par défaut) --}}
                    @foreach ($hiddenCategories as $category)
                        @php
                            $isActive = $activeCategory === $category->slug;
                            $accent = $accents[$category->slug] ?? 'bg-cream text-coffee ring-coffee/20';
                            $label = $labels[$category->slug] ?? $category->name;
                        @endphp

                        <a
                            x-show="expanded"
                            x-transition.opacity.duration.200ms
                            style="display: none;"
                            href="{{ route('menu.index', array_filter(['category' => $category->slug, 'q' => $searchTerm])) }}"
                            class="inline-flex items-center rounded-full px-4 py-2 text-sm font-semibold ring-1 transition duration-200
                                {{ $isActive
                                    ? $accent
                                    : 'border border-coffee/10 bg-white text-charcoal hover:border-amber/40 hover:text-amber-dark' }}"
                        >
                            {{ $label }}
                        </a>
                    @endforeach

                    {{-- Bouton d'expansion (uniquement s'il existe des catégories masquées) --}}
                    @if ($hiddenCategories->isNotEmpty())
                        <button
                            type="button"
                            x-show="!expanded"
                            @click="expanded = true"
                            class="inline-flex items-center gap-1.5 rounded-full border border-dashed border-coffee/25 bg-cream px-4 py-2 text-sm font-semibold text-coffee transition duration-200 hover:border-amber hover:text-amber-dark"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 5v14M5 12h14" />
                            </svg>
                            Voir toutes les catégories
                        </button>

                        <button
                            type="button"
                            x-show="expanded"
                            @click="expanded = false"
                            style="display: none;"
                            class="inline-flex items-center gap-1.5 rounded-full border border-dashed border-coffee/25 bg-cream px-4 py-2 text-sm font-semibold text-coffee transition duration-200 hover:border-amber hover:text-amber-dark"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M5 12h14" />
                            </svg>
                            Réduire les catégories
                        </button>
                    @endif

                </div>
            </div>

            {{-- ========== NOMBRE DE RÉSULTATS ========== --}}
            <p class="mt-8 text-sm text-gray-500">
                {{ $menus->total() }}
                {{ $menus->total() > 1 ? 'plats trouvés' : 'plat trouvé' }}
                @if ($searchTerm)
                    pour « <span class="font-semibold text-coffee">{{ $searchTerm }}</span> »
                @endif
            </p>

            {{-- ========== GRILLE DES PLATS ========== --}}
            @if ($menus->isEmpty())
                <div class="mt-4 rounded-2xl border border-coffee/10 bg-white p-12 text-center">
                    <p class="font-display text-2xl text-coffee">Aucun plat ne correspond</p>
                    <p class="mt-2 text-gray-500">
                        Essayez une autre recherche ou choisissez une autre catégorie.
                    </p>
                    <a href="{{ route('menu.index') }}" class="mt-6 inline-flex items-center gap-2 rounded-xl bg-amber px-5 py-3 text-sm font-semibold text-white transition duration-200 hover:bg-amber-dark">
                        Réinitialiser les filtres
                    </a>
                </div>
            @else
                <div class="mt-4 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($menus as $menu)
                        <div class="reveal" style="--d: {{ min($loop->index, 6) * 0.06 }}s">
                            <x-menu-card :menu="$menu" />
                        </div>
                    @endforeach
                </div>

                {{-- ========== PAGINATION (conserve filtres + recherche) ========== --}}
                @if ($menus->hasPages())
                    <div class="mt-10">
                        {{ $menus->links() }}
                    </div>
                @endif
            @endif

        </div>
    </div>

@endsection
