@php
    $statuses = config('reservations.statuses');
    // « completed » (Terminée) et « expired » (Expirée) sont des statuts
    // dérivés du temps, pas des valeurs stockées : ils sont proposés comme
    // filtres mais ne correspondent à aucune valeur en base. « Masquées »
    // est un filtre d'affichage : ces réservations sont conservées mais
    // retirées de la liste par défaut.
    $filterOptions = collect($statuses)
        ->only(['pending', 'confirmed', 'completed', 'rejected', 'cancelled', 'expired'])
        ->put('hidden', [
            'label' => 'Masquées',
            'icon' => 'eye-off',
            'tone' => 'gray',
        ]);
    $activeStatus = $filterOptions->has(request('status')) ? request('status') : null;
    $searchTerm = trim((string) request('search', ''));
    $hasFilters = $activeStatus !== null || $searchTerm !== '';

    // Détails par réservation (modale + demande spéciale).
    $details = $reservations->getCollection()->mapWithKeys(function ($reservation) use ($statuses) {
        $meta = $statuses[$reservation->displayStatus()] ?? $statuses['pending'];
        $attendanceMeta = config('reservations.attendance')[$reservation->attendance] ?? config('reservations.attendance')['unknown'];

        return [$reservation->id => [
            'name' => $reservation->user->name,
            'email' => $reservation->user->email,
            'date' => $reservation->reservation_date->translatedFormat('l j F Y'),
            'time' => \Illuminate\Support\Carbon::parse($reservation->reservation_time)->format('H:i'),
            'end' => $reservation->expectedEnd()->format('H:i'),
            'guests' => $reservation->guests_count,
            'table' => $reservation->table?->number,
            'statusLabel' => $meta['label'],
            'tone' => $meta['tone'],
            'attendanceLabel' => $attendanceMeta['label'],
            'request' => $reservation->special_request,
        ]];
    });

    $attendanceTones = [
        'unknown' => 'bg-gray-100 text-gray-500 ring-gray-200',
        'present' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'absent' => 'bg-red-50 text-red-700 ring-red-200',
        'excused' => 'bg-amber/10 text-amber-dark ring-amber/20',
    ];
    $attendanceIcons = [
        'unknown' => 'circle-help',
        'present' => 'circle-check',
        'absent' => 'circle-x',
        'excused' => 'circle-slash',
    ];
@endphp

<x-admin-layout title="Réservations">
    {{-- En-tête --}}
    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="font-display text-2xl text-coffee sm:text-3xl">Gestion des réservations</h1>
            <p class="mt-1 text-sm text-charcoal/60">Examinez les demandes et gérez les réservations de vos clients.</p>
        </div>
        @unless($reservations->isEmpty())
            <p class="text-sm text-charcoal/50">
                {{ $reservations->total() }} {{ $reservations->total() > 1 ? 'réservations' : 'réservation' }}
            </p>
        @endunless
    </div>

    {{-- Légende des actions (purement informative : aucun déclenchement) --}}
    <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2 rounded-xl border border-coffee/10 bg-cream/70 px-4 py-2.5 text-xs text-charcoal/60">
        <span class="font-semibold uppercase tracking-wider text-charcoal/40">Légende</span>
        <span class="inline-flex items-center gap-1.5"><x-lucide-circle-check class="h-3.5 w-3.5 text-emerald-600" /> Confirmer</span>
        <span class="inline-flex items-center gap-1.5"><x-lucide-circle-x class="h-3.5 w-3.5 text-red-600" /> Rejeter</span>
        <span class="inline-flex items-center gap-1.5"><x-lucide-calendar-x class="h-3.5 w-3.5 text-coffee/60" /> Annuler</span>
        <span class="inline-flex items-center gap-1.5"><x-lucide-user-check class="h-3.5 w-3.5 text-emerald-600" /> Marquer présent</span>
        <span class="inline-flex items-center gap-1.5"><x-lucide-user-x class="h-3.5 w-3.5 text-red-600" /> Marquer absent</span>
        <span class="inline-flex items-center gap-1.5"><x-lucide-circle-slash class="h-3.5 w-3.5 text-amber-dark" /> Excuser l'absence</span>
        <span class="inline-flex items-center gap-1.5"><x-lucide-eye-off class="h-3.5 w-3.5 text-charcoal/50" /> Masquer</span>
        <span class="inline-flex items-center gap-1.5"><x-lucide-ellipsis class="h-3.5 w-3.5 text-coffee" /> Menu des actions</span>
    </div>

    {{-- Recherche & filtre --}}
    <form method="GET" action="{{ route('admin.reservations.index') }}" class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center">
        <div class="relative flex-1">
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-charcoal/40">
                <x-lucide-search class="h-4 w-4" />
            </span>
            <label for="search" class="sr-only">Rechercher une réservation</label>
            <input id="search" name="search" type="search" value="{{ $searchTerm }}"
                placeholder="Rechercher par nom ou e-mail..."
                class="w-full rounded-xl border border-coffee/15 bg-white py-2.5 pl-10 pr-4 text-sm text-coffee placeholder:text-charcoal/40 transition focus:border-amber focus:outline-none focus:ring-2 focus:ring-amber/30">
        </div>

        <div class="relative sm:w-56">
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-charcoal/40">
                <x-lucide-list-filter class="h-4 w-4" />
            </span>
            <label for="status" class="sr-only">Filtrer par statut</label>
            <select id="status" name="status" onchange="this.form.submit()"
                class="w-full appearance-none rounded-xl border border-coffee/15 bg-white py-2.5 pl-10 pr-9 text-sm text-coffee transition focus:border-amber focus:outline-none focus:ring-2 focus:ring-amber/30">
                <option value="">Tous les statuts</option>
                @foreach($filterOptions as $value => $meta)
                    <option value="{{ $value }}" @selected($activeStatus === $value)>{{ $meta['label'] }}</option>
                @endforeach
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

        @if($hasFilters)
            <a href="{{ route('admin.reservations.index') }}"
                class="inline-flex items-center justify-center gap-2 rounded-xl border border-coffee/15 px-4 py-2.5 text-sm font-medium text-charcoal/70 transition hover:border-amber hover:text-amber-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-amber/40">
                <x-lucide-rotate-ccw class="h-4 w-4" />
                Réinitialiser
            </a>
        @endif
    </form>

    @if($reservations->isEmpty())
        {{-- État vide --}}
        <div class="mt-8 flex flex-col items-center gap-3 rounded-2xl border border-dashed border-coffee/15 bg-white px-6 py-16 text-center">
            <span class="inline-flex h-14 w-14 items-center justify-center rounded-full bg-cream text-charcoal/40">
                @if($hasFilters)
                    <x-lucide-search-x class="h-7 w-7" />
                @else
                    <x-lucide-inbox class="h-7 w-7" />
                @endif
            </span>
            @if($hasFilters)
                <h2 class="font-display text-lg text-coffee">Aucune réservation ne correspond à votre recherche</h2>
                <p class="max-w-sm text-sm text-charcoal/60">Essayez un autre nom, un autre e-mail ou modifiez le filtre de statut.</p>
                <a href="{{ route('admin.reservations.index') }}"
                    class="mt-2 inline-flex items-center gap-2 rounded-xl bg-amber px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-amber-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-amber/40">
                    <x-lucide-rotate-ccw class="h-4 w-4" />
                    Réinitialiser les filtres
                </a>
            @else
                <h2 class="font-display text-lg text-coffee">Aucune réservation pour le moment</h2>
                <p class="max-w-sm text-sm text-charcoal/60">Les nouvelles demandes de réservation de vos clients apparaîtront ici.</p>
            @endif
        </div>
    @else
        {{-- Tableau (écrans moyens et plus ; les cartes mobiles le remplacent en dessous) --}}
        {{--
            Le conteneur ne doit JAMAIS rogné le contenu : `overflow-x-auto`
            offre un défilement horizontal uniquement quand c'est nécessaire,
            et `min-w-[1120px]` évite la compression des colonnes. Le menu
            d'actions, lui, est téléporté hors de ce conteneur.
        --}}
        <div class="mt-6 hidden rounded-2xl border border-coffee/10 bg-white shadow-sm md:block">
            <div class="overflow-x-auto rounded-2xl">
            <table class="w-full min-w-[1120px] divide-y divide-coffee/10">
                <thead class="border-b border-coffee/10 bg-cream/60">
                    <tr>
                        <th scope="col" class="whitespace-nowrap px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-charcoal/50">Client</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-charcoal/50">Date</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-charcoal/50">Heure</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-charcoal/50">Personnes</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-charcoal/50">Table</th>
                        <th scope="col" class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-charcoal/50">Demande spéciale</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-charcoal/50">Statut</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-charcoal/50">Présence</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-charcoal/50">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-coffee/10">
                    @foreach($reservations as $reservation)
                        <tr class="transition-colors hover:bg-cream/60">
                            <td class="px-4 py-3">
                                <div class="font-semibold text-coffee">{{ $reservation->user->name }}</div>
                                <div class="text-xs text-charcoal/50">{{ $reservation->user->email }}</div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-charcoal">
                                {{ $reservation->reservation_date->translatedFormat('d M Y') }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 tabular-nums text-charcoal">
                                {{ \Illuminate\Support\Carbon::parse($reservation->reservation_time)->format('H:i') }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center gap-1.5 text-charcoal">
                                    <x-lucide-users class="h-4 w-4 text-charcoal/40" />
                                    {{ $reservation->guests_count }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                @if($reservation->table)
                                    <span class="inline-flex items-center gap-1.5 font-semibold text-coffee">
                                        <x-lucide-armchair class="h-4 w-4 text-amber-dark" />
                                        N°{{ $reservation->table->number }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-500">Non attribuée</span>
                                @endif
                            </td>
                            <td class="max-w-[16rem] px-4 py-3">
                                @if($reservation->special_request)
                                    <button type="button"
                                        @click="$dispatch('open-reservation-details', @js($details[$reservation->id]))"
                                        title="{{ $reservation->special_request }}"
                                        aria-label="Voir la demande complète de {{ $reservation->user->name }}"
                                        class="group inline-flex max-w-full items-center gap-1.5 text-left text-charcoal/70 transition hover:text-amber-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-amber/30">
                                        <span class="truncate">{{ \Illuminate\Support\Str::limit($reservation->special_request, 40) }}</span>
                                        <x-lucide-eye class="h-3.5 w-3.5 shrink-0 text-charcoal/30 group-hover:text-amber-dark" />
                                    </button>
                                @else
                                    <span class="text-charcoal/30">&mdash;</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <x-reservation-status-badge :status="$reservation->displayStatus()" />
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                @if ($reservation->attendance !== \App\Models\Reservation::ATTENDANCE_UNKNOWN || $reservation->canRecordAttendance())
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $attendanceTones[$reservation->attendance] ?? $attendanceTones['unknown'] }}">
                                        @svg('lucide-' . ($attendanceIcons[$reservation->attendance] ?? 'circle-help'), 'h-3.5 w-3.5 shrink-0')
                                        {{ $reservation->attendanceLabel() }}
                                    </span>
                                @else
                                    <span class="text-xs text-charcoal/30">&mdash;</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <x-reservation-actions :reservation="$reservation" />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        </div>

        {{-- Cartes (mobile) --}}
        <div class="mt-6 space-y-4 md:hidden">
            @foreach($reservations as $reservation)
                <article class="rounded-2xl border border-coffee/10 bg-white p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-coffee">{{ $reservation->user->name }}</p>
                            <p class="truncate text-xs text-charcoal/50">{{ $reservation->user->email }}</p>
                        </div>
                        <div class="flex shrink-0 flex-col items-end gap-2">
                            <x-reservation-status-badge :status="$reservation->displayStatus()" />
                            @if ($reservation->attendance !== \App\Models\Reservation::ATTENDANCE_UNKNOWN || $reservation->canRecordAttendance())
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $attendanceTones[$reservation->attendance] ?? $attendanceTones['unknown'] }}">
                                    @svg('lucide-' . ($attendanceIcons[$reservation->attendance] ?? 'circle-help'), 'h-3.5 w-3.5 shrink-0')
                                    {{ $reservation->attendanceLabel() }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wider text-charcoal/40">Date</dt>
                            <dd class="mt-0.5 text-charcoal">
                                {{ $reservation->reservation_date->translatedFormat('d M Y') }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wider text-charcoal/40">Heure</dt>
                            <dd class="mt-0.5 tabular-nums text-charcoal">
                                {{ \Illuminate\Support\Carbon::parse($reservation->reservation_time)->format('H:i') }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wider text-charcoal/40">Personnes</dt>
                            <dd class="mt-0.5 inline-flex items-center gap-1.5 text-charcoal">
                                <x-lucide-users class="h-4 w-4 text-charcoal/40" />
                                {{ $reservation->guests_count }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wider text-charcoal/40">Table</dt>
                            <dd class="mt-0.5">
                                @if($reservation->table)
                                    <span class="inline-flex items-center gap-1.5 font-semibold text-coffee">
                                        <x-lucide-armchair class="h-4 w-4 text-amber-dark" />
                                        N°{{ $reservation->table->number }}
                                    </span>
                                @else
                                    <span class="text-charcoal/40">Non attribuée</span>
                                @endif
                            </dd>
                        </div>
                    </dl>

                    @if($reservation->special_request)
                        <button type="button"
                            @click="$dispatch('open-reservation-details', @js($details[$reservation->id]))"
                            class="group mt-4 flex w-full items-start gap-2 rounded-xl bg-cream/70 px-3 py-2.5 text-left text-sm text-charcoal/70 transition hover:bg-cream focus:outline-none focus-visible:ring-2 focus-visible:ring-amber/30">
                            <x-lucide-message-square class="mt-0.5 h-4 w-4 shrink-0 text-charcoal/40" />
                            <span class="line-clamp-2">{{ $reservation->special_request }}</span>
                        </button>
                    @endif

                    <div class="mt-4 flex items-center justify-end border-t border-coffee/10 pt-3">
                        <x-reservation-actions :reservation="$reservation" />
                    </div>
                </article>
            @endforeach
        </div>

        {{-- Pagination --}}
        @if($reservations->hasPages())
            <div class="mt-6">
                {{ $reservations->links() }}
            </div>
        @endif
    @endif

    {{-- Modales --}}
    <x-confirm-action-modal />
    <x-reservation-details-modal />
</x-admin-layout>
