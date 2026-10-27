<x-admin-layout title="Gestion des plats">

    {{-- ========== EN-TÊTE ========== --}}
    <div class="reveal flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm text-charcoal/60">Administration · Carte</p>
            <h1 class="mt-1 font-display text-3xl text-coffee sm:text-4xl">Gestion des plats</h1>
            <p class="mt-2 text-sm text-charcoal/60">
                Ajoutez, modifiez ou supprimez les plats de la carte.
            </p>
        </div>

        <a href="{{ route('admin.menus.create') }}"
           class="inline-flex items-center gap-2 self-start rounded-lg bg-coffee px-4 py-2.5 text-sm font-semibold text-cream shadow-sm transition hover:bg-amber-dark">
            <x-lucide-plus class="h-4 w-4" />
            Ajouter un plat
        </a>
    </div>

    {{-- ========== LISTE DES PLATS ========== --}}
    @if ($menus->isEmpty())
        <div class="reveal mt-8 rounded-2xl border border-coffee/10 bg-white p-12 text-center shadow-sm">
            <p class="font-display text-2xl text-coffee">Aucun plat pour le moment</p>
            <p class="mt-2 text-sm text-charcoal/60">
                Commencez par ajouter votre premier plat à la carte.
            </p>
            <a href="{{ route('admin.menus.create') }}"
               class="mt-6 inline-flex items-center gap-2 rounded-xl bg-amber px-5 py-3 text-sm font-semibold text-white transition duration-200 hover:bg-amber-dark">
                <x-lucide-plus class="h-4 w-4" />
                Ajouter un plat
            </a>
        </div>
    @else
        <div class="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($menus as $menu)
                <div class="reveal" style="--d: {{ min($loop->index, 6) * 0.06 }}s">
                    <x-menu-card :menu="$menu" :admin="true" />
                </div>
            @endforeach
        </div>

        @if ($menus->hasPages())
            <div class="mt-10">
                {{ $menus->links() }}
            </div>
        @endif
    @endif

    {{-- ========== CONFIRMATION DE SUPPRESSION ========== --}}
    <x-delete-confirm-modal />

</x-admin-layout>
