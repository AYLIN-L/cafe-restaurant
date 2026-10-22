@props(['menu', 'admin' => false])

{{-- Carte de plat réutilisable (accueil + catalogue) --}}
<article
    class="group flex h-full w-full flex-col overflow-hidden rounded-2xl border border-coffee/10 bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-xl"
>

    {{-- ========== IMAGE + BADGES ========== --}}
    <div class="relative h-52 overflow-hidden bg-cream">

        @if ($menu->image)
            <img
                src="{{ asset('storage/' . $menu->image) }}"
                alt="{{ $menu->title }}"
                loading="lazy"
                class="h-full w-full object-cover transition duration-500 motion-safe:group-hover:scale-105"
            >
        @endif

        {{-- Badge catégorie (en haut à gauche) --}}
        <span
            class="absolute left-3 top-3 rounded-full bg-cream px-3 py-1 text-xs font-semibold text-amber-dark"
        >
            {{ $menu->category->name }}
        </span>

        {{-- Badge disponibilité (en haut à droite) selon is_available --}}
        @if ($menu->is_available)
            <span
                class="absolute right-3 top-3 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700"
            >
                En stock
            </span>
        @else
            <span
                class="absolute right-3 top-3 rounded-full bg-red-50 px-3 py-1 text-xs font-semibold text-red-700"
            >
                Rupture de stock
            </span>
        @endif

    </div>

    {{-- ========== CONTENU ========== --}}
    <div class="flex flex-1 flex-col p-6">

        {{-- Titre et prix --}}
        <div class="flex items-start justify-between gap-3">
            <h2 class="font-display text-2xl leading-tight text-coffee">
                {{ $menu->title }}
            </h2>

            <span class="shrink-0 pt-1 text-lg font-bold text-amber-dark">
                {{ number_format($menu->price, 2, ',', ' ') }} DH
            </span>
        </div>

        {{-- Description --}}
        <p class="mt-4 text-[15px] leading-relaxed text-gray-500">
            {{ $menu->description }}
        </p>

        {{-- Séparateur + bouton (poussé en bas pour aligner toutes les cards) --}}
        <div class="mt-auto border-t border-coffee/10 pt-4">
            <div class="flex items-center justify-between gap-3">
                <a
                    href="{{ route('menu.show', $menu) }}"
                    class="inline-flex items-center gap-2 text-sm font-semibold text-coffee transition duration-200 hover:text-amber-dark"
                >
                    <span>Voir détails</span>
                    {{-- Icône flèche --}}
                    <svg class="h-4 w-4 transition duration-200 motion-safe:group-hover:translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 12h14M13 6l6 6-6 6" />
                    </svg>
                </a>

                {{-- ===== Actions administrateur (jamais affichées côté public) ===== --}}
                @if ($admin)
                    <div x-data class="flex shrink-0 items-center gap-1">
                        {{-- Modifier --}}
                        <a
                            href="{{ route('admin.menus.edit', $menu) }}"
                            title="Modifier"
                            aria-label="Modifier {{ $menu->title }}"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-emerald-500 transition duration-200 hover:bg-emerald-50 hover:text-emerald-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500/40"
                        >
                            <x-lucide-pencil class="h-4 w-4" />
                        </a>

                        {{-- Supprimer --}}
                        <button
                            type="button"
                            title="Supprimer"
                            aria-label="Supprimer {{ $menu->title }}"
                            @click="$dispatch('open-delete-modal', {
                                action: @js(route('admin.menus.destroy', $menu)),
                                name: @js($menu->title),
                                warning: 'Les commentaires associés à ce plat seront également supprimés.'
                            })"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-red-600 transition duration-200 hover:bg-red-50 hover:text-red-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500/40"
                        >
                            <x-lucide-trash-2 class="h-4 w-4" />
                        </button>
                    </div>
                @endif
            </div>
        </div>

    </div>
</article>
