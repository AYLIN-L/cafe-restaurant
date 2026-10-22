@extends('layouts.public')

@section('content')
<section class="relative overflow-hidden bg-cream py-10 sm:py-16">

    {{-- Lueur décorative douce en arrière-plan --}}
    <div class="pointer-events-none absolute -right-32 -top-32 h-96 w-96 rounded-full bg-amber/10 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-32 -left-32 h-96 w-96 rounded-full bg-coffee/10 blur-3xl"></div>

    <div class="relative mx-auto max-w-4xl px-4">

        {{-- Lien retour --}}
        <a href="{{ route('reservations.index') }}" class="text-sm text-amber-dark hover:underline">
            ← Mes réservations
        </a>

        {{-- Titre --}}
        <h1 class="mt-6 font-display text-4xl leading-[1.05] tracking-tight text-coffee sm:text-5xl">
            Réserver une table
        </h1>

        {{-- Description --}}
        <p class="mt-6 max-w-lg text-lg leading-relaxed text-gray-500">
            Choisissez la date, l'heure et le nombre de convives. La disponibilité
            est vérifiée automatiquement en temps réel.
        </p>

        {{-- ========== FORMULAIRE ========== --}}
        <div class="mt-10 rounded-2xl border border-coffee/10 bg-white p-6 shadow-xl sm:p-10">

            {{-- Message de succès (après l'envoi) --}}
            @if (session('success'))
                <div class="mb-8 flex items-start gap-3 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                    <svg class="mt-0.5 h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M20 6 9 17l-5-5" />
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-8 flex items-start gap-3 rounded-xl bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
                    <svg class="mt-0.5 h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="10" /><path d="M12 8v4M12 16h.01" />
                    </svg>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            {{-- Compte bloqué : les nouvelles réservations sont suspendues --}}
            @if (! empty($bookingBlock['blocked']))
                <div class="mb-8 flex items-start gap-3 rounded-xl bg-red-50 px-4 py-3 text-sm font-medium text-red-700 ring-1 ring-inset ring-red-200">
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

            <form action="{{ route('reservations.store') }}" method="POST" class="space-y-6"
                  x-data="reservationForm()"
                  x-init="init()"
                  x-cloak>
                @csrf

                {{-- Résumé des règles --}}
                <p class="rounded-xl bg-cream px-4 py-3 text-sm text-charcoal/70">
                    Restaurant ouvert de <strong>{{ $openingTime }}</strong> à <strong>{{ $closingTime }}</strong>.
                    Durée d'une réservation :
                    <strong>{{ $durationMinutes % 60 === 0 ? intdiv($durationMinutes, 60).' heures' : $durationMinutes.' minutes' }}</strong>.
                    Créneaux toutes les <strong>{{ (int) \config('reservations.slot_step_minutes', 30) }} minutes</strong>,
                    dernière réservation possible à <strong>{{ \App\Models\Table::exists() ? $slotTimes[array_key_last($slotTimes)] : '20:00' }}</strong>.
                    Une seule réservation par jour et par personne.
                </p>

                {{-- Date & heure --}}
                <div class="grid gap-6 sm:grid-cols-2">

                    {{-- Champ : Date --}}
                    <div>
                        <label for="reservation_date" class="mb-2 block text-sm font-semibold text-coffee">
                            Date
                        </label>
                        <input
                            type="date"
                            id="reservation_date"
                            name="reservation_date"
                            x-model="date"
                            x-on:change="scheduleCheck()"
                            min="{{ $minDate }}"
                            max="{{ $maxDate }}"
                            required
                            class="w-full rounded-xl border bg-white px-4 py-3.5 text-[15px] text-coffee placeholder:text-gray-400 transition duration-200 focus:border-amber focus:ring-2 focus:ring-amber/30 @error('reservation_date') border-red-400 @else border-coffee/15 @enderror"
                        >
                        @error('reservation_date')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Champ : Nombre de convives --}}
                    <div>
                        <label for="guests_count" class="mb-2 block text-sm font-semibold text-coffee">
                            Nombre de convives
                        </label>
                        <input
                            type="number"
                            id="guests_count"
                            name="guests_count"
                            x-model.number="guests"
                            x-on:input="scheduleCheck()"
                            x-on:change="scheduleCheck()"
                            min="1"
                            max="{{ max($maxGuests, 1) }}"
                            required
                            class="w-full rounded-xl border bg-white px-4 py-3.5 text-[15px] text-coffee placeholder:text-gray-400 transition duration-200 focus:border-amber focus:ring-2 focus:ring-amber/30 @error('guests_count') border-red-400 @else border-coffee/15 @enderror"
                        >
                        <p class="mt-2 text-xs text-charcoal/50">Jusqu'à {{ $maxGuests }} personnes.</p>
                        @error('guests_count')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Champ : Heure (réseau de boutons de 30 minutes) --}}
                <div>
                    <span class="mb-2 block text-sm font-semibold text-coffee">
                        Heure de début
                    </span>

                    {{-- Valeur réellement envoyée au serveur --}}
                    <input type="hidden" id="reservation_time" name="reservation_time" x-model="time">

                    <div class="grid grid-cols-3 gap-2 sm:grid-cols-4" role="group" aria-label="Créneaux disponibles">
                        <template x-for="slot in slots" :key="slot.time">
                            <button
                                type="button"
                                @click="selectTime(slot)"
                                :disabled="!slot.available"
                                :aria-pressed="time === slot.time"
                                :class="[
                                    'rounded-xl border px-3 py-2.5 text-sm font-semibold tabular-nums transition',
                                    !slot.available
                                        ? 'cursor-not-allowed border-coffee/10 bg-cream/60 text-charcoal/30'
                                        : (time === slot.time
                                            ? 'border-amber bg-amber text-white shadow-sm'
                                            : 'border-coffee/15 bg-white text-coffee hover:border-amber hover:bg-amber/5'),
                                ]"
                                x-text="slot.time">
                            </button>
                        </template>
                    </div>

                    <p class="mt-2 text-xs text-charcoal/50" x-show="slots.length === 0">
                        Aucun créneau disponible pour cette date.
                    </p>

                    @error('reservation_time')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Indicateur de disponibilité --}}
                <div class="rounded-xl border px-4 py-3 text-sm font-medium transition"
                     :class="available ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : (checking ? 'border-coffee/15 bg-cream text-charcoal/60' : 'border-red-200 bg-red-50 text-red-700')">
                    <div class="flex items-start gap-3">
                        <span class="mt-0.5 inline-flex h-2 w-2 shrink-0 rounded-full"
                              :class="available ? 'bg-emerald-500' : (checking ? 'bg-amber' : 'bg-red-500')"></span>
                        <span x-text="message"></span>
                    </div>
                    <p class="mt-1 pl-5 text-xs opacity-80" x-show="available && endTime">
                        Table disponible de <span x-text="time"></span> à <span x-text="endTime"></span> (durée de <span x-text="durationLabel"></span>).
                    </p>
                </div>

                {{-- Champ : Demande particulière --}}
                <div>
                    <label for="special_request" class="mb-2 block text-sm font-semibold text-coffee">
                        Demande particulière <span class="font-normal text-gray-400">(facultatif)</span>
                    </label>
                    <textarea
                        id="special_request"
                        name="special_request"
                        rows="4"
                        maxlength="500"
                        placeholder="Ex : Table près de la fenêtre, anniversaire..."
                        class="w-full resize-none rounded-xl border bg-white px-4 py-3.5 text-[15px] leading-relaxed text-coffee placeholder:text-gray-400 transition duration-200 focus:border-amber focus:ring-2 focus:ring-amber/30 @error('special_request') border-red-400 @else border-coffee/15 @enderror"
                    >{{ old('special_request') }}</textarea>
                    @error('special_request')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Bouton d'envoi --}}
                <button
                    type="submit"
                    :disabled="!available || checking || !canBook"
                    :class="(!available || checking || !canBook) ? 'cursor-not-allowed opacity-60' : 'hover:-translate-y-0.5 hover:bg-amber-dark'"
                    class="inline-flex items-center gap-2.5 rounded-xl bg-amber px-6 py-3.5 text-[15px] font-semibold text-white transition duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-light focus-visible:ring-offset-2"
                >
                    <span x-text="checking ? 'Vérification en cours…' : 'Confirmer la réservation'"></span>
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 12h14M13 6l6 6-6 6" />
                    </svg>
                </button>

                <p class="text-xs text-charcoal/50">
                    Votre réservation sera en attente de confirmation par notre équipe.
                    La table vous sera attribuée à la confirmation.
                    Une seule réservation par jour et par personne.
                </p>

            </form>
        </div>
    </div>
</section>

@endsection

@push('scripts')
<script>
    /**
     * Formulaire de réservation : vérification de disponibilité en AJAX.
     *
     * - une requête par changement (date, heure, convives), avec annulation
     *   des réponses obsolètes ;
     * - un débordement (debounce) de 300 ms pour les saisies rapides ;
     * - un rafraîchissement automatique toutes les 5 minutes tant que le
     *   formulaire est visible.
     *
     * L'AJAX n'est qu'un confort : Laravel revérifie tout à l'envoi.
     */
    window.reservationForm = function () {
        return {
            date: @js(old('reservation_date', $defaultDate)),
            time: @js(old('reservation_time', $defaultTime)),
            guests: @js(old('guests_count', $defaultGuests)),
            maxGuests: @js(max($maxGuests, 1)),
            slots: @js($initialSlots),
            durationMinutes: @js($durationMinutes),
            availabilityUrl: @js($availabilityUrl),

            available: false,
            canBook: true,
            checking: false,
            message: 'Veuillez sélectionner une date, une heure et un nombre de personnes valides.',
            endTime: null,

            requestId: 0,
            controller: null,
            debounceTimer: null,
            refreshTimer: null,

            get durationLabel() {
                const minutes = this.durationMinutes;
                return minutes % 60 === 0
                    ? (minutes / 60) + (minutes === 60 ? ' heure' : ' heures')
                    : minutes + ' minutes';
            },

            init() {
                this.check();
                // Rafraîchit la disponibilité toutes les 5 minutes si la page est visible.
                this.refreshTimer = setInterval(() => {
                    if (!document.hidden) {
                        this.check();
                    }
                }, 300000);
            },

            destroy() {
                clearInterval(this.refreshTimer);
                clearTimeout(this.debounceTimer);
                if (this.controller) {
                    this.controller.abort();
                }
            },

            scheduleCheck() {
                clearTimeout(this.debounceTimer);
                this.debounceTimer = setTimeout(() => this.check(), 300);
            },

            // Sélection d'un créneau dans le réseau de boutons.
            selectTime(slot) {
                if (!slot || !slot.available) {
                    return;
                }

                this.time = slot.time;
                this.check();
            },

            async check() {
                if (!this.date || !this.time || !this.guests) {
                    this.available = false;
                    this.message = 'Veuillez sélectionner une date, une heure et un nombre de personnes valides.';
                    this.endTime = null;
                    return;
                }

                // Annule la requête précédente pour éviter d'écraser un résultat récent.
                if (this.controller) {
                    this.controller.abort();
                }
                this.controller = new AbortController();
                const requestId = ++this.requestId;

                this.checking = true;

                try {
                    const response = await window.axios.get(this.availabilityUrl, {
                        params: {
                            reservation_date: this.date,
                            reservation_time: this.time,
                            guests_count: this.guests,
                        },
                        signal: this.controller.signal,
                    });

                    if (requestId !== this.requestId) {
                        return; // réponse périmée
                    }

                    this.apply(response.data);
                } catch (error) {
                    if (error.name === 'CanceledError' || error.name === 'AbortError') {
                        return;
                    }

                    if (requestId !== this.requestId) {
                        return;
                    }

                    if (error.response && error.response.status === 422 && error.response.data) {
                        this.apply(error.response.data);
                    } else {
                        this.available = false;
                        this.endTime = null;
                        this.message = 'Impossible de vérifier la disponibilité. Vérifiez votre connexion puis réessayez.';
                    }
                } finally {
                    if (requestId === this.requestId) {
                        this.checking = false;
                    }
                }
            },

            apply(data) {
                this.available = !!data.available;
                this.canBook = data.can_book !== false;
                this.message = data.message || 'Veuillez sélectionner une date, une heure et un nombre de personnes valides.';
                this.endTime = data.end_time || null;

                if (Array.isArray(data.slots)) {
                    this.slots = data.slots;
                }
            },
        };
    };
</script>
@endpush
