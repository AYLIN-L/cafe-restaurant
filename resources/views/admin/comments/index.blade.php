@php
    $pendingCount = $stats['pending'] ?? 0;
    $approvedCount = $stats['approved'] ?? 0;
    $rejectedCount = $stats['rejected'] ?? 0;
    $hasFilters = ($filter ?? null) !== null || ($search ?? null) !== null;

    $stateMeta = [
        'pending' => [
            'label' => 'En attente',
            'classes' => 'bg-amber-50 text-amber-dark ring-amber/200',
            'dot' => 'bg-amber',
            'icon' => 'clock',
        ],
        'approved' => [
            'label' => 'Approuvé',
            'classes' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            'dot' => 'bg-emerald-500',
            'icon' => 'circle-check',
        ],
        'rejected' => [
            'label' => 'Rejeté',
            'classes' => 'bg-red-50 text-red-700 ring-red-200',
            'dot' => 'bg-red-500',
            'icon' => 'circle-x',
        ],
    ];
@endphp

<x-admin-layout title="Commentaires">

    {{-- En-tête --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="font-display text-2xl text-coffee sm:text-3xl">Modération des avis</h1>
            <p class="mt-1 text-sm text-charcoal/60">
                Seuls les avis approuvés sont visibles publiquement sur le site.
            </p>
        </div>
    </div>

    {{-- Statistiques (cliquables = filtres) --}}
    <div class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-3">
        @foreach (['pending' => 'En attente de modération', 'approved' => 'Approuvés (publics)', 'rejected' => 'Rejetés (masqués)'] as $key => $caption)
            @php $meta = $stateMeta[$key]; $count = $stats[$key] ?? 0; @endphp
            <a href="{{ route('admin.comments.index', array_filter(['filter' => $key, 'search' => $search])) }}"
                class="group flex items-center justify-between gap-3 rounded-2xl border bg-white px-4 py-3.5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md {{ ($filter ?? null) === $key ? 'border-amber ring-1 ring-amber/40' : 'border-coffee/10' }}">
                <span class="flex items-center gap-2.5">
                    <span class="h-2 w-2 rounded-full {{ $meta['dot'] }}"></span>
                    <span class="text-sm font-medium text-charcoal/70">{{ $caption }}</span>
                </span>
                <span class="font-display text-2xl tabular-nums text-coffee">{{ $count }}</span>
            </a>
        @endforeach
    </div>

    {{-- Recherche & filtre --}}
    <form method="GET" action="{{ route('admin.comments.index') }}" class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center">
        <div class="relative flex-1">
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-charcoal/40">
                <x-lucide-search class="h-4 w-4" />
            </span>
            <label for="search" class="sr-only">Rechercher un avis</label>
            <input id="search" name="search" type="search" value="{{ $search ?? '' }}"
                placeholder="Rechercher dans le contenu ou par nom de client..."
                class="w-full rounded-xl border border-coffee/15 bg-white py-2.5 pl-10 pr-4 text-sm text-coffee placeholder:text-charcoal/40 transition focus:border-amber focus:outline-none focus:ring-2 focus:ring-amber/30">
        </div>

        <div class="relative sm:w-52">
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-charcoal/40">
                <x-lucide-list-filter class="h-4 w-4" />
            </span>
            <label for="filter" class="sr-only">Filtrer les avis</label>
            <select id="filter" name="filter" onchange="this.form.submit()"
                class="w-full appearance-none rounded-xl border border-coffee/15 bg-white py-2.5 pl-10 pr-9 text-sm text-coffee transition focus:border-amber focus:outline-none focus:ring-2 focus:ring-amber/30">
                <option value="">Tous les avis</option>
                <option value="pending" @selected(($filter ?? null) === 'pending')>En attente</option>
                <option value="approved" @selected(($filter ?? null) === 'approved')>Approuvés</option>
                <option value="rejected" @selected(($filter ?? null) === 'rejected')>Rejetés</option>
            </select>
            <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-charcoal/40">
                <x-lucide-chevron-down class="h-4 w-4" />
            </span>
        </div>

        <button type="submit"
            class="inline-flex items-center justify-center gap-2 rounded-xl bg-coffee px-4 py-2.5 text-sm font-semibold text-cream shadow-sm transition hover:bg-amber-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-amber/40">
            <x-lucide-search class="h-4 w-4" />
            Rechercher
        </button>

        @if ($hasFilters)
            <a href="{{ route('admin.comments.index') }}"
                class="inline-flex items-center justify-center gap-2 rounded-xl border border-coffee/15 px-4 py-2.5 text-sm font-medium text-charcoal/70 transition hover:border-amber hover:text-amber-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-amber/40">
                <x-lucide-rotate-ccw class="h-4 w-4" />
                Réinitialiser
            </a>
        @endif
    </form>

    @if ($comments->isEmpty())
        {{-- État vide --}}
        <div class="mt-8 flex flex-col items-center gap-3 rounded-2xl border border-dashed border-coffee/15 bg-white px-6 py-16 text-center">
            <span class="inline-flex h-14 w-14 items-center justify-center rounded-full bg-cream text-charcoal/40">
                @if ($hasFilters)
                    <x-lucide-search-x class="h-7 w-7" />
                @else
                    <x-lucide-message-square class="h-7 w-7" />
                @endif
            </span>
            @if ($hasFilters)
                <h2 class="font-display text-lg text-coffee">Aucun avis ne correspond à votre recherche</h2>
                <p class="max-w-sm text-sm text-charcoal/60">Essayez un autre terme ou modifiez le filtre d'état.</p>
                <a href="{{ route('admin.comments.index') }}"
                    class="mt-2 inline-flex items-center gap-2 rounded-xl bg-amber px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-amber-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-amber/40">
                    <x-lucide-rotate-ccw class="h-4 w-4" />
                    Réinitialiser les filtres
                </a>
            @else
                <h2 class="font-display text-lg text-coffee">Aucun avis pour le moment</h2>
                <p class="max-w-sm text-sm text-charcoal/60">Les avis laissés par les clients apparaîtront ici pour modération.</p>
            @endif
        </div>
    @else
        {{-- Liste --}}
        <div class="mt-6 space-y-4">
            @foreach ($comments as $comment)
                @php
                    $state = $comment->moderationState();
                    $meta = $stateMeta[$state];
                @endphp
                <article class="rounded-2xl border border-coffee/10 bg-white p-4 shadow-sm sm:p-5">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">

                        {{-- Contenu de l'avis --}}
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $meta['classes'] }}">
                                    @svg('lucide-' . $meta['icon'], 'h-3.5 w-3.5 shrink-0')
                                    {{ $meta['label'] }}
                                </span>
                                <span class="font-semibold text-coffee">{{ $comment->user?->name ?? 'Client supprimé' }}</span>
                                <span class="text-xs text-charcoal/40">{{ $comment->created_at->translatedFormat('l j F Y à H:i') }}</span>
                            </div>

                            {{-- Note --}}
                            <div class="mt-2 flex items-center gap-1 text-amber" aria-label="Note : {{ $comment->rating }} sur 5">
                                @for ($i = 1; $i <= 5; $i++)
                                    @if ($i <= $comment->rating)
                                        <x-lucide-star class="h-4 w-4 fill-current" />
                                    @else
                                        <x-lucide-star class="h-4 w-4 text-charcoal/15" />
                                    @endif
                                @endfor
                                <span class="ml-1 text-xs font-medium text-charcoal/50">{{ $comment->rating }}/5</span>
                            </div>

                            {{-- Texte --}}
                            <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-charcoal/80">{{ $comment->content }}</p>

                            <p class="mt-2 text-xs text-charcoal/40">
                                @if ($comment->menu)
                                    Plat : <span class="font-medium text-charcoal/60">{{ $comment->menu->name }}</span>
                                @else
                                    Plat supprimé
                                @endif
                                @if ($comment->moderated_at)
                                    · Modéré le {{ $comment->moderated_at->translatedFormat('j F Y') }}
                                    @if ($comment->moderator)
                                        par {{ $comment->moderator->name }}
                                    @endif
                                @endif
                            </p>

                            @if ($comment->moderation_note)
                                <p class="mt-2 rounded-lg bg-cream px-3 py-2 text-xs text-charcoal/70">
                                    <span class="font-semibold text-coffee">Motif :</span> {{ $comment->moderation_note }}
                                </p>
                            @endif
                        </div>

                        {{-- Actions --}}
                        <div class="flex shrink-0 flex-wrap items-center gap-2">
                            @if ($comment->isApproved())
                                {{-- Approuvé : rien à faire, seulement la suppression --}}
                                <span class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700">
                                    <x-lucide-eye class="h-3.5 w-3.5" />
                                    Visible publiquement
                                </span>
                            @else
                                {{-- Approuver --}}
                                <form id="comment-{{ $comment->id }}-approve" method="POST" action="{{ route('admin.comments.approve', $comment) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                        class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-emerald-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500/40">
                                        <x-lucide-circle-check class="h-3.5 w-3.5" />
                                        Approuver
                                    </button>
                                </form>
                            @endif

                            @if ($comment->isApproved())
                                {{-- Repasser en attente de modération --}}
                                <form id="comment-{{ $comment->id }}-restore" method="POST" action="{{ route('admin.comments.restore', $comment) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="button"
                                        @click="$dispatch('open-confirm-modal', {
                                            formId: @js('comment-'.$comment->id.'-restore'),
                                            title: @js('Remettre en attente de modération'),
                                            message: @js('L\'avis ne sera plus visible publiquement et retournera dans la file de modération.'),
                                            label: @js('Remettre en attente'),
                                            tone: 'gray'
                                        })"
                                        class="inline-flex items-center gap-1.5 rounded-lg border border-coffee/15 px-3 py-2 text-xs font-semibold text-charcoal/70 transition hover:border-amber hover:text-amber-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-amber/40">
                                        <x-lucide-rotate-ccw class="h-3.5 w-3.5" />
                                        Remettre en attente
                                    </button>
                                </form>
                            @else
                                {{-- Rejeter (avec motif optionnel) --}}
                                <form id="comment-{{ $comment->id }}-reject" method="POST" action="{{ route('admin.comments.reject', $comment) }}"
                                    class="flex flex-wrap items-center gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <label for="comment-{{ $comment->id }}-note" class="sr-only">Motif du rejet</label>
                                    <input id="comment-{{ $comment->id }}-note" type="text" name="moderation_note" maxlength="255"
                                        placeholder="Motif (optionnel)"
                                        class="w-40 rounded-lg border border-coffee/15 bg-white px-2.5 py-1.5 text-xs text-coffee placeholder:text-charcoal/35 transition focus:border-amber focus:outline-none focus:ring-2 focus:ring-amber/30">
                                    <button type="button"
                                        @click="$dispatch('open-confirm-modal', {
                                            formId: @js('comment-'.$comment->id.'-reject'),
                                            title: @js('Rejeter l\'avis'),
                                            message: @js('L\'avis sera masqué du site public. Il reste consultable ici et peut être remis en attente de modération.'),
                                            label: @js('Rejeter l\'avis'),
                                            tone: 'red'
                                        })"
                                        class="inline-flex items-center gap-1.5 rounded-lg bg-red-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-red-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500/40">
                                        <x-lucide-circle-x class="h-3.5 w-3.5" />
                                        Rejeter
                                    </button>
                                </form>
                            @endif

                            {{-- Supprimer (définitif) --}}
                            <form id="comment-{{ $comment->id }}-destroy" method="POST" action="{{ route('admin.comments.destroy', $comment) }}">
                                @csrf
                                @method('DELETE')
                                <button type="button"
                                    @click="$dispatch('open-confirm-modal', {
                                        formId: @js('comment-'.$comment->id.'-destroy'),
                                        title: @js('Supprimer l\'avis'),
                                        message: @js('L\'avis sera définitivement supprimé. Cette action est irréversible : préférez le rejet si vous souhaitez conserver la trace.'),
                                        label: @js('Supprimer définitivement'),
                                        tone: 'red'
                                    })"
                                    title="Supprimer définitivement"
                                    aria-label="Supprimer définitivement l'avis de {{ $comment->user?->name }}"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-charcoal/40 transition hover:bg-red-50 hover:text-red-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500/40">
                                    <x-lucide-trash-2 class="h-4 w-4" />
                                </button>
                            </form>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        {{-- Pagination --}}
        @if ($comments->hasPages())
            <div class="mt-6">
                {{ $comments->links() }}
            </div>
        @endif
    @endif

    <x-confirm-action-modal />
</x-admin-layout>
