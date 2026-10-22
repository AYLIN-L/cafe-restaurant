@php
    $absenceThreshold = (int) config('reservations.absence_threshold', 5);
    $hasFilters = ($search ?? null) !== null || ($filter ?? null) !== null;
@endphp

<x-admin-layout title="Clients">

    {{-- En-tête --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="font-display text-2xl text-coffee sm:text-3xl">Gestion des clients</h1>
            <p class="mt-1 text-sm text-charcoal/60">
                Suivez les réservations, les absences vérifiées et bloquez si nécessaire les comptes en règle.
            </p>
        </div>
        <p class="text-sm text-charcoal/50">
            {{ $totalClients }} client{{ $totalClients > 1 ? 's' : '' }}
            @if ($blockedClients > 0)
                · <span class="font-semibold text-red-600">{{ $blockedClients }} bloqué{{ $blockedClients > 1 ? 's' : '' }}</span>
            @endif
        </p>
    </div>

    {{-- Rappel de la règle --}}
    <div class="mt-6 flex items-start gap-3 rounded-xl border border-coffee/10 bg-white px-4 py-3 text-sm text-charcoal/70 shadow-sm">
        <span class="mt-0.5 text-amber-dark"><x-lucide-shield-check class="h-4 w-4" /></span>
        <span>
            Un client est automatiquement bloqué à partir de
            <strong>{{ $absenceThreshold }} absences vérifiées</strong>.
            Seules les réservations confirmées et marquées « absent » comptent : une demande expirée
            ou annulée ne pénalise jamais le client. Le déblocage reste une décision de l'administrateur.
        </span>
    </div>

    {{-- Recherche & filtre --}}
    <form method="GET" action="{{ route('admin.users.index') }}" class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center">
        <div class="relative flex-1">
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-charcoal/40">
                <x-lucide-search class="h-4 w-4" />
            </span>
            <label for="search" class="sr-only">Rechercher un client</label>
            <input id="search" name="search" type="search" value="{{ $search ?? '' }}"
                placeholder="Rechercher par nom, e-mail ou téléphone..."
                class="w-full rounded-xl border border-coffee/15 bg-white py-2.5 pl-10 pr-4 text-sm text-coffee placeholder:text-charcoal/40 transition focus:border-amber focus:outline-none focus:ring-2 focus:ring-amber/30">
        </div>

        <div class="relative sm:w-52">
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-charcoal/40">
                <x-lucide-list-filter class="h-4 w-4" />
            </span>
            <label for="filter" class="sr-only">Filtrer les clients</label>
            <select id="filter" name="filter" onchange="this.form.submit()"
                class="w-full appearance-none rounded-xl border border-coffee/15 bg-white py-2.5 pl-10 pr-9 text-sm text-coffee transition focus:border-amber focus:outline-none focus:ring-2 focus:ring-amber/30">
                <option value="">Tous les clients</option>
                <option value="blocked" @selected(($filter ?? null) === 'blocked')>Bloqués</option>
                <option value="unblocked" @selected(($filter ?? null) === 'unblocked')>Autorisés</option>
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
            <a href="{{ route('admin.users.index') }}"
                class="inline-flex items-center justify-center gap-2 rounded-xl border border-coffee/15 px-4 py-2.5 text-sm font-medium text-charcoal/70 transition hover:border-amber hover:text-amber-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-amber/40">
                <x-lucide-rotate-ccw class="h-4 w-4" />
                Réinitialiser
            </a>
        @endif
    </form>

    @if ($users->isEmpty())
        {{-- État vide --}}
        <div class="mt-8 flex flex-col items-center gap-3 rounded-2xl border border-dashed border-coffee/15 bg-white px-6 py-16 text-center">
            <span class="inline-flex h-14 w-14 items-center justify-center rounded-full bg-cream text-charcoal/40">
                @if ($hasFilters)
                    <x-lucide-search-x class="h-7 w-7" />
                @else
                    <x-lucide-users class="h-7 w-7" />
                @endif
            </span>
            @if ($hasFilters)
                <h2 class="font-display text-lg text-coffee">Aucun client ne correspond à votre recherche</h2>
                <p class="max-w-sm text-sm text-charcoal/60">Essayez un autre nom, un autre e-mail ou modifiez le filtre.</p>
                <a href="{{ route('admin.users.index') }}"
                    class="mt-2 inline-flex items-center gap-2 rounded-xl bg-amber px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-amber-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-amber/40">
                    <x-lucide-rotate-ccw class="h-4 w-4" />
                    Réinitialiser les filtres
                </a>
            @else
                <h2 class="font-display text-lg text-coffee">Aucun client pour le moment</h2>
                <p class="max-w-sm text-sm text-charcoal/60">Les clients s'inscrivant sur le site apparaîtront ici.</p>
            @endif
        </div>
    @else
        {{-- Tableau (écrans moyens et plus) --}}
        <div class="mt-6 hidden overflow-hidden rounded-2xl border border-coffee/10 bg-white shadow-sm md:block">
            <table class="min-w-full divide-y divide-coffee/10">
                <thead class="bg-cream/60">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-charcoal/50">Client</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-charcoal/50">Réservations</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-charcoal/50">Absences vérifiées</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-charcoal/50">État</th>
                        <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-charcoal/50">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-coffee/10">
                    @foreach ($users as $user)
                        @php
                            $blockState = $user->bookingBlockState();
                            $initials = collect(explode(' ', trim($user->name)))
                                ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))
                                ->take(2)->implode('');
                        @endphp
                        <tr class="transition-colors hover:bg-cream/40">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-coffee text-xs font-semibold text-cream">{{ $initials }}</span>
                                    <div class="min-w-0">
                                        <div class="font-semibold text-coffee">{{ $user->name }}</div>
                                        <div class="truncate text-xs text-charcoal/50">{{ $user->email }}</div>
                                        @if ($user->phone)
                                            <div class="truncate text-xs text-charcoal/40">{{ $user->phone }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-semibold tabular-nums text-coffee">{{ $user->reservations_count }}</div>
                                @if ($user->reservations_max_reservation_date)
                                    <a href="{{ route('admin.reservations.index', ['search' => $user->name]) }}"
                                        class="mt-0.5 inline-flex items-center gap-1 text-xs text-charcoal/50 transition hover:text-amber-dark">
                                        <x-lucide-history class="h-3.5 w-3.5" />
                                        dernière : {{ \Illuminate\Support\Carbon::parse($user->reservations_max_reservation_date)->format('d/m/Y') }}
                                    </a>
                                @else
                                    <span class="mt-0.5 block text-xs text-charcoal/30">aucune réservation</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <span class="font-semibold tabular-nums {{ $blockState['absences'] > 0 ? 'text-red-600' : 'text-charcoal/40' }}">
                                        {{ $blockState['absences'] }} / {{ $blockState['threshold'] }}
                                    </span>
                                    @if ($blockState['absences'] > 0)
                                        <span class="inline-flex h-1.5 w-1.5 rounded-full bg-red-500" title="Absences vérifiées"></span>
                                    @endif
                                </div>
                                <div class="mt-1 h-1.5 w-28 overflow-hidden rounded-full bg-coffee/10">
                                    <div class="h-full rounded-full {{ $blockState['absences'] >= $blockState['threshold'] ? 'bg-red-500' : 'bg-amber' }}"
                                        style="width: {{ min(100, (int) round($blockState['absences'] / max(1, $blockState['threshold']) * 100)) }}%"></div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                @if ($blockState['blocked'])
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700 ring-1 ring-inset ring-red-200">
                                        <x-lucide-ban class="h-3.5 w-3.5" />
                                        Bloqué
                                    </span>
                                    @if ($blockState['overridden'])
                                        <span class="mt-1 block text-xs text-charcoal/40">exempté manuellement</span>
                                    @endif
                                @else
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                                        <x-lucide-circle-check class="h-3.5 w-3.5" />
                                        Autorisé
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1.5">
                                    @if ($blockState['blocked'])
                                        <form id="user-{{ $user->id }}-unblock" method="POST" action="{{ route('admin.users.unblock', $user) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" title="Autoriser les nouvelles réservations"
                                                aria-label="Autoriser les nouvelles réservations de {{ $user->name }}"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-emerald-600 transition duration-200 hover:bg-emerald-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500/40">
                                                <x-lucide-lock-open class="h-[18px] w-[18px]" />
                                            </button>
                                        </form>
                                    @else
                                        <form id="user-{{ $user->id }}-block" method="POST" action="{{ route('admin.users.block', $user) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="button"
                                                @click="$dispatch('open-confirm-modal', {
                                                    formId: @js('user-'.$user->id.'-block'),
                                                    title: @js('Bloquer les réservations'),
                                                    message: @js('Les nouvelles réservations de '.$user->name.' seront refusées. Les réservations déjà prises restent valides.'),
                                                    label: @js('Bloquer'),
                                                    tone: 'red'
                                                })"
                                                title="Bloquer les nouvelles réservations"
                                                aria-label="Bloquer les nouvelles réservations de {{ $user->name }}"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-red-600 transition duration-200 hover:bg-red-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500/40">
                                                <x-lucide-lock class="h-[18px] w-[18px]" />
                                            </button>
                                        </form>
                                    @endif

                                    @if ($user->reservations_count === 0)
                                        <form id="user-{{ $user->id }}-delete" method="POST" action="{{ route('admin.users.destroy', $user) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button"
                                                @click="$dispatch('open-confirm-modal', {
                                                    formId: @js('user-'.$user->id.'-delete'),
                                                    title: @js('Supprimer le client'),
                                                    message: @js('Le compte de '.$user->name.' sera définitivement supprimé. Cette action est irréversible.'),
                                                    label: @js('Supprimer'),
                                                    tone: 'red'
                                                })"
                                                title="Supprimer le client"
                                                aria-label="Supprimer le client {{ $user->name }}"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-red-600 transition duration-200 hover:bg-red-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500/40">
                                                <x-lucide-trash-2 class="h-[18px] w-[18px]" />
                                            </button>
                                        </form>
                                    @else
                                        <button type="button" disabled
                                            title="Suppression impossible : ce client possède des réservations"
                                            aria-label="Suppression impossible : ce client possède des réservations"
                                            class="inline-flex h-9 w-9 cursor-not-allowed items-center justify-center rounded-lg text-charcoal/25">
                                            <x-lucide-trash-2 class="h-[18px] w-[18px]" />
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Cartes (mobile) --}}
        <div class="mt-6 space-y-4 md:hidden">
            @foreach ($users as $user)
                @php
                    $blockState = $user->bookingBlockState();
                    $initials = collect(explode(' ', trim($user->name)))
                        ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))
                        ->take(2)->implode('');
                @endphp
                <article class="rounded-2xl border border-coffee/10 bg-white p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-coffee text-xs font-semibold text-cream">{{ $initials }}</span>
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-coffee">{{ $user->name }}</p>
                                <p class="truncate text-xs text-charcoal/50">{{ $user->email }}</p>
                            </div>
                        </div>
                        @if ($blockState['blocked'])
                            <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700 ring-1 ring-inset ring-red-200">
                                <x-lucide-ban class="h-3.5 w-3.5" />
                                Bloqué
                            </span>
                        @else
                            <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                                <x-lucide-circle-check class="h-3.5 w-3.5" />
                                Autorisé
                            </span>
                        @endif
                    </div>

                    <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wider text-charcoal/40">Réservations</dt>
                            <dd class="mt-0.5 tabular-nums text-coffee">{{ $user->reservations_count }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wider text-charcoal/40">Absences vérifiées</dt>
                            <dd class="mt-0.5 tabular-nums {{ $blockState['absences'] > 0 ? 'text-red-600' : 'text-charcoal' }}">
                                {{ $blockState['absences'] }} / {{ $blockState['threshold'] }}
                            </dd>
                        </div>
                    </dl>

                    <div class="mt-4 flex items-center justify-end gap-2 border-t border-coffee/10 pt-3">
                        @if ($blockState['blocked'])
                            <form method="POST" action="{{ route('admin.users.unblock', $user) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 px-3 py-1.5 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-50">
                                    <x-lucide-lock-open class="h-4 w-4" />
                                    Débloquer
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('admin.users.block', $user) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 px-3 py-1.5 text-sm font-semibold text-red-600 transition hover:bg-red-50">
                                    <x-lucide-lock class="h-4 w-4" />
                                    Bloquer
                                </button>
                            </form>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>

        {{-- Pagination --}}
        @if ($users->hasPages())
            <div class="mt-6">
                {{ $users->links() }}
            </div>
        @endif
    @endif

    <x-confirm-action-modal />
</x-admin-layout>
