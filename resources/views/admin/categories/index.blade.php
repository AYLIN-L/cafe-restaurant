<x-admin-layout title="Gestion des catégories">

    {{-- ========== EN-TÊTE ========== --}}
    <div class="reveal flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm text-charcoal/60">Administration · Carte</p>
            <h1 class="mt-1 font-display text-3xl text-coffee sm:text-4xl">Gestion des catégories</h1>
            <p class="mt-2 text-sm text-charcoal/60">
                Organisez la carte en créant et en classant vos catégories.
            </p>
        </div>

        <a href="{{ route('admin.categories.create') }}"
           class="inline-flex items-center gap-2 self-start rounded-lg bg-coffee px-4 py-2.5 text-sm font-semibold text-cream shadow-sm transition hover:bg-amber-dark">
            <x-lucide-plus class="h-4 w-4" />
            Ajouter une catégorie
        </a>
    </div>

    {{-- ========== LISTE DES CATÉGORIES ========== --}}
    @if ($categories->isEmpty())
        <div class="reveal mt-8 rounded-2xl border border-coffee/10 bg-white p-12 text-center shadow-sm">
            <p class="font-display text-2xl text-coffee">Aucune catégorie pour le moment</p>
            <p class="mt-2 text-sm text-charcoal/60">
                Créez une première catégorie pour organiser vos plats.
            </p>
            <a href="{{ route('admin.categories.create') }}"
               class="mt-6 inline-flex items-center gap-2 rounded-xl bg-amber px-5 py-3 text-sm font-semibold text-white transition duration-200 hover:bg-amber-dark">
                <x-lucide-plus class="h-4 w-4" />
                Ajouter une catégorie
            </a>
        </div>
    @else
        <div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($categories as $category)
                @php $hasMenus = $category->menus_count > 0; @endphp
                <div class="reveal flex items-center justify-between gap-4 rounded-2xl border border-coffee/10 bg-white p-5 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:border-amber/40 hover:shadow-md"
                     style="--d: {{ min($loop->index, 6) * 0.05 }}s">

                    <div class="flex min-w-0 items-center gap-4">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-cream text-amber-dark ring-1 ring-coffee/10">
                            <x-lucide-tag class="h-5 w-5" />
                        </span>
                        <div class="min-w-0">
                            <h2 class="truncate font-display text-xl text-coffee">{{ $category->name }}</h2>
                            <p class="mt-0.5 text-xs text-charcoal/50">
                                {{ $category->menus_count }} plat{{ $category->menus_count > 1 ? 's' : '' }}
                            </p>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div x-data class="flex shrink-0 items-center gap-1">
                        {{-- Modifier --}}
                        <a href="{{ route('admin.categories.edit', $category) }}"
                           title="Modifier"
                           aria-label="Modifier {{ $category->name }}"
                           class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-emerald-500 transition duration-200 hover:bg-emerald-50 hover:text-emerald-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500/40">
                            <x-lucide-pencil class="h-4 w-4" />
                        </a>

                        {{-- Supprimer — protégé si la catégorie contient des plats --}}
                        @if ($hasMenus)
                            <button type="button"
                                    disabled
                                    title="Suppression impossible : cette catégorie contient des plats"
                                    aria-label="Suppression impossible : la catégorie &quot;{{ $category->name }}&quot; contient des plats"
                                    class="inline-flex h-9 w-9 cursor-not-allowed items-center justify-center rounded-lg text-charcoal/25">
                                <x-lucide-trash-2 class="h-4 w-4" />
                            </button>
                        @else
                            <button type="button"
                                    title="Supprimer"
                                    aria-label="Supprimer {{ $category->name }}"
                                    @click="$dispatch('open-delete-modal', {
                                        action: @js(route('admin.categories.destroy', $category)),
                                        name: @js($category->name),
                                        warning: ''
                                    })"
                                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-red-600 transition duration-200 hover:bg-red-50 hover:text-red-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500/40">
                                <x-lucide-trash-2 class="h-4 w-4" />
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        @if ($categories->hasPages())
            <div class="mt-10">
                {{ $categories->links() }}
            </div>
        @endif
    @endif

    {{-- ========== CONFIRMATION DE SUPPRESSION ========== --}}
    <x-delete-confirm-modal />

</x-admin-layout>
