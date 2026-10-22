<x-admin-layout title="Modifier la table">

    <div class="mx-auto max-w-2xl">

        {{-- Lien retour --}}
        <a href="{{ route('admin.tables.index') }}"
           class="inline-flex items-center gap-1.5 text-sm font-medium text-charcoal/60 transition hover:text-amber-dark">
            <x-icon name="arrow-right" class="h-4 w-4 rotate-180" />
            Retour aux tables
        </a>

        {{-- En-tête --}}
        <h1 class="mt-4 font-display text-3xl text-coffee sm:text-4xl">Table n°{{ $table->number }}</h1>
        <p class="mt-2 text-sm text-charcoal/60">
            Modifiez le numéro, la capacité ou l'état de la table.
        </p>

        {{-- ========== FORMULAIRE ========== --}}
        <div class="mt-8 rounded-2xl border border-coffee/10 bg-white p-6 shadow-sm sm:p-8">

            <form action="{{ route('admin.tables.update', $table) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                {{-- Numéro --}}
                <div>
                    <label for="number" class="mb-2 block text-sm font-semibold text-coffee">
                        Numéro de table
                    </label>
                    <input
                        type="number"
                        id="number"
                        name="number"
                        value="{{ old('number', $table->number) }}"
                        min="1"
                        max="999"
                        required
                        autofocus
                        class="w-full rounded-xl border bg-white px-4 py-3.5 text-[15px] text-coffee placeholder:text-charcoal/40 transition duration-200 focus:border-amber focus:ring-2 focus:ring-amber/30 @error('number') border-red-400 @else border-coffee/15 @enderror"
                    >
                    @error('number')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Capacité --}}
                <div>
                    <label for="capacity" class="mb-2 block text-sm font-semibold text-coffee">
                        Capacité (nombre de places)
                    </label>
                    <input
                        type="number"
                        id="capacity"
                        name="capacity"
                        value="{{ old('capacity', $table->capacity) }}"
                        min="1"
                        max="20"
                        required
                        class="w-full rounded-xl border bg-white px-4 py-3.5 text-[15px] text-coffee placeholder:text-charcoal/40 transition duration-200 focus:border-amber focus:ring-2 focus:ring-amber/30 @error('capacity') border-red-400 @else border-coffee/15 @enderror"
                    >
                    @error('capacity')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- État --}}
                <div class="flex items-center gap-3">
                    <input type="hidden" name="is_active" value="0">
                    <input
                        type="checkbox"
                        id="is_active"
                        name="is_active"
                        value="1"
                        @checked(old('is_active', $table->is_active))
                        class="h-5 w-5 rounded border-coffee/20 text-amber focus:ring-amber/40"
                    >
                    <label for="is_active" class="text-sm font-medium text-coffee">
                        Table opérationnelle (active)
                    </label>
                </div>

                {{-- Actions --}}
                <div class="flex flex-col-reverse gap-3 border-t border-coffee/10 pt-6 sm:flex-row sm:justify-between">
                    <button type="button"
                        @click="$dispatch('open-delete-modal', {
                            action: @js(route('admin.tables.destroy', $table)),
                            name: @js('la table n°'.$table->number),
                            warning: ''
                        })"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-red-200 px-5 py-3 text-sm font-semibold text-red-600 transition duration-200 hover:bg-red-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500/40">
                        <x-lucide-trash-2 class="h-4 w-4" />
                        Supprimer
                    </button>

                    <div class="flex flex-col-reverse gap-3 sm:flex-row">
                        <a href="{{ route('admin.tables.index') }}"
                           class="inline-flex items-center justify-center rounded-xl border border-coffee/15 px-5 py-3 text-sm font-semibold text-coffee transition duration-200 hover:border-amber hover:text-amber-dark">
                            Annuler
                        </a>
                        <button type="submit"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-coffee px-6 py-3 text-sm font-semibold text-cream shadow-sm transition duration-200 hover:-translate-y-0.5 hover:bg-amber-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-light">
                            <x-lucide-save class="h-4 w-4" />
                            Enregistrer
                        </button>
                    </div>
                </div>
            </form>

        </div>
    </div>

    <x-delete-confirm-modal />

</x-admin-layout>
