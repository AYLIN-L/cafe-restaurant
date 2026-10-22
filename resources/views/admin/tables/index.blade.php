<x-admin-layout title="Gestion de la salle">

    @php
        $totalSeats = $tables->sum('capacity');
        $activeTables = $tables->where('is_active', true)->count();

        $states = $tables->mapWithKeys(fn ($table) => [$table->id => $table->stateAt($now)]);

        $occupiedNow = $states->filter(fn ($state) => in_array($state, ['walk_in', 'occupied'], true))->count();
        $reservedNow = $states->filter(fn ($state) => $state === 'reserved')->count();
        $freeNow = $states->filter(fn ($state) => $state === 'free')->count();
        $seatsFree = $tables->filter(fn ($table) => $states[$table->id] === 'free')->sum('capacity');

        $stateMeta = [
            'inactive' => ['label' => 'Hors service', 'classes' => 'bg-gray-100 text-gray-500 ring-gray-200', 'dot' => 'bg-gray-400'],
            'walk_in'  => ['label' => 'Occupée (sans réservation)', 'classes' => 'bg-amber/10 text-amber-dark ring-amber/20', 'dot' => 'bg-amber'],
            'occupied' => ['label' => 'Occupée', 'classes' => 'bg-emerald-50 text-emerald-700 ring-emerald-200', 'dot' => 'bg-emerald-500'],
            'reserved' => ['label' => 'Réservée', 'classes' => 'bg-amber/10 text-amber-dark ring-amber/20', 'dot' => 'bg-amber'],
            'free'     => ['label' => 'Libre', 'classes' => 'bg-emerald-50 text-emerald-700 ring-emerald-200', 'dot' => 'bg-emerald-500'],
        ];

        $walkInStates = [
            'walk_in'  => ['label' => 'Client installé', 'classes' => 'bg-amber/10 text-amber-dark ring-amber/20', 'dot' => 'bg-amber'],
            'occupied' => ['label' => 'Réservation en cours', 'classes' => 'bg-emerald-50 text-emerald-700 ring-emerald-200', 'dot' => 'bg-emerald-500'],
            'reserved' => ['label' => 'Prochaine réservation', 'classes' => 'bg-amber/10 text-amber-dark ring-amber/20', 'dot' => 'bg-amber'],
            'free'     => ['label' => 'Libre', 'classes' => 'bg-emerald-50 text-emerald-700 ring-emerald-200', 'dot' => 'bg-emerald-500'],
        ];
    @endphp

    {{-- ========== EN-TÊTE ========== --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm text-charcoal/60">Administration · Salle</p>
            <h1 class="mt-1 font-display text-3xl text-coffee sm:text-4xl">Gestion des tables</h1>
            <p class="mt-2 text-sm text-charcoal/60">
                Gérez votre salle : capacité, occupation en temps réel et clients sans réservation.
            </p>
        </div>

        <a href="{{ route('admin.tables.create') }}"
           class="inline-flex items-center gap-2 self-start rounded-lg bg-coffee px-4 py-2.5 text-sm font-semibold text-cream shadow-sm transition hover:bg-amber-dark">
            <x-icon name="plus" class="h-4 w-4" />
            Ajouter une table
        </a>
    </div>

    {{-- ========== STATISTIQUES ========== --}}
    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-coffee/10 bg-white p-5 shadow-sm">
            <div class="flex items-center gap-3">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-cream text-amber-dark ring-1 ring-coffee/10">
                    <x-icon name="table" class="h-5 w-5" />
                </span>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-charcoal/50">Tables actives</p>
                    <p class="font-display text-2xl text-coffee">{{ $activeTables }} / {{ $tables->count() }}</p>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-coffee/10 bg-white p-5 shadow-sm">
            <div class="flex items-center gap-3">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-cream text-amber-dark ring-1 ring-coffee/10">
                    <x-lucide-users class="h-5 w-5" />
                </span>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-charcoal/50">Places assises</p>
                    <p class="font-display text-2xl text-coffee">{{ $totalSeats }}</p>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-coffee/10 bg-white p-5 shadow-sm">
            <div class="flex items-center gap-3">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-cream text-emerald-600 ring-1 ring-coffee/10">
                    <x-lucide-armchair class="h-5 w-5" />
                </span>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-charcoal/50">Occupées maintenant</p>
                    <p class="font-display text-2xl text-coffee">{{ $occupiedNow }}</p>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-coffee/10 bg-white p-5 shadow-sm">
            <div class="flex items-center gap-3">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-cream text-emerald-600 ring-1 ring-coffee/10">
                    <x-lucide-circle-check class="h-5 w-5" />
                </span>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-charcoal/50">Libres · places</p>
                    <p class="font-display text-2xl text-coffee">{{ $freeNow }} · {{ $seatsFree }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ========== CLIENTS SANS RÉSERVATION ========== --}}
    <div class="mt-8 rounded-2xl border border-coffee/10 bg-white p-5 shadow-sm sm:p-6">
        <h2 class="font-display text-xl text-coffee">Installer un client sans réservation</h2>
        <p class="mt-1 text-sm text-charcoal/60">
            Le système attribue automatiquement la plus petite table libre, sans conflit avec les réservations à venir.
        </p>

        <form action="{{ route('admin.walkins.store') }}" method="POST" class="mt-4 grid gap-4 sm:grid-cols-[7rem_8rem_1fr_auto] sm:items-end">
            @csrf
            <div>
                <label for="guests_count" class="mb-2 block text-sm font-semibold text-coffee">Convives</label>
                <input type="number" id="guests_count" name="guests_count" value="{{ old('guests_count') }}" min="1" max="20" required
                    class="w-full rounded-xl border bg-white px-4 py-3 text-[15px] text-coffee placeholder:text-charcoal/40 transition focus:border-amber focus:ring-2 focus:ring-amber/30 @error('guests_count') border-red-400 @else border-coffee/15 @enderror">
                @error('guests_count')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="seated_time" class="mb-2 block text-sm font-semibold text-coffee">
                    Heure d'arrivée <span class="font-normal text-charcoal/40">(facultatif)</span>
                </label>
                <input type="time" id="seated_time" name="seated_time" value="{{ old('seated_time', $now->format('H:i')) }}" step="60"
                    class="w-full rounded-xl border bg-white px-4 py-3 text-[15px] text-coffee placeholder:text-charcoal/40 transition focus:border-amber focus:ring-2 focus:ring-amber/30 @error('seated_time') border-red-400 @else border-coffee/15 @enderror">
                @error('seated_time')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-2 text-xs text-charcoal/50">
                    Par défaut, l'heure actuelle. La table reste bloquée jusqu'à sa libération explicite.
                </p>
            </div>
            <div>
                <label for="special_request" class="mb-2 block text-sm font-semibold text-coffee">
                    Note <span class="font-normal text-charcoal/40">(facultatif)</span>
                </label>
                <input type="text" id="special_request" name="special_request" value="{{ old('special_request') }}" maxlength="500"
                    placeholder="Ex : près de la fenêtre"
                    class="w-full rounded-xl border bg-white px-4 py-3 text-[15px] text-coffee placeholder:text-charcoal/40 transition focus:border-amber focus:ring-2 focus:ring-amber/30 @error('special_request') border-red-400 @else border-coffee/15 @enderror">
                @error('special_request')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-amber px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-amber/40">
                <x-lucide-user-plus class="h-4 w-4" />
                Installer
            </button>
        </form>

        {{-- Clients actuellement installés --}}
        @if ($activeWalkIns->isNotEmpty())
            <div class="mt-5 divide-y divide-coffee/10 border-t border-coffee/10">
                @foreach ($activeWalkIns as $walkIn)
                    @php $overdue = $walkIn->isOverdue($now); @endphp
                    <div class="flex flex-wrap items-center justify-between gap-3 py-3">
                        <div class="flex items-center gap-3">
                            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-cream text-amber-dark ring-1 ring-coffee/10">
                                <x-lucide-armchair class="h-4 w-4" />
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-coffee">
                                    Table n°{{ $walkIn->table?->number ?? '—' }} · {{ $walkIn->guests_count }} convive{{ $walkIn->guests_count > 1 ? 's' : '' }}
                                </p>
                                <p class="text-xs text-charcoal/50">
                                    Installé à {{ $walkIn->seated_at->format('H\hi') }}
                                    @if ($walkIn->special_request) · {{ $walkIn->special_request }} @endif
                                </p>
                                @if ($overdue)
                                    <p class="mt-1 inline-flex items-center gap-1.5 rounded-full bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700 ring-1 ring-inset ring-red-200">
                                        <x-lucide-triangle-alert class="h-3.5 w-3.5" />
                                        Dépassement : durée habituelle dépassée — pensez à libérer la table.
                                    </p>
                                @endif
                            </div>
                        </div>
                        <form method="POST" action="{{ route('admin.walkins.release', $walkIn) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 px-3 py-1.5 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500/40">
                                <x-lucide-circle-check class="h-4 w-4" />
                                Libérer la table
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- ========== LISTE DES TABLES ========== --}}
    @if ($tables->isEmpty())
        <div class="mt-8 rounded-2xl border border-coffee/10 bg-white p-12 text-center shadow-sm">
            <p class="font-display text-2xl text-coffee">Aucune table pour le moment</p>
            <p class="mt-2 text-sm text-charcoal/60">Ajoutez vos tables pour gérer la salle et les réservations.</p>
            <a href="{{ route('admin.tables.create') }}"
               class="mt-6 inline-flex items-center gap-2 rounded-xl bg-amber px-5 py-3 text-sm font-semibold text-white transition duration-200 hover:bg-amber-dark">
                <x-icon name="plus" class="h-4 w-4" />
                Ajouter une table
            </a>
        </div>
    @else
        <div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($tables as $table)
                @php
                    $state = $states[$table->id];
                    $meta = $stateMeta[$state] ?? $stateMeta['free'];
                    $next = $table->nextReservationAt($now);
                @endphp
                <div class="flex flex-col rounded-2xl border border-coffee/10 bg-white p-5 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:shadow-md">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-cream text-amber-dark ring-1 ring-coffee/10">
                                <x-icon name="table" class="h-5 w-5" />
                            </span>
                            <div>
                                <h2 class="font-display text-xl text-coffee">Table n°{{ $table->number }}</h2>
                                <p class="text-xs text-charcoal/50">{{ $table->capacity }} place{{ $table->capacity > 1 ? 's' : '' }}</p>
                            </div>
                        </div>

                        <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $meta['classes'] }}">
                            <span class="h-1.5 w-1.5 rounded-full {{ $meta['dot'] }}"></span>
                            {{ $meta['label'] }}
                        </span>
                    </div>

                    {{-- Occupation / prochaine réservation --}}
                    <div class="mt-4 space-y-1.5">
                        @if ($state === 'walk_in')
                            <p class="inline-flex items-center gap-1.5 text-sm font-medium text-amber-dark">
                                <x-lucide-armchair class="h-4 w-4" />
                                Table occupée
                            </p>
                        @elseif ($state === 'occupied')
                            <p class="inline-flex items-center gap-1.5 text-sm font-medium text-emerald-700">
                                <x-lucide-armchair class="h-4 w-4" />
                                Réservation en cours
                            </p>
                        @elseif ($state === 'reserved')
                            <p class="inline-flex items-center gap-1.5 text-sm text-charcoal/60">
                                <x-lucide-clock class="h-4 w-4 text-amber-dark" />
                                Réservée à {{ $next?->scheduledAt()->format('H\hi') ?? '—' }}
                            </p>
                        @elseif ($state === 'free')
                            <p class="inline-flex items-center gap-1.5 text-sm text-charcoal/50">
                                <x-lucide-circle-check class="h-4 w-4 text-emerald-500" />
                                Libre
                                @if ($next)
                                    · réservée à {{ $next->scheduledAt()->format('H\hi') }}
                                @endif
                            </p>
                        @else
                            <p class="inline-flex items-center gap-1.5 text-sm text-charcoal/40">
                                <x-lucide-ban class="h-4 w-4" />
                                Hors service
                            </p>
                        @endif
                    </div>

                    {{-- Actions --}}
                    <div class="mt-4 flex items-center justify-end gap-1 border-t border-coffee/10 pt-3">
                        {{-- Activer / désactiver --}}
                        <form id="table-toggle-{{ $table->id }}" method="POST" action="{{ route('admin.tables.toggle', $table) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit"
                                title="{{ $table->is_active ? 'Désactiver' : 'Activer' }}"
                                aria-label="{{ $table->is_active ? 'Désactiver' : 'Activer' }} la table n°{{ $table->number }}"
                                @if ($table->is_active && in_array($state, ['walk_in', 'occupied', 'reserved'], true))
                                    @click="$dispatch('open-confirm-modal', {
                                        formId: @js('table-toggle-'.$table->id),
                                        title: @js('Désactiver la table n°'.$table->number),
                                        message: @js($state === 'walk_in'
                                            ? 'La table n°'.$table->number.' est actuellement occupée par un client sans réservation. La désactiver la retirera immédiatement de la salle.'
                                            : 'La table n°'.$table->number.' est ' . mb_strtolower($meta['label']) . '. La désactiver la retirera de la salle et bloquera les prochaines attributions.'),
                                        label: @js('Désactiver'),
                                        tone: 'red'
                                    })"
                                @endif
                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-charcoal/60 transition duration-200 hover:bg-coffee/5 hover:text-coffee focus:outline-none focus-visible:ring-2 focus-visible:ring-amber/30">
                                <x-lucide-power class="h-4 w-4" />
                            </button>
                        </form>

                        {{-- Modifier --}}
                        <a href="{{ route('admin.tables.edit', $table) }}"
                           title="Modifier" aria-label="Modifier la table n°{{ $table->number }}"
                           class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-amber-dark transition duration-200 hover:bg-amber/10 hover:text-amber-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-amber/40">
                            <x-lucide-pencil class="h-4 w-4" />
                        </a>

                        {{-- Supprimer — protégé si la table porte des réservations actives ou des clients --}}
                        @if ($table->active_reservations_count > 0 || $table->walk_ins_count > 0)
                            <button type="button" disabled
                                title="Suppression impossible : cette table est liée à des réservations ou à des clients"
                                aria-label="Suppression impossible : la table n°{{ $table->number }} est liée à des réservations ou à des clients"
                                class="inline-flex h-9 w-9 cursor-not-allowed items-center justify-center rounded-lg text-charcoal/25">
                                <x-lucide-trash-2 class="h-4 w-4" />
                            </button>
                        @else
                            <button type="button"
                                title="Supprimer" aria-label="Supprimer la table n°{{ $table->number }}"
                                @click="$dispatch('open-delete-modal', {
                                    action: @js(route('admin.tables.destroy', $table)),
                                    name: @js('la table n°'.$table->number),
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
    @endif

    <x-confirm-action-modal />
    <x-delete-confirm-modal />

</x-admin-layout>
