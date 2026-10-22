<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WalkIn;
use App\Services\TableAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class WalkInController extends Controller
{
    public function __construct(private TableAssignmentService $assignment) {}

    // Installe un client sans réservation sur la plus petite table libre.
    // L'heure de début est facultative : à défaut, on prend « maintenant ».
    public function store(Request $request)
    {
        $data = $request->validate([
            'guests_count' => ['required', 'integer', 'min:1', 'max:20'],
            'special_request' => ['nullable', 'string', 'max:500'],
            'seated_time' => ['nullable', 'date_format:H:i'],
        ]);

        $start = null;

        if (! empty($data['seated_time'])) {
            $start = $this->assignment->startAt(now()->toDateString(), $data['seated_time']);

            // Une arrivée ne peut pas être dans le futur.
            if ($start->isFuture()) {
                return back()->withInput()->withErrors([
                    'seated_time' => 'L\'heure d\'arrivée ne peut pas être dans le futur.',
                ]);
            }

            if (! $this->assignment->isWithinHours($data['seated_time'])) {
                return back()->withInput()->withErrors([
                    'seated_time' => 'Le restaurant est ouvert entre '.$this->assignment->openingTime().' et '.$this->assignment->closingTime().' — durée maximale de '.$this->assignment->duration().' minutes.',
                ]);
            }
        }

        unset($data['seated_time']);

        try {
            $walkIn = DB::transaction(fn () => $this->assignment->seatWalkIn(
                $data['guests_count'],
                $data['special_request'] ?? null,
                $start,
                // Les tables candidates sont verrouillées : deux installations
                // simultanées ne peuvent pas aboutir sur la même table.
                true,
            ));
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', "Une erreur est survenue lors de l'installation du client.");
        }

        if ($walkIn === null) {
            return back()->with('error', 'Aucune table libre ne peut accueillir ce nombre de convives pour le moment.');
        }

        return back()->with('success', "Client installé à la table n°{$walkIn->table->number}.");
    }

    // Libère manuellement une table occupée par un client sans réservation.
    public function release(WalkIn $walkIn)
    {
        if (! $walkIn->isActive()) {
            return back()->with('error', 'Cette table a déjà été libérée.');
        }

        $walkIn->update(['released_at' => now()]);

        return back()->with('success', 'La table a été libérée. Elle redevient réservable après le temps de préparation.');
    }
}
