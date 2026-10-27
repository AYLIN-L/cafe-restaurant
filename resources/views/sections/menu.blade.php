<section id="menu" class="relative overflow-hidden bg-cream py-16 scroll-mt-20 sm:py-24">

    {{-- Lueurs décoratives --}}
    <div class="pointer-events-none absolute -left-40 top-10 h-80 w-80 rounded-full bg-amber/[0.07] blur-3xl" aria-hidden="true"></div>
    <div class="pointer-events-none absolute -right-32 bottom-0 h-80 w-80 rounded-full bg-coffee/[0.05] blur-3xl" aria-hidden="true"></div>

    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

        {{-- ========== EN-TÊTE DE SECTION ========== --}}
        <div class="reveal flex flex-col gap-7 lg:flex-row lg:items-end lg:justify-between">

            {{-- Titre + tagline --}}
            <div class="max-w-2xl">
                <div class="flex items-center gap-3 text-[11px] font-semibold uppercase tracking-[0.24em] text-amber-dark sm:text-xs">
                    <span class="h-px w-10 bg-amber/70" aria-hidden="true"></span>
                    Les favoris de nos clients
                </div>

                <h2 class="mt-4 font-display text-4xl leading-[1.08] tracking-tight text-coffee sm:text-5xl">
                    Nos plats les plus appréciés
                </h2>

                <p class="mt-4 max-w-xl text-base leading-7 text-coffee/60 sm:text-lg sm:leading-8">
                    Les saveurs que nos clients aiment retrouver, jour après jour.
                </p>
            </div>

            {{-- Lien vers le catalogue complet --}}
            <a
                href="{{ route('menu.index') }}"
                class="group inline-flex shrink-0 items-center justify-center gap-2.5 self-start rounded-full border border-coffee/15 bg-white/80 px-5 py-2.5 text-sm font-semibold text-coffee transition duration-200 hover:border-amber/60 hover:bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-amber/40 focus-visible:ring-offset-2 focus-visible:ring-offset-cream lg:self-auto"
            >
                <span>Voir tous les plats</span>
                <svg class="h-4 w-4 transition duration-200 motion-safe:group-hover:translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M5 12h14M13 6l6 6-6 6" />
                </svg>
            </a>

        </div>

        {{-- ========== GRILLE DES PLATS POPULAIRES ========== --}}
        <div class="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 lg:gap-7">

            @forelse ($menus as $menu)
                <div class="reveal min-w-0" style="--d: {{ min($loop->index, 6) * 0.06 }}s">
                    <x-menu-card :menu="$menu" />
                </div>
            @empty
                <div class="col-span-full rounded-2xl border border-dashed border-coffee/20 bg-white/70 px-6 py-12 text-center">
                    <p class="font-display text-2xl text-coffee">Aucun plat disponible pour le moment</p>
                    <p class="mt-2 text-sm leading-6 text-coffee/55">
                        Notre carte se prépare. Revenez très bientôt pour la découvrir.
                    </p>
                </div>
            @endforelse

        </div>

    </div>
</section>
