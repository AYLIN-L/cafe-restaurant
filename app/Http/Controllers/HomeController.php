<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    // Affiche la page d'accueil unique : hero + menu + contact.
    public function index()
    {
        // Plats les plus appréciés : note moyenne des avis approuvés,
        // puis nombre d'avis, puis ordre stable (id).
        $menus = Menu::with('category')
            ->whereHas('comments', function ($query) {
                $query->where('is_approved', true);
            })
            ->withAvg(['comments as avg_rating' => function ($query) {
                $query->where('is_approved', true);
            }], 'rating')
            ->withCount(['comments as reviews_count' => function ($query) {
                $query->where('is_approved', true);
            }])
            ->orderByDesc('avg_rating')
            ->orderByDesc('reviews_count')
            ->orderBy('id')
            ->take(4)
            ->get();

        // Complète avec les plats récents si moins de 4 plats ont des avis.
        if ($menus->count() < 4) {
            $fill = Menu::with('category')
                ->whereNotIn('id', $menus->pluck('id'))
                ->latest()
                ->take(4 - $menus->count())
                ->get();

            $menus = $menus->concat($fill);
        }

        return view('home', compact('menus'));
    }
}
