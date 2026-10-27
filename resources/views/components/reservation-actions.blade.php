@props(['reservation'])

@php
    $id = $reservation->id;
    $clientName = $reservation->user->name;
    $status = $reservation->status;
    $attendance = $reservation->attendance;

    // -------------------------------------------------------------------------
    // Actions autorisées : UNIQUEMENT celles que le statut actuel autorise.
    // Chaque action porte sa route, sa valeur et son dialogue de confirmation.
    // -------------------------------------------------------------------------
    $items = [];

    // ---------- Demande en attente : Confirmer / Rejeter ----------
    if ($status === 'pending') {
        $items['confirm'] = [
            'label' => 'Confirmer',
            'icon' => 'circle-check',
            'classes' => 'text-emerald-600 hover:bg-emerald-50',
            'route' => route('admin.reservations.status', $reservation),
            'method' => 'PATCH',
            'name' => 'status',
            'value' => 'confirmed',
            'confirm' => [
                'title' => 'Confirmer la réservation',
                'message' => 'La réservation de '.$clientName.' sera confirmée et une table lui sera attribuée.',
                'label' => 'Confirmer',
                'tone' => 'emerald',
            ],
        ];

        $items['reject'] = [
            'label' => 'Rejeter',
            'icon' => 'circle-x',
            'classes' => 'text-red-600 hover:bg-red-50',
            'route' => route('admin.reservations.status', $reservation),
            'method' => 'PATCH',
            'name' => 'status',
            'value' => 'rejected',
            'confirm' => [
                'title' => 'Rejeter la réservation',
                'message' => 'La demande de '.$clientName.' sera refusée et le client en sera informé par e-mail.',
                'label' => 'Rejeter',
                'tone' => 'red',
            ],
        ];
    }

    // ---------- Réservation confirmée ----------
    if ($status === 'confirmed') {
        if ($reservation->canAdminCancel()) {
            // Créneau non écoulé : seule action disponible.
            $items['cancel'] = [
                'label' => 'Annuler',
                'icon' => 'ban',
                'classes' => 'text-amber-dark hover:bg-amber/10',
                'route' => route('admin.reservations.status', $reservation),
                'method' => 'PATCH',
                'name' => 'status',
                'value' => 'cancelled',
                'confirm' => [
                    'title' => 'Annuler la réservation',
                    'message' => 'La réservation confirmée de '.$clientName.' sera annulée : la table et sa journée seront libérées. Le client en sera informé par e-mail.',
                    'label' => 'Annuler la réservation',
                    'tone' => 'amber',
                ],
            ];
        } elseif ($attendance === 'unknown') {
            // Créneau écoulé, présence non encore enregistrée.
            $items['present'] = [
                'label' => 'Marquer présent',
                'icon' => 'user-check',
                'classes' => 'text-emerald-600 hover:bg-emerald-50',
                'route' => route('admin.reservations.attendance', $reservation),
                'method' => 'PATCH',
                'name' => 'attendance',
                'value' => 'present',
                'confirm' => [
                    'title' => 'Marquer le client présent',
                    'message' => 'La présence de '.$clientName.' sera enregistrée : aucune absence ne sera comptabilisée. Le masquage deviendra disponible.',
                    'label' => 'Marquer présent',
                    'tone' => 'emerald',
                ],
            ];

            $items['absent'] = [
                'label' => 'Marquer absent',
                'icon' => 'user-x',
                'classes' => 'text-red-600 hover:bg-red-50',
                'route' => route('admin.reservations.attendance', $reservation),
                'method' => 'PATCH',
                'name' => 'attendance',
                'value' => 'absent',
                'confirm' => [
                    'title' => 'Marquer le client absent',
                    'message' => 'L’absence de '.$clientName.' sera comptabilisée comme absence non excusée. À partir de '.config('reservations.absence_threshold', 5).' absences, ses nouvelles réservations sont bloquées.',
                    'label' => 'Marquer absent',
                    'tone' => 'red',
                ],
            ];
        } elseif ($attendance === 'absent') {
            // Absence déjà enregistrée : seule l'excuse reste possible.
            $items['excuse'] = [
                'label' => "Excuser l'absence",
                'icon' => 'circle-slash',
                'classes' => 'text-amber-dark hover:bg-amber/10',
                'route' => route('admin.reservations.attendance', $reservation),
                'method' => 'PATCH',
                'name' => 'attendance',
                'value' => 'excused',
                'confirm' => [
                    'title' => "Excuser l'absence",
                    'message' => "L'absence de ".$clientName." sera marquée comme excusée : elle sortira du compteur d'absences non excusées. Le client reste enregistré comme absent.",
                    'label' => "Excuser l'absence",
                    'tone' => 'amber',
                ],
            ];
        }
        // Présence déjà enregistrée (présent ou excusé) : aucune action de statut.
    }

    // ---------- Masquage / restauration ----------
    if ($reservation->hidden_at !== null) {
        $items['restore'] = [
            'label' => 'Réafficher',
            'icon' => 'eye',
            'classes' => 'text-amber-dark hover:bg-amber/10',
            'route' => route('admin.reservations.restore', $reservation),
            'method' => 'PATCH',
            'name' => '_token',
            'value' => '',
            'confirm' => null,
        ];
    } elseif ($reservation->isHideable()) {
        $items['hide'] = [
            'label' => 'Masquer',
            'icon' => 'eye-off',
            'classes' => 'text-charcoal/60 hover:bg-coffee/5 hover:text-coffee',
            'route' => route('admin.reservations.hide', $reservation),
            'method' => 'PATCH',
            'name' => '_token',
            'value' => '',
            'confirm' => [
                'title' => 'Masquer la réservation',
                'message' => 'La réservation de '.$clientName.' sera masquée de la liste. Elle n’est pas supprimée : l’historique et les statistiques restent exacts.',
                'label' => 'Masquer',
                'tone' => 'coffee',
            ],
        ];
    }

    foreach ($items as $key => $item) {
        $items[$key]['formId'] = 'resv-'.$id.'-'.$key;
    }
@endphp

@if ($items !== [])
<div class="inline-flex justify-end"
     x-data="{ open: false, pos: { top: '0px', left: '0px' } }"
     @click.outside="open = false"
     @keydown.escape.window="open = false"
     @scroll.window="open = false"
     @resize.window="open = false">

    {{-- Bouton trois points --}}
    <button type="button"
            @click="open = !open; if (open) { $nextTick(() => { const trigger = $el; const menu = document.getElementById('resv-menu-{{ $id }}'); if (!menu) return; const r = trigger.getBoundingClientRect(); const w = menu.offsetWidth; const h = menu.offsetHeight; let top = r.bottom + 6; let left = r.right - w; if (top + h > window.innerHeight - 8) { top = Math.max(8, r.top - h - 6); } if (left < 8) { left = 8; } pos = { top: top + 'px', left: left + 'px' }; }) }"
            :aria-expanded="open"
            aria-haspopup="menu"
            title="Actions"
            aria-label="Actions pour la réservation de {{ $clientName }}"
            class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-charcoal/50 transition duration-200 hover:bg-coffee/5 hover:text-coffee focus:outline-none focus-visible:ring-2 focus-visible:ring-amber/40 focus-visible:ring-offset-1 focus-visible:ring-offset-white">
        <x-lucide-ellipsis class="h-5 w-5" />
    </button>

    {{-- Menu : téléporté hors du conteneur défilant pour ne jamais être rogné --}}
    <template x-teleport="body">
        <div id="resv-menu-{{ $id }}"
             x-show="open"
             :style="`top:${pos.top};left:${pos.left}`"
             style="display: none;"
             class="fixed z-50 w-56 rounded-xl border border-coffee/15 bg-white p-1.5 shadow-lg"
             role="menu"
             aria-label="Actions pour la réservation de {{ $clientName }}">
            @foreach($items as $key => $item)
                @if($item['confirm'] !== null)
                    <button type="button"
                            role="menuitem"
                            @click="$dispatch('open-confirm-modal', {
                                formId: @js($item['formId']),
                                title: @js($item['confirm']['title']),
                                message: @js($item['confirm']['message']),
                                label: @js($item['confirm']['label']),
                                tone: @js($item['confirm']['tone'])
                            })"
                            class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left text-sm font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-amber/30 {{ $item['classes'] }}">
                        <x-dynamic-component :component="'lucide-'.$item['icon']" class="h-4 w-4 shrink-0" />
                        <span>{{ $item['label'] }}</span>
                    </button>
                @else
                    <button type="button"
                            role="menuitem"
                            @click="document.getElementById(@js($item['formId']))?.requestSubmit()"
                            class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left text-sm font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-amber/30 {{ $item['classes'] }}">
                        <x-dynamic-component :component="'lucide-'.$item['icon']" class="h-4 w-4 shrink-0" />
                        <span>{{ $item['label'] }}</span>
                    </button>
                @endif
            @endforeach
        </div>
    </template>

    {{-- Formulaires associés (CSRF + méthode réelle), soumis par la modale --}}
    <div class="hidden">
        @foreach($items as $key => $item)
            <form id="{{ $item['formId'] }}" method="POST" action="{{ $item['route'] }}">
                @csrf
                @method($item['method'])
                @if($item['name'] !== '_token')
                    <input type="hidden" name="{{ $item['name'] }}" value="{{ $item['value'] }}">
                @endif
            </form>
        @endforeach
    </div>
</div>
@endif
