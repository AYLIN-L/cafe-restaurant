<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Table;
use App\Models\WalkIn;
use Illuminate\Http\Request;

class TableController extends Controller
{
    // Affiche la salle : tables, état réel et clients sans réservation.
    public function index()
    {
        $now = now();

        $tables = Table::with([
            'reservations' => fn ($query) => $query
                ->confirmed()
                ->whereDate('reservation_date', $now->toDateString()),
            'walkIns' => fn ($query) => $query
                ->where(function ($query) {
                    // Un client sans réservation non libéré reste installé
                    // indéfiniment : il faut donc le voir même si son
                    // horodatage est antérieur à aujourd'hui.
                    $query->whereNull('released_at')
                        ->orWhere('released_at', '>=', now()->subDay());
                })
                ->orderByDesc('seated_at'),
        ])
            ->withCount([
                // Les réservations annulées ou refusées ne bloquent pas la
                // suppression : elles ne représentent aucun client réel.
                'reservations as active_reservations_count' => fn ($query) => $query
                    ->whereNotIn('status', ['cancelled', 'rejected']),
                'walkIns as walk_ins_count',
            ])
            ->orderBy('number')
            ->get();

        $activeWalkIns = WalkIn::with('table')
            ->active()
            ->latest('seated_at')
            ->get();

        // États réels de chaque table (calculés une seule fois, à partir des
        // relations déjà chargées).
        $states = $tables->mapWithKeys(fn (Table $table) => [$table->id => $table->stateAt($now)]);

        // Statistiques de la salle : unique source de vérité. Le tableau de
        // bord n'affiche plus ces chiffres, la gestion des tables les porte
        // désormais toutes.
        $inService = $tables->filter(fn (Table $table) => $table->is_active);

        $stats = [
            // Tables
            'tablesTotal' => $tables->count(),
            'tablesActive' => $inService->count(),
            'tablesInactive' => $tables->count() - $inService->count(),

            // Places : les tables en service font référence, le total de la
            // salle (tables hors service incluses) est indiqué en second.
            'seatsActive' => (int) $inService->sum('capacity'),
            'seatsTotal' => (int) $tables->sum('capacity'),

            // Occupation en temps réel
            'occupied' => $states->filter(fn ($state) => in_array($state, [Table::STATE_WALK_IN, Table::STATE_OCCUPIED], true))->count(),
            'reserved' => $states->filter(fn ($state) => $state === Table::STATE_RESERVED)->count(),
            'free' => $states->filter(fn ($state) => $state === Table::STATE_FREE)->count(),
            'seatsFree' => (int) $tables->filter(fn (Table $table) => $states[$table->id] === Table::STATE_FREE)->sum('capacity'),

            // Clients installés sans réservation
            'walkIns' => $activeWalkIns->count(),
        ];

        return view('admin.tables.index', compact('tables', 'activeWalkIns', 'now', 'states', 'stats'));
    }

    // Affiche le formulaire de création d'une table.
    public function create()
    {
        return view('admin.tables.create');
    }

    // Valide et enregistre une nouvelle table.
    public function store(Request $request)
    {
        $data = $request->validate([
            'number' => ['required', 'integer', 'min:1', 'max:999', 'unique:tables,number'],
            'capacity' => ['required', 'integer', 'min:1', 'max:20'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        Table::create($data);

        return redirect()->route('admin.tables.index')
            ->with('success', 'La table a bien été créée.');
    }

    // Affiche le formulaire de modification d'une table.
    public function edit(Table $table)
    {
        return view('admin.tables.edit', compact('table'));
    }

    // Valide et met à jour une table.
    public function update(Request $request, Table $table)
    {
        $data = $request->validate([
            'number' => ['required', 'integer', 'min:1', 'max:999', 'unique:tables,number,'.$table->id],
            'capacity' => ['required', 'integer', 'min:1', 'max:20'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        $table->update($data);

        return redirect()->route('admin.tables.index')
            ->with('success', 'La table a bien été modifiée.');
    }

    // Bascule l'état actif/inactif d'une table (opérationnel ou non).
    public function toggle(Table $table)
    {
        $table->is_active = ! $table->is_active;
        $table->save();

        return back()->with('success', 'L\'état de la table a été mis à jour.');
    }

    // Supprime une table (protégée si elle porte des réservations actives).
    public function destroy(Table $table)
    {
        $hasReservations = $table->reservations()
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->exists();

        if ($hasReservations || $table->walkIns()->exists()) {
            return back()->with('error', 'Impossible de supprimer cette table : elle est liée à des réservations ou à des clients. Désactivez-la plutôt.');
        }

        // Les réservations annulées ou refusées ne bloquent pas la suppression :
        // on détache simplement leur référence avant de supprimer la table.
        $table->reservations()->update(['table_id' => null]);

        $table->delete();

        return back()->with('success', 'La table a bien été supprimée.');
    }
}
