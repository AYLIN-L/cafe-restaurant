<?php

namespace App\Providers;

use App\Models\Comment;
use App\Models\Reservation;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Pagination : la vue « coffee » (thème café-restaurant de la page
        // Menu publique) devient la vue par défaut de TOUTES les listes
        // paginées — admin réservations, plats, catégories, commentaires,
        // clients et historique client — pour un rendu cohérent partout.
        // Les pages qui ne paginent pas ne sont pas affectées.
        Paginator::defaultView('pagination.coffee');

        // Compteurs de notifications affichés dans l'en-tête de l'administration
        // (toutes les pages). La logique est strictement identique à celle du
        // tableau de bord : une seule source de vérité.
        View::composer('components.admin-layout', function ($view) {
            $view->with([
                'pendingReservations' => Reservation::where('status', Reservation::STATUS_PENDING)->count(),
                'pendingComments' => Comment::pendingModeration()->count(),
            ]);
        });
    }
}
