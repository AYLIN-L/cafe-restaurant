@php($restaurant = config('restaurant'))

<section id="hero"
    class="relative min-h-[calc(100vh-80px)] overflow-hidden bg-coffee bg-cover bg-center"
    style="background-image: url('{{ asset('images/hero-image1.jpg') }}');"
>

    {{-- ========== CALQUES DE DÉGRADÉ (effet ombre sur l'image) ========== --}}

    {{-- 1. Teinte globale : réchauffe légèrement la photo --}}
    <div class="pointer-events-none absolute inset-0 bg-coffee/20"></div>

    {{-- 2. Ombre large et douce : s'étend sur toute l'image pour un fondu naturel --}}
    <div class="pointer-events-none absolute inset-0 bg-gradient-to-r from-coffee/85 via-coffee/40 to-transparent"></div>

    {{-- 3. Ombre dense à gauche : garantit la lisibilité du texte --}}
    <div class="pointer-events-none absolute inset-y-0 left-0 w-[65%] bg-gradient-to-r from-coffee from-10% via-coffee/80 via-50% to-transparent"></div>

    {{-- 4. Lueur ambrée sur la droite : ajoute de la chaleur à la photo --}}
    <div class="pointer-events-none absolute inset-y-0 right-0 w-1/3 bg-gradient-to-l from-amber/10 to-transparent"></div>

    {{-- 5. Vignette du haut : adoucit le bord supérieur --}}
    <div class="pointer-events-none absolute inset-x-0 top-0 h-32 bg-gradient-to-b from-coffee/40 to-transparent"></div>

    {{-- 6. Vignette du bas : améliore la lecture de l'adresse et de la note --}}
    <div class="pointer-events-none absolute inset-x-0 bottom-0 h-48 bg-gradient-to-t from-charcoal/60 via-charcoal/20 to-transparent"></div>


    {{-- ========== CONTENU DU HERO ========== --}}
    <div class="container relative z-10 flex min-h-[calc(100vh-80px)] items-center">
        <div class="max-w-2xl py-16 pl-4 sm:pl-0 lg:pl-12">

            {{-- Logo --}}

            {{-- Horaires d'ouverture --}}
            <div class="mb-6 inline-flex items-center rounded-full bg-cream px-3.5 py-1.5 text-sm font-medium text-amber-dark">
               Chaque jour · de 8h à 22h
            </div>

            {{-- Titre principal --}}
            <h1 class="font-display text-6xl leading-[1.05] tracking-tight text-white sm:text-7xl lg:text-[5.5rem]">
                <span class="block">Bon goût.</span>
                <span class="block italic text-amber-light">Bons moments.</span>
            </h1>

            {{-- Description --}}
            <p class="mt-8 max-w-xl text-lg leading-relaxed text-white/75 sm:text-xl sm:leading-8">
           Un café de quartier et une cuisine qui proposent des plats soignés, un excellent café et un accueil chaleureux, du matin au soir.
            </p>

            {{-- Boutons d'action --}}
            <div class="mt-10 flex flex-wrap gap-3">

                {{-- Bouton principal : réservation --}}
                <a
                    href="{{ route('reservations.index') }}"
                    class="inline-flex items-center gap-2.5 rounded-xl bg-amber px-6 py-3.5 text-[15px] font-semibold text-white transition duration-200 hover:bg-amber-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-light focus-visible:ring-offset-2 focus-visible:ring-offset-coffee"
                >
                    <span>Réserver une table</span>
                    {{-- Icône flèche --}}
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 12h14M13 6l6 6-6 6" />
                    </svg>
                </a>

                {{-- Bouton secondaire : menu --}}
                <a
                    href="{{ route('home') }}#menu"
                    class="inline-flex items-center rounded-xl border border-white/40 px-6 py-3.5 text-[15px] font-semibold text-white transition duration-200 hover:bg-white hover:text-coffee focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-coffee"
                >
                    Découvrir le menu
                </a>

            </div>

            {{-- Adresse et note --}}
            <div class="mt-12 flex flex-wrap items-center gap-x-8 gap-y-4 text-[15px] text-white/70">

                {{-- Adresse --}}
                <div class="flex items-center gap-2.5">
                    {{-- Icône localisation --}}
                    <svg class="h-[18px] w-[18px] text-amber-light" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0Z" />
                        <circle cx="12" cy="10" r="3" />
                    </svg>
                    <span>{{ $restaurant['address']['line1'] }}, {{ $restaurant['address']['city'] }}</span>
                </div>

                {{-- Note clients --}}
                <div class="flex items-center gap-2.5">
                    {{-- Icône étoile --}}
                    <svg class="h-[18px] w-[18px] text-amber-light" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="m12 2 3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2Z" />
                    </svg>
                    <span>4.9 · 420+ reviews</span>
                </div>

            </div>

        </div>
    </div>
</section>