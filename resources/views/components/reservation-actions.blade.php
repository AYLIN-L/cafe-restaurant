@props(['reservation', 'details' => []])

@php
    $id = $reservation->id;
    $clientName = $reservation->user->name;
    $base = 'inline-flex h-9 w-9 items-center justify-center rounded-lg transition duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-1 focus-visible:ring-offset-white';
    $attendanceMeta = config('reservations.attendance');
@endphp

<div class="flex items-center justify-end gap-1.5">

    {{-- ========== DEMANDE EN ATTENTE ========== --}}
    @if ($reservation->status === 'pending')
        {{-- Confirmer --}}
        <form id="resv-{{ $id }}-confirm" method="POST" action="{{ route('admin.reservations.status', $reservation) }}">
            @csrf
            @method('PATCH')
            <input type="hidden" name="status" value="confirmed">
            <button type="submit" title="Confirmer la réservation" aria-label="Confirmer la réservation de {{ $clientName }}"
                class="{{ $base }} text-emerald-600 hover:bg-emerald-50 focus-visible:ring-emerald-500/40">
                <x-lucide-circle-check class="h-[18px] w-[18px]" />
            </button>
        </form>

        {{-- Refuser --}}
        <form id="resv-{{ $id }}-reject" method="POST" action="{{ route('admin.reservations.status', $reservation) }}">
            @csrf
            @method('PATCH')
            <input type="hidden" name="status" value="rejected">
            <button type="button"
                @click="$dispatch('open-confirm-modal', {
                    formId: @js('resv-'.$id.'-reject'),
                    title: @js('Refuser la réservation'),
                    message: @js('Vous êtes sur le point de refuser la demande de '.$clientName.'. Le client ne pourra plus y donner suite.'),
                    label: @js('Refuser'),
                    tone: 'red'
                })"
                title="Refuser" aria-label="Refuser la réservation de {{ $clientName }}"
                class="{{ $base }} text-red-600 hover:bg-red-50 focus-visible:ring-red-500/40">
                <x-lucide-circle-x class="h-[18px] w-[18px]" />
            </button>
        </form>

        {{-- Annuler --}}
        <form id="resv-{{ $id }}-cancel" method="POST" action="{{ route('admin.reservations.status', $reservation) }}">
            @csrf
            @method('PATCH')
            <input type="hidden" name="status" value="cancelled">
            <button type="button"
                @click="$dispatch('open-confirm-modal', {
                    formId: @js('resv-'.$id.'-cancel'),
                    title: @js('Annuler la réservation'),
                    message: @js('Vous êtes sur le point d\'annuler la demande de '.$clientName.'. La journée sera de nouveau disponible pour ce client.'),
                    label: @js('Annuler la réservation'),
                    tone: 'gray'
                })"
                title="Annuler" aria-label="Annuler la réservation de {{ $clientName }}"
                class="{{ $base }} text-gray-500 hover:bg-gray-100 hover:text-gray-700 focus-visible:ring-gray-400/40">
                <x-lucide-calendar-x class="h-[18px] w-[18px]" />
            </button>
        </form>

    {{-- ========== RÉSERVATION CONFIRMÉE ========== --}}
    @elseif ($reservation->status === 'confirmed')
        {{-- Client présent --}}
        <form id="resv-{{ $id }}-attendance-present" method="POST" action="{{ route('admin.reservations.attendance', $reservation) }}">
            @csrf
            @method('PATCH')
            <input type="hidden" name="attendance" value="present">
            <button type="submit" title="Marquer le client présent" aria-label="Marquer {{ $clientName }} comme présent"
                class="{{ $base }} text-emerald-600 hover:bg-emerald-50 focus-visible:ring-emerald-500/40">
                <x-lucide-user-check class="h-[18px] w-[18px]" />
            </button>
        </form>

        {{-- Client absent (absence vérifiée) --}}
        <form id="resv-{{ $id }}-attendance-absent" method="POST" action="{{ route('admin.reservations.attendance', $reservation) }}">
            @csrf
            @method('PATCH')
            <input type="hidden" name="attendance" value="absent">
            <button type="button"
                @click="$dispatch('open-confirm-modal', {
                    formId: @js('resv-'.$id.'-attendance-absent'),
                    title: @js('Marquer une absence'),
                    message: @js('L\'absence de '.$clientName.' sera comptabilisée. À partir de '.config('reservations.absence_threshold', 5).' absences vérifiées, ses nouvelles réservations sont bloquées.'),
                    label: @js('Marquer absent'),
                    tone: 'red'
                })"
                title="Marquer le client absent" aria-label="Marquer {{ $clientName }} comme absent"
                class="{{ $base }} text-red-600 hover:bg-red-50 focus-visible:ring-red-500/40">
                <x-lucide-user-x class="h-[18px] w-[18px]" />
            </button>
        </form>

        {{-- Absence excusée (ne compte pas) --}}
        <form id="resv-{{ $id }}-attendance-excused" method="POST" action="{{ route('admin.reservations.attendance', $reservation) }}">
            @csrf
            @method('PATCH')
            <input type="hidden" name="attendance" value="excused">
            <button type="submit" title="Marquer l'absence comme excusée (ne compte pas)" aria-label="Marquer l'absence de {{ $clientName }} comme excusée"
                class="{{ $base }} text-amber-dark hover:bg-amber/10 focus-visible:ring-amber/500/40">
                <x-lucide-circle-slash class="h-[18px] w-[18px]" />
            </button>
        </form>

        {{-- Annuler --}}
        <form id="resv-{{ $id }}-cancel" method="POST" action="{{ route('admin.reservations.status', $reservation) }}">
            @csrf
            @method('PATCH')
            <input type="hidden" name="status" value="cancelled">
            <button type="button"
                @click="$dispatch('open-confirm-modal', {
                    formId: @js('resv-'.$id.'-cancel'),
                    title: @js('Annuler la réservation'),
                    message: @js('Vous êtes sur le point d\'annuler la réservation confirmée de '.$clientName.'. La table et la journée seront libérées.'),
                    label: @js('Annuler la réservation'),
                    tone: 'gray'
                })"
                title="Annuler" aria-label="Annuler la réservation de {{ $clientName }}"
                class="{{ $base }} text-gray-500 hover:bg-gray-100 hover:text-gray-700 focus-visible:ring-gray-400/40">
                <x-lucide-calendar-x class="h-[18px] w-[18px]" />
            </button>
        </form>

    {{-- ========== RÉSERVATION CLÔTURÉE (refusée, annulée ou expirée) ========== --}}
    @else
        {{-- Vue (réservation refusée ou annulée) --}}
        <button type="button"
            @click="$dispatch('open-reservation-details', @js($details))"
            title="Voir les détails" aria-label="Voir les détails de la réservation de {{ $clientName }}"
            class="{{ $base }} text-amber-dark hover:bg-amber/10 focus-visible:ring-amber/40">
            <x-lucide-eye class="h-[18px] w-[18px]" />
        </button>
    @endif

    {{-- ========== MASQUAGE / RESTAURATION ========== --}}
    @if ($reservation->hidden_at !== null)
        {{-- Restaurer --}}
        <form id="resv-{{ $id }}-restore" method="POST" action="{{ route('admin.reservations.restore', $reservation) }}">
            @csrf
            @method('PATCH')
            <button type="submit" title="Réafficher la réservation" aria-label="Réafficher la réservation de {{ $clientName }}"
                class="{{ $base }} text-amber-dark hover:bg-amber/10 focus-visible:ring-amber/500/40">
                <x-lucide-eye class="h-[18px] w-[18px]" />
            </button>
        </form>
    @elseif ($reservation->isHideable())
        {{-- Masquer (jamais supprimé) --}}
        <form id="resv-{{ $id }}-hide" method="POST" action="{{ route('admin.reservations.hide', $reservation) }}">
            @csrf
            @method('PATCH')
            <button type="button"
                @click="$dispatch('open-confirm-modal', {
                    formId: @js('resv-'.$id.'-hide'),
                    title: @js('Masquer la réservation'),
                    message: @js('La réservation de '.$clientName.' sera masquée de la liste. Elle n\'est pas supprimée : l\'historique et les statistiques restent exacts.'),
                    label: @js('Masquer'),
                    tone: 'gray'
                })"
                title="Masquer la réservation" aria-label="Masquer la réservation de {{ $clientName }}"
                class="{{ $base }} text-charcoal/50 hover:bg-charcoal/5 hover:text-charcoal focus-visible:ring-charcoal/40">
                <x-lucide-eye-off class="h-[18px] w-[18px]" />
            </button>
        </form>
    @endif
</div>
