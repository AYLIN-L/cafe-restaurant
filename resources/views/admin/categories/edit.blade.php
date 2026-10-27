<x-admin-layout title="Modifier la catégorie">

    <div class="reveal mx-auto max-w-2xl">

        {{-- Lien retour --}}
        <a href="{{ route('admin.categories.index') }}"
           class="inline-flex items-center gap-1.5 text-sm font-medium text-charcoal/60 transition hover:text-amber-dark">
            <x-lucide-arrow-right class="h-4 w-4 rotate-180" />
            Retour aux catégories
        </a>

        {{-- En-tête --}}
        <h1 class="mt-4 font-display text-3xl text-coffee sm:text-4xl">Modifier la catégorie</h1>
        <p class="mt-2 text-sm text-charcoal/60">
            Mettez à jour le nom de la catégorie «&nbsp;{{ $category->name }}&nbsp;».
        </p>

        {{-- ========== FORMULAIRE ========== --}}
        <div class="mt-8 rounded-2xl border border-coffee/10 bg-white p-6 shadow-sm sm:p-8">

            <form action="{{ route('admin.categories.update', $category) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                {{-- Nom --}}
                <div>
                    <label for="name" class="mb-2 block text-sm font-semibold text-coffee">
                        Nom de la catégorie
                    </label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="{{ old('name', $category->name) }}"
                        maxlength="255"
                        required
                        autofocus
                        placeholder="Ex : Desserts, Boissons..."
                        class="w-full rounded-xl border bg-white px-4 py-3.5 text-[15px] text-coffee placeholder:text-charcoal/40 transition duration-200 focus:border-amber focus:ring-2 focus:ring-amber/30 @error('name') border-red-400 @else border-coffee/15 @enderror"
                    >
                    @error('name')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-2 text-xs text-charcoal/50">
                        Le nom apparaîtra dans le menu et dans les filtres.
                    </p>
                </div>

                {{-- Actions --}}
                <div class="flex flex-col-reverse gap-3 border-t border-coffee/10 pt-6 sm:flex-row sm:justify-between">
                    {{-- Supprimer la catégorie (protégé si elle contient des plats) --}}
                    @if ($category->menus()->exists())
                        <button
                            type="button"
                            disabled
                            title="Suppression impossible : cette catégorie contient des plats"
                            class="inline-flex cursor-not-allowed items-center justify-center gap-2 rounded-xl border border-coffee/15 px-5 py-3 text-sm font-semibold text-charcoal/30"
                        >
                            <x-lucide-trash-2 class="h-4 w-4" />
                            Supprimer
                        </button>
                    @else
                        <button
                            type="button"
                            x-data
                            @click="$dispatch('open-delete-modal', {
                                action: @js(route('admin.categories.destroy', $category)),
                                name: @js($category->name),
                                warning: ''
                            })"
                            class="inline-flex items-center justify-center gap-2 rounded-xl border border-red-200 px-5 py-3 text-sm font-semibold text-red-600 transition duration-200 hover:border-red-400 hover:bg-red-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-400/40"
                        >
                            <x-lucide-trash-2 class="h-4 w-4" />
                            Supprimer
                        </button>
                    @endif

                    <div class="flex flex-col-reverse gap-3 sm:flex-row">
                        <a href="{{ route('admin.categories.index') }}"
                           class="inline-flex items-center justify-center rounded-xl border border-coffee/15 px-5 py-3 text-sm font-semibold text-coffee transition duration-200 hover:border-amber hover:text-amber-dark">
                            Annuler
                        </a>
                        <button
                            type="submit"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-coffee px-6 py-3 text-sm font-semibold text-cream shadow-sm transition duration-200 hover:-translate-y-0.5 hover:bg-amber-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-light"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M20 6 9 17l-5-5" />
                            </svg>
                            Enregistrer les modifications
                        </button>
                    </div>
                </div>
            </form>

        </div>
    </div>

    {{-- ========== CONFIRMATION DE SUPPRESSION ========== --}}
    <x-delete-confirm-modal />

</x-admin-layout>
