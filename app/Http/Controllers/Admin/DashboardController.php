<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Menu;
use App\Models\Reservation;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    /**
     * Affiche les statistiques principales de l'administration.
     *
     * Les statistiques de la salle (tables, places, occupation) ne font pas
     * partie de ce tableau de bord : elles sont calculées et affichées par
     * le contrôleur des tables, qui en est l'unique source de vérité.
     */
    public function index()
    {
        // Le restaurant travaille en heure de Casablanca : la journée métier
        // et la semaine sont calculées dans ce fuseau, jamais en UTC.
        $now = Carbon::now(config('app.timezone'));

        // Une réservation annulée, refusée ou expirée ne représente aucune
        // activité : seuls les statuts « actifs » comptent (règle métier).
        // Le comptage porte sur la DATE DE RÉSERVATION (reservation_date),
        // pas sur la date de création de la demande.
        $todayCount = Reservation::active()->forDate($now)->count();

        // Semaine métier : du lundi au dimanche, bornes incluses.
        $weekStart = $now->copy()->startOfWeek(CarbonInterface::MONDAY);
        $weekEnd = $now->copy()->endOfWeek(CarbonInterface::SUNDAY);

        $weekCount = Reservation::active()->scheduledBetween($weekStart, $weekEnd)->count();

        // Demandes qui attendent encore une décision : toutes dates.
        $pendingReservations = Reservation::where('status', Reservation::STATUS_PENDING)->count();

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
            'topMenus',
            'totalMenus',
            'availableMenus',
            'totalCategories',
            'totalClients',
            'pendingComments'
        ));
    }
}
