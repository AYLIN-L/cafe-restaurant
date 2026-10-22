<x-admin-layout title="Nouveau plat">

    <div class="reveal mx-auto max-w-3xl">

        {{-- Lien retour --}}
        <a href="{{ route('admin.menus.index') }}"
           class="inline-flex items-center gap-1.5 text-sm font-medium text-charcoal/60 transition hover:text-amber-dark">
            <x-icon name="arrow-right" class="h-4 w-4 rotate-180" />
            Retour aux plats
        </a>

        {{-- En-tête --}}
        <h1 class="mt-4 font-display text-3xl text-coffee sm:text-4xl">Nouveau plat</h1>
        <p class="mt-2 text-sm text-charcoal/60">
            Renseignez les informations du plat. Il sera affiché sur la carte publique.
        </p>

        {{-- ========== FORMULAIRE ========== --}}
        <div class="mt-8 rounded-2xl border border-coffee/10 bg-white p-6 shadow-sm sm:p-8">

            <form action="{{ route('admin.menus.store') }}" method="POST" enctype="multipart/form-data"
                  class="space-y-6" x-data="{ hasCategory: {{ old('category_id') ? 'true' : 'false' }} }">
                @csrf

                {{-- ========== CATÉGORIE ========== --}}
                <div>
                    <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                        <label for="category_id" class="flex items-center gap-2 text-sm font-semibold text-coffee">
                            Catégorie
                            {{-- Indicateur de sélection (accent émeraude) --}}
                            <span
                                x-show="hasCategory"
                                x-transition.opacity.duration.200ms
                                style="display: none;"
                                class="inline-flex items-center gap-1 text-xs font-medium text-emerald-500"
                            >
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M20 6 9 17l-5-5" />
                                </svg>
                                Sélectionnée
                            </span>
                        </label>

                        {{-- Ajout d'une catégorie : route existante --}}
                        <a
                            href="{{ route('admin.categories.create') }}"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-500/30 px-3 py-1.5 text-xs font-semibold text-emerald-600 transition duration-200 hover:border-emerald-500 hover:bg-emerald-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500/40"
                        >
                            <x-icon name="plus" class="h-3.5 w-3.5" />
                            Ajouter une catégorie
                        </a>
                    </div>

                    <select
                        id="category_id"
                        name="category_id"
                        required
                        @change="hasCategory = $event.target.value !== ''"
                        class="w-full rounded-xl border bg-white px-4 py-3.5 pr-10 text-[15px] text-coffee transition duration-200 focus:border-amber focus:ring-2 focus:ring-amber/30 @error('category_id') border-red-400 @else border-coffee/15 @enderror"
                    >
                        <option value="" disabled {{ old('category_id') ? '' : 'selected' }}>-- Choisir une catégorie --</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>

                    @error('category_id')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror

                    @if ($categories->isEmpty())
                        <p class="mt-2 flex items-center gap-1.5 text-xs text-charcoal/50">
                            <x-icon name="tag" class="h-3.5 w-3.5" />
                            Aucune catégorie pour le moment. Créez-en une avec le bouton ci-dessus.
                        </p>
                    @endif
                </div>

                {{-- ========== TITRE ========== --}}
                <div>
                    <label for="title" class="mb-2 block text-sm font-semibold text-coffee">
                        Nom du plat
                    </label>
                    <input
                        type="text"
                        id="title"
                        name="title"
                        value="{{ old('title') }}"
                        maxlength="255"
                        required
                        placeholder="Ex : Salade méditerranéenne"
                        class="w-full rounded-xl border bg-white px-4 py-3.5 text-[15px] text-coffee placeholder:text-charcoal/40 transition duration-200 focus:border-amber focus:ring-2 focus:ring-amber/30 @error('title') border-red-400 @else border-coffee/15 @enderror"
                    >
                    @error('title')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- ========== PRIX ========== --}}
                <div>
                    <label for="price" class="mb-2 block text-sm font-semibold text-coffee">
                        Prix
                    </label>
                    <div class="relative">
                        <input
                            type="number"
                            id="price"
                            name="price"
                            value="{{ old('price') }}"
                            min="0"
                            step="0.01"
                            required
                            inputmode="decimal"
                            placeholder="Ex : 45.00"
                            class="w-full rounded-xl border bg-white px-4 py-3.5 pr-14 text-[15px] text-coffee placeholder:text-charcoal/40 transition duration-200 focus:border-amber focus:ring-2 focus:ring-amber/30 @error('price') border-red-400 @else border-coffee/15 @enderror"
                        >
                        <span class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-sm font-semibold text-charcoal/40">
                            DH
                        </span>
                    </div>
                    @error('price')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- ========== DESCRIPTION ========== --}}
                <div>
                    <label for="description" class="mb-2 block text-sm font-semibold text-coffee">
                        Description <span class="font-normal text-charcoal/40">(facultatif)</span>
                    </label>
                    <textarea
                        id="description"
                        name="description"
                        rows="4"
                        placeholder="Décrivez le plat, ses ingrédients, sa particularité..."
                        class="w-full resize-none rounded-xl border bg-white px-4 py-3.5 text-[15px] leading-relaxed text-coffee placeholder:text-charcoal/40 transition duration-200 focus:border-amber focus:ring-2 focus:ring-amber/30 @error('description') border-red-400 @else border-coffee/15 @enderror"
                    >{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- ========== IMAGE ========== --}}
                <div>
                    <label for="image" class="mb-2 block text-sm font-semibold text-coffee">
                        Image <span class="font-normal text-charcoal/40">(facultatif, 2 Mo max)</span>
                    </label>
                    <input
                        type="file"
                        id="image"
                        name="image"
                        accept="image/*"
                        class="block w-full cursor-pointer rounded-xl border border-coffee/15 bg-white text-sm text-charcoal/60 transition duration-200 file:mr-4 file:cursor-pointer file:rounded-l-xl file:border-0 file:bg-cream file:px-4 file:py-3.5 file:text-sm file:font-semibold file:text-coffee hover:file:bg-amber/10 focus:border-amber focus:ring-2 focus:ring-amber/30 @error('image') border-red-400 @enderror"
                    >
                    @error('image')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- ========== DISPONIBILITÉ ========== --}}
                <div class="flex items-start gap-3 rounded-xl border border-coffee/10 bg-cream/60 p-4">
                    <input
                        type="checkbox"
                        id="is_available"
                        name="is_available"
                        value="1"
                        @checked(old('is_available', true))
                        class="mt-0.5 h-5 w-5 rounded border-coffee/25 text-emerald-500 transition focus:ring-2 focus:ring-emerald-500/40"
                    >
                    <label for="is_available" class="cursor-pointer select-none">
                        <span class="block text-sm font-semibold text-coffee">Disponible immédiatement</span>
                        <span class="block text-xs text-charcoal/50">
                            Décochez si le plat est momentanément indisponible.
                        </span>
                    </label>
                </div>

                {{-- ========== ACTIONS ========== --}}
                <div class="flex flex-col-reverse gap-3 border-t border-coffee/10 pt-6 sm:flex-row sm:justify-end">
                    <a href="{{ route('admin.menus.index') }}"
                       class="inline-flex items-center justify-center rounded-xl border border-coffee/15 px-5 py-3 text-sm font-semibold text-coffee transition duration-200 hover:border-amber hover:text-amber-dark">
                        Annuler
                    </a>
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-coffee px-6 py-3 text-sm font-semibold text-cream shadow-sm transition duration-200 hover:-translate-y-0.5 hover:bg-amber-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-light"
                    >
                        <x-icon name="plus" class="h-4 w-4" />
                        Créer le plat
                    </button>
                </div>

            </form>

        </div>
    </div>

</x-admin-layout>
