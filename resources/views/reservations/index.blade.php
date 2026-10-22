@extends('layouts.public')

@section('content')
<section class="relative overflow-hidden bg-cream py-10 sm:py-16 min-h-screen">

    {{-- Lueur décorative douce en arrière-plan --}}
    <div class="pointer-events-none absolute -right-32 -top-32 h-96 w-96 rounded-full bg-amber/10 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-32 -left-32 h-96 w-96 rounded-full bg-coffee/10 blur-3xl"></div>

        @php
            $statusLabel = [
                'pending'   => 'En attente',
                'confirmed' => 'Confirmée',
                'rejected'  => 'Refusée',
                'cancelled' => 'Annulée',
                'completed' => 'Terminée',
                'expired'   => 'Expirée',
            ];
            $statusStyle = [
                'pending'   => 'bg-amber-50 text-amber-dark',
                'confirmed' => 'bg-emerald-50 text-emerald-700',
                'rejected'  => 'bg-red-50 text-red-700',
                'cancelled' => 'bg-gray-100 text-gray-600',
                'completed' => 'bg-gray-100 text-gray-600',
                'expired'   => 'bg-gray-100 text-gray-600',
            ];
            $attendanceLabel = [
                'unknown' => 'Présence non renseignée',
                'present' => 'Client présent',
                'absent'  => 'Client absent',
                'excused' => 'Absence excusée',
            ];
            $attendanceStyle = [
                'unknown' => 'bg-gray-100 text-gray-500',
                'present' => 'bg-emerald-50 text-emerald-700',
                'absent'  => 'bg-red-50 text-red-700',
                'excused' => 'bg-amber-50 text-amber-dark',
            ];
        @endphp

    <div class="relative mx-auto max-w-6xl px-4">

        {{-- En-tête --}}
        <div class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">

            <div>
                {{-- Titre --}}
                <h1 class="mt-2 font-display text-4xl leading-[1.05] tracking-tight text-coffee sm:text-5xl">
                    Mes réservations
                </h1>

                {{-- Description --}}
                <p class="mt-6 max-w-lg text-lg leading-relaxed text-gray-500">
                    Retrouvez ici l'historique de toutes vos réservations.
                </p>
            </div>

            {{-- Bouton nouvelle réservation --}}
            <a
                href="{{ route('reservations.create') }}"
                class="inline-flex items-center justify-center gap-2.5 rounded-xl bg-amber px-6 py-3.5 text-[15px] font-semibold text-white transition duration-200 hover:-translate-y-0.5 hover:bg-amber-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-light focus-visible:ring-offset-2"
            >
                <span>Nouvelle réservation</span>
                {{-- Icône flèche --}}
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 5v14M5 12h14" />
                </svg>
            </a>
        </div>

        {{-- Message de succès --}}
        @if (session('success'))
            <div class="mt-8 flex items-start gap-3 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                <svg class="mt-0.5 h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M20 6 9 17l-5-5" />
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        {{-- Compte bloqué : les nouvelles réservations sont suspendues --}}
        @if (! empty($bookingBlock['blocked']))
            <div class="mt-8 flex items-start gap-3 rounded-xl bg-red-50 px-4 py-3 text-sm font-medium text-red-700 ring-1 ring-inset ring-red-200">
                <svg class="mt-0.5 h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="3" y="11" width="18" height="11" rx="2" /><path d="M7 11V7a5 5 0 0 1 10 0v4" />
                </svg>
                <span>
                    Vos nouvelles réservations sont temporairement suspendues
                    ({{ $bookingBlock['absences'] }} absence{{ $bookingBlock['absences'] > 1 ? 's' : '' }} vérifiée{{ $bookingBlock['absences'] > 1 ? 's' : '' }}).
                    Contactez le restaurant pour régulariser votre situation.
                </span>
            </div>
        @endif

        {{-- ========== HISTORIQUE ========== --}}
        <div class="mt-8 space-y-5">
            @forelse ($reservations as $reservation)
                <article class="rounded-2xl border border-coffee/10 bg-white p-6 shadow-sm sm:p-8">

                    <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">

                        {{-- Infos principales --}}
                        <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:gap-8">

                            {{-- Date --}}
                            <div class="flex items-center gap-3">
                                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-cream text-amber-dark">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <rect x="3" y="4" width="18" height="18" rx="2" />
                                        <path d="M16 2v4M8 2v4M3 10h18" />
                                    </svg>
                                </span>
                                <div>
                                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Date</p>
                                    <p class="font-semibold text-coffee">{{ $reservation->reservation_date->format('d/m/Y') }}</p>
                                </div>
                            </div>

                            {{-- Heure --}}
                            <div class="flex items-center gap-3">
                                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-cream text-amber-dark">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <circle cx="12" cy="12" r="10" />
                                        <path d="M12 6v6l4 2" />
                                    </svg>
                                </span>
                                <div>
                                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Heure</p>
                                    <p class="font-semibold text-coffee">{{ \Carbon\Carbon::parse($reservation->reservation_time)->format('H:i') }}</p>
                                </div>
                            </div>

                            {{-- Convives --}}
                            <div class="flex items-center gap-3">
                                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-cream text-amber-dark">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                                        <circle cx="9" cy="7" r="4" />
                                        <path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" />
                                    </svg>
                                </span>
                                <div>
                                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Convives</p>
                                    <p class="font-semibold text-coffee">{{ $reservation->guests_count }}</p>
                                </div>
                            </div>

                            {{-- Table + durée --}}
                            <div class="flex items-center gap-3">
                                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-cream text-amber-dark">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <circle cx="12" cy="12" r="7" />
                                        <path d="M5 12H3M21 12h-2M12 5V3M12 21v-2" />
                                    </svg>
                                </span>
                                <div>
                                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Table</p>
                                    <p class="font-semibold text-coffee">
                                        @if ($reservation->table)
                                            n°{{ $reservation->table->number }}
                                        @else
                                            <span class="font-normal text-gray-400">Non attribuée</span>
                                        @endif
                                    </p>
                                    <p class="text-xs text-gray-400">
                                        Durée : {{ $reservation->scheduledAt()->format('H:i') }} – {{ $reservation->expectedEnd()->format('H:i') }}
                                    </p>
                                </div>
                            </div>

                        </div>

                        {{-- Statut + actions --}}
                        <div class="flex flex-col items-start gap-4 sm:flex-row sm:items-center lg:flex-col lg:items-end">

                            {{-- Badge statut --}}
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center rounded-full px-3.5 py-1.5 text-xs font-semibold {{ $statusStyle[$reservation->displayStatus()] ?? 'bg-gray-100 text-gray-600' }}">
                                    {{ $statusLabel[$reservation->displayStatus()] ?? $reservation->status }}
                                </span>

                                {{-- Présence enregistrée par le restaurant --}}
                                @if ($reservation->canRecordAttendance())
                                    <span class="inline-flex items-center rounded-full px-3.5 py-1.5 text-xs font-semibold {{ $attendanceStyle[$reservation->attendance] ?? $attendanceStyle['unknown'] }}">
                                        {{ $attendanceLabel[$reservation->attendance] ?? 'Présence non renseignée' }}
                                    </span>
                                @endif
                            </div>

                            {{-- Annulation --}}
                            @if ($reservation->isCancellable())
                                <form method="POST" action="{{ route('reservations.cancel', $reservation) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button
                                        type="submit"
                                        onclick="return confirm('Voulez-vous vraiment annuler cette réservation ?');"
                                        class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 px-4 py-2 text-sm font-semibold text-red-600 transition duration-200 hover:bg-red-50"
                                    >
                                        {{-- Icône croix --}}
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M6 6l12 12M18 6 6 18" />
                                        </svg>
                                        <span>Annuler</span>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>

                    {{-- Demande particulière --}}
                    @if ($reservation->special_request)
                        <div class="mt-6 border-t border-coffee/10 pt-5">
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Demande particulière</p>
                            <p class="mt-2 text-[15px] leading-relaxed text-charcoal/80">{{ $reservation->special_request }}</p>
                        </div>
                    @endif

                    {{-- Référence & date de création --}}
                    <div class="mt-6 flex flex-wrap items-center gap-x-6 gap-y-2 border-t border-coffee/10 pt-5 text-xs text-gray-400">
                        <span>Référence #{{ $reservation->id }}</span>
                        <span>Demandée le {{ $reservation->created_at->format('d/m/Y') }} à {{ $reservation->created_at->format('H:i') }}</span>
                    </div>
                </article>
            @empty
                {{-- État vide --}}
                <div class="rounded-2xl border border-dashed border-coffee/20 bg-white p-12 text-center shadow-sm">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-cream text-amber-dark">
                        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="3" y="4" width="18" height="18" rx="2" />
                            <path d="M16 2v4M8 2v4M3 10h18" />
                        </svg>
                    </div>
                    <h2 class="mt-6 font-display text-2xl text-coffee">Aucune réservation pour le moment</h2>
                    <p class="mt-3 text-gray-500">Réservez votre première table et profitez de bons moments chez nous.</p>
                    <a
                        href="{{ route('reservations.create') }}"
                        class="mt-8 inline-flex items-center gap-2.5 rounded-xl bg-amber px-6 py-3.5 text-[15px] font-semibold text-white transition duration-200 hover:-translate-y-0.5 hover:bg-amber-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-light focus-visible:ring-offset-2"
                    >
                        <span>Réserver une table</span>
                        {{-- Icône flèche --}}
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M5 12h14M13 6l6 6-6 6" />
                        </svg>
                    </a>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if ($reservations->hasPages())
            <div class="mt-10">
                {{ $reservations->links() }}
            </div>
        @endif

    </div>
</section>

@endsection