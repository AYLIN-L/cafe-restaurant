@props(['title' => 'Confirmer la suppression'])

{{-- Boîte de confirmation réutilisable (administration).
     S'ouvre via l'événement : $dispatch('open-delete-modal', { action, name, warning }). --}}
<div
    x-data="{ open: false, action: '', name: '', warning: '' }"
    x-on:open-delete-modal.window="
        open = true;
        action = $event.detail.action;
        name = $event.detail.name;
        warning = $event.detail.warning || '';
    "
    x-on:keydown.escape.window="open = false"
    x-show="open"
    class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0"
    style="display: none;"
    role="dialog"
    aria-modal="true"
    aria-labelledby="delete-modal-title"
>
    {{-- Fond sombre --}}
    <div
        x-show="open"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="open = false"
        class="fixed inset-0 bg-coffee/50 backdrop-blur-sm"
    ></div>

    {{-- Panneau --}}
    <div
        x-show="open"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        class="relative mx-auto mt-16 w-full max-w-md overflow-hidden rounded-2xl border border-coffee/10 bg-white shadow-xl sm:mt-24"
    >
        <div class="p-6 sm:p-7">
            <div class="flex items-start gap-4">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-red-50 text-red-600">
                    <x-lucide-trash-2 class="h-5 w-5" />
                </span>

                <div class="min-w-0">
                    <h2 id="delete-modal-title" class="font-display text-xl text-coffee">{{ $title }}</h2>
                    <p class="mt-2 text-sm leading-relaxed text-charcoal/70">
                        Êtes-vous sûr de vouloir supprimer
                        «&nbsp;<span class="font-semibold text-coffee" x-text="name"></span>&nbsp;» ?
                        Cette action peut être irréversible.
                    </p>

                    {{-- Avertissement complémentaire (ex. suppression en cascade) --}}
                    <p
                        x-show="warning"
                        x-text="warning"
                        class="mt-3 rounded-lg bg-amber/10 px-3 py-2 text-xs font-medium leading-relaxed text-amber-dark"
                    ></p>
                </div>
            </div>
        </div>

        <form
            :action="action"
            method="POST"
            class="flex flex-col-reverse gap-3 border-t border-coffee/10 bg-cream/60 px-6 py-4 sm:flex-row sm:justify-end"
        >
            @csrf
            @method('DELETE')

            <button
                type="button"
                @click="open = false"
                class="inline-flex items-center justify-center rounded-xl border border-coffee/15 px-5 py-2.5 text-sm font-semibold text-coffee transition duration-200 hover:border-amber hover:text-amber-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-light"
            >
                Annuler
            </button>

            <button
                type="submit"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition duration-200 hover:-translate-y-0.5 hover:bg-red-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-400 focus-visible:ring-offset-2"
            >
                <x-lucide-trash-2 class="h-4 w-4" />
                Confirmer la suppression
            </button>
        </form>
    </div>
</div>
