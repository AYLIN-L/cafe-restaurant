@props(['menu', 'admin' => false])

{{-- Carte de plat réutilisable (accueil + catalogue + administration) --}}
<article
    class="group/card relative flex h-full w-full flex-col overflow-hidden rounded-2xl border border-coffee/10 bg-white shadow-[0_1px_8px_-4px_rgba(59,35,20,0.18)] transition duration-200 hover:-translate-y-1 hover:border-amber/30 hover:shadow-[0_18px_40px_-22px_rgba(59,35,20,0.5)]"
>

    {{-- ========== IMAGE + BADGES ========== --}}
    <div class="relative aspect-[16/10] shrink-0 overflow-hidden bg-[#F4EEE4]">

        @if ($menu->image)
            <img
                src="{{ asset('storage/' . $menu->image) }}"
                alt="{{ $menu->title }}"
                loading="lazy"
                class="h-full w-full object-cover transition duration-500 ease-out motion-safe:group-hover/card:scale-[1.04]"
            >
        @else
            {{-- Placeholder élégant, cohérent avec la palette --}}
            <div class="flex h-full w-full items-center justify-center bg-[radial-gradient(circle_at_top,rgba(249,246,239,1)_0%,rgba(239,232,220,1)_100%)]"
                 aria-hidden="true">
                <div class="flex flex-col items-center gap-2 text-center">
                    <div class="flex h-14 w-14 items-center justify-center rounded-full border border-coffee/10 bg-white/70 text-coffee/30 shadow-sm">
                        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 8h1a4 4 0 1 1 0 8h-1"/>
                            <path d="M3 8h14v9a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4Z"/>
                            <path d="M6 2v2M10 2v2M14 2v2"/>
                        </svg>
                    </div>
                    <span class="text-[10px] font-medium uppercase tracking-[0.18em] text-coffee/30">Café Resto</span>
                </div>
            </div>
        @endif

        {{-- Lisibilité des badges --}}
        <div class="pointer-events-none absolute inset-x-0 top-0 h-20 bg-gradient-to-b from-coffee/25 to-transparent" aria-hidden="true"></div>
        <div class="pointer-events-none absolute inset-x-0 bottom-0 h-16 bg-gradient-to-t from-coffee/15 to-transparent" aria-hidden="true"></div>

        {{-- Badges du haut --}}
        <div class="absolute inset-x-3 top-3 flex items-start justify-between gap-2">

            {{-- Badge catégorie --}}
            <span class="max-w-[65%] truncate rounded-full bg-cream/95 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-coffee shadow-sm ring-1 ring-white/60">
                {{ $menu->category->name }}
            </span>

            {{-- Badge disponibilité --}}
            @if ($menu->is_available)
                <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-white/95 px-2.5 py-1 text-[10px] font-semibold text-emerald-700 shadow-sm ring-1 ring-emerald-600/10">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                    En stock
                </span>
            @else
                <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-white/95 px-2.5 py-1 text-[10px] font-semibold text-red-700 shadow-sm ring-1 ring-red-600/10">
                    <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
                    Rupture de stock
                </span>
            @endif

        </div>

        {{-- Prix --}}
        <span class="absolute bottom-3 right-3 inline-flex items-baseline gap-1 rounded-full bg-cream/95 px-3 py-1.5 text-sm font-semibold text-coffee shadow-sm ring-1 ring-white/60">
            {{ number_format($menu->price, 2, ',', ' ') }}
            <span class="text-[9px] font-medium uppercase tracking-[0.16em] text-amber-dark">DH</span>
        </span>

    </div>

    {{-- ========== CONTENU ========== --}}
    <div class="flex flex-1 flex-col p-5">

        {{-- Titre --}}
        <h2 class="font-display text-[1.3rem] leading-snug text-coffee transition-colors duration-150 group-hover/card:text-amber-dark">
            {{ $menu->title }}
        </h2>

        {{-- Description --}}
        <p class="mt-2 overflow-hidden text-[13px] leading-6 text-coffee/60 [display:-webkit-box] [-webkit-box-orient:vertical] [-webkit-line-clamp:2]">
            {{ $menu->description }}
        </p>

        {{-- Lien (poussé en bas) --}}
        <div class="mt-auto pt-4">
            <div class="flex items-center justify-between gap-3 border-t border-coffee/10 pt-3.5">
                <a
                    href="{{ route('menu.show', $menu) }}"
                    class="inline-flex items-center gap-2 text-[12.5px] font-semibold uppercase tracking-[0.1em] text-coffee transition duration-150 hover:text-amber-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-amber/40"
                >
                    <span>Voir détails</span>
                    <svg class="h-4 w-4 transition duration-150 motion-safe:group-hover/card:translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 12h14M13 6l6 6-6 6" />
                    </svg>
                </a>

                {{-- ===== Actions administrateur ===== --}}
                @if ($admin)
                    <div x-data class="flex shrink-0 items-center gap-1">
                        <a
                            href="{{ route('admin.menus.edit', $menu) }}"
                            title="Modifier"
                            aria-label="Modifier {{ $menu->title }}"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-emerald-500 transition duration-150 hover:bg-emerald-50 hover:text-emerald-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500/40"
                        >
                            <x-lucide-pencil class="h-4 w-4" />
                        </a>

                        <button
                            type="button"
                            title="Supprimer"
                            aria-label="Supprimer {{ $menu->title }}"
                            @click="$dispatch('open-delete-modal', {
                                action: @js(route('admin.menus.destroy', $menu)),
                                name: @js($menu->title),
                                warning: 'Les commentaires associés à ce plat seront également supprimés.'
                            })"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-red-600 transition duration-150 hover:bg-red-50 hover:text-red-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500/40"
                        >
                            <x-lucide-trash-2 class="h-4 w-4" />
                        </button>
                    </div>
                @endif
            </div>
        </div>

    </div>
</article>
