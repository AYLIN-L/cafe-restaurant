@extends('layouts.public')

@section('content')

    @php
        // Libellés français des catégories.
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

        // Couleurs secondaires des catégories actives.
        $accents = [
            'desserts' => 'bg-pink-100 text-pink-700 ring-pink-200',
            'drinks' => 'bg-orange-100 text-orange-700 ring-orange-200',
            'juices' => 'bg-orange-100 text-orange-700 ring-orange-200',
            'coffee' => 'bg-amber-100 text-amber-800 ring-amber-200',
            'starters' => 'bg-emerald-100 text-emerald-700 ring-emerald-200',
            'mains' => 'bg-red-100 text-red-700 ring-red-200',
        ];

        // Apparence des catégories inactives.
        $chipIdle = 'bg-white/80 text-coffee ring-coffee/15 hover:bg-white hover:ring-amber/60';

        $activeCategory = request('category');
        $searchTerm = request('q');

        // Affichage progressif des catégories.
        $visibleCategories = $categories->take(10);
        $hiddenCategories = $categories->slice(10);
    @endphp

    {{-- Animations légères et respect des préférences d'accessibilité --}}
    <style>
        @keyframes drawLine {
            to {
                stroke-dashoffset: 0;
            }
        }

        @keyframes softReveal {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .draw-line {
            stroke-dasharray: 220;
            stroke-dashoffset: 220;
            animation: drawLine 1.1s 0.2s ease-out forwards;
        }

        .menu-reveal {
            animation: softReveal 0.55s ease-out both;
            animation-delay: var(--d, 0s);
        }

        @media (prefers-reduced-motion: reduce) {
            .draw-line {
                animation: none;
                stroke-dashoffset: 0;
            }

            .menu-reveal {
                animation: none;
            }
        }
    </style>

    <div class="relative isolate min-h-screen overflow-hidden bg-[#FBF8F2] pb-16 sm:pb-24">

        {{-- Fond discret --}}
        <div class="pointer-events-none absolute inset-0 -z-10 overflow-hidden" aria-hidden="true">
            <div class="absolute -right-40 top-24 h-80 w-80 rounded-full bg-amber/[0.06] blur-3xl"></div>
            <div class="absolute -left-40 top-[40rem] h-96 w-96 rounded-full bg-coffee/[0.04] blur-3xl"></div>
            <div class="absolute right-0 top-[72rem] h-80 w-80 rounded-full bg-amber/[0.04] blur-3xl"></div>
        </div>

        {{-- =====================================================
             EN-TÊTE ÉDITORIAL
        ====================================================== --}}
        <header class="relative overflow-hidden">

            {{-- Accent décoratif --}}
            <div class="pointer-events-none absolute -right-24 -top-28 h-80 w-80 rounded-full border border-amber/10" aria-hidden="true"></div>
            <div class="pointer-events-none absolute -right-10 -top-14 h-56 w-56 rounded-full border border-amber/10" aria-hidden="true"></div>

            <div class="relative mx-auto max-w-7xl px-4 pb-10 pt-6 sm:px-6 sm:pb-14 sm:pt-8 lg:px-8">

                {{-- Retour à l'accueil --}}
                <a href="{{ route('home') }}#menu"
                   class="group inline-flex items-center gap-2 rounded-full border border-coffee/15 bg-white/80 px-4 py-2 text-sm font-medium text-coffee transition duration-200 hover:border-amber/60 hover:bg-white">

                    <svg class="h-4 w-4 transition-transform duration-200 group-hover:-translate-x-1"
                         viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                         stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m15 18-6-6 6-6"/>
                        <path d="M9 12h11"/>
                    </svg>

                    Retour à l'accueil
                </a>

                {{-- Titre principal --}}
                <div class="mx-auto mt-8 max-w-3xl text-center sm:mt-10">

                    <div class="menu-reveal inline-flex items-center gap-3 text-[11px] font-semibold uppercase tracking-[0.28em] text-amber-dark sm:text-xs">

                        <span class="h-px w-8 bg-amber/70"></span>
                        L'art de bien recevoir
                        <span class="h-px w-8 bg-amber/70"></span>

                    </div>

                    <h1 class="menu-reveal mt-5 font-display text-5xl font-medium leading-[1.08] tracking-tight text-coffee sm:mt-6 sm:text-6xl lg:text-7xl"
                        style="--d: .1s">

                        Notre
                        <span class="relative inline-block italic text-amber-dark">
                            carte
                            <svg class="absolute -bottom-3 left-0 w-full" viewBox="0 0 200 14" fill="none"
                                 preserveAspectRatio="none" aria-hidden="true">
                                <path class="draw-line" d="M3 9c30-8 60-8 95-3s65 4 99-2"
                                      stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
                            </svg>
                        </span>

                    </h1>

                    <p class="menu-reveal mx-auto mt-6 max-w-xl text-sm leading-7 text-coffee/65 sm:mt-7 sm:text-base sm:leading-8"
                       style="--d: .2s">
                        Découvrez notre sélection de plats et de boissons, préparés pour accompagner vos meilleurs moments.
                    </p>

                    {{-- Séparateur typographique --}}
                    <div class="menu-reveal mt-6 flex items-center justify-center gap-2 sm:mt-7"
                         style="--d: .3s" aria-hidden="true">
                        <span class="h-1 w-1 rounded-full bg-amber/50"></span>
                        <span class="h-px w-12 bg-coffee/15"></span>
                        <span class="h-1.5 w-1.5 rounded-full bg-amber"></span>
                        <span class="h-px w-12 bg-coffee/15"></span>
                        <span class="h-1 w-1 rounded-full bg-amber/50"></span>
                    </div>

                </div>

            </div>
        </header>

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

            {{-- =================================================
                 RECHERCHE
            ================================================== --}}
            <form
                method="GET"
                action="{{ route('menu.index') }}"
                class="menu-reveal relative z-10 mx-auto -mt-5 flex max-w-3xl flex-col gap-2 sm:-mt-6 sm:flex-row sm:items-center sm:gap-2"
                style="--d: .1s"
            >

                {{-- Conserver la catégorie pendant la recherche --}}
                @if ($activeCategory)
                    <input type="hidden" name="category" value="{{ $activeCategory }}">
                @endif

                <div class="relative flex-1">
                    {{-- Icône de recherche --}}
                    <svg class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-coffee/40"
                         viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                         stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="11" cy="11" r="7"/>
                        <path d="m21 21-4.3-4.3"/>
                    </svg>

                    <input
                        type="search"
                        name="q"
                        value="{{ $searchTerm }}"
                        placeholder="Rechercher un plat, une boisson..."
                        aria-label="Rechercher un plat ou une boisson"
                        class="w-full rounded-full border border-coffee/10 bg-white py-3 pl-11 pr-4 text-sm text-coffee shadow-[0_10px_40px_-16px_rgba(59,35,20,0.28)] placeholder:text-coffee/40 focus:border-amber/60 focus:outline-none focus:ring-2 focus:ring-amber/30 sm:py-3.5"
                    >
                </div>

                <button
                    type="submit"
                    class="inline-flex h-11 items-center justify-center gap-2 rounded-full bg-coffee px-5 text-sm font-semibold text-cream transition duration-150 hover:bg-amber-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-amber/40"
                    aria-label="Lancer la recherche"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="11" cy="11" r="7"/>
                        <path d="m21 21-4.3-4.3"/>
                    </svg>
                    Rechercher
                </button>

                <a
                    href="{{ route('menu.index') }}"
                    class="inline-flex h-11 items-center justify-center rounded-full border border-coffee/15 bg-white px-5 text-sm font-medium text-coffee transition duration-150 hover:border-amber/60 hover:bg-cream/70 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber/40"
                >
                    Réinitialiser
                </a>

            </form>

            {{-- =================================================
                 FILTRES PAR CATÉGORIE
            ================================================== --}}
            <section class="mt-8 sm:mt-10" x-data="{ expanded: false }" aria-label="Filtrer les plats par catégorie">

                <div class="flex flex-col items-center gap-5">

                    <div class="flex items-center gap-3">
                        <span class="h-px w-6 bg-amber/60"></span>
                        <h2 class="text-xs font-semibold uppercase tracking-[0.2em] text-coffee/65">
                            Explorer notre carte
                        </h2>
                        <span class="h-px w-6 bg-amber/60"></span>
                    </div>

                    <div class="flex w-full flex-wrap justify-center gap-2">

                        {{-- Toutes les catégories --}}
                        <a
                            href="{{ route('menu.index', array_filter(['q' => $searchTerm])) }}"
                            @class([
                                'menu-reveal inline-flex h-9 items-center gap-2 rounded-full px-4 text-sm font-medium ring-1 transition duration-150 sm:px-5',
                                'bg-coffee text-cream ring-coffee' => !$activeCategory,
                                $chipIdle => (bool) $activeCategory,
                            ])
                            style="--d: .05s"
                            @if (!$activeCategory) aria-current="page" @endif
                        >
                            <span class="h-1.5 w-1.5 rounded-full {{ !$activeCategory ? 'bg-amber' : 'bg-coffee/25' }}"></span>
                            Toutes
                        </a>

                        {{-- Dix premières catégories --}}
                        @foreach ($visibleCategories as $category)

                            @php
                                $isActive = $activeCategory === $category->slug;
                                $label = $labels[$category->slug] ?? $category->name;
                            @endphp

                            <a
                                href="{{ route('menu.index', array_filter(['category' => $category->slug, 'q' => $searchTerm])) }}"
                                @class([
                                    'menu-reveal inline-flex h-9 items-center gap-2 rounded-full px-4 text-sm font-medium ring-1 transition duration-150 sm:px-5',
                                    'bg-coffee text-cream ring-coffee' => $isActive,
                                    $chipIdle => !$isActive,
                                ])
                                style="--d: {{ min($loop->index, 8) * 0.03 + 0.06 }}s"
                                @if ($isActive) aria-current="page" @endif
                            >
                                <span class="h-1.5 w-1.5 rounded-full {{ $isActive ? 'bg-amber' : 'bg-coffee/25' }}"></span>
                                {{ $label }}
                            </a>

                        @endforeach

                        {{-- Catégories supplémentaires --}}
                        @foreach ($hiddenCategories as $category)

                            @php
                                $isActive = $activeCategory === $category->slug;
                                $label = $labels[$category->slug] ?? $category->name;
                            @endphp

                            <a
                                x-cloak
                                x-show="expanded"
                                x-transition:enter="transition duration-150 ease-out"
                                x-transition:enter-start="opacity-0"
                                x-transition:enter-end="opacity-100"
                                href="{{ route('menu.index', array_filter(['category' => $category->slug, 'q' => $searchTerm])) }}"
                                @class([
                                    'inline-flex h-9 items-center gap-2 rounded-full px-4 text-sm font-medium ring-1 transition duration-150 sm:px-5',
                                    'bg-coffee text-cream ring-coffee' => $isActive,
                                    $chipIdle => !$isActive,
                                ])
                                @if ($isActive) aria-current="page" @endif
                            >
                                <span class="h-1.5 w-1.5 rounded-full {{ $isActive ? 'bg-amber' : 'bg-coffee/25' }}"></span>
                                {{ $label }}
                            </a>

                        @endforeach

                        {{-- Boutons pour développer ou réduire --}}
                        @if ($hiddenCategories->isNotEmpty())

                            <button
                                type="button"
                                x-cloak
                                x-show="!expanded"
                                @click="expanded = true"
                                class="inline-flex h-9 items-center gap-2 rounded-full border border-dashed border-coffee/25 bg-white/60 px-4 text-sm font-medium text-coffee/75 transition duration-150 hover:border-amber/70 hover:text-coffee sm:px-5"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M12 5v14M5 12h14"/>
                                </svg>
                                Voir toutes les catégories
                            </button>

                            <button
                                type="button"
                                x-cloak
                                x-show="expanded"
                                @click="expanded = false"
                                class="inline-flex h-9 items-center gap-2 rounded-full border border-dashed border-coffee/25 bg-white/60 px-4 text-sm font-medium text-coffee/75 transition duration-150 hover:border-amber/70 hover:text-coffee sm:px-5"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M5 12h14"/>
                                </svg>
                                Réduire les catégories
                            </button>

                        @endif

                    </div>

                </div>

            </section>

            {{-- =================================================
                 COMPTEUR DE RÉSULTATS
            ================================================== --}}
            <div class="mt-10 flex items-center gap-4 sm:mt-12">

                <span class="h-px flex-1 bg-gradient-to-r from-transparent to-coffee/15"></span>

                <p class="shrink-0 text-center text-[13px] text-coffee/55 sm:text-sm">
                    <span class="font-display text-2xl font-medium text-coffee sm:text-[1.8rem]">
                        {{ $menus->total() }}
                    </span>
                    {{ $menus->total() > 1 ? 'plats trouvés' : 'plat trouvé' }}
                    @if ($searchTerm)
                        <span class="text-coffee/45">pour</span>
                        <span class="font-medium text-amber-dark">« {{ $searchTerm }} »</span>
                    @endif
                </p>

                <span class="h-px flex-1 bg-gradient-to-l from-transparent to-coffee/15"></span>

            </div>

            {{-- =================================================
                 GRILLE DES PLATS
            ================================================== --}}
            @if ($menus->isEmpty())

                {{-- Aucun résultat --}}
                <div class="mx-auto mt-10 max-w-2xl rounded-3xl border border-coffee/10 bg-white/80 px-6 py-14 text-center shadow-sm sm:px-12">

                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-amber/10 text-amber-dark"
                         aria-hidden="true">

                        <svg class="h-7 w-7"
                             viewBox="0 0 24 24"
                             fill="none"
                             stroke="currentColor"
                             stroke-width="1.5"
                             stroke-linecap="round"
                             stroke-linejoin="round">
                            <circle cx="11" cy="11" r="7"/>
                            <path d="m21 21-4.3-4.3"/>
                            <path d="M8.5 11h5"/>
                        </svg>

                    </div>

                    <h2 class="mt-5 font-display text-2xl text-coffee sm:text-3xl">
                        Aucun plat ne correspond
                    </h2>

                    <p class="mx-auto mt-3 max-w-md text-sm leading-6 text-coffee/60">
                        Aucun résultat ne correspond à votre recherche. Essayez un autre terme ou réinitialisez les filtres.
                    </p>

                    <a
                        href="{{ route('menu.index') }}"
                        class="mt-7 inline-flex items-center justify-center gap-2 rounded-full bg-coffee px-6 py-3 text-sm font-semibold text-white transition duration-300 hover:bg-amber-dark hover:shadow-md"
                    >
                        Réinitialiser les filtres

                        <svg class="h-4 w-4"
                             viewBox="0 0 24 24"
                             fill="none"
                             stroke="currentColor"
                             stroke-width="1.8"
                             stroke-linecap="round"
                             stroke-linejoin="round"
                             aria-hidden="true">
                            <path d="M5 12h14m-6-6 6 6-6 6"/>
                        </svg>
                    </a>

                </div>

            @else

                {{-- Cartes des plats --}}
                <div class="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">

                    @foreach ($menus as $menu)

                        <div class="menu-reveal min-w-0" style="--d: {{ min($loop->index, 8) * 0.04 }}s">
                            <x-menu-card :menu="$menu" />
                        </div>

                    @endforeach

                </div>

                {{-- Pagination --}}
                @if ($menus->hasPages())
                    <div class="mt-14 sm:mt-16">
                        {{ $menus->links('pagination.coffee') }}
                    </div>
                @endif

            @endif

        </div>

    </div>

@endsection