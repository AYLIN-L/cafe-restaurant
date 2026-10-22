<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Menu;
use App\Models\Reservation;
use App\Models\Table;
use App\Models\User;
use App\Models\WalkIn;

class DashboardController extends Controller
{
    // Affiche les statistiques principales de l'administration.
    public function index()
    {
        $now = now();

        // Les réservations annulées ou refusées ne représentent aucune
        // activité : elles sont exclues des compteurs.
        $todayCount = Reservation::whereDate('reservation_date', $now->toDateString())
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->count();

        $weekCount = Reservation::whereBetween('reservation_date', [
            $now->copy()->startOfWeek()->toDateString(),
            $now->copy()->endOfWeek()->toDateString(),
        ])
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->count();

        $pendingReservations = Reservation::where('status', Reservation::STATUS_PENDING)->count();

        // Occupation de la salle en temps réel.
        $activeTables = Table::where('is_active', true)
            ->with([
                'reservations' => fn ($query) => $query
                    ->confirmed()
                    ->whereDate('reservation_date', $now->toDateString()),
                'walkIns' => fn ($query) => $query->where(function ($query) {
                    // Un client non libéré reste installé indéfiniment.
                    $query->whereNull('released_at')
                        ->orWhere('released_at', '>=', now()->subDay());
                }),
            ])
            ->get();

        $occupiedTables = $activeTables
            ->filter(fn (Table $table) => in_array($table->stateAt($now), [Table::STATE_WALK_IN, Table::STATE_OCCUPIED], true))
            ->count();

        $freeTables = $activeTables
            ->filter(fn (Table $table) => $table->stateAt($now) === Table::STATE_FREE)
            ->count();

        $seatsFree = $activeTables
            ->filter(fn (Table $table) => $table->stateAt($now) === Table::STATE_FREE)
            ->sum('capacity');

        $totalSeats = $activeTables->sum('capacity');

        $activeWalkIns = WalkIn::active()->count();

        // Classement des plats les plus commentés : seuls les avis approuvés
        // (donc publics) sont pris en compte.
        $topMenus = Menu::withCount(['comments' => fn ($query) => $query->approved()])
            ->orderByDesc('comments_count')
            ->take(5)
            ->get();
        $totalMenus = Menu::count();
        $availableMenus = Menu::where('is_available', true)->count();
        $totalCategories = Category::count();
        $totalClients = User::where('role', 'client')->count();

        // Avis qui attendent encore une décision de modération : ni approuvés,
        // ni déjà rejetés (un avis rejeté est traité, il ne bloque plus la file).
        $pendingComments = Comment::pendingModeration()->count();

        return view('admin.dashboard', compact(
            'todayCount',
            'weekCount',
            'pendingReservations',
            'occupiedTables',
            'freeTables',
            'seatsFree',
            'totalSeats',
            'activeTables',
            'activeWalkIns',
            'topMenus',
            'totalMenus',
            'availableMenus',
            'totalCategories',
            'totalClients',
            'pendingComments'
        ));
    }
}
