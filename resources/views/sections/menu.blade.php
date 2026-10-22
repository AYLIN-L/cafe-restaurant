<section id="menu" class="mx-auto max-w-7xl px-4 py-16 scroll-mt-16">

    {{-- ========== EN-TÊTE DE SECTION ========== --}}
    <div class="reveal flex flex-col gap-6 md:flex-row md:items-end md:justify-between">

        {{-- Titre + tagline (alignés à gauche) --}}
        <div class="max-w-2xl">
            <span class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-dark">
                Les favoris de nos clients
            </span>

            <h2 class="mt-3 font-display text-4xl leading-tight text-coffee sm:text-5xl">
                Nos plats les plus appréciés
            </h2>

            <p class="mt-4 text-lg leading-relaxed text-gray-500">
                Les saveurs que nos clients aiment retrouver, jour après jour.
            </p>
        </div>

        {{-- Lien vers le catalogue complet (aligné à droite sur desktop) --}}
        <a
            href="{{ route('menu.index') }}"
            class="group inline-flex shrink-0 items-center gap-2 rounded-xl border border-coffee/15 px-5 py-3 text-sm font-semibold text-coffee transition duration-300 hover:border-amber hover:bg-amber hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-light"
        >
            <span>Voir tous les plats</span>
            <svg class="h-4 w-4 transition duration-300 motion-safe:group-hover:translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M5 12h14M13 6l6 6-6 6" />
            </svg>
        </a>

    </div>

    {{-- ========== GRILLE DES PLATS POPULAIRES ========== --}}
    <div class="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">

        @forelse ($menus as $menu)
            <div
                class="reveal"
                style="--d: {{ min($loop->index, 6) * 0.08 }}s"
            >
                <x-menu-card :menu="$menu" />
            </div>
        @empty
            <p class="col-span-full rounded-2xl border border-coffee/10 bg-white p-8 text-center text-gray-500">
                Aucun plat disponible pour le moment.
            </p>
        @endforelse

    </div>
</section>
